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

    /** @return Collection<int, Registration> */
    public function registrationsForPhone(Activity $activity, string $phone): Collection
    {
        $this->ensureAvailable($activity);

        $registrations = $activity->registrations()
            ->whereIn('phone', $this->phoneVariants($phone))
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'phone', 'checkin_status', 'checked_in_at']);

        if ($registrations->isEmpty()) {
            throw ValidationException::withMessages([
                'phone' => 'ไม่พบรายชื่อที่ลงทะเบียนด้วยเบอร์โทรศัพท์นี้',
            ]);
        }

        return $registrations;
    }

    public function checkIn(Activity $activity, string $phone, string $registrationCode): Registration
    {
        return DB::transaction(function () use ($activity, $phone, $registrationCode): Registration {
            $lockedActivity = Activity::query()->lockForUpdate()->findOrFail($activity->id);
            $this->ensureAvailable($lockedActivity);

            $registration = Registration::query()
                ->where('activity_id', $lockedActivity->id)
                ->where('code', $registrationCode)
                ->whereIn('phone', $this->phoneVariants($phone))
                ->lockForUpdate()
                ->first();

            if (! $registration) {
                throw ValidationException::withMessages([
                    'registration_code' => 'ไม่พบรายชื่อที่เลือกสำหรับเบอร์โทรศัพท์นี้',
                ]);
            }

            if ($registration->checked_in_at) {
                throw ValidationException::withMessages([
                    'registration_code' => $registration->name.' Check-in แล้ว',
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
                'checkin' => 'กิจกรรมนี้ยังไม่เปิด Check-in หรือสิ้นสุดช่วง Check-in แล้ว',
            ]);
        }
    }

    /** @return array<int, string> */
    private function phoneVariants(string $phone): array
    {
        $phone = $this->normalizePhone($phone);

        return [
            $phone,
            substr($phone, 0, 3).'-'.substr($phone, 3, 3).'-'.substr($phone, 6),
        ];
    }
}
