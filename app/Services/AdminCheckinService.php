<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityRound;
use App\Models\CheckinLog;
use App\Models\Option;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * เช็คอินหน้างานฝั่งเจ้าหน้าที่ — คู่ฝั่งเซิร์ฟเวอร์ของ assets/js/checkin-service.js
 *
 * กฎสามข้อที่ไฟล์นี้รับผิดชอบ (ยกมาจากหัวไฟล์ของฝั่งหน้าจอ)
 * 1. ข้อมูลแยกตามกิจกรรม — ทุกเมธอดรับ Activity เป็นพารามิเตอร์แรกเสมอ
 * 2. เวลาเช็คอินมาจากนาฬิกาเซิร์ฟเวอร์เท่านั้น (now()) ห้ามรับเวลาจากเครื่องหน้างาน
 *    เพราะนาฬิกาเครื่องหน้างานตั้งเองได้ ลำดับการเช็คอินจะเพี้ยนทันที
 * 3. ทั้งเช็คอินและยกเลิกต้องเขียน act_checkin_logs ในทรานแซกชันเดียวกับการแก้สถานะ
 *    ถ้าเขียน log ไม่สำเร็จ ต้องไม่มีการเปลี่ยนสถานะหลงเหลือ
 */
class AdminCheckinService
{
    /**
     * รอบ + รายชื่อทั้งหมดของกิจกรรม ในรูปที่หน้าจอเช็คอินใช้ได้ตรง ๆ
     *
     * @return array<string, mixed>
     */
    public function snapshot(Activity $activity): array
    {
        $rounds = $this->rounds($activity);

        $registrations = $activity->registrations()
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'phone', 'activity_round_id', 'age_range_id', 'is_manual_entry', 'checked_in_at']);

        $ageLabels = $this->ageLabels($registrations);

        return [
            'activityId' => $activity->code,
            'rounds' => array_values($rounds),
            'people' => $registrations
                ->map(fn (Registration $r) => $this->toPerson($r, $rounds, $ageLabels))
                ->all(),
        ];
    }

    /**
     * เช็คอินผู้ลงทะเบียนหนึ่งคน
     *
     * กดซ้ำหรือคนที่สแกนเองไปแล้วให้คืนค่าเดิม ไม่ถือเป็นข้อผิดพลาด —
     * ที่หน้างานสองเครื่องกดพร้อมกันได้ตลอด ถ้าเด้ง error เจ้าหน้าที่จะนึกว่าเช็คอินไม่ติด
     *
     * @return array<string, mixed>
     */
    public function checkIn(Activity $activity, string $registrationCode, string $source, User $actor): array
    {
        return DB::transaction(function () use ($activity, $registrationCode, $source, $actor): array {
            $registration = $this->lockRegistration($activity, $registrationCode);

            if (! $registration->checked_in_at) {
                $checkedInAt = now();

                $registration->update([
                    'checkin_status' => 'เข้าร่วมแล้ว',
                    'checked_in_at' => $checkedInAt,
                ]);

                CheckinLog::create([
                    'registration_id' => $registration->id,
                    'action' => 'check_in',
                    'method' => $source,
                    'performed_by' => $actor->id,
                    'performed_at' => $checkedInAt,
                ]);
            }

            $rounds = $this->rounds($activity);

            return $this->toPerson($registration->refresh(), $rounds, $this->ageLabels(collect([$registration])));
        });
    }

    /**
     * ยกเลิกการเช็คอิน
     *
     * คืน entry ของ audit log กลับไปให้หน้าจอ เพื่อยืนยันว่า "บันทึกประวัติสำเร็จแล้ว"
     * ไม่ใช่แค่ล้างเวลาเช็คอินเฉย ๆ
     *
     * หมายเหตุ: act_checkin_logs ไม่มีคอลัมน์เหตุผล จึงส่งกลับให้หน้าจอแสดงเท่านั้น ไม่ได้เก็บลงฐาน
     *
     * @return array<string, mixed>
     */
    public function undoCheckIn(Activity $activity, string $registrationCode, string $reason, User $actor): array
    {
        return DB::transaction(function () use ($activity, $registrationCode, $reason, $actor): array {
            $registration = $this->lockRegistration($activity, $registrationCode);

            if (! $registration->checked_in_at) {
                throw ValidationException::withMessages([
                    'registration' => $registration->name.' ยังไม่ได้เช็คอิน จึงยกเลิกไม่ได้',
                ]);
            }

            $previous = $registration->checked_in_at;
            $performedAt = now();

            $registration->update([
                'checkin_status' => 'ยังไม่เข้าร่วม',
                'checked_in_at' => null,
            ]);

            $log = CheckinLog::create([
                'registration_id' => $registration->id,
                'action' => 'undo',
                'method' => 'staff',
                'performed_by' => $actor->id,
                'performed_at' => $performedAt,
            ]);

            return [
                'registrationId' => $registration->code,
                'audit' => [
                    'id' => $log->id,
                    'action' => 'checkin.undo',
                    'activityId' => $activity->code,
                    'registrationId' => $registration->code,
                    'registrationName' => $registration->name,
                    /* previousCheckedInAt = เวลาที่เคยเช็คอิน · at = เวลาที่กดยกเลิก คนละค่ากัน */
                    'previousCheckedInAt' => $previous->format('H:i'),
                    'actorUsername' => $actor->username ?? '-',
                    'actorName' => $actor->name,
                    'at' => $performedAt->toIso8601String(),
                    'reason' => $reason,
                ],
            ];
        });
    }

    /**
     * เพิ่มผู้เข้าร่วมหน้างานแล้วเช็คอินให้ทันทีในคำสั่งเดียว
     *
     * คนที่ยืนอยู่หน้างานถือว่ามาถึงแล้ว "เดินมาหน้างานแต่ยังไม่มา" เป็นสถานะที่ขัดกันเอง
     * ไม่ตรวจที่นั่งเต็ม — เจ้าหน้าที่ที่หน้างานเห็นสถานการณ์จริงมากกว่าตัวเลขในระบบ
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function addWalkIn(Activity $activity, array $data, User $actor): array
    {
        return DB::transaction(function () use ($activity, $data, $actor): array {
            $rounds = $this->rounds($activity);
            $roundId = $this->roundIdFor($rounds, $data['roundKey'] ?? '');
            $phone = $this->normalizePhone($data['phone']);
            $checkedInAt = now();

            $ageRange = $data['ageRange'] ?? null;
            $ageRangeId = $ageRange
                ? Option::query()->group('age_range')->where('label', $ageRange)->value('id')
                : null;

            $registration = Registration::create([
                'code' => 'WLK-'.Str::upper(Str::random(16)),
                'activity_id' => $activity->id,
                'activity_round_id' => $roundId,
                /* จับคู่กับผู้เข้าร่วมเดิมด้วยเบอร์โทรถ้ามี — ไม่มีก็ปล่อยว่างไว้ได้ตามปม E
                   หน้างานไม่ใช่จังหวะที่จะสร้างโปรไฟล์ใหม่พร้อมความยินยอมให้ครบถ้วน */
                'participant_id' => $this->participantIdForPhone($phone),
                'name' => $data['name'],
                'phone' => $phone,
                'age_range_id' => $ageRangeId,
                'checkin_status' => 'เข้าร่วมแล้ว',
                'registered_at' => $checkedInAt,
                'checked_in_at' => $checkedInAt,
                'is_manual_entry' => true,
            ]);

            CheckinLog::create([
                'registration_id' => $registration->id,
                'action' => 'check_in',
                'method' => 'staff',
                'performed_by' => $actor->id,
                'performed_at' => $checkedInAt,
            ]);

            return $this->toPerson($registration, $rounds, $ageRangeId ? [$ageRangeId => $ageRange] : []);
        });
    }

    /**
     * ประวัติการเช็คอิน/ยกเลิกของกิจกรรม — ใหม่สุดอยู่บน
     *
     * @return array<int, array<string, mixed>>
     */
    public function auditLog(Activity $activity): array
    {
        return CheckinLog::query()
            ->whereIn('registration_id', $activity->registrations()->select('id'))
            ->with(['registration:id,code,name', 'performedBy:id,name,username'])
            ->orderByDesc('performed_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (CheckinLog $log) => [
                'id' => $log->id,
                'action' => $log->action === 'undo' ? 'checkin.undo' : 'checkin.create',
                'activityId' => $activity->code,
                'registrationId' => $log->registration?->code,
                'registrationName' => $log->registration?->name,
                'method' => $log->method,
                'actorName' => $log->performedBy?->name ?? '-',
                'actorUsername' => $log->performedBy?->username ?? '-',
                'at' => $log->performed_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * รอบของกิจกรรม เรียงตามวัน — คีย์เป็นรหัสรอบในฐานข้อมูล
     *
     * กิจกรรมที่ไม่ได้แบ่งรอบยังต้องมีหนึ่งรายการให้ตัวเลือกรอบของ Walk-in ไม่ว่างเปล่า
     * ใช้คีย์ว่างซึ่งจะถูกบันทึกเป็น activity_round_id = NULL
     *
     * @return array<string, array<string, mixed>>
     */
    private function rounds(Activity $activity): array
    {
        $rounds = $activity->rounds()->get(['id', 'round_date', 'time_start', 'time_end']);

        if ($rounds->isEmpty()) {
            return ['' => [
                'key' => '',
                'label' => 'ไม่ระบุรอบ',
                'date' => $activity->start_date?->toDateString(),
                'time' => '',
            ]];
        }

        $many = $rounds->count() > 1;

        return $rounds->mapWithKeys(fn (ActivityRound $round, int $i) => [
            (string) $round->id => [
                'key' => (string) $round->id,
                'label' => $many ? 'รอบ '.($i + 1) : 'รอบเดียว',
                'date' => $round->round_date?->toDateString(),
                'time' => $round->time_start ? substr((string) $round->time_start, 0, 5) : '',
            ],
        ])->all();
    }

    /**
     * @param  array<string, array<string, mixed>>  $rounds
     */
    private function roundIdFor(array $rounds, string $roundKey): ?int
    {
        return isset($rounds[$roundKey]) && $roundKey !== '' ? (int) $roundKey : null;
    }

    /**
     * แปลงหนึ่งแถวลงทะเบียนเป็นรูปที่หน้าจอเช็คอินใช้
     *
     * checkedInAt เป็น HH:MM สำหรับแสดงผล ส่วนการเรียงลำดับใช้ checkedInAtIso
     * เพราะกิจกรรมที่กินหลายวันจะเรียงด้วย HH:MM ไม่ได้
     *
     * @param  array<string, array<string, mixed>>  $rounds
     * @param  array<int, string>  $ageLabels
     * @return array<string, mixed>
     */
    private function toPerson(Registration $registration, array $rounds, array $ageLabels): array
    {
        /* ผู้ลงทะเบียนที่ไม่ได้ผูกรอบไว้ต้องขึ้นว่า "ไม่ระบุรอบ" ไม่ใช่ถูกจับยัดเข้ารอบแรก
           ไม่งั้นตัวเลขรายรอบที่เจ้าหน้าที่เห็นจะไม่ตรงกับที่คนมาจริง */
        $round = $rounds[(string) $registration->activity_round_id]
            ?? ['key' => '', 'label' => 'ไม่ระบุรอบ'];

        return [
            'id' => $registration->code,
            'code' => $registration->code,
            'name' => $registration->name,
            'phone' => $registration->phone ?? '',
            'ageRange' => $ageLabels[$registration->age_range_id] ?? '',
            'roundKey' => $round['key'] ?? '',
            'round' => $round['label'] ?? '',
            'walkIn' => (bool) $registration->is_manual_entry,
            'checkedInAt' => $registration->checked_in_at?->format('H:i') ?? '',
            'checkedInAtIso' => $registration->checked_in_at?->toIso8601String() ?? '',
            /* scan = ผู้เข้าร่วมสแกน QR เอง · staff = เจ้าหน้าที่กดให้ที่หน้าจอนี้ */
            'source' => $registration->is_manual_entry ? 'staff' : 'scan',
        ];
    }

    /**
     * ป้ายช่วงอายุของทั้งชุด — ดึงครั้งเดียวกันไม่ให้เกิด N+1 ตอนวาดรายชื่อเป็นร้อยแถว
     *
     * @param  Collection<int, Registration>|\Illuminate\Support\Collection<int, Registration>  $registrations
     * @return array<int, string>
     */
    private function ageLabels($registrations): array
    {
        $ids = $registrations->pluck('age_range_id')->filter()->unique();

        return $ids->isEmpty()
            ? []
            : Option::query()->whereIn('id', $ids)->pluck('label', 'id')->all();
    }

    /** ล็อกแถวก่อนแก้เสมอ — สองเครื่องที่หน้างานกดคนเดียวกันพร้อมกันได้ตลอด */
    private function lockRegistration(Activity $activity, string $registrationCode): Registration
    {
        $registration = Registration::query()
            ->where('activity_id', $activity->id)
            ->where('code', $registrationCode)
            ->lockForUpdate()
            ->first();

        if (! $registration) {
            throw ValidationException::withMessages([
                'registration' => 'ไม่พบผู้ลงทะเบียนรายนี้ในกิจกรรม',
            ]);
        }

        return $registration;
    }

    private function participantIdForPhone(string $phone): ?int
    {
        $formatted = strlen($phone) >= 9
            ? substr($phone, 0, 3).'-'.substr($phone, 3, 3).'-'.substr($phone, 6)
            : $phone;

        return Participant::query()->whereIn('phone', [$phone, $formatted])->value('id');
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?: $phone;
    }
}
