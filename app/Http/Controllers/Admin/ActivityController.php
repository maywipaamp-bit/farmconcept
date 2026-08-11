<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Contracts\View\View;

class ActivityController extends Controller
{
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
            ->with(['rounds:id,activity_id,round_date,time_start,time_end,location,capacity'])
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.activities.list', [
            'activities' => $activities->map(fn (Activity $a) => $this->toListRow($a)),
            'sessions' => $activities->mapWithKeys(fn (Activity $a) => [$a->code => $this->toSessions($a)]),
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
    private function toListRow(Activity $activity): array
    {
        return [
            'id' => $activity->code,
            'name' => $activity->name,
            'type' => $activity->type,
            'status' => $activity->status,
            'capacity' => $activity->capacity,
            'registered' => $activity->registrations_count,
            'startDate' => $activity->start_date?->toDateString(),
            'endDate' => $activity->end_date?->toDateString(),
            'hasFee' => $activity->has_fee,
            'fee' => (float) $activity->fee,
            'areaList' => $activity->areas->pluck('name')->all(),
            'instructorList' => $activity->instructors->pluck('name')->all(),
            'program' => $activity->program?->name,
            'format' => $activity->format?->name,
            'updatedAt' => $activity->updated_at?->toIso8601String(),
        ];
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
            'time' => substr((string) $round->time_start, 0, 5) . ' - ' . substr((string) $round->time_end, 0, 5),
            'location' => $round->location,
            'capacity' => $round->capacity,
        ])->all();
    }
}
