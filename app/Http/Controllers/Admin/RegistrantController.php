<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\CheckinLog;
use App\Models\PaymentAccount;
use App\Models\PaymentSlip;
use App\Models\Registration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ผู้ลงทะเบียนรายกิจกรรม (admin/activities/registrants)
 *
 * หน้าจอเดิมอ่านจาก mock-data.js ทั้งหน้า ที่นี่เปลี่ยนเฉพาะ "แหล่งข้อมูล" เป็น MySQL
 * และเปลี่ยนสามการกระทำที่เคยแก้แค่ในหน่วยความจำ (ยืนยันสลิป · ปฏิเสธสลิป · เช็คอิน/ยกเลิก)
 * ให้เขียนลงฐานจริงพร้อม audit log ตามที่หัวไฟล์ assets/js/activity-registrants.js สั่งไว้
 *
 * ทุกอย่างแยกตามกิจกรรม — เลือกกิจกรรมด้วย ?id=<code> ไม่ใช่ id ตัวเลข
 * เพื่อไม่ให้ URL บอกลำดับข้อมูลในฐาน แบบเดียวกับหน้าอื่นในโมดูลนี้
 */
class RegistrantController extends Controller
{
    /** ป้ายแทนสถานะการเงินของกิจกรรมที่ไม่เก็บค่าใช้จ่าย — ต้องตรงกับค่า FREE ในสคริปต์หน้าจอ */
    private const FREE = 'ไม่มีค่าใช้จ่าย';

    private const PAID = 'ชำระแล้ว';

    private const UNPAID = 'ยังไม่ชำระ';

    private const PENDING = 'รอตรวจสอบ';

    private const REJECTED = 'ปฏิเสธ';

    private const GENDERS = [
        'male' => 'ชาย',
        'female' => 'หญิง',
        'other' => 'เพศทางเลือก',
        'undisclosed' => 'ไม่ระบุ',
    ];

    public function index(Request $request): View
    {
        $activities = $this->selectableActivities();
        $activity = $this->pickActivity($activities, $request->query('id'));

        return view('admin.activities.registrants', [
            'activityOptions' => $activities->map(fn (Activity $item) => [
                'id' => $item->code,
                'name' => $item->name,
                'startDate' => $item->start_date?->toDateString(),
                'startDateLabel' => $this->thaiDate($item->start_date?->toDateString()),
                'registered' => $item->registrations_count,
            ])->values(),
            'activity' => $activity ? [
                'id' => $activity->code,
                'name' => $activity->name,
                'hasFee' => (bool) $activity->has_fee,
                'fee' => (float) $activity->fee,
                'capacity' => $this->capacityOf($activity),
            ] : null,
            'rows' => $activity ? $this->rowsFor($activity) : collect(),
            'paymentAccount' => $this->paymentAccountLabel(),
        ]);
    }

