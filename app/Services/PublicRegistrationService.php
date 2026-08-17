<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityRound;
use App\Models\Consent;
use App\Models\ConsentDocument;
use App\Models\Form;
use App\Models\Participant;
use App\Models\PaymentSlip;
use App\Models\Registration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicRegistrationService
{
    public function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?: '';
    }

    public function alreadyRegistered(Activity $activity, string $phone): bool
    {
        $phone = $this->normalizePhone($phone);
        $formatted = substr($phone, 0, 3).'-'.substr($phone, 3, 3).'-'.substr($phone, 6);

        return $activity->registrations()->whereIn('phone', [$phone, $formatted])->exists();
    }

    /**
     * การจองทั้งหมดของเบอร์โทรหรืออีเมลนี้ในกิจกรรมนี้ — ใช้ตอบหน้าจอ "คุณลงทะเบียนแล้ว"
     *
     * @return Collection<int, Registration>
     */
    public function findByContact(Activity $activity, string $contact, bool $isPhone): Collection
    {
        $query = $activity->registrations()->orderBy('id');

        if ($isPhone) {
            $phone = $this->normalizePhone($contact);
            $formatted = substr($phone, 0, 3).'-'.substr($phone, 3, 3).'-'.substr($phone, 6);

            return $query->whereIn('phone', [$phone, $formatted])->get();
        }

        return $query->where('email', mb_strtolower($contact))->get();
    }

    /**
     * การจองของบัญชี LINE นี้ในกิจกรรมนี้ — ใช้ตอนกลับมาจากหน้ายินยอมของ LINE
     * เพื่อพาไปหน้าจอ "คุณลงทะเบียนแล้ว" โดยไม่ต้องให้กรอกเบอร์ซ้ำ
     *
     * @return Collection<int, Registration>
     */
    public function findByLineUserId(Activity $activity, string $lineUserId): Collection
    {
        $participantIds = Participant::query()
            ->where('line_user_id', $lineUserId)
            ->pluck('id');

        if ($participantIds->isEmpty()) {
            return collect();
        }

        return $activity->registrations()
            ->whereIn('participant_id', $participantIds)
            ->orderBy('id')
            ->get();
    }

    /**
     * ประวัติการลงทะเบียน "กิจกรรมอื่น" ของบัญชี LINE นี้ — ใช้แสดงบนหน้าจอลงทะเบียนแล้ว
     *
     * ยืนยันตัวตนผ่าน LINE มาแล้วจึงแสดงประวัติของเจ้าของบัญชีได้
     * ตัดกิจกรรมที่กำลังดูอยู่ออก เพราะแสดงเป็นรายละเอียดการจองด้านบนแล้ว
     *
     * @return Collection<int, Registration>
     */
    public function historyForLineUser(string $lineUserId, int $exceptActivityId, int $limit = 5): Collection
    {
        $participantIds = Participant::query()
            ->where('line_user_id', $lineUserId)
            ->pluck('id');

        if ($participantIds->isEmpty()) {
            return collect();
        }

        return Registration::query()
            ->whereIn('participant_id', $participantIds)
            ->where('activity_id', '!=', $exceptActivityId)
            /* กิจกรรมที่ถูกลบไปแล้วไม่ต้องโชว์ค้างไว้ให้สับสน */
            ->whereHas('activity')
            ->with('activity:id,code,name,start_date')
            ->latest('registered_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * ข้อมูลติดต่อล่าสุดของบัญชี LINE นี้ — ใช้เติมฟอร์มให้คนที่เคยลงทะเบียนกิจกรรมอื่นมาแล้ว
     *
     * @return array{name: ?string, phone: ?string, email: ?string}
     */
    public function lastContactForLineUser(string $lineUserId): array
    {
        $participant = Participant::query()
            ->where('line_user_id', $lineUserId)
            ->latest('id')
            ->first();

        if (! $participant) {
            return ['name' => null, 'phone' => null, 'email' => null];
        }

        return [
            /* กลุ่มตัวอย่างที่ลงทะเบียนเองผ่าน QR ไม่ได้ให้ชื่อจริงไว้ ระบบใช้รหัสบุคคลเป็นชื่อในระบบแทน
               เอามาเติมช่อง "ชื่อ - นามสกุล" ไม่ได้ เพราะผู้ใช้จะเห็น "P0005" แล้วส่งไปทั้งอย่างนั้น
               ปล่อยว่างให้กรอกชื่อจริงเองดีกว่าได้ชื่อที่ไม่ใช่ชื่อคน */
            'name' => $participant->name === $participant->person_code ? null : $participant->name,
            'phone' => $this->normalizePhone((string) $participant->phone) ?: null,
            'email' => $participant->registrations()->whereNotNull('email')->latest('id')->value('email'),
        ];
    }

    public function registrationForm(Activity $activity): ?Form
    {
        return $activity->forms()
            ->wherePivot('slot', 'registration')
            ->where('type', Form::TYPE_REGISTRATION)
            ->where('status', Form::STATUS_ACTIVE)
            ->first();
    }

    public function maxSeats(Activity $activity): int
    {
        $form = $this->registrationForm($activity);

        if (! $form || $form->registration_mode !== 'group') {
            return 1;
        }

        return max(1, min(5, (int) ($form->max_participants ?: 1)));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{userId: string, displayName: string, pictureUrl: ?string}|null  $lineProfile
     *         โปรไฟล์ LINE ของผู้ลงทะเบียนหลัก — มาจาก session ไม่ใช่ค่าที่ผู้ใช้ส่งมาเอง
     * @return Collection<int, Registration>
     */
    public function register(Activity $activity, array $data, ?array $lineProfile = null): Collection
    {
        return DB::transaction(function () use ($activity, $data, $lineProfile): Collection {
            $lockedActivity = Activity::query()->lockForUpdate()->findOrFail($activity->id);
            $lockedActivity->loadCount('registrations');

            $form = $this->registrationForm($lockedActivity);
            $seatCount = (int) $data['seat_count'];
            $phone = $this->normalizePhone($data['phone']);

            if (! $lockedActivity->is_published
                || $lockedActivity->visibility !== 'สาธารณะ'
                || ! $lockedActivity->acceptsRegistration()
                || ! $form) {
                throw ValidationException::withMessages([
                    'registration' => 'กิจกรรมนี้ยังไม่เปิดรับลงทะเบียนหรือปิดรับสมัครแล้ว',
                ]);
            }

            if ($seatCount > $this->maxSeats($lockedActivity)) {
                throw ValidationException::withMessages([
                    'seat_count' => 'จำนวนที่นั่งเกินกว่าที่กิจกรรมอนุญาตต่อหนึ่งการจอง',
                ]);
            }

            if ($this->alreadyRegistered($lockedActivity, $phone)) {
                throw ValidationException::withMessages([
                    'phone' => 'เบอร์โทรศัพท์นี้ลงทะเบียนกิจกรรมนี้แล้ว',
                ]);
            }

            $round = $this->roundFor($lockedActivity, $data['activity_round_id'] ?? null);
            $this->ensureCapacity($lockedActivity, $round, $seatCount);
            $consentDocument = ConsentDocument::query()
                ->where('consent_type', 'pdpa')
                ->where('is_active', true)
                ->latest('effective_date')
                ->first();

            return collect($data['participants'])->values()->map(function (array $person, int $index) use ($lockedActivity, $round, $phone, $consentDocument, $data, $lineProfile): Registration {
                /* บัญชี LINE ผูกกับผู้ลงทะเบียนหลักเท่านั้น — ผู้ร่วมเพิ่มเป็นคนละคนที่ไม่ได้ล็อกอินเอง */
                $participant = $this->participant($person['name'], $phone, $index === 0 ? $lineProfile : null);

                Consent::create([
                    'participant_id' => $participant->id,
                    'consent_document_id' => $consentDocument?->id,
                    'status' => 'ยินยอม',
                    'consent_version' => $consentDocument?->version,
                    'consented_at' => now(),
                    'recorded_via' => 'registration',
                ]);

                /* ข้อมูลติดต่อและช่องทางที่ทราบข่าวเก็บที่ผู้ลงทะเบียนหลัก (แถวแรก) เท่านั้น
                   ส่วนช่วงอายุ/อาชีพเป็นของรายบุคคล เก็บทุกแถว */
                return Registration::create([
                    'code' => 'REG-'.Str::upper(Str::random(16)),
                    'activity_id' => $lockedActivity->id,
                    'activity_round_id' => $round?->id,
                    'participant_id' => $participant->id,
                    'name' => $person['name'],
                    'phone' => $phone,
                    'email' => $index === 0 ? ($data['email'] ?? null) : null,
                    'age_range_id' => $person['age_range_id'] ?? null,
                    'occupation_id' => $person['occupation_id'] ?? null,
                    'source_channel_id' => $index === 0 ? ($data['source_channel_id'] ?? null) : null,
                    'dietary_note' => $index === 0 ? ($data['note'] ?? null) : null,
                    'registered_at' => now(),
                    'is_manual_entry' => false,
                ]);
            });
        });
    }

    /**
     * แจ้งชำระเงินของการจองชุดเดียวกัน — สลิปแนบกับแถวผู้ลงทะเบียนหลัก
     * และทุกแถวในชุดเปลี่ยนสถานะเป็น "รอตรวจสอบ" ให้เจ้าหน้าที่ตรวจต่อ
     *
     * @param  list<string>  $codes
     * @return Collection<int, Registration>
     */
    public function notifyPayment(Activity $activity, array $codes, ?UploadedFile $slip): Collection
    {
        return DB::transaction(function () use ($activity, $codes, $slip): Collection {
            $registrations = $activity->registrations()
                ->whereIn('code', $codes)
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            if ($registrations->isEmpty()) {
                throw ValidationException::withMessages([
                    'codes' => 'ไม่พบข้อมูลการจอง กรุณาลงทะเบียนใหม่อีกครั้ง',
                ]);
            }

            if ($slip) {
                /* ไฟล์สลิปเก็บนอก public/ ตามกติกาของ act_payment_slips
                   ต้องเสิร์ฟผ่าน route ที่ตรวจสิทธิ์ฝั่ง backoffice เท่านั้น */
                $filePath = $this->storeSlipFile($slip);

                if (! $filePath) {
                    throw ValidationException::withMessages([
                        'slip' => 'ไม่สามารถบันทึกไฟล์สลิปได้ กรุณาตรวจสอบไฟล์แล้วลองใหม่อีกครั้ง',
                    ]);
                }

                PaymentSlip::create([
                    'registration_id' => $registrations->first()->id,
                    'file_path' => $filePath,
                    'amount' => (float) $activity->fee * $registrations->count(),
                    'transferred_at' => now(),
                    'status' => 'รอตรวจสอบ',
                ]);
            }

            $activity->registrations()
                ->whereIn('id', $registrations->pluck('id'))
                ->update(['payment_status' => 'รอตรวจสอบ']);

            /* อัปเดตค่าในหน่วยความจำให้ตรงกับที่เพิ่งเขียน ผู้เรียกจะได้ไม่ต้อง query ซ้ำ */
            $registrations->each(fn (Registration $registration) => $registration->setAttribute('payment_status', 'รอตรวจสอบ'));

            return $registrations;
        });
    }

    /**
     * บันทึกไฟล์สลิปการโอนเงินลงในดิสก์ local อย่างปลอดภัย
     *
     * บน Windows IIS / PHP บางสภาพแวดล้อม getRealPath() อาจคืนค่าว่างหรือ false
     * ส่งผลให้ store() เรียก fopen("") แล้วเกิด ValueError: Path must not be empty
     */
    private function storeSlipFile(UploadedFile $file): ?string
    {
        if (! $file->isValid()) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png');
        $path = 'payment-slips/'.Str::random(40).'.'.$extension;
        $temporaryPath = $file->getRealPath() ?: $file->getPathname();

        if ($temporaryPath && file_exists($temporaryPath)) {
            $contents = @file_get_contents($temporaryPath);
            if ($contents !== false && Storage::disk('local')->put($path, $contents)) {
                return $path;
            }
        }

        try {
            return $file->store('payment-slips', 'local') ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param  array{userId: string, displayName: string, pictureUrl: ?string}|null  $lineProfile
     */
    private function participant(string $name, string $phone, ?array $lineProfile = null): Participant
    {
        $formattedPhone = substr($phone, 0, 3).'-'.substr($phone, 3, 3).'-'.substr($phone, 6);

        /* คนที่เคยล็อกอิน LINE ไว้แล้วให้จับคู่ด้วยบัญชี LINE ก่อน เพราะเป็นตัวระบุที่ไม่เปลี่ยน
           ต่างจากชื่อ+เบอร์ที่พิมพ์ต่างกันนิดเดียวก็กลายเป็นคนใหม่

           แต่ต้องเป็นเบอร์เดียวกับที่ผูกไว้ด้วย ไม่งั้นคนที่ล็อกอิน LINE ค้างไว้แล้ว
           กรอกเบอร์ลงทะเบียนให้ "คนอื่น" จะถูกจับคู่กลับมาเป็นตัวเอง แล้วการจองของคนอื่น
           ไปผูกกับผู้เข้าร่วมผิดคนโดยไม่มีใครสังเกต */
        $participant = null;

        if ($lineProfile) {
            $participant = Participant::query()
                ->where('line_user_id', $lineProfile['userId'])
                ->whereIn('phone', [$phone, $formattedPhone])
                ->lockForUpdate()
                ->first();
        }

        $participant ??= Participant::query()
            ->where('name', $name)
            ->whereIn('phone', [$phone, $formattedPhone])
            ->lockForUpdate()
            ->first();

        if ($participant) {
            $participant->update([
                'consent_status' => 'ยินยอม',
                'phone' => $participant->phone ?: $phone,
            ] + $this->lineColumns($lineProfile, $participant));

            return $participant;
        }

        $code = 'PID-'.Str::upper(Str::random(16));

        return Participant::create([
            'code' => $code,
            'person_code' => $code,
            'name' => $name,
            'phone' => $phone,
            'consent_status' => 'ยินยอม',
        ] + $this->lineColumns($lineProfile, null));
    }

    /**
     * คอลัมน์บัญชี LINE ที่จะเขียนลงผู้เข้าร่วม
     *
     * line_user_id เป็น unique — ถ้าบัญชีนี้ถูกผูกกับผู้เข้าร่วมคนอื่นไปแล้ว
     * ต้องไม่เขียนซ้ำ ไม่งั้นการบันทึกจะล้มทั้งรายการโดยที่ผู้ใช้ไม่เข้าใจว่าทำอะไรผิด
     * (กรณีนี้เกิดได้เมื่อคนหนึ่งใช้ LINE ตัวเองลงทะเบียนแทนคนอื่นด้วยชื่อ-เบอร์คนละคน)
     *
     * @param  array{userId: string, displayName: string, pictureUrl: ?string}|null  $lineProfile
     * @return array<string, mixed>
     */
    private function lineColumns(?array $lineProfile, ?Participant $participant): array
    {
        if (! $lineProfile) {
            return [];
        }

        if ($participant?->line_user_id === $lineProfile['userId']) {
            return [
                'line_display_name' => $lineProfile['displayName'],
                'line_picture_url' => $lineProfile['pictureUrl'],
            ];
        }

        /* withTrashed สำคัญ — unique index ที่ฐานข้อมูลนับแถวที่ soft delete ไปแล้วด้วย
           ถ้าเช็กด้วย scope ปกติจะมองไม่เห็นเจ้าของเดิมที่ถูกลบไป แล้วเขียนทับจนได้ duplicate entry */
        $takenByOther = Participant::withTrashed()
            ->where('line_user_id', $lineProfile['userId'])
            ->when($participant, fn ($query) => $query->whereKeyNot($participant->id))
            ->exists();

        if ($takenByOther || ($participant && $participant->line_user_id)) {
            return [];
        }

        return [
            'line_user_id' => $lineProfile['userId'],
            'line_display_name' => $lineProfile['displayName'],
            'line_picture_url' => $lineProfile['pictureUrl'],
        ];
    }

    private function roundFor(Activity $activity, mixed $roundId): ?ActivityRound
    {
        $rounds = $activity->rounds()->lockForUpdate()->get();

        if ($rounds->isEmpty()) {
            return null;
        }

        if ($rounds->count() === 1 && ! $roundId) {
            return $rounds->first();
        }

        $round = $rounds->firstWhere('id', (int) $roundId);

        if (! $round) {
            throw ValidationException::withMessages([
                'activity_round_id' => 'กรุณาเลือกรอบกิจกรรมที่ต้องการสมัคร',
            ]);
        }

        return $round;
    }

    private function ensureCapacity(Activity $activity, ?ActivityRound $round, int $seatCount): void
    {
        $activityRegistered = $activity->registrations()->count();

        if ($activity->capacity > 0 && $activityRegistered + $seatCount > $activity->capacity) {
            throw ValidationException::withMessages([
                'seat_count' => 'จำนวนที่นั่งคงเหลือไม่เพียงพอ กรุณาลดจำนวนที่นั่ง',
            ]);
        }

        if ($round && $round->capacity > 0 && $round->registrations()->count() + $seatCount > $round->capacity) {
            throw ValidationException::withMessages([
                'activity_round_id' => 'รอบกิจกรรมที่เลือกมีที่นั่งคงเหลือไม่เพียงพอ',
            ]);
        }
    }
}
