<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRequest;
use App\Models\Activity;
use App\Models\ActivityFormat;
use App\Models\Area;
use App\Models\Course;
use App\Models\Form;
use App\Models\Instructor;
use App\Models\Program;
use App\Models\Question;
use App\Models\Registration;
use App\Models\SatisfactionResponse;
use App\Models\TargetGroup;
use App\Services\ActivitySatisfactionService;
use App\Services\ActivityService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ActivityController extends Controller
{
    /** รหัสสมมติของกิจกรรมที่ยังไม่ได้บันทึก — ฟอร์มใช้เป็นคีย์ชั่วคราวเท่านั้น ไม่เคยลงฐาน */
    private const NEW_CODE = 'NEW';

    /** เพดานรูปปกตามกฎของแอป — ใช้ทั้งตอน validate และตอนบอกหน้าจอ */
    private const COVER_MAX_KB = 5120;

    use AuthorizesRequests;

    /**
     * รายการกิจกรรม
     *
     * ยังส่งข้อมูลทั้งชุดให้หน้าจอกรอง/แบ่งหน้าเองแบบเดิม เพราะเป็นหน้านำร่อง
     * ที่ตั้งใจเปลี่ยนเฉพาะ "แหล่งข้อมูล" ไม่แตะพฤติกรรมของ UI
     * ขั้นถัดไปคือย้าย filter/sort/paginate ไปทำที่ฝั่งเซิร์ฟเวอร์ตามข้อ 4.5
     */
    public function index(): View
    {
        /* forList() ใส่ eager load + withCount ให้แล้ว จึงไม่เกิด N+1 ตอนวาดคอลัมน์
           โปรแกรม/พื้นที่/วิทยากร และไม่ต้องนับผู้ลงทะเบียนทีละแถว */
        $activities = Activity::forList()
            ->withCount([
                'registrations as checked_in_count' => fn ($q) => $q->whereNotNull('checked_in_at'),
                'satisfactionResponses as responses_count',
            ])
            ->with([
                'rounds:id,activity_id,round_date,time_start,time_end,location,capacity',
                'parentEvent:id,code,name',
                'updatedBy:id,name',
            ])
            /* ใหม่สุดอยู่บน และแก้ข้อมูลแล้วลำดับไม่ขยับ — ใช้ id ไม่ใช่ updated_at
               ซึ่งจะดีดแถวที่เพิ่งบันทึกขึ้นบนสุดทุกครั้งจนคนที่ไล่แก้ทีละแถวเสียตำแหน่ง */
            ->orderByDesc('id')
            ->get();

        /* ลำดับจริงบนหน้าเว็บสาธารณะ (id => ลำดับที่ 1,2,3,…) — เรียงด้วยเงื่อนไขเดียวกับ
           PublicActivityController เป๊ะ เพื่อให้เลขที่แอดมินเห็นตรงกับที่ผู้เข้าชมเห็นจริง */
        $publicRanks = Activity::published()
            ->where('visibility', 'สาธารณะ')
            ->where('public_sort_order', '>', 0)
            ->orderBy('public_sort_order')
            ->orderBy('start_date')
            ->pluck('id')
            ->flip()
            ->map(fn (int $index) => $index + 1);

        return view('admin.activities.list', [
            'activities' => $activities->map(fn (Activity $a) => $this->toListRow($a, $publicRanks->get($a->id))),
            'sessions' => $activities->mapWithKeys(fn (Activity $a) => [$a->code => $this->toSessions($a)]),
        ]);
    }

    /**
     * หน้าภาพรวมกิจกรรม (ดูรายละเอียด)
     *
     * หน้าอ่านอย่างเดียว — รวมสิ่งที่แอดมินต้องเหลือบดูบ่อยที่สุดไว้จอเดียว:
     * รายละเอียด · ตัวเลขสรุป (ลงทะเบียน/เช็คอิน/ประเมิน/รายรับ) · QR · รอบกิจกรรม
     * ส่วนการแก้ไขยังอยู่ที่หน้า edit ตามเดิม จึงไม่ต้องตรวจสิทธิ์ update ที่นี่
     */
    public function show(Activity $activity): View
    {
        $activity->load([
            'program:id,name', 'course:id,name', 'format:id,name',
            'areas:id,name', 'instructors:id,name', 'parentEvent:id,code,name',
            'rounds' => fn ($q) => $q->withCount('registrations'),
            'qrCodes',
        ])->loadCount([
            'registrations',
            'registrations as checked_in_count' => fn ($q) => $q->whereNotNull('checked_in_at'),
            'satisfactionResponses as responses_count',
        ]);

        /* รายรับรวม — เฉพาะรายที่ยืนยันการชำระแล้ว ใช้ยอดจริงจากสลิปใบล่าสุด
           ถ้าสลิปไม่ระบุยอด (กรอกมือ/walk-in) ถอยไปใช้ค่าเข้าร่วมของกิจกรรม
           เกณฑ์เดียวกับคอลัมน์ยอดเงินในหน้าผู้ลงทะเบียน (RegistrantController) */
        $revenue = 0.0;
        $pendingSlips = 0;

        if ($activity->has_fee) {
            $registrations = $activity->registrations()
                ->with('paymentSlips:id,registration_id,amount,status')
                ->get(['id', 'payment_status']);

            $revenue = $registrations
                ->where('payment_status', 'ชำระแล้ว')
                ->sum(fn (Registration $r) => (float) ($r->paymentSlips->first()?->amount ?? $activity->fee));

            $pendingSlips = $registrations
                ->flatMap->paymentSlips
                ->where('status', 'รอตรวจสอบ')
                ->count();
        }

        return view('admin.activities.overview', [
            'activity' => $activity,
            'revenue' => $revenue,
            'pendingSlips' => $pendingSlips,
            'qrCodes' => $this->qrPayload($activity),
        ]);
    }

    /**
     * แท็บผู้เข้าร่วมของหน้ารายละเอียดกิจกรรม
     *
     * ตารางรายชื่อพร้อม "ทุกฟิลด์ที่กิจกรรมนี้เก็บจริง" — ฟิลด์เสริม (อีเมล ช่วงอายุ
     * อาชีพ ช่องทางที่รู้จัก) เปิด/ปิดตามแบบลงทะเบียนของกิจกรรม จึงต่างกันได้รายกิจกรรม
     * ฟิลด์ที่แบบไม่ได้คุม (เพศ ความสนใจ ฯลฯ) แสดงเมื่อมีข้อมูลอย่างน้อยหนึ่งแถว
     * การเลือกคอลัมน์และส่งออก Excel ทำฝั่งหน้าจอ เพราะข้อมูลส่งไปครบทุกคอลัมน์อยู่แล้ว
     */
    public function participants(Activity $activity): View
    {
        $activity->load(['rounds', 'qrCodes'])->loadCount([
            'registrations',
            'registrations as checked_in_count' => fn ($q) => $q->whereNotNull('checked_in_at'),
            'satisfactionResponses as responses_count',
        ]);

        $registrations = Registration::query()
            ->where('activity_id', $activity->id)
            ->with([
                'activityRound:id,round_date,time_start',
                'ageRange:id,label',
                'occupation:id,label',
                'sourceChannel:id,label',
                'area:id,name',
                'interests:id,label',
                'paymentSlips:id,registration_id,status',
            ])
            ->orderByDesc('registered_at')
            ->orderByDesc('id')
            ->get();

        /* ลำดับรอบคิดจากวันที่ ไม่ใช่ id — รอบที่เพิ่มทีหลังอาจเป็นวันก่อนหน้า
           (เกณฑ์เดียวกับหน้าผู้ลงทะเบียนเดิม) */
        $roundOrder = $activity->rounds
            ->sortBy(fn ($round) => $round->round_date->toDateString())
            ->values()
            ->mapWithKeys(fn ($round, int $index) => [$round->id => $index + 1]);

        $genders = ['male' => 'ชาย', 'female' => 'หญิง', 'other' => 'เพศทางเลือก', 'undisclosed' => 'ไม่ระบุ'];
        $thMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        /* ลูกค้าประจำ — คนเดียวกัน (เบอร์โทร "หรือ" อีเมลตรงกัน) เคยลงทะเบียน
           กิจกรรมอื่นไหน เมื่อไหร่บ้าง — นับเป็นกิจกรรม (unique) ไม่ใช่จำนวนแถว
           เพราะกิจกรรมเดียวลงหลายที่นั่งได้ ส่งเป็นรายการไปให้หน้าจอ
           ใช้ทั้งตัวเลขใต้ชื่อและ popup ประวัติ */
        $priorRegs = Registration::query()
            ->where('activity_id', '!=', $activity->id)
            ->where(fn ($q) => $q
                ->whereIn('phone', $registrations->pluck('phone')->filter()->unique())
                ->orWhereIn('email', $registrations->pluck('email')->filter()->unique()))
            ->with('activity:id,name,start_date')
            ->get(['id', 'phone', 'email', 'activity_id']);

        $priorFor = fn (Registration $r): Collection => $priorRegs
            ->filter(fn (Registration $p) => ($r->phone && $p->phone === $r->phone)
                || ($r->email && $p->email === $r->email))
            ->unique('activity_id')
            ->sortByDesc(fn (Registration $p) => $p->activity?->start_date?->toDateString() ?? '')
            ->map(fn (Registration $p) => [
                'name' => $p->activity?->name ?? '(กิจกรรมถูกลบแล้ว)',
                'date' => $p->activity?->start_date
                    ? $p->activity->start_date->day.' '.$thMonths[$p->activity->start_date->month - 1].' '.($p->activity->start_date->year + 543)
                    : '—',
            ])
            ->values();

        $rows = $registrations->map(function (Registration $r) use ($activity, $roundOrder, $genders, $thMonths, $priorFor) {
            $roundLabel = 'รอบเดียว';
            if ($activity->rounds->count() > 1) {
                $round = $activity->rounds->firstWhere('id', $r->activity_round_id);
                $roundLabel = 'รอบที่ '.($roundOrder->get($r->activity_round_id) ?? 1)
                    .($round ? ' · '.$round->round_date->day.' '.$thMonths[$round->round_date->month - 1] : '');
            }

            /* สลิปใบล่าสุด — แนบกับผู้จองหลักของชุด สมาชิกร่วมชุดจึงไม่มีไฟล์ให้ดู */
            $slip = $r->paymentSlips->first();

            $prior = $priorFor($r);

            return [
                'code' => $r->code,
                'name' => $r->name,
                'prior' => $prior->count(),
                'priorList' => $prior->all(),
                'phone' => $r->phone,
                'round' => $roundLabel,
                'payment' => $activity->has_fee ? ($r->payment_status ?: 'ยังไม่ชำระ') : '',
                'slip' => ($activity->has_fee && $slip) ? [
                    'url' => route('admin.activities.registrants.slip', ['registration' => $r->code, 'slip' => $slip->id]),
                    'status' => $slip->status,
                ] : null,
                'email' => $r->email ?: '',
                'age_range' => $r->ageRange?->label ?? '',
                'occupation' => $r->occupation?->label ?? $r->occupation_raw ?? '',
                'source_channel' => $r->sourceChannel?->label ?? '',
                'gender' => $genders[$r->gender] ?? '',
                'interests' => $r->interests->pluck('label')->join(', '),
                'dietary_note' => $r->dietary_note ?: '',
                'area' => $r->area?->name ?? '',
                'registered_at' => $r->registered_at
                    ? $r->registered_at->day.' '.$thMonths[$r->registered_at->month - 1].' '.($r->registered_at->year + 543)
                        .' · '.$r->registered_at->format('H:i').' น.'
                    : '',
            ];
        })->values();

        /* ฟิลด์เสริมที่แบบลงทะเบียนของกิจกรรมนี้คุม — ไม่มีแถวตั้งค่า = เปิดแบบไม่บังคับ
           (เกณฑ์เดียวกับหน้าลงทะเบียนสาธารณะ) */
        $form = $activity->forms()
            ->wherePivot('slot', 'registration')
            ->with('fields')
            ->first();

        $formEnabled = function (string $key) use ($form): bool {
            $field = $form?->fields->firstWhere('field_key', $key);

            return $field ? (bool) $field->is_enabled : true;
        };

        $hasData = fn (string $key): bool => $rows->contains(fn (array $row) => $row[$key] !== '');

        /* คอลัมน์ของตาราง — fixed ปิดไม่ได้ (คือแกนของรายชื่อ) ที่เหลือเลือกแสดงได้
           ฟิลด์ตามแบบลงทะเบียนโผล่เมื่อแบบเปิดหรือมีข้อมูลเก่าค้างอยู่
           ฟิลด์นอกแบบ (เพศ ความสนใจ ฯลฯ) โผล่เฉพาะเมื่อมีข้อมูลจริง */
        $columns = collect([
            ['key' => 'name', 'label' => 'ชื่อผู้เข้าร่วม', 'fixed' => true, 'show' => true],
            ['key' => 'phone', 'label' => 'เบอร์โทร', 'fixed' => true, 'show' => true],
            ['key' => 'round', 'label' => 'รอบที่ลงทะเบียน', 'fixed' => true, 'show' => $activity->rounds->count() > 1],
            ['key' => 'email', 'label' => 'อีเมล', 'fixed' => false, 'show' => $formEnabled('email') || $hasData('email')],
            ['key' => 'age_range', 'label' => 'ช่วงอายุ', 'fixed' => false, 'show' => $formEnabled('age_range') || $hasData('age_range')],
            ['key' => 'occupation', 'label' => 'อาชีพ', 'fixed' => false, 'show' => $formEnabled('occupation') || $hasData('occupation')],
            ['key' => 'source_channel', 'label' => 'ช่องทางที่รู้จัก', 'fixed' => false, 'show' => $formEnabled('source_channel') || $hasData('source_channel')],
            ['key' => 'gender', 'label' => 'เพศ', 'fixed' => false, 'show' => $hasData('gender')],
            ['key' => 'interests', 'label' => 'ความสนใจ', 'fixed' => false, 'show' => $hasData('interests')],
            ['key' => 'dietary_note', 'label' => 'ข้อจำกัดด้านอาหาร', 'fixed' => false, 'show' => $hasData('dietary_note')],
            ['key' => 'area', 'label' => 'พื้นที่', 'fixed' => false, 'show' => $hasData('area')],
            /* สองคอลัมน์การเงิน — เฉพาะกิจกรรมที่มีค่าใช้จ่าย อยู่ก่อนวันที่ลงทะเบียนเสมอ */
            ['key' => 'slip', 'label' => 'สลิป', 'fixed' => false, 'show' => (bool) $activity->has_fee],
            ['key' => 'payment', 'label' => 'สถานะชำระเงิน', 'fixed' => false, 'show' => (bool) $activity->has_fee],
            ['key' => 'registered_at', 'label' => 'วันที่ลงทะเบียน', 'fixed' => false, 'show' => true],
        ])->filter(fn (array $col) => $col['show'])->values();

        /* ลิงก์และ QR ลงทะเบียน — ปุ่ม "ลงทะเบียน" เปิดหน้าลงทะเบียนสาธารณะแทนผู้เข้าร่วม
           และ popup QR ให้สแกนหน้างาน (แบบเดียวกับปุ่มในแท็บแบบประเมิน)
           ไม่มี QR ก็ยังมีลิงก์หน้าลงทะเบียนตรง ๆ ให้กดได้เสมอ */
        $registerQr = collect($this->qrPayload($activity))->firstWhere('purpose', 'public');
        $registerUrl = $registerQr['url'] ?? ($activity->requires_registration
            ? route('public.activities.register', $activity->code)
            : null);

        return view('admin.activities.participants', [
            'activity' => $activity,
            'columns' => $columns,
            'rows' => $rows,
            'registerQr' => $registerQr,
            'registerUrl' => $registerUrl,
        ]);
    }

    /**
     * แท็บ Check-in ของหน้ารายละเอียดกิจกรรม
     *
     * ตารางอ่านอย่างเดียวตามชุดข้อมูลที่ระบบเก็บตอนเช็คอิน:
     * ใครมาแล้ว/ยังไม่มา เวลาเช็คอิน วิธี (เจ้าหน้าที่กด/สแกน QR เอง) และใครเป็นคนทำรายการ
     * งานกดเช็คอินจริงยังอยู่หน้า Check-in หน้างานตามเดิม
     */
    public function checkins(Activity $activity): View
    {
        $activity->load(['rounds', 'qrCodes'])->loadCount([
            'registrations',
            'registrations as checked_in_count' => fn ($q) => $q->whereNotNull('checked_in_at'),
            'satisfactionResponses as responses_count',
        ]);

        $registrations = Registration::query()
            ->where('activity_id', $activity->id)
            ->with(['checkinLogs' => fn ($q) => $q->with('performedBy:id,name')])
            ->orderByRaw('checked_in_at IS NULL')
            ->orderByDesc('checked_in_at')
            ->orderByDesc('id')
            ->get();

        $roundOrder = $activity->rounds
            ->sortBy(fn ($round) => $round->round_date->toDateString())
            ->values()
            ->mapWithKeys(fn ($round, int $index) => [$round->id => $index + 1]);

        $thMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $methodLabels = ['staff' => 'เจ้าหน้าที่', 'scan' => 'สแกน QR ด้วยตนเอง', 'qr' => 'สแกน QR ด้วยตนเอง'];

        $rows = $registrations->map(function (Registration $r) use ($activity, $roundOrder, $thMonths, $methodLabels) {
            $roundLabel = 'รอบเดียว';
            if ($activity->rounds->count() > 1) {
                $round = $activity->rounds->firstWhere('id', $r->activity_round_id);
                $roundLabel = 'รอบที่ '.($roundOrder->get($r->activity_round_id) ?? 1)
                    .($round ? ' · '.$round->round_date->day.' '.$thMonths[$round->round_date->month - 1] : '');
            }

            /* log ล่าสุดของการ "เช็คอิน" (ไม่ใช่ยกเลิก) — บอกวิธีและคนทำรายการ
               เช็คอินจากฝั่งผู้เข้าร่วม (สแกนเอง) ไม่มีเจ้าหน้าที่ */
            $checkinLog = $r->checked_in_at
                ? $r->checkinLogs->firstWhere('action', 'check_in')
                : null;

            return [
                'code' => $r->code,
                'name' => $r->name,
                'walkIn' => (bool) $r->is_manual_entry,
                'phone' => $r->phone,
                'round' => $roundLabel,
                'status' => $r->checked_in_at ? 'เช็คอินแล้ว' : 'ยังไม่เช็คอิน',
                'checkedIn' => (bool) $r->checked_in_at,
                'checked_in_at' => $r->checked_in_at
                    ? $r->checked_in_at->day.' '.$thMonths[$r->checked_in_at->month - 1].' '.($r->checked_in_at->year + 543)
                        .' · '.$r->checked_in_at->format('H:i').' น.'
                    : '',
                'method' => $checkinLog ? ($methodLabels[$checkinLog->method] ?? $checkinLog->method) : '',
                'performed_by' => $checkinLog?->performedBy?->name ?? '',
            ];
        })->values();

        /* สถานะกับเวลาเช็คอินอยู่ท้ายสุด — เป็นผลลัพธ์ของแถว อ่านหลังข้อมูลระบุตัวคนเสมอ */
        $columns = collect([
            ['key' => 'name', 'label' => 'ชื่อผู้เข้าร่วม', 'fixed' => true, 'show' => true],
            ['key' => 'phone', 'label' => 'เบอร์โทร', 'fixed' => true, 'show' => true],
            ['key' => 'round', 'label' => 'รอบที่ลงทะเบียน', 'fixed' => true, 'show' => $activity->rounds->count() > 1],
            ['key' => 'method', 'label' => 'วิธีเช็คอิน', 'fixed' => false, 'show' => true],
            ['key' => 'performed_by', 'label' => 'ผู้ทำรายการ', 'fixed' => false, 'show' => $rows->contains(fn (array $row) => $row['performed_by'] !== '')],
            ['key' => 'status', 'label' => 'สถานะ', 'fixed' => true, 'show' => true],
            ['key' => 'checked_in_at', 'label' => 'เวลาเช็คอิน', 'fixed' => false, 'show' => true],
        ])->filter(fn (array $col) => $col['show'])->values();

        /* popup QR สำหรับ Check-in หน้างาน — ให้ผู้เข้าร่วมสแกนยืนยันตัวตนเอง
           ไม่มี QR ก็ยังมีลิงก์หน้า Check-in สาธารณะตรง ๆ ให้เปิดได้ */
        $checkinQr = collect($this->qrPayload($activity))->firstWhere('purpose', 'checkin');
        $checkinUrl = $checkinQr['url'] ?? ($activity->requires_checkin
            ? route('public.activities.show', ['activity' => $activity->code, 'action' => 'checkin'])
            : null);

        return view('admin.activities.checkins', [
            'activity' => $activity,
            'columns' => $columns,
            'rows' => $rows,
            'checkinQr' => $checkinQr,
            'checkinUrl' => $checkinUrl,
        ]);
    }

    /**
     * แท็บแบบประเมินของหน้ารายละเอียดกิจกรรม
     *
     * คำตอบเป็นนิรนามโดยออกแบบ (evl_satisfaction_responses ไม่มี FK ไปหาคน)
     * ตัวระบุเดียวคือลำดับผู้ตอบ — คอลัมน์คะแนนสร้างตามคำถามให้คะแนนของ
     * แบบประเมินที่กิจกรรมนั้นใช้จริง จึงต่างกันได้รายกิจกรรม
     */
    public function evaluations(Activity $activity, ActivitySatisfactionService $satisfaction): View
    {
        $activity->load(['rounds', 'qrCodes'])->loadCount([
            'registrations',
            'registrations as checked_in_count' => fn ($q) => $q->whereNotNull('checked_in_at'),
            'satisfactionResponses as responses_count',
        ]);

        $responses = SatisfactionResponse::query()
            ->where('activity_id', $activity->id)
            ->with('answers:id,response_id,response_type,question_id,score,text_value')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        /* คำถามให้คะแนนที่มีคำตอบจริง — อ่านจากคำตอบ ไม่ใช่จากฟอร์ม
           กิจกรรมที่ถอดแบบประเมินออกแล้วแต่มีคำตอบค้าง ข้อมูลเดิมต้องยังครบ */
        $questionIds = $responses
            ->flatMap(fn (SatisfactionResponse $r) => $r->answers->whereNotNull('score')->pluck('question_id'))
            ->unique()
            ->values();

        $questions = Question::query()
            ->whereIn('id', $questionIds)
            ->orderBy('form_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'text']);

        $roundOrder = $activity->rounds
            ->sortBy(fn ($round) => $round->round_date->toDateString())
            ->values()
            ->mapWithKeys(fn ($round, int $index) => [$round->id => $index + 1]);

        $thMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        $rows = $responses->map(function (SatisfactionResponse $r) use ($activity, $roundOrder, $questions, $thMonths, $satisfaction) {
            $roundLabel = 'รอบเดียว';
            if ($activity->rounds->count() > 1) {
                $roundLabel = $r->activity_round_id
                    ? 'รอบที่ '.($roundOrder->get($r->activity_round_id) ?? 1)
                    : 'ไม่ระบุรอบ';
            }

            $scores = $r->answers->whereNotNull('score')->keyBy('question_id');
            $rated = $scores->pluck('score')->map(fn ($s) => (float) $s);
            $average = $rated->isEmpty() ? null : round($rated->avg(), 1);

            $row = [
                'round' => $roundLabel,
                'average' => $average,
                /* คำอธิบายระดับแบบเข้าใจง่าย (ดีมาก/ดี/ปานกลาง/ต้องปรับปรุง) — เกณฑ์เดียวกับ
                   ป้ายเกรดรวมในแท็บรายงาน ไม่คิดเกณฑ์แยกที่นี่ */
                'grade' => $average === null ? null : $satisfaction->grade($average),
                'comment' => $r->answers->pluck('text_value')->map(fn ($t) => trim((string) $t))->filter()->implode(' · '),
                'submitted_at' => $r->submitted_at
                    ? $r->submitted_at->day.' '.$thMonths[$r->submitted_at->month - 1].' '.($r->submitted_at->year + 543)
                        .' · '.$r->submitted_at->format('H:i').' น.'
                    : '',
            ];

            /* คำตอบรายข้อแสดงเป็นคำ (น้อยที่สุด … มากที่สุด) ไม่ใช่ตัวเลขเปล่า ๆ
               ป้ายชุดเดียวกับที่ผู้ตอบเห็นตอนทำแบบประเมิน จึงอ่านแล้วตรงกับที่เขาเลือกจริง */
            $ratingLabels = config('farmconcept.tracking_round.rating_labels');

            foreach ($questions as $question) {
                $score = $scores->get($question->id)?->score;
                $row['q'.$question->id] = $score !== null
                    ? ($ratingLabels[(int) $score] ?? (string) (int) $score)
                    : '';
            }

            return $row;
        })->values();

        $columns = collect([
            ['key' => 'round', 'label' => 'รอบ', 'fixed' => true, 'show' => $activity->rounds->count() > 1],
        ])
            ->concat($questions->map(fn (Question $q) => [
                'key' => 'q'.$q->id,
                'label' => $q->text,
                'fixed' => false,
                'show' => true,
            ]))
            ->concat([
                ['key' => 'average', 'label' => 'เฉลี่ย', 'fixed' => true, 'show' => true],
                ['key' => 'comment', 'label' => 'ความเห็น', 'fixed' => false, 'show' => true],
                ['key' => 'submitted_at', 'label' => 'วันที่ตอบ', 'fixed' => false, 'show' => true],
            ])
            ->filter(fn (array $col) => $col['show'])
            ->values();

        /* ลิงก์และ QR ของแบบประเมิน — ปุ่ม "ทำแบบประเมินแทน" และ popup QR ให้สแกนหน้างาน
           กิจกรรมที่เปิดแบบประเมินแต่ยังไม่มี QR ใช้ URL หน้าแบบประเมินตรง ๆ แทน
           (ปลายทางเดียวกับที่ QR redirect ไป) — ปุ่ม QR จะไม่แสดงในกรณีนั้น */
        $surveyQr = collect($this->qrPayload($activity))->firstWhere('purpose', 'post_survey');
        $surveyUrl = $surveyQr['url'] ?? ($activity->has_post_survey
            ? route('public.activities.survey', $activity->code)
            : null);

        return view('admin.activities.evaluations', [
            'activity' => $activity,
            'columns' => $columns,
            'rows' => $rows,
            'surveyQr' => $surveyQr,
            'surveyUrl' => $surveyUrl,
        ]);
    }

    /**
     * แท็บรายงานภาพรวมของหน้ารายละเอียดกิจกรรม
     *
     * สรุปเป็นกราฟจากข้อมูลชุดเดียวกับแท็บอื่นในหน้านี้ทั้งหมด ไม่มีตัวเลขเก็บแยกไว้ต่างหาก
     * ที่มาของแต่ละกราฟ: เช็คอิน → act_registrations.checked_in_at,
     * การชำระเงิน → act_registrations.payment_status, แนวโน้มลงทะเบียน → registered_at,
     * แบบประเมิน → ActivitySatisfactionService (ตัวเดียวกับแท็บแบบประเมิน)
     */
    public function reports(Activity $activity, ActivitySatisfactionService $satisfaction): View
    {
        $activity->load('rounds')->loadCount([
            'registrations',
            'registrations as checked_in_count' => fn ($q) => $q->whereNotNull('checked_in_at'),
            'satisfactionResponses as responses_count',
        ]);

        $registrations = Registration::query()
            ->where('activity_id', $activity->id)
            ->with([
                'paymentSlips:id,registration_id,amount,status',
                'ageRange:id,label,sort_order',
                'occupation:id,label',
                'sourceChannel:id,label',
                'area:id,name',
                'interests:id,label',
            ])
            ->get([
                'id', 'payment_status', 'registered_at', 'checked_in_at', 'gender', 'is_manual_entry', 'activity_round_id',
                /* ต้องเลือก FK ของแต่ละความสัมพันธ์มาด้วยเสมอ ไม่งั้น belongsTo จะหาไม่เจอและได้ null ทุกแถว */
                'age_range_id', 'occupation_id', 'source_channel_id', 'area_id', 'occupation_raw',
            ]);

        $checkedIn = $activity->checked_in_count;
        $notCheckedIn = max(0, $activity->registrations_count - $checkedIn);

        /* โดนัทเช็คอิน — สองชิ้นพอ ไม่ต้องแยกเหตุผลที่ยังไม่มา (มีคอลัมน์นั้นอยู่แล้วในแท็บ Check-in) */
        $checkin = [
            'total' => $activity->registrations_count,
            'segments' => $this->donutSegments([
                ['label' => 'เช็คอินแล้ว', 'count' => $checkedIn, 'tone' => 'success'],
                ['label' => 'ยังไม่เช็คอิน', 'count' => $notCheckedIn, 'tone' => 'muted'],
            ]),
        ];

        /* โดนัทสถานะชำระเงิน + รายรับรวม — เฉพาะกิจกรรมที่มีค่าใช้จ่าย
           ยอดรายรับใช้เกณฑ์เดียวกับแท็บภาพรวม: ยึดยอดจากสลิปใบล่าสุด ถอยไปใช้ค่าเข้าร่วมถ้าสลิปไม่ระบุ */
        $payment = null;
        $revenue = 0.0;
        if ($activity->has_fee) {
            $counts = $registrations->countBy(fn (Registration $r) => $r->payment_status ?: 'ยังไม่ชำระ');
            $payment = [
                'total' => $registrations->count(),
                'segments' => $this->donutSegments([
                    ['label' => 'ชำระแล้ว', 'count' => $counts->get('ชำระแล้ว', 0), 'tone' => 'success'],
                    ['label' => 'รอตรวจสอบ', 'count' => $counts->get('รอตรวจสอบ', 0), 'tone' => 'warning'],
                    ['label' => 'ยังไม่ชำระ', 'count' => $counts->get('ยังไม่ชำระ', 0), 'tone' => 'danger'],
                ]),
            ];

            $revenue = $registrations
                ->where('payment_status', 'ชำระแล้ว')
                ->sum(fn (Registration $r) => (float) ($r->paymentSlips->first()?->amount ?? $activity->fee));
        }

        /* Walk-in vs ลงทะเบียนล่วงหน้า — สองชิ้น สีคนละความหมายจากโทนสถานะ (info=หน้างาน) */
        $walkin = null;
        if ($registrations->isNotEmpty()) {
            $walkInCount = $registrations->where('is_manual_entry', true)->count();
            $walkin = [
                'total' => $registrations->count(),
                'segments' => $this->donutSegments([
                    ['label' => 'ลงทะเบียนล่วงหน้า', 'count' => $registrations->count() - $walkInCount, 'tone' => 'success'],
                    ['label' => 'Walk-in หน้างาน', 'count' => $walkInCount, 'tone' => 'info'],
                ]),
            ];
        }

        $genders = ['male' => 'ชาย', 'female' => 'หญิง', 'other' => 'เพศทางเลือก', 'undisclosed' => 'ไม่ระบุ'];

        /* กราฟประชากรศาสตร์ — สร้างจากชุดผู้ลงทะเบียนชุดไหนก็ได้ด้วยสูตรเดียวกัน
           รายงาน "ลงทะเบียน" ใช้ทุกคนที่ลงทะเบียน · รายงาน "Check-in" ใช้เฉพาะคนที่มาจริง
           ตัวเลขสองชุดจึงตอบคนละคำถาม (ใครสมัคร vs ใครมา) แต่คิดด้วยเกณฑ์เดียวกัน */
        $demographicsOf = function (Collection $rows) use ($genders): ?array {
            if ($rows->isEmpty()) {
                return null;
            }

            $result = [
                'gender' => $this->rankedDonut($rows, fn (Registration $r) => $genders[$r->gender] ?? null),
                'age' => $this->barList(
                    $rows,
                    fn (Registration $r) => $r->ageRange?->label,
                    fn (Registration $r) => $r->ageRange?->sort_order ?? 999
                ),
                'occupation' => $this->barList($rows, fn (Registration $r) => $r->occupation?->label ?: $r->occupation_raw),
                'source' => $this->rankedDonut($rows, fn (Registration $r) => $r->sourceChannel?->label),
                'area' => $this->barList($rows, fn (Registration $r) => $r->area?->name),
            ];

            /* ความสนใจเป็นแท็กเลือกได้หลายอัน — นับจากรายการที่เลือก ไม่ใช่นับรายคน */
            $interestCounts = $rows->flatMap->interests->countBy(fn ($i) => $i->label);
            if ($interestCounts->isNotEmpty()) {
                $result['interests'] = $interestCounts->sortDesc()->take(12)
                    ->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])
                    ->values()->all();
            }

            return $result;
        };

        $demographics = $demographicsOf($registrations);
        $checkinDemographics = $demographicsOf($registrations->whereNotNull('checked_in_at'));

        /* รอบที่ลงทะเบียน + เช็คอินรายรอบ — เฉพาะกิจกรรมที่แบ่งมากกว่าหนึ่งรอบ
           คิดจากชุดข้อมูลเดียวกัน ตัวเลขสองกราฟจึงตรงกันเสมอ */
        $roundChart = null;
        $roundCheckinChart = null;
        if ($activity->rounds->count() > 1) {
            $roundOrder = $activity->rounds->sortBy(fn ($r) => $r->round_date->toDateString())->values();
            $counts = $registrations->countBy('activity_round_id');
            $checkedInCounts = $registrations->whereNotNull('checked_in_at')->countBy('activity_round_id');
            $maxRound = max(1, $counts->max() ?: 1);

            $roundChart = $roundOrder->map(fn ($round, int $i) => [
                'label' => 'รอบ '.($i + 1),
                'count' => $counts->get($round->id, 0),
                'pct' => (int) round($counts->get($round->id, 0) / $maxRound * 100),
            ])->values()->all();

            $roundCheckinChart = $roundOrder->map(function ($round, int $i) use ($counts, $checkedInCounts) {
                $registered = $counts->get($round->id, 0);
                $came = $checkedInCounts->get($round->id, 0);

                return [
                    'label' => 'รอบ '.($i + 1),
                    'count' => $came,
                    /* เทียบกับผู้ลงทะเบียนของรอบนั้น ไม่ใช่รอบที่มากที่สุด — อ่านเป็น "อัตราการมา" ได้ตรง ๆ */
                    'pct' => $registered > 0 ? (int) round($came / $registered * 100) : 0,
                ];
            })->values()->all();
        }

        $survey = $activity->has_post_survey || $activity->responses_count > 0
            ? $satisfaction->summary($activity)
            : null;
        $questionCharts = $activity->has_post_survey || $activity->responses_count > 0
            ? $satisfaction->questionBreakdown($activity)
            : [];

        /* รายการกราฟทั้งหมดของหน้านี้ — ใช้สร้างแผงติ๊กเลือกว่าจะแสดงกราฟไหนบ้าง
           key ต้องตรงกับ data-chart ของการ์ดแต่ละใบใน view เป๊ะ
           report = กราฟนี้อยู่ในรายงานชุดไหนบ้าง (การ์ดหนึ่งใบอยู่ได้หลายชุด) */
        $chartOptions = collect([
            ['key' => 'checkin', 'label' => 'สถานะเช็คอิน', 'report' => 'overview checkin', 'show' => true],
            ['key' => 'roundCheckin', 'label' => 'เช็คอินแยกรายรอบ', 'report' => 'checkin', 'show' => (bool) $roundCheckinChart],
            ['key' => 'payment', 'label' => 'สถานะการชำระเงิน', 'report' => 'overview registration', 'show' => (bool) $payment],
            ['key' => 'walkin', 'label' => 'Walk-in กับลงทะเบียนล่วงหน้า', 'report' => 'registration', 'show' => (bool) $walkin],
            ['key' => 'round', 'label' => 'รอบที่ลงทะเบียน', 'report' => 'registration', 'show' => (bool) $roundChart],
            ['key' => 'gender', 'label' => 'เพศผู้เข้าร่วม', 'report' => 'registration', 'show' => (bool) ($demographics['gender']['segments'] ?? false)],
            ['key' => 'age', 'label' => 'ช่วงอายุ', 'report' => 'registration', 'show' => (bool) ($demographics['age'] ?? false)],
            ['key' => 'occupation', 'label' => 'อาชีพ', 'report' => 'registration', 'show' => (bool) ($demographics['occupation'] ?? false)],
            ['key' => 'source', 'label' => 'ช่องทางที่รู้จักกิจกรรม', 'report' => 'registration', 'show' => (bool) ($demographics['source']['segments'] ?? false)],
            ['key' => 'area', 'label' => 'พื้นที่', 'report' => 'registration', 'show' => (bool) ($demographics['area'] ?? false)],
            ['key' => 'interests', 'label' => 'ความสนใจ', 'report' => 'registration', 'show' => (bool) ($demographics['interests'] ?? false)],

            /* ชุดเดียวกันแต่นับเฉพาะคนที่เช็คอินแล้ว — ตอบว่า "คนที่มาจริงเป็นใคร" */
            ['key' => 'ckGender', 'label' => 'เพศ (ผู้ที่มาจริง)', 'report' => 'checkin', 'show' => (bool) ($checkinDemographics['gender']['segments'] ?? false)],
            ['key' => 'ckAge', 'label' => 'ช่วงอายุ (ผู้ที่มาจริง)', 'report' => 'checkin', 'show' => (bool) ($checkinDemographics['age'] ?? false)],
            ['key' => 'ckOccupation', 'label' => 'อาชีพ (ผู้ที่มาจริง)', 'report' => 'checkin', 'show' => (bool) ($checkinDemographics['occupation'] ?? false)],
            ['key' => 'ckSource', 'label' => 'ช่องทางที่รู้จักกิจกรรม (ผู้ที่มาจริง)', 'report' => 'checkin', 'show' => (bool) ($checkinDemographics['source']['segments'] ?? false)],
            ['key' => 'ckArea', 'label' => 'พื้นที่ (ผู้ที่มาจริง)', 'report' => 'checkin', 'show' => (bool) ($checkinDemographics['area'] ?? false)],
            ['key' => 'ckInterests', 'label' => 'ความสนใจ (ผู้ที่มาจริง)', 'report' => 'checkin', 'show' => (bool) ($checkinDemographics['interests'] ?? false)],
            ['key' => 'survey', 'label' => 'คะแนนความพึงพอใจโดยรวม', 'report' => 'overview survey', 'show' => (bool) $survey],
        ])
            ->concat(collect($questionCharts)->map(fn (array $q) => [
                'key' => 'q'.$q['id'],
                'label' => 'แบบประเมิน: '.$q['label'],
                'report' => 'survey',
                'show' => true,
            ]))
            ->filter(fn (array $c) => $c['show'])
            ->values();

        /* ชุดรายงานที่เลือกดูได้ — ซ่อนชุดที่ไม่มีกราฟอยู่เลย ไม่ให้กดแล้วเจอหน้าว่าง */
        $reportTabs = collect([
            ['key' => 'overview', 'label' => 'ภาพรวม'],
            ['key' => 'registration', 'label' => 'ลงทะเบียน'],
            ['key' => 'checkin', 'label' => 'Check-in'],
            ['key' => 'survey', 'label' => 'แบบประเมิน'],
        ])->filter(fn (array $tab) => $chartOptions->contains(
            fn (array $c) => in_array($tab['key'], explode(' ', $c['report']), true)
        ))->values();

        return view('admin.activities.reports', [
            'activity' => $activity,
            'checkin' => $checkin,
            'payment' => $payment,
            'revenue' => $revenue,
            'walkin' => $walkin,
            'roundChart' => $roundChart,
            'roundCheckinChart' => $roundCheckinChart,
            'demographics' => $demographics,
            'checkinDemographics' => $checkinDemographics,
            'survey' => $survey,
            'questionCharts' => $questionCharts,
            'chartOptions' => $chartOptions,
            'reportTabs' => $reportTabs,
        ]);
    }

    /**
     * โดนัทสีวนตามอันดับ (rank 0–4) — ใช้กับข้อมูลที่ไม่มีความหมายสถานะตายตัว
     * (เพศ ช่องทางที่รู้จัก ฯลฯ) ต่างจาก donutSegments() ที่สีมีความหมายคงที่
     *
     * @template T
     * @param  Collection<int, T>  $rows
     * @param  callable(T): (string|null)  $labelFn
     * @return array{total: int, segments: array<int, array<string, mixed>>}
     */
    private function rankedDonut(Collection $rows, callable $labelFn): array
    {
        $counts = $rows->map($labelFn)->filter()->countBy();

        if ($counts->isEmpty()) {
            return ['total' => 0, 'segments' => []];
        }

        $total = $counts->sum();
        $circumference = 2 * M_PI * 76;
        $offset = 0.0;

        $segments = $counts->sortDesc()->map(function (int $count, string $label) use ($total, $circumference, &$offset, &$rank) {
            $rank ??= 0;
            $length = ($count / $total) * $circumference;

            $segment = [
                'label' => $label,
                'count' => $count,
                'rank' => $rank % 5,
                'pct' => (int) round($count / $total * 100),
                'dash' => round(max($length - 3, 0), 2).' '.round($circumference - max($length - 3, 0), 2),
                'offset' => round(-$offset, 2),
            ];

            $offset += $length;
            $rank++;

            return $segment;
        })->values()->all();

        return ['total' => $total, 'segments' => $segments];
    }

    /**
     * แท่งกราฟแนวนอนแบบทั่วไป — เรียงจากมากไปน้อยตามค่าเริ่มต้น หรือตาม $orderFn ถ้าระบุ
     * (ใช้กับช่วงอายุที่ต้องการเรียงตามลำดับช่วงจริง ไม่ใช่ตามจำนวนคน)
     * ตัดเหลือ 8 อันดับแรก ที่เหลือรวมเป็น "อื่น ๆ" กันแท่งเยอะจนอ่านไม่ออก
     *
     * @template T
     * @param  Collection<int, T>  $rows
     * @param  callable(T): (string|null)  $labelFn
     * @param  (callable(T): int)|null  $orderFn
     * @return array<int, array<string, mixed>>|null
     */
    private function barList(Collection $rows, callable $labelFn, ?callable $orderFn = null): ?array
    {
        $counts = $rows->map($labelFn)->filter()->countBy();

        if ($counts->isEmpty()) {
            return null;
        }

        if ($orderFn) {
            $orderByLabel = $rows->filter(fn ($r) => $labelFn($r))
                ->unique(fn ($r) => $labelFn($r))
                ->mapWithKeys(fn ($r) => [$labelFn($r) => $orderFn($r)]);
            $counts = $counts->sortBy(fn (int $count, string $label) => $orderByLabel->get($label, 999));
        } else {
            $counts = $counts->sortDesc();
        }

        $top = $counts->take(8);
        $rest = $counts->skip(8)->sum();

        if ($rest > 0) {
            $top->put('อื่น ๆ', $rest);
        }

        $max = $top->max() ?: 1;

        return $top->map(fn (int $count, string $label) => [
            'label' => $label,
            'count' => $count,
            'pct' => (int) round($count / $max * 100),
        ])->values()->all();
    }

    /**
     * แปลงจำนวนเป็นค่า stroke-dasharray/offset ของวงโดนัท — เรขาคณิตเดียวกับแดชบอร์ดภาพรวม
     * (r=76 · เว้นช่อง 3 หน่วยระหว่างชิ้น) แต่ใช้โทนสีสถานะของระบบแทนสเกลเขียว 5 ระดับ
     *
     * @param  array<int, array{label: string, count: int, tone: string}>  $items
     * @return array<int, array<string, mixed>>
     */
    private function donutSegments(array $items): array
    {
        $total = array_sum(array_column($items, 'count'));
        $circumference = 2 * M_PI * 76;
        $offset = 0.0;

        return collect($items)->map(function (array $item) use ($total, $circumference, &$offset) {
            $length = $total > 0 ? ($item['count'] / $total) * $circumference : 0;

            $segment = [
                'label' => $item['label'],
                'count' => $item['count'],
                'tone' => $item['tone'],
                'pct' => $total > 0 ? round($item['count'] / $total * 100) : 0,
                'dash' => round(max($length - 3, 0), 2).' '.round($circumference - max($length - 3, 0), 2),
                'offset' => round(-$offset, 2),
            ];

            $offset += $length;

            return $segment;
        })->all();
    }

    /**
     * ฟอร์มแก้ไขกิจกรรม
     *
     * ฟอร์มเดิมอ้างอิงทุกอย่างด้วย "ชื่อ" ไม่ใช่ id จึงต้องส่งตารางแปลงชื่อ→id ไปด้วย
     * และส่งค่าปัจจุบันทั้งชุดไป เพื่อให้ฟิลด์ที่ฟอร์มไม่ได้คุม (visibility / organizer /
     * data_source) คงค่าเดิมไว้ตอนบันทึก ไม่ถูกล้างเป็นค่าว่าง
     */
    public function edit(Activity $activity): View
    {
        $this->authorize('update', $activity);

        $activity->load(['areas', 'instructors', 'targetGroups', 'rounds', 'program', 'course', 'format', 'parentEvent', 'forms', 'qrCodes']);

        return $this->formView($activity);
    }

    /**
     * ฟอร์มสร้างกิจกรรมใหม่
     *
     * ใช้ Blade ตัวเดียวกับหน้าแก้ไข เพราะฟิลด์เหมือนกันทุกช่อง
     * ต่างกันแค่ปลายทางที่ส่งข้อมูลไป ถ้าแยกไฟล์จะต้องแก้สองที่ทุกครั้งที่เพิ่มฟิลด์
     *
     * กิจกรรมยังไม่มีตัวตนในฐาน จึงสร้าง instance เปล่าที่ยังไม่บันทึกขึ้นมาแทน
     * แล้วผูก relation ว่างไว้เอง ไม่ปล่อยให้ Eloquent ไปยิง query ด้วยคีย์ null
     */
    public function create(): View
    {
        $this->authorize('create', Activity::class);

        $activity = new Activity([
            'code' => self::NEW_CODE,
            'status' => Activity::STATUS_DRAFT,
            'type' => Activity::TYPE_ACTIVITY,
            'visibility' => 'สาธารณะ',
            'capacity' => 0,
            'fee' => 0,
        ]);

        foreach (['areas', 'instructors', 'targetGroups', 'rounds', 'forms', 'qrCodes'] as $many) {
            $activity->setRelation($many, collect());
        }

        foreach (['program', 'course', 'format', 'parentEvent'] as $one) {
            $activity->setRelation($one, null);
        }

        $activity->registrations_count = 0;

        return $this->formView($activity);
    }

    /**
     * บันทึกกิจกรรมใหม่
     *
     * ใช้ ActivityRequest ตัวเดียวกับการแก้ไข — ฉบับร่างตรวจน้อย เผยแพร่ตรวจครบ
     * ตอบ URL ของหน้าแก้ไขกลับไปด้วย เพื่อให้หน้าจอเปลี่ยนโหมดต่อได้โดยไม่ต้องโหลดใหม่
     */
    public function store(ActivityRequest $request, ActivityService $service): JsonResponse
    {
        $this->authorize('create', Activity::class);

        $activity = $service->create($request->validated(), $request->user());

        return response()->json([
            'message' => 'สร้าง'.($activity->isEvent() ? 'อีเวนท์' : 'กิจกรรม').' "'.$activity->name.'" แล้ว',
            'code' => $activity->code,
            'redirect' => route('admin.activities.index'),
            'activity' => $this->toListRow($activity->load(['program', 'format', 'areas', 'instructors'])->loadCount('registrations')),
        ], 201);
    }

    /**
     * ข้อมูลทั้งหมดที่ฟอร์มต้องใช้ — ใช้ร่วมกันทั้งโหมดสร้างและโหมดแก้ไข
     *
     * ฟอร์มเดิมอ้างอิงทุกอย่างด้วย "ชื่อ" ไม่ใช่ id จึงต้องส่งตารางแปลงชื่อ→id ไปด้วย
     * และส่งค่าปัจจุบันทั้งชุดไป เพื่อให้ฟิลด์ที่ฟอร์มไม่ได้คุม (visibility / organizer /
     * data_source) คงค่าเดิมไว้ตอนบันทึก ไม่ถูกล้างเป็นค่าว่าง
     */
    private function formView(Activity $activity): View
    {
        $evaluationForms = Form::active()
            ->whereIn('type', [Form::TYPE_REGISTRATION, Form::TYPE_POST_ACTIVITY])
            ->with(['questions:id,form_id,question_type,text,is_required,sort_order'])
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn (Form $form) => [
                'id' => (string) $form->id,
                'code' => $form->code,
                'name' => $form->name,
                'type' => $form->type,
                'status' => $form->status,
                'questions' => $form->questions->map(fn ($question) => [
                    'text' => $question->text,
                    'type' => $question->question_type,
                    'required' => $question->is_required,
                ])->values()->all(),
            ])->values();

        return view('admin.activities.form', [
            'activity' => $activity,
            'isCreate' => ! $activity->exists,
            'activities' => [$this->toFormRow($activity)],
            'sessions' => [$activity->code => $this->toSessions($activity)],
            'areas' => Area::orderBy('name')->get(['id', 'name']),
            'targetGroups' => TargetGroup::active()->get(['id', 'name']),

            /* ต้องส่ง icon ไปด้วย ไม่งั้นชิปหมวดหมู่จะไม่มีไอคอน
               (catIconSvg ใช้ค่านี้ไปหา path ใน activityCategoryIcons) */
            'formats' => ActivityFormat::active()->get(['id', 'name', 'icon'])
                ->map(fn (ActivityFormat $f) => ['name' => $f->name, 'icon' => $f->icon, 'active' => true]),

            /* โปรแกรม → หลักสูตร → วิทยากรที่สอนหลักสูตรนั้น
               ของเดิมเขียนไว้ตายตัวในไฟล์ JS และเป็นคนละชุดกับฐานข้อมูลทั้งหมด */
            'catalog' => $this->programCatalog(),

            /* อีเวนท์ที่เลือกเป็นแม่ได้ — ไม่รวมตัวเอง กันชี้หาตัวเอง */
            'events' => Activity::selectableEvents($activity->id)->get(['id', 'name']),
            'evaluationForms' => $evaluationForms,
            'qrCodes' => $this->qrPayload($activity),

            /* สองชุดนี้ยังไม่มีตารางในฐาน — docs/activity-module.md บรรทัด 84 ระบุว่า
               ประเภทกิจกรรมไม่ต้องมีหน้าจอจัดการ จึงเก็บเป็น config ไปก่อน
               ถ้าภายหลังทำเป็นตาราง ให้เปลี่ยนมาอ่านจากตารางที่นี่จุดเดียว */
            'types' => config('farmconcept.activity_types'),
            'venueModes' => config('farmconcept.venue_modes'),
            'lookup' => $this->lookupTables(),
            'current' => $this->currentPayload($activity),
            'coverMaxBytes' => $this->coverMaxBytes(),
        ]);
    }

    /**
     * เพดานขนาดรูปปกที่อัปได้จริงบนเครื่องนี้ (ไบต์)
     *
     * กฎของแอปคือ 5MB แต่ PHP ตัดไฟล์ทิ้งตั้งแต่ก่อนถึง Laravel ถ้าเกิน upload_max_filesize
     * หรือ post_max_size — ผลคือ Laravel เห็นเป็น "ไม่ได้แนบไฟล์มา" แล้วฟ้องผิดเรื่อง
     * ส่งค่าที่เล็กที่สุดในสามค่าไปให้หน้าจอ เพื่อบอกผู้ใช้ตรง ๆ ก่อนจะส่งไฟล์ที่ไม่มีทางผ่าน
     */
    private function coverMaxBytes(): int
    {
        $toBytes = function (string $value): int {
            $value = trim($value);
            $unit = strtolower(substr($value, -1));
            $number = (int) $value;

            return match ($unit) {
                'g' => $number * 1024 ** 3,
                'm' => $number * 1024 ** 2,
                'k' => $number * 1024,
                default => $number,
            };
        };

        return min(
            self::COVER_MAX_KB * 1024,
            $toBytes(ini_get('upload_max_filesize') ?: '2M'),
            $toBytes(ini_get('post_max_size') ?: '8M'),
        );
    }

    /**
     * โปรแกรม → หลักสูตร → วิทยากร ในรูปแบบที่ฟอร์มใช้เลือกแบบต่อเนื่อง
     *
     * วิทยากรของแต่ละหลักสูตรมาจาก mst_instructor_course ไม่ใช่รายชื่อวิทยากรทั้งหมด
     * เพื่อให้เลือกได้เฉพาะคนที่สอนหลักสูตรนั้นจริง
     *
     * @return array<int, array<string, mixed>>
     */
    private function programCatalog(): array
    {
        return Program::active()
            ->with(['courses.instructors:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Program $program) => [
                'program' => $program->name,
                'courses' => $program->courses->map(fn (Course $course) => [
                    'name' => $course->name,
                    /* ส่ง id มากับชื่อเลย ไม่ให้หน้าจอต้องนำชื่อวิทยากรไปค้นหา id อีกรอบ
                       ชื่อแก้ไขได้และอาจมีช่องว่างต่างกัน แต่ id เป็นตัวอ้างอิงจริงของฐานข้อมูล */
                    'teachers' => $course->instructors
                        ->map(fn (Instructor $instructor) => [
                            'id' => $instructor->id,
                            'name' => $instructor->name,
                        ])
                        ->values()
                        ->all(),
                ])->all(),
            ])
            ->all();
    }

    /**
     * ตารางแปลงชื่อ → id ของทุก master data ที่ฟอร์มใช้
     *
     * @return array<string, mixed>
     */
    private function lookupTables(): array
    {
        return [
            'areas' => Area::pluck('id', 'name'),
            'targetGroups' => TargetGroup::pluck('id', 'name'),
            'instructors' => Instructor::pluck('id', 'name'),
            'formats' => ActivityFormat::pluck('id', 'name'),
            'events' => Activity::where('type', Activity::TYPE_EVENT)->pluck('id', 'name'),
            'courses' => Course::get(['id', 'name', 'program_id'])
                ->keyBy('name')
                ->map(fn (Course $c) => ['id' => $c->id, 'program_id' => $c->program_id]),
        ];
    }

    /**
     * ค่าปัจจุบันในรูปแบบเดียวกับที่ ActivityRequest รับ
     *
     * @return array<string, mixed>
     */
    private function currentPayload(Activity $activity): array
    {
        $registrationForm = $activity->forms->first(fn (Form $form) => $form->pivot->slot === 'registration');
        $postSurveyForm = $activity->forms->first(fn (Form $form) => $form->pivot->slot === 'post_survey');

        return [
            'name' => $activity->name,
            'description' => $activity->description,
            'type' => $activity->type,
            'participant_type' => $activity->participant_type,
            'parent_event_id' => $activity->parent_event_id,
            'status' => $activity->status,
            'visibility' => $activity->visibility,
            'program_id' => $activity->program_id,
            'course_id' => $activity->course_id,
            'format_id' => $activity->format_id,
            'venue_mode' => $activity->venue_mode,
            'data_source' => $activity->data_source,
            'organizer' => $activity->organizer,
            'capacity' => $activity->capacity,
            'has_fee' => $activity->has_fee,
            'fee' => (float) $activity->fee,
            'requires_registration' => $activity->requires_registration,
            'requires_checkin' => $activity->requires_checkin,
            'has_post_survey' => $activity->has_post_survey,
            'registration_form_id' => $registrationForm?->id,
            'post_survey_form_id' => $postSurveyForm?->id,
            'is_published' => $activity->is_published,
            'is_featured' => $activity->is_featured,
            'start_date' => $activity->start_date?->toDateString(),
            'end_date' => $activity->end_date?->toDateString(),
            'checkin_start_at' => $activity->checkin_start_at?->format('Y-m-d H:i:s'),
            'checkin_end_at' => $activity->checkin_end_at?->format('Y-m-d H:i:s'),
            'survey_start_at' => $activity->survey_start_at?->format('Y-m-d H:i:s'),
            'survey_end_at' => $activity->survey_end_at?->format('Y-m-d H:i:s'),
            'publish_start_at' => $activity->publish_start_at?->format('Y-m-d H:i:s'),
            'publish_end_at' => $activity->publish_end_at?->format('Y-m-d H:i:s'),
            'registration_start_at' => $activity->registration_start_at?->format('Y-m-d H:i:s'),
            'registration_end_at' => $activity->registration_end_at?->format('Y-m-d H:i:s'),
            'public_sort_order' => $activity->public_sort_order,
            'area_ids' => $activity->areas->pluck('id'),
            'instructor_ids' => $activity->instructors->pluck('id'),
            'target_group_ids' => $activity->targetGroups->pluck('id'),
        ];
    }

    /** รูปแบบที่ activity-create.js คาดไว้ตอนเติมค่าลงฟอร์ม (โหมดแก้ไข) */
    private function toFormRow(Activity $activity): array
    {
        return $this->toListRow($activity) + [
            'description' => $activity->description,
            'parentEventName' => $activity->parentEvent?->name,
            'course' => $activity->course?->name,
            'targetGroups' => $activity->targetGroups->pluck('name')->all(),
            'isPublished' => $activity->is_published,
            'isFeatured' => $activity->is_featured,
            'tags' => $activity->format ? [$activity->format->name] : [],
            'area' => $activity->areas->first()?->name,
            'evaluationFormIds' => $activity->forms->pluck('id')->map(fn ($id) => (string) $id)->all(),
            'coverImage' => $activity->cover_image_path,
            'coverImageUrl' => $activity->cover_image_path
                ? route('admin.activities.cover.show', $activity->code)
                : null,

            /* สวิตช์จริงจากฐานข้อมูล — ฟอร์มเดิมต้องอนุมานเอาเองเพราะข้อมูลจำลองไม่มีสามฟิลด์นี้ */
            'joinFlags' => [
                'reg' => $activity->requires_registration,
                'chk' => $activity->requires_checkin,
                'survey' => $activity->has_post_survey,
            ],

            /* ช่วงเวลา — ฟอร์มผ่าเป็นช่องวันกับช่องเวลาเอง */
            'registrationStart' => $activity->registration_start_at?->format('Y-m-d H:i'),
            'registrationEnd' => $activity->registration_end_at?->format('Y-m-d H:i'),
            'checkinStart' => $activity->checkin_start_at?->format('Y-m-d H:i'),
            'checkinEnd' => $activity->checkin_end_at?->format('Y-m-d H:i'),
            'surveyStart' => $activity->survey_start_at?->format('Y-m-d H:i'),
            'surveyEnd' => $activity->survey_end_at?->format('Y-m-d H:i'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function qrPayload(Activity $activity): array
    {
        if (! $activity->exists) {
            return [];
        }

        $labels = [
            'public' => $activity->requires_registration ? 'ลงทะเบียนเข้าร่วม' : 'หน้ากิจกรรม',
            'checkin' => 'Check-in หน้างาน',
            'post_survey' => 'แบบประเมินหลังกิจกรรม',
        ];

        return $activity->qrCodes
            ->whereIn('purpose', array_keys($labels))
            ->sortBy(fn ($qr) => array_search($qr->purpose, array_keys($labels), true))
            ->map(fn ($qr) => [
                'purpose' => $qr->purpose,
                'label' => $labels[$qr->purpose],
                'url' => url($qr->target_url),
                'imageUrl' => route('admin.activities.qr.show', [$activity->code, $qr->purpose]),
                'downloadUrl' => route('admin.activities.qr.show', [$activity->code, $qr->purpose, 'download' => 1]),
                'active' => $qr->is_active,
            ])
            ->values()
            ->all();
    }

    /**
     * บันทึกการแก้ไขกิจกรรม
     *
     * การตรวจข้อมูลอยู่ที่ ActivityRequest ทั้งหมด (ฉบับร่างตรวจน้อย เผยแพร่ตรวจครบ)
     * ตัวงานอยู่ที่ Service เพราะต้องเขียน 6 ตารางใน transaction เดียว
     */
    public function update(ActivityRequest $request, Activity $activity, ActivityService $service): JsonResponse
    {
        $this->authorize('update', $activity);

        $updated = $service->update($activity, $request->validated(), $request->user());

        /* ไม่ redirect หลังบันทึก — ต่างจาก store() ที่ต้องย้ายจาก /create ไปหน้าแก้ไขของรหัสที่เพิ่งได้
           ตรงนี้อยู่หน้าแก้ไขอยู่แล้ว การเด้งกลับไปหน้ารายการทุกครั้งที่กด "บันทึกร่าง" ทำให้แก้ไขต่อเนื่องไม่ได้
           (เช่น เพิ่มรอบแล้วกดบันทึก จะถูกเด้งออกจากฟอร์มก่อนเพิ่มรอบถัดไป) */
        return response()->json([
            'message' => 'บันทึกกิจกรรม "'.$updated->name.'" แล้ว',
            'activity' => $this->toListRow($updated->load(['program', 'format', 'areas', 'instructors'])->loadCount('registrations')),
        ]);
    }

    /**
     * เปลี่ยนสถานะกิจกรรมอย่างเดียว — ใช้กับ dropdown สถานะที่หน้ารายการ
     *
     * แยกจาก update() เพราะฟอร์มเต็มผ่าน ActivityRequest บังคับกรอกฟิลด์อื่นครบด้วย
     * (โดยเฉพาะตอนเผยแพร่) ซึ่งไม่เกี่ยวกับการแค่เปลี่ยนสถานะทีละแถวจากตาราง
     * สิทธิ์และเงื่อนไข "แก้ไขกิจกรรมที่ยกเลิกแล้วไม่ได้" ยึดตาม ActivityPolicy::update() ชุดเดียวกับฟอร์มเต็ม
     */
    public function updateStatus(Request $request, Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', Activity::STATUSES)],
        ], [], ['status' => 'สถานะ']);

        $activity->update(['status' => $data['status']]);

        return response()->json([
            'message' => 'เปลี่ยนสถานะกิจกรรม "'.$activity->name.'" เป็น "'.$activity->status.'" แล้ว',
            'status' => $activity->status,
        ]);
    }

    /** แสดงรูปผ่าน Laravel เพื่อให้ใช้งานบน IIS ได้โดยไม่ขึ้นกับ public/storage symlink */
    public function showCover(Activity $activity)
    {
        $this->authorize('update', $activity);

        abort_unless(
            $activity->cover_image_path
                && Storage::disk('public')->exists($activity->cover_image_path),
            404
        );

        return Storage::disk('public')->response(
            $activity->cover_image_path,
            null,
            ['Cache-Control' => 'private, max-age=300']
        );
    }

    /**
     * อัปโหลดรูปปกกิจกรรม
     *
     * แยกเป็น endpoint ของตัวเองแทนการส่งไฟล์มากับ PUT ที่บันทึกฟอร์ม
     * เพราะ PHP อ่าน multipart จาก PUT ไม่ได้ตรง ๆ และการอัปทันทีทำให้เห็นรูปพรีวิวได้เลย
     *
     * เก็บบน disk `public` เพราะรูปปกเป็นของสาธารณะ ผู้เข้าร่วมเห็นบนหน้ากิจกรรม
     * ต่างจากสลิปการชำระเงินที่ต้องเก็บนอก public แล้วเสิร์ฟผ่าน route ที่ตรวจสิทธิ์
     */
    public function uploadCover(Request $request, Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $request->validate(
            ['cover' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::COVER_MAX_KB]],
            [],
            ['cover' => 'รูปภาพปก']
        );

        $file = $request->file('cover');
        if (! $file || ! $file->isValid()) {
            return response()->json([
                'message' => 'ไฟล์รูปภาพไม่ถูกต้อง หรือขนาดใหญ่เกินเพดานที่ PHP บนเซิร์ฟเวอร์รองรับ (upload_max_filesize)',
                'errors' => ['cover' => ['ไม่สามารถอ่านไฟล์รูปภาพได้ กรุณาตรวจสอบขนาดไฟล์หรือลองใหม่อีกครั้ง']],
            ], 422);
        }

        /* ลบไฟล์เดิมก่อน ไม่งั้นเปลี่ยนรูปหลายรอบจะเหลือไฟล์กำพร้าสะสมไปเรื่อย ๆ */
        $this->deleteCoverFile($activity);

        $path = $this->storeCoverFile($file);

        if (! $path) {
            return response()->json([
                'message' => 'ไม่สามารถบันทึกไฟล์รูปภาพลงในดิสก์ได้',
                'errors' => ['cover' => ['เกิดข้อผิดพลาดในการบันทึกรูปภาพ']],
            ], 500);
        }

        $activity->forceFill(['cover_image_path' => $path])->save();

        return response()->json([
            'message' => 'อัปโหลดรูปปกแล้ว',
            'path' => $path,
            'url' => route('admin.activities.cover.show', $activity->code),
            'label' => $file->getClientOriginalName()
                .' · '.round($file->getSize() / 1048576, 1).'MB',
        ]);
    }

    private function storeCoverFile(UploadedFile $file): ?string
    {
        if (! $file->isValid()) {
            return null;
        }

        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png';
        $path = 'activity-covers/'.Str::random(40).'.'.strtolower($ext);

        $tmpPath = $file->getRealPath() ?: $file->getPathname();

        if (! empty($tmpPath) && file_exists($tmpPath)) {
            $contents = @file_get_contents($tmpPath);
            if ($contents !== false) {
                Storage::disk('public')->put($path, $contents);

                return $path;
            }
        }

        return $file->store('activity-covers', 'public');
    }

    /** ลบรูปปก — ลบทั้งไฟล์และค่าในฐาน ไม่ปล่อยให้ไฟล์ค้าง */
    public function deleteCover(Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $this->deleteCoverFile($activity);
        $activity->forceFill(['cover_image_path' => null])->save();

        return response()->json(['message' => 'ลบรูปปกแล้ว']);
    }

    private function deleteCoverFile(Activity $activity): void
    {
        if ($activity->cover_image_path) {
            Storage::disk('public')->delete($activity->cover_image_path);
        }
    }

    /**
     * ลบกิจกรรม
     *
     * ตรวจสิทธิ์ผ่าน ActivityPolicy — ล้มเหลวจะได้ 403 พร้อมเหตุผลที่แสดงให้ผู้ใช้อ่านได้
     * ตัวงานจริงอยู่ที่ Service เพราะมีสองขั้นตอนที่ต้องสำเร็จพร้อมกัน (ลบ + บันทึก log)
     */
    public function destroy(Request $request, Activity $activity, ActivityService $service): JsonResponse
    {
        $this->authorize('delete', $activity);

        $service->delete($activity, $request->user());

        return response()->json([
            'message' => 'ลบกิจกรรม "'.$activity->name.'" แล้ว',
        ]);
    }

    /**
     * แปลงเป็นรูปแบบที่สคริปต์หน้าจอเดิมอ่านได้
     *
     * ชื่อคีย์เป็น camelCase ตามที่ assets/js/activity-module.js คาดไว้ ไม่ใช่ snake_case ของฐานข้อมูล
     * เป็นสะพานชั่วคราวระหว่างการย้าย — เมื่อเขียน UI ใหม่เป็น Blade เต็มตัวแล้วชั้นนี้จะถูกถอดออก
     *
     * @return array<string, mixed>
     */
    private function toListRow(Activity $activity, ?int $publicRank = null): array
    {
        return [
            'id' => $activity->code,
            /* สถานะบนหน้าเว็บสาธารณะ — publicRank มีค่าเฉพาะตอนหน้ารายการส่งมาให้
               (คำนวณจากรายการทั้งชุด) ส่วนเหตุผลที่ไม่แสดงอ่านได้จากตัวกิจกรรมเอง */
            'publicRank' => $publicRank,
            'publicSortOrder' => (int) $activity->public_sort_order,
            'publicHiddenReason' => $publicRank === null ? $this->publicHiddenReason($activity) : null,
            'name' => $activity->name,
            'type' => $activity->type,
            'status' => $activity->status,
            'capacity' => $activity->capacity,
            'registered' => $activity->registrations_count,
            'checkedIn' => (int) ($activity->checked_in_count ?? 0),
            'responses' => (int) ($activity->responses_count ?? 0),
            'startDate' => $activity->start_date?->toDateString(),
            'endDate' => $activity->end_date?->toDateString(),
            'hasFee' => $activity->has_fee,
            'fee' => (float) $activity->fee,
            'areaList' => $activity->areas->pluck('name')->all(),
            'instructorList' => $activity->instructors->pluck('name')->all(),
            'program' => $activity->program?->name,
            'format' => $activity->format?->name,
            'parentEventName' => $activity->parentEvent?->name,

            /* ตารางแสดง "ชื่อคนแก้" กับ "วันที่ | เวลา" เหมือนหน้าอื่นในระบบ
               updatedAt ยังเป็น ISO เต็มเพราะตัวเรียงลำดับฝั่งหน้าจอใช้เทียบข้อความ */
            'updatedBy' => $activity->updatedBy?->name,
            'updatedAt' => $activity->updated_at?->toIso8601String(),
            'updatedDate' => $activity->updated_at?->toDateString(),
            'updatedTime' => $activity->updated_at?->format('H.i'),
        ];
    }

    /**
     * เหตุผลที่กิจกรรมไม่แสดงบนหน้าเว็บสาธารณะ — เรียงตามเงื่อนไขของ scopeForPublicListing
     *
     * ไล่เช็คเงื่อนไขเดียวกับที่หน้าเว็บใช้กรองทีละข้อ แล้วบอกข้อแรกที่ไม่ผ่าน
     * เพื่อให้แอดมินรู้ว่าต้องแก้อะไรกิจกรรมถึงจะโผล่ ไม่ใช่แค่รู้ว่า "ไม่แสดง"
     */
    private function publicHiddenReason(Activity $activity): string
    {
        $now = now();

        return match (true) {
            ! $activity->is_published => 'ยังไม่ได้เผยแพร่',
            $activity->visibility !== 'สาธารณะ' => 'การมองเห็นไม่ใช่สาธารณะ',
            $activity->publish_start_at && $activity->publish_start_at->gt($now) => 'ยังไม่ถึงช่วงเผยแพร่',
            $activity->publish_end_at && $activity->publish_end_at->lt($now) => 'พ้นช่วงเผยแพร่แล้ว',
            (int) $activity->public_sort_order <= 0 => 'ยังไม่กำหนดลำดับแสดง',
            default => 'ไม่เข้าเงื่อนไขการแสดง',
        };
    }

    /**
     * รอบกิจกรรมในรูปแบบที่ TFC.activity.schedules() อ่านได้ — คีย์ time เป็น "09:00 - 12:00"
     *
     * @return array<int, array<string, mixed>>
     */
    private function toSessions(Activity $activity): array
    {
        return $activity->rounds->map(fn ($round) => [
            'date' => $round->round_date->toDateString(),
            'time' => substr((string) $round->time_start, 0, 5).' - '.substr((string) $round->time_end, 0, 5),
            'location' => $round->location,
            'capacity' => $round->capacity,
        ])->all();
    }
}