    /**
     * ยืนยันหรือปฏิเสธการชำระเงินของผู้ลงทะเบียนหนึ่งราย
     *
     * สลิปใบล่าสุดถูกปิดผลไปพร้อมกัน (พร้อมคนตรวจและเวลา) เพราะเป็นการตัดสินใบเดียวกัน
     * ถ้าแยกกันจะเกิดสถานะที่การลงทะเบียนบอกว่าชำระแล้วแต่สลิปยังค้าง "รอตรวจสอบ"
     */
    public function updatePayment(Request $request, Registration $registration): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.self::PAID.','.self::UNPAID],
        ]);

        abort_if($registration->activity && ! $registration->activity->has_fee, 422, 'กิจกรรมนี้ไม่มีค่าใช้จ่าย');

        $confirmed = $data['status'] === self::PAID;

        DB::transaction(function () use ($registration, $confirmed, $request): void {
            $registration->update(['payment_status' => $confirmed ? self::PAID : self::UNPAID]);

            $slip = $registration->paymentSlips()->where('status', self::PENDING)->first();

            $slip?->update([
                'status' => $confirmed ? self::PAID : self::REJECTED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'reject_reason' => $confirmed ? null : 'เจ้าหน้าที่ตรวจแล้วพบว่าสลิปไม่ถูกต้อง',
            ]);
        });

        return response()->json([
            'message' => $confirmed
                ? 'ยืนยันการชำระเงินของ '.$registration->name.' แล้ว'
                : 'ทำเครื่องหมายสลิปของ '.$registration->name.' ว่าไม่ถูกต้อง',
            'pay' => $confirmed ? self::PAID : self::UNPAID,
        ]);
    }

    /**
     * เช็คอินหน้างานโดยเจ้าหน้าที่
     *
     * เวลาที่บันทึกมาจากนาฬิกาเซิร์ฟเวอร์เสมอ ไม่รับจากเครื่องหน้างานตามกติกาของ act_checkin_logs
     * กดซ้ำจากอีกแท็บหนึ่งจะไม่เขียนทับเวลาเดิม แต่ตอบเวลาที่บันทึกไว้แล้วกลับไปให้หน้าจอตรงกัน
     */
    public function checkin(Request $request, Registration $registration): JsonResponse
    {
        $checkedInAt = DB::transaction(function () use ($registration, $request) {
            $locked = Registration::query()->lockForUpdate()->findOrFail($registration->id);

            if ($locked->checked_in_at) {
                return $locked->checked_in_at;
            }

            $now = now();
            $locked->update(['checkin_status' => 'เข้าร่วมแล้ว', 'checked_in_at' => $now]);

            CheckinLog::create([
                'registration_id' => $locked->id,
                'action' => 'check_in',
                'method' => 'staff',
                'performed_by' => $request->user()->id,
                'performed_at' => $now,
            ]);

            return $now;
        });

        return response()->json([
            'message' => 'เช็คอิน '.$registration->name.' แล้ว',
            'checkedInAt' => $checkedInAt->format('H:i'),
        ]);
    }

    /** ยกเลิกการเช็คอิน — ล้างเวลาออกแต่เก็บรอยไว้ใน act_checkin_logs ว่าใครยกเลิกเมื่อไหร่ */
    public function undoCheckin(Request $request, Registration $registration): JsonResponse
    {
        DB::transaction(function () use ($registration, $request): void {
            $locked = Registration::query()->lockForUpdate()->findOrFail($registration->id);

            if (! $locked->checked_in_at) {
                return;
            }

            $locked->update(['checkin_status' => 'ยังไม่เข้าร่วม', 'checked_in_at' => null]);

            CheckinLog::create([
                'registration_id' => $locked->id,
                'action' => 'undo',
                'method' => 'staff',
                'performed_by' => $request->user()->id,
                'performed_at' => now(),
            ]);
        });

        return response()->json(['message' => 'ยกเลิกเช็คอิน '.$registration->name.' แล้ว']);
    }

    /**
     * ไฟล์สลิป — เก็บนอก public/ จึงต้องผ่าน route ที่ตรวจสิทธิ์เสมอ
     * เสิร์ฟเฉพาะสลิปที่เป็นของการลงทะเบียนใน URL ไม่งั้นเดา id สลิปข้ามกิจกรรมได้
     */
    public function slip(Registration $registration, PaymentSlip $slip): StreamedResponse
    {
        abort_unless($slip->registration_id === $registration->id, 404);
        abort_unless(Storage::disk('local')->exists($slip->file_path), 404);

        return Storage::disk('local')->response($slip->file_path, null, [
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * กิจกรรมที่หน้านี้เลือกดูได้ — ที่เปิดรับลงทะเบียน หรือที่มีคนลงทะเบียนไว้แล้ว
     * กิจกรรมที่ปิดรับแต่มีรายชื่อค้างอยู่ต้องเลือกดูได้ ไม่งั้นข้อมูลเดิมหายจากหน้าจอ
     *
     * @return Collection<int, Activity>
     */
    private function selectableActivities(): Collection
    {
        return Activity::query()
            ->where(fn ($query) => $query
                ->where('requires_registration', true)
                ->orWhereHas('registrations'))
            ->withCount('registrations')
            ->with(['rounds:id,activity_id,round_date,capacity', 'areas:id,name'])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * กิจกรรมที่กำลังดูอยู่ — ตาม ?id= ก่อน ถ้าไม่ระบุเลือกกิจกรรมที่เก็บค่าใช้จ่าย
     * เพราะเป็นกรณีที่หน้านี้มีงานให้ทำจริง (ตรงกับพฤติกรรมเดิมของหน้าจอ)
     *
     * @param  Collection<int, Activity>  $activities
     */
    private function pickActivity(Collection $activities, ?string $code): ?Activity
    {
        return $activities->firstWhere('code', $code)
            ?? $activities->first(fn (Activity $item) => (bool) $item->has_fee)
            ?? $activities->first();
    }

    /** ที่นั่งที่รับได้ — รวมจากทุกรอบถ้ามี ไม่งั้นใช้ค่าของกิจกรรม */
    private function capacityOf(Activity $activity): int
    {
        $fromRounds = (int) $activity->rounds->sum('capacity');

        return $activity->rounds->isNotEmpty() && $fromRounds > 0
            ? $fromRounds
            : (int) $activity->capacity;
    }

    /**
     * แถวทั้งหมดของกิจกรรมหนึ่ง — คีย์ตรงกับที่ REG_FORM_FIELDS ในสคริปต์หน้าจออ่าน
     *
     * eager load ครบทุก relation ที่ใช้วาด กันไม่ให้เกิด N+1 ตอนกิจกรรมมีผู้ลงทะเบียนหลักร้อยคน
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function rowsFor(Activity $activity): Collection
    {
        $registrations = Registration::query()
            ->where('activity_id', $activity->id)
            ->with([
                'activityRound:id,round_date',
                'occupation:id,label',
                'ageRange:id,label',
                'sourceChannel:id,label',
                'area:id,name',
                'interests:id,label',
                'paymentSlips',
            ])
            ->orderByDesc('registered_at')
            ->orderByDesc('id')
            ->get();

        /* ลำดับรอบคิดจากวันที่ของรอบ ไม่ใช่ id เพราะรอบที่เพิ่มทีหลังอาจเป็นวันที่ก่อนหน้า */
        $roundOrder = $activity->rounds
            ->sortBy(fn ($round) => $round->round_date->toDateString())
            ->values()
            ->mapWithKeys(fn ($round, int $index) => [$round->id => $index + 1]);

        $areaFallback = $activity->relationLoaded('areas') ? $activity->areas->pluck('name')->join(' · ') : '';

        return $registrations->map(function (Registration $registration) use ($activity, $roundOrder, $areaFallback): array {
            $slip = $registration->paymentSlips->first();

            return [
                'id' => (string) $registration->id,
                'ref' => $registration->code,
                'code' => $registration->code,
                'name' => $registration->name,
                'phone' => $registration->phone,
                'email' => $registration->email ?: '',
                'gender' => self::GENDERS[$registration->gender] ?? '',
                'ageRange' => $registration->ageRange?->label ?? '',
                'occupation' => $registration->occupation?->label ?? $registration->occupation_raw ?? '',
                'sourceChannel' => $registration->sourceChannel?->label ?? '',
                'interests' => $registration->interests->pluck('label')->join(', '),
                'dietaryNote' => $registration->dietary_note ?: '',
                'area' => $registration->area?->name ?: ($areaFallback ?: '-'),
                'round' => $this->roundLabel($activity, $registration, $roundOrder),
                'registeredAt' => $registration->registered_at
                    ? $this->thaiDate($registration->registered_at->toDateString()).' · '.$registration->registered_at->format('H:i').' น.'
                    : '',

                /* กิจกรรมฟรีไม่มีสถานะการเงินให้ตรวจ ใช้ "ไม่มีค่าใช้จ่าย" แทน */
                'pay' => $activity->has_fee ? $registration->payment_status : self::FREE,
                'amount' => $activity->has_fee ? (float) ($slip?->amount ?? $activity->fee) : 0,
                'checkedIn' => (bool) $registration->checked_in_at,
                'checkedInAt' => $registration->checked_in_at?->format('H:i') ?? '',
                'walkIn' => (bool) $registration->is_manual_entry,

                /* สลิปแนบกับผู้จองหลักของชุด สมาชิกคนอื่นในชุดเดียวกันจึงไม่มีไฟล์ให้ดู */
                'slip' => $slip ? [
                    'url' => route('admin.activities.registrants.slip', [
                        'registration' => $registration->code,
                        'slip' => $slip->id,
                    ]),
                    'name' => basename($slip->file_path),
                    'status' => $slip->status,
                    'transferredAt' => $slip->transferred_at
                        ? $this->thaiDate($slip->transferred_at->toDateString()).' · '.$slip->transferred_at->format('H:i').' น.'
                        : '',
                ] : null,
            ];
        });
    }

    /**
     * @param  Collection<int, int>  $roundOrder
     */
    private function roundLabel(Activity $activity, Registration $registration, Collection $roundOrder): string
    {
        if ($activity->rounds->count() <= 1) {
            return 'รอบเดียว';
        }

        return 'รอบ '.($roundOrder->get($registration->activity_round_id) ?? 1);
    }

    /** บัญชีปลายทางที่เปิดใช้อยู่ — แสดงในโมดัลสลิปเพื่อให้เทียบกับหลักฐานที่แนบมาได้ */
    private function paymentAccountLabel(): ?string
    {
        $account = PaymentAccount::query()->where('is_active', true)->first();

        if (! $account) {
            return null;
        }

        $bank = config('farmconcept.banks.'.$account->bank_code, $account->bank_code);

        return $bank.' '.$account->account_number.' · '.$account->account_name;
    }

    /** "12 ส.ค. 2569" — รูปแบบเดียวกับ TFC.formatThaiDate ฝั่งหน้าจอ */
    private function thaiDate(?string $isoDate): string
    {
        if (! $isoDate) {
            return '';
        }

        $months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        [$year, $month, $day] = array_map('intval', explode('-', $isoDate));

        return $day.' '.$months[$month - 1].' '.($year + 543);
    }
}
