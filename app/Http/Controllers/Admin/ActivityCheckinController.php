<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCheckinRequest;
use App\Http\Requests\AdminWalkInRequest;
use App\Models\Activity;
use App\Models\Option;
use App\Services\AdminCheckinService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Check-in หน้างานฝั่งเจ้าหน้าที่
 *
 * หน้าจอเป็น Blade หนึ่งหน้า แล้วคุยกับ endpoint JSON ชุดนี้ผ่าน assets/js/checkin-service.js
 * รูปของ URL และ payload ต้องตรงกับที่ไฟล์นั้นกำหนดไว้ (ชั้น "ขนส่งข้อมูล" ของฝั่งหน้าจอ)
 */
class ActivityCheckinController extends Controller
{
    use AuthorizesRequests;

    /**
     * หน้าจอเช็คอิน
     *
     * ส่งไปเฉพาะรายการกิจกรรมที่ต้องเช็คอิน — รายชื่อผู้เข้าร่วมโหลดผ่าน snapshot()
     * เพราะต้องรีเฟรชเองทุกไม่กี่วินาทีอยู่แล้ว การฝังมากับหน้าจึงไม่ช่วยอะไร
     */
    public function index(Request $request): View
    {
        $activities = Activity::query()
            ->where('requires_checkin', true)
            ->where('status', '!=', Activity::STATUS_CANCELLED)
            ->with(['areas:id,name', 'rounds:id,activity_id,round_date,time_start,time_end'])
            ->withCount('registrations')
            ->withExists(['qrCodes as has_checkin_qr' => fn ($q) => $q->where('purpose', 'checkin')->active()])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $selected = $activities->firstWhere('code', $request->query('id'))
            ?? $activities->first(fn (Activity $a) => $a->acceptsCheckin())
            ?? $activities->first();

        return view('admin.activities.checkin', [
            'activities' => $activities->map(fn (Activity $a) => $this->toPickerRow($a)),
            'selectedId' => $selected?->code,
            'ageRanges' => Option::query()->group('age_range')->active()->pluck('label')->all(),
        ]);
    }

    /** GET /admin/activities/{activity}/checkin */
    public function snapshot(Activity $activity, AdminCheckinService $service): JsonResponse
    {
        $this->authorize('checkIn', $activity);

        return response()->json($service->snapshot($activity));
    }

    /** POST /admin/activities/{activity}/checkin */
    public function store(AdminCheckinRequest $request, Activity $activity, AdminCheckinService $service): JsonResponse
    {
        $this->authorize('checkIn', $activity);

        return response()->json($service->checkIn(
            $activity,
            $request->validated('registrationId'),
            $request->validated('source') ?? 'staff',
            $request->user(),
        ));
    }

    /** DELETE /admin/activities/{activity}/checkin/{registration} */
    public function destroy(Request $request, Activity $activity, string $registration, AdminCheckinService $service): JsonResponse
    {
        $this->authorize('checkIn', $activity);

        return response()->json($service->undoCheckIn(
            $activity,
            $registration,
            (string) $request->input('reason', ''),
            $request->user(),
        ));
    }

    /** POST /admin/activities/{activity}/walk-ins */
    public function walkIn(AdminWalkInRequest $request, Activity $activity, AdminCheckinService $service): JsonResponse
    {
        $this->authorize('checkIn', $activity);

        return response()->json($service->addWalkIn($activity, $request->validated(), $request->user()));
    }

    /** GET /admin/activities/{activity}/checkin/audit */
    public function audit(Activity $activity, AdminCheckinService $service): JsonResponse
    {
        $this->authorize('checkIn', $activity);

        return response()->json($service->auditLog($activity));
    }

    /**
     * หนึ่งบรรทัดในแผงเลือกกิจกรรม — ชื่อ วันเวลา สถานที่ และจำนวนผู้ลงทะเบียน
     *
     * `id` เป็นรหัสกิจกรรม (code) ไม่ใช่ id ตัวเลข ให้ตรงกับ getRouteKeyName()
     * ของ Activity หน้าจอจึงเอาค่านี้ไปต่อเป็น URL ของ API ได้ตรง ๆ
     *
     * @return array<string, mixed>
     */
    private function toPickerRow(Activity $activity): array
    {
        $round = $activity->rounds->first();
        $time = $round?->time_start ? substr((string) $round->time_start, 0, 5) : '';

        if ($time && $round?->time_end) {
            $time .= '–'.substr((string) $round->time_end, 0, 5);
        }

        return [
            'id' => $activity->code,
            'name' => $activity->name,
            'startDate' => $activity->start_date?->toDateString(),
            'time' => $time,
            'area' => $activity->areas->pluck('name')->join(', '),
            'status' => $activity->status,
            'registeredCount' => $activity->registrations_count,
            'openForCheckin' => $activity->acceptsCheckin(),
            /* ไม่มีแถว QR ที่เปิดใช้งาน = กิจกรรมยังไม่เผยแพร่ หรือปิดสวิตช์เช็คอินไว้
               หน้าจอจะซ่อนกล่อง QR แทนที่จะโชว์รูปเสียให้ผู้ใช้เดาเอง */
            'hasCheckinQr' => (bool) $activity->has_checkin_qr,
        ];
    }
}
