<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\CheckinLog;
use App\Models\Registration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicCheckinService
{
    public function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?: '';
    }

    /**
     * ค้นรายชื่อจากเบอร์โทรศัพท์หรืออีเมล — ช่องเดียวรับได้ทั้งสองแบบ
     *
     * @return Collection<int, Registration>
     */
    public function registrationsFor(Activity $activity, string $contact): Collection
    {
        $this->ensureAvailable($activity);

        $registrations = $activity->registrations()
            ->where($this->matchContact($contact))
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'phone', 'email', 'checkin_status', 'checked_in_at']);

        if ($registrations->isEmpty()) {
            throw ValidationException::withMessages([
                'contact' => 'ไม่พบรายชื่อที่ลงทะเบียนด้วยเบอร์โทรศัพท์หรืออีเมลนี้',
            ]);
        }

        return $registrations;
    }

    public function checkIn(Activity $activity, string $contact, string $registrationCode): Registration
    {
        return DB::transaction(function () use ($activity, $contact, $registrationCode): Registration {
            $lockedActivity = Activity::query()->lockForUpdate()->findOrFail($activity->id);
            $this->ensureAvailable($lockedActivity);

            $registration = Registration::query()
                ->where('activity_id', $lockedActivity->id)
                ->where('code', $registrationCode)
                ->where($this->matchContact($contact))
                ->lockForUpdate()
                ->first();

            if (! $registration) {
                throw ValidationException::withMessages([
                    'registration_code' => 'ไม่พบรายชื่อที่เลือกสำหรับเบอร์โทรศัพท์หรืออีเมลนี้',
                ]);
            }

            if ($registration->checked_in_at) {
                throw ValidationException::withMessages([
                    'registration_code' => $registration->name.' เช็กอินแล้ว',
                ]);
            }

            $checkedInAt = now();
            $registration->update([
                'checkin_status' => 'เข้าร่วมแล้ว',
                'checked_in_at' => $checkedInAt,
            ]);

            CheckinLog::create([
                'registration_id' => $registration->id,
                'action' => 'check_in',
                'method' => 'scan',
                'performed_at' => $checkedInAt,
            ]);

            return $registration->fresh();
        });
    }

    private function ensureAvailable(Activity $activity): void
    {
        if ($activity->visibility !== 'สาธารณะ' || ! $activity->acceptsCheckin()) {
            throw ValidationException::withMessages([
                'checkin' => 'กิจกรรมนี้ยังไม่เปิดเช็กอิน หรือสิ้นสุดช่วงเช็กอินแล้ว',
            ]);
        }
    }

    /**
     * เงื่อนไขจับคู่ผู้ลงทะเบียนจากค่าที่ผู้ใช้พิมพ์มาช่องเดียว
     *
     * มี @ = อีเมล (เทียบแบบไม่สนตัวพิมพ์เล็กใหญ่) นอกนั้นถือเป็นเบอร์โทรศัพท์
     * คืนเป็น closure เพื่อให้ทุกที่ที่ค้นใช้เกณฑ์เดียวกัน — ค้นเจอแล้วแต่เช็กอินไม่ผ่าน
     * เพราะเกณฑ์ต่างกันคืออาการที่หาสาเหตุยากที่สุดของ flow นี้
     */
    private function matchContact(string $contact): \Closure
    {
        $contact = trim($contact);

        if (str_contains($contact, '@')) {
            return fn ($query) => $query->whereRaw('LOWER(email) = ?', [mb_strtolower($contact)]);
        }

        return fn ($query) => $query->whereIn('phone', $this->phoneVariants($contact));
    }

    /** เบอร์ถูกเก็บมาทั้งแบบมีขีดและไม่มี ขึ้นกับว่าลงทะเบียนผ่านทางไหน จึงต้องค้นทั้งสองแบบ */
    private function phoneVariants(string $phone): array
    {
        $phone = $this->normalizePhone($phone);

        return [
            $phone,
            substr($phone, 0, 3).'-'.substr($phone, 3, 3).'-'.substr($phone, 6),
        ];
    }
}
