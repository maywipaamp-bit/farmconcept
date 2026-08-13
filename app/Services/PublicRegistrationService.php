<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityRound;
use App\Models\Consent;
use App\Models\ConsentDocument;
use App\Models\Form;
use App\Models\Participant;
use App\Models\Registration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
     * @return Collection<int, Registration>
     */
    public function register(Activity $activity, array $data): Collection
    {
        return DB::transaction(function () use ($activity, $data): Collection {
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

            return collect($data['names'])->map(function (string $name) use ($lockedActivity, $round, $phone, $consentDocument): Registration {
                $participant = $this->participant($name, $phone);

                Consent::create([
                    'participant_id' => $participant->id,
                    'consent_document_id' => $consentDocument?->id,
                    'status' => 'ยินยอม',
                    'consent_version' => $consentDocument?->version,
                    'consented_at' => now(),
                    'recorded_via' => 'registration',
                ]);

                return Registration::create([
                    'code' => 'REG-'.Str::upper(Str::random(16)),
                    'activity_id' => $lockedActivity->id,
                    'activity_round_id' => $round?->id,
                    'participant_id' => $participant->id,
                    'name' => $name,
                    'phone' => $phone,
                    'registered_at' => now(),
                    'is_manual_entry' => false,
                ]);
            });
        });
    }

    private function participant(string $name, string $phone): Participant
    {
        $formattedPhone = substr($phone, 0, 3).'-'.substr($phone, 3, 3).'-'.substr($phone, 6);
        $participant = Participant::query()
            ->where('name', $name)
            ->whereIn('phone', [$phone, $formattedPhone])
            ->lockForUpdate()
            ->first();

        if ($participant) {
            $participant->update(['consent_status' => 'ยินยอม']);

            return $participant;
        }

        $code = 'PID-'.Str::upper(Str::random(16));

        return Participant::create([
            'code' => $code,
            'person_code' => $code,
            'name' => $name,
            'phone' => $phone,
            'consent_status' => 'ยินยอม',
        ]);
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
