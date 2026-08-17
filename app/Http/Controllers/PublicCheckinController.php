<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicCheckinLookupRequest;
use App\Http\Requests\PublicCheckinRequest;
use App\Models\Activity;
use App\Services\PublicCheckinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PublicCheckinController extends Controller
{
    /**
     * หน้า Check-in — หน้าของตัวเอง ไม่ปนกับหน้ารายละเอียดกิจกรรม
     * โครงเดียวกับหน้าแบบประเมิน (PublicPostSurveyController::page)
     */
    public function page(string $activity): View
    {
        $activity = Activity::forPublicListing()
            ->where('code', $activity)
            ->firstOrFail();

        return view('public.activities.checkin', [
            'activity' => $activity,
            'enabled' => $activity->acceptsCheckin(),
            'lookupUrl' => route('public.activities.checkin.lookup', $activity->code),
            'storeUrl' => route('public.activities.checkin.store', $activity->code),
        ]);
    }

    public function lookup(
        PublicCheckinLookupRequest $request,
        Activity $activity,
        PublicCheckinService $service,
    ): JsonResponse {
        $registrations = $service->registrationsForPhone($activity, $request->validated('phone'));

        return response()->json([
            'message' => 'พบรายชื่อผู้ลงทะเบียน '.$registrations->count().' คน',
            'registrations' => $registrations->map(fn ($registration) => [
                'code' => $registration->code,
                'name' => $registration->name,
                'checkedIn' => $registration->checked_in_at !== null,
                'checkedInAt' => $this->thaiMoment($registration->checked_in_at),
            ])->values(),
        ]);
    }

    public function store(
        PublicCheckinRequest $request,
        Activity $activity,
        PublicCheckinService $service,
    ): JsonResponse {
        $registration = $service->checkIn(
            $activity,
            $request->validated('phone'),
            $request->validated('registration_code'),
        );

        return response()->json([
            'message' => 'เช็กอิน '.$registration->name.' เรียบร้อย',
            'registration' => [
                'code' => $registration->code,
                'name' => $registration->name,
                'checkedIn' => true,
                'checkedInAt' => $this->thaiMoment($registration->checked_in_at),
            ],
        ]);
    }

    /**
     * "16:42 น. · 17 ส.ค. 2569" — เวลาก่อนวันที่ ตามที่หน้าจอผลลัพธ์ต้องการ
     *
     * จัดรูปแบบที่นี่ ไม่ใช่ฝั่งหน้าจอ เพราะเป็นเวลาของเซิร์ฟเวอร์
     * เครื่องผู้ใช้ที่ตั้งเวลาผิดจะได้ไม่แสดงเวลาที่ไม่ตรงกับที่บันทึกไว้จริง
     */
    private function thaiMoment(?Carbon $moment): ?string
    {
        if (! $moment) {
            return null;
        }

        $thMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $moment->format('H:i').' น. · '.$moment->day.' '.$thMonths[$moment->month - 1].' '.($moment->year + 543);
    }
}
