<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicCheckinLookupRequest;
use App\Http\Requests\PublicCheckinRequest;
use App\Models\Activity;
use App\Services\PublicCheckinService;
use Illuminate\Http\JsonResponse;
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
            'message' => 'Check-in '.$registration->name.' เรียบร้อย',
            'registration' => [
                'code' => $registration->code,
                'name' => $registration->name,
                'checkedIn' => true,
            ],
        ]);
    }
}
