<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\ActivitySatisfactionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * ประเมินกิจกรรม (admin/activities/responses)
 *
 * หน้าเดิมอ่านจาก mock-data.js ผ่าน assets/js/satisfaction-service.js ทั้งหน้า
 * ที่นี่เปลี่ยนแหล่งข้อมูลเป็น MySQL โดยหน้าตาและตัวกรองยังเป็นชุดเดิมทุกตัว
 * แล้วเพิ่มแผงสรุปผลด้านบน ซึ่งคำนวณจากคำตอบชุดเดียวกับตาราง ไม่ใช่ค่าที่เก็บแยกไว้
 *
 * เลือกกิจกรรมด้วย ?id=<code> ไม่ใช่ id ตัวเลข แบบเดียวกับหน้าอื่นในโมดูลนี้
 * ตรรกะการอ่านทั้งหมดอยู่ใน ActivitySatisfactionService — ที่นี่แค่แปลงเป็น payload ของหน้าจอ
 */
class ActivityResponseController extends Controller
{
    private const PAGE_SIZES = [10, 20, 50];

    public function __construct(private readonly ActivitySatisfactionService $service) {}

    public function index(Request $request): View
    {
        $activities = $this->service->selectableActivities();
        $activity = $this->pick($activities, $request->query('id'));

        return view('admin.activities.responses', [
            'activityOptions' => $this->options($activities),
            'activity' => $activity ? $this->activityPayload($activity) : null,
            'summary' => $activity ? $this->service->summary($activity) : null,
            'initial' => $activity
                ? $this->service->responseList($activity, ['limit' => self::PAGE_SIZES[0]])
                : ['total' => 0, 'rows' => []],
            'pageSizes' => self::PAGE_SIZES,
        ]);
    }

    /** ตารางรายคำตอบของกิจกรรมหนึ่ง — แบ่งหน้าที่ฐานข้อมูล ไม่ใช่ตัดที่เบราว์เซอร์ */
    public function data(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'id' => ['required', 'string'],
            'band' => ['nullable', Rule::in(['all', 'praise', 'mid', 'improve'])],
            'keyword' => ['nullable', 'string', 'max:120'],
            /* ไม่ส่ง limit = ขอครบทุกแถวที่ตรงเงื่อนไข ใช้ตอนส่งออก Excel เท่านั้น */
            'limit' => ['nullable', 'integer', Rule::in(self::PAGE_SIZES)],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $activity = $this->find($filters['id']);

        return response()->json($this->service->responseList($activity, $filters));
    }

    /** การ์ดสรุป การกระจายดาว และคะแนนรายข้อ — เรียกตอนเปลี่ยนกิจกรรมเท่านั้น */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate(['id' => ['required', 'string']]);
        $activity = $this->find($validated['id']);

        return response()->json([
            'activity' => $this->activityPayload($activity),
            'summary' => $this->service->summary($activity),
        ]);
    }

    private function find(string $code): Activity
    {
        return Activity::query()->where('code', $code)->firstOrFail();
    }

    /**
     * กิจกรรมที่เปิดอยู่ — ตัวที่ระบุมาใน ?id= ถ้าไม่มีให้ใช้ตัวแรกของรายการ
     * รายการเรียงกิจกรรมที่มีคำตอบไว้ก่อนแล้ว เปิดหน้ามาจึงไม่เจอตารางว่างโดยไม่จำเป็น
     *
     * @param  Collection<int, Activity>  $activities
     */
    private function pick(Collection $activities, ?string $code): ?Activity
    {
        return $activities->firstWhere('code', $code) ?? $activities->first();
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array<int, array<string, mixed>>
     */
    private function options(Collection $activities): array
    {
        return $activities->map(fn (Activity $activity) => [
            'id' => $activity->code,
            'name' => $activity->name,
            'startDateLabel' => $this->thaiDate($activity->start_date?->toDateString()),
            'responseCount' => (int) $activity->satisfaction_responses_count,
        ])->values()->all();
    }

    /** @return array<string, mixed> */
    private function activityPayload(Activity $activity): array
    {
        return [
            'id' => $activity->code,
            'name' => $activity->name,
            'startDateLabel' => $this->thaiDate($activity->start_date?->toDateString()),
        ];
    }

    /** "12 ส.ค. 2569" — รูปแบบเดียวกับ TFC.formatThaiDate ฝั่งหน้าจอ */
    private function thaiDate(?string $isoDate): string
    {
        if (! $isoDate) {
            return '';
        }

        [$year, $month, $day] = array_map('intval', explode('-', $isoDate));
        $months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $day.' '.$months[$month - 1].' '.($year + 543);
    }
}
