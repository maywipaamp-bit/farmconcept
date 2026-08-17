<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicPostSurveyRequest;
use App\Models\Activity;
use App\Services\PublicPostSurveyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicPostSurveyController extends Controller
{
    /**
     * หน้าแบบประเมินหลังกิจกรรม — หน้าของตัวเอง ไม่ปนกับหน้ารายละเอียดกิจกรรม
     * เจ้าหน้าที่ที่ล็อกอินอยู่เปิดทำแทนได้โดยไม่ติดช่วงเวลาเปิดรับคำตอบ
     */
    public function page(Request $request, string $activity, PublicPostSurveyService $service): View
    {
        $activity = Activity::forPublicListing()
            ->where('code', $activity)
            ->firstOrFail();

        $isStaff = $request->user()?->canAccessMenu('activities-list') ?? false;
        $form = $service->form($activity);

        return view('public.activities.survey', [
            'activity' => $activity,
            'form' => $form,
            'enabled' => $activity->acceptsPostSurvey($isStaff) && $form !== null,
            'storeUrl' => route('public.activities.post-survey.store', $activity->code),
        ]);
    }

    public function store(
        PublicPostSurveyRequest $request,
        Activity $activity,
        PublicPostSurveyService $service,
    ): JsonResponse {
        /* เจ้าหน้าที่ที่ล็อกอินอยู่ส่งคำตอบแทนผู้เข้าร่วมได้โดยไม่ติดช่วงเวลาเปิดรับ */
        $byStaff = $request->user()?->canAccessMenu('activities-list') ?? false;

        $service->submit($activity, $request->validated('answers'), $byStaff);

        return response()->json([
            'message' => 'ส่งแบบประเมินเรียบร้อย ขอบคุณสำหรับความคิดเห็น',
        ], 201);
    }
}
