<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivityService
{
    /**
     * บันทึกการแก้ไขกิจกรรม
     *
     * ทุกอย่างอยู่ใน transaction เดียว เพราะข้อมูลกิจกรรมกระจายอยู่ 6 ตาราง
     * ถ้าสำเร็จบางส่วนจะได้กิจกรรมที่ข้อมูลไม่ตรงกันเอง เช่น เปลี่ยนเป็นเผยแพร่แล้ว
     * แต่ QR ยังไม่ถูกเปิดใช้งาน
     *
     * @param  array<string, mixed>  $data  ข้อมูลที่ผ่าน ActivityRequest มาแล้ว
     */
    public function update(Activity $activity, array $data, User $actor): Activity
    {
        return DB::transaction(function () use ($activity, $data, $actor): Activity {
            $activity->fill($this->columns($data));
            $changed = array_keys($activity->getDirty());
            $activity->updated_by = $actor->id;
            $activity->save();

            $activity->areas()->sync($data['area_ids'] ?? []);
            $activity->instructors()->sync($data['instructor_ids'] ?? []);
            $activity->targetGroups()->sync($data['target_group_ids'] ?? []);

            $this->syncRounds($activity, $data['rounds'] ?? []);
            $this->syncQrCodes($activity);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'activity.updated',
                'subject_type' => 'activity',
                'subject_id' => $activity->id,
                'detail' => 'แก้ไขกิจกรรม ' . $activity->code
                    . ($changed === [] ? ' (ไม่มีคอลัมน์หลักเปลี่ยน)' : ' · ฟิลด์ที่เปลี่ยน: ' . implode(', ', $changed)),
            ]);

            return $activity;
        });
    }

    /**
     * คอลัมน์ที่เขียนลงตารางกิจกรรมโดยตรง
     *
     * ระบุรายชื่อไว้ชัดแทนการส่ง $data ทั้งก้อนเข้า fill()
     * เพื่อไม่ให้ฟิลด์ที่ผู้ใช้ไม่ควรแก้ (code, created_by, deleted_at) หลุดเข้ามาได้
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function columns(array $data): array
    {
        $keys = [
            'name', 'description', 'type', 'participant_type', 'parent_event_id',
            'program_id', 'course_id', 'format_id',
            'data_source', 'venue_mode', 'status',
            'requires_registration', 'requires_checkin', 'has_post_survey',
            'has_fee', 'capacity', 'organizer',
            'start_date', 'end_date', 'checkin_start_at', 'checkin_end_at',
            'survey_start_at', 'survey_end_at',
            'is_published', 'publish_start_at', 'publish_end_at',
            'registration_start_at', 'registration_end_at',
            'visibility', 'is_featured', 'public_sort_order',
        ];

        $columns = array_intersect_key($data, array_flip($keys));

        /* ไม่มีค่าเข้าร่วมแล้วต้องล้างจำนวนเงินด้วย ไม่งั้นจะเหลือเลขค้างไว้
           แล้วพอเปิดกลับมาจะเก็บเงินโดยที่แอดมินไม่ได้ตั้งใจ */
        $columns['fee'] = ($data['has_fee'] ?? false) ? ($data['fee'] ?? 0) : 0;

        return $columns;
    }

    /**
     * รอบกิจกรรม — ลบรอบที่ถูกเอาออกแล้วเขียนชุดใหม่ทับ
     *
     * รอบที่มีผู้ลงทะเบียนอยู่แล้วจะลบไม่ได้ (FK ตั้งเป็น SET NULL ที่ act_registrations)
     * ผู้ลงทะเบียนจึงไม่หาย แต่จะไม่ผูกกับรอบใด — ต้องให้แอดมินจับคู่ใหม่
     *
     * @param  array<int, array<string, mixed>>  $rounds
     */
    private function syncRounds(Activity $activity, array $rounds): void
    {
        $keep = [];

        foreach ($rounds as $round) {
            $row = $activity->rounds()->updateOrCreate(
                ['round_date' => $round['round_date']],
                [
                    'time_start' => $round['time_start'],
                    'time_end' => $round['time_end'],
                    'location' => $round['location'] ?? null,
                    'capacity' => $round['capacity'] ?? 0,
                ]
            );

            $keep[] = $row->id;
        }

        $activity->rounds()->whereNotIn('id', $keep ?: [0])->delete();
    }

    /**
     * QR ตามสวิตช์ที่เปิดไว้
     *
     * `public` มีทุกกิจกรรมเสมอ · `checkin` / `post_survey` ตามสวิตช์
     * ปิดสวิตช์แล้ว **ไม่ลบแถว** แค่ปิดใช้งาน เพราะ QR ที่พิมพ์แจกไปแล้วเรียกคืนไม่ได้
     * ต้องสแกนแล้วเจอหน้าอธิบาย ไม่ใช่ 404
     */
    private function syncQrCodes(Activity $activity): void
    {
        $wanted = ['public' => true];

        if ($activity->requires_checkin) {
            $wanted['checkin'] = true;
        }

        if ($activity->has_post_survey) {
            $wanted['post_survey'] = true;
        }

        foreach (['public', 'checkin', 'post_survey'] as $purpose) {
            $enabled = isset($wanted[$purpose]) && $activity->is_published;

            $qr = $activity->qrCodes()->firstOrNew(['purpose' => $purpose]);

            if (! $qr->exists) {
                /* ไม่มี QR อยู่เดิมและสวิตช์ก็ปิด — ไม่ต้องสร้างแถวเปล่าไว้ */
                if (! isset($wanted[$purpose])) {
                    continue;
                }

                $qr->token = Str::lower(Str::random(24));
                $qr->target_url = '/' . ['public' => 'r', 'checkin' => 'c', 'post_survey' => 's'][$purpose] . '/' . $qr->token;
            }

            $qr->is_active = $enabled;
            $qr->save();
        }
    }

    /**
     * สร้างกิจกรรมใหม่
     *
     * เดินตามเส้นทางเดียวกับ update() ทุกขั้น ต่างกันแค่ต้องออกรหัสและบันทึกผู้สร้าง
     * QR ถูกสร้างตั้งแต่ตอนนี้เลย (syncQrCodes) เพราะฟอร์มมีสวิตช์ลงทะเบียน/เช็คอิน
     * อยู่แล้ว ถ้ารอให้แก้ไขอีกรอบจะได้กิจกรรมที่เปิดลงทะเบียนแต่ไม่มี QR ให้สแกน
     *
     * @param  array<string, mixed>  $data  ข้อมูลที่ผ่าน ActivityRequest มาแล้ว
     */
    public function create(array $data, User $actor): Activity
    {
        return DB::transaction(function () use ($data, $actor): Activity {
            $activity = new Activity($this->columns($data));
            $activity->code = $this->nextCode();
            $activity->created_by = $actor->id;
            $activity->updated_by = $actor->id;
            $activity->save();

            $activity->areas()->sync($data['area_ids'] ?? []);
            $activity->instructors()->sync($data['instructor_ids'] ?? []);
            $activity->targetGroups()->sync($data['target_group_ids'] ?? []);

            $this->syncRounds($activity, $data['rounds'] ?? []);
            $this->syncQrCodes($activity);

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'activity.created',
                'subject_type' => 'activity',
                'subject_id' => $activity->id,
                'detail' => 'สร้างกิจกรรม ' . $activity->code . ' — ' . $activity->name,
            ]);

            return $activity;
        });
    }

    /**
     * รหัสกิจกรรมถัดไปของปีปัจจุบัน — ACT-2026-001
     *
     * เรียกได้เฉพาะภายใน transaction ของ create() เท่านั้น
     * lockForUpdate กันสองคนกดบันทึกพร้อมกันแล้วได้รหัสซ้ำ ซึ่ง unique index
     * จะปฏิเสธคนหลังทิ้งไปเฉย ๆ โดยที่ผู้ใช้ไม่เข้าใจว่าทำอะไรผิด
     *
     * นับจากรหัสสูงสุด ไม่ใช่จำนวนแถว เพราะกิจกรรมที่ถูกลบไปแล้วยังกินเลขเดิมอยู่
     * (soft delete) นับจำนวนแถวจะออกเลขซ้ำกับของที่ลบไป
     */
    private function nextCode(): string
    {
        $prefix = 'ACT-' . now()->year . '-';

        $last = Activity::withTrashed()
            ->where('code', 'like', $prefix . '%')
            ->lockForUpdate()
            ->max('code');

        $running = $last ? (int) Str::afterLast($last, '-') : 0;

        return $prefix . str_pad((string) ($running + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * ลบกิจกรรม (soft delete) พร้อมบันทึก audit log
     *
     * ใช้ transaction เพราะสองขั้นตอนต้องสำเร็จพร้อมกัน — ถ้าลบได้แต่บันทึก log ไม่ได้
     * จะเหลือกิจกรรมที่หายไปโดยไม่มีใครรู้ว่าใครลบและลบเมื่อไหร่
     *
     * ใช้ soft delete ไม่ใช่ลบจริง เพราะยังต้องกู้คืนได้ถ้าลบผิด
     * และตารางลูก (รอบกิจกรรม ผู้ลงทะเบียน QR) ตั้ง FK เป็น cascade ไว้
     * ถ้าลบจริงข้อมูลลูกจะหายตามไปทั้งหมดโดยกู้ไม่ได้
     */
    public function delete(Activity $activity, User $actor): void
    {
        DB::transaction(function () use ($activity, $actor): void {
            $activity->delete();

            ActivityLog::create([
                'user_id' => $actor->id,
                'action' => 'activity.deleted',
                'subject_type' => 'activity',
                'subject_id' => $activity->id,
                'detail' => 'ลบกิจกรรม ' . $activity->code . ' — ' . $activity->name,
            ]);
        });
    }
}
