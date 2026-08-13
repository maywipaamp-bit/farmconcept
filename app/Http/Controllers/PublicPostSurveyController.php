<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicPostSurveyRequest;
use App\Models\Activity;
use App\Services\PublicPostSurveyService;
use Illuminate\Http\JsonResponse;

class PublicPostSurveyController extends Controller
{
    public function store(
        PublicPostSurveyRequest $request,
        Activity $activity,
        PublicPostSurveyService $service,
    ): JsonResponse {
        $service->submit($activity, $request->validated('answers'));

        return response()->json([
            'message' => 'ส่งแบบประเมินเรียบร้อย ขอบคุณสำหรับความคิดเห็น',
        ], 201);
    }
}
