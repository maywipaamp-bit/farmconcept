<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CohortHealthReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * รายงานสุขภาพกลุ่มตัวอย่าง (admin/reports/cohort-health)
 *
 * ตัวกรองอยู่ใน query string ทั้งหมด — คัดลอก URL ส่งต่อให้คนอื่นแล้วได้ผลชุดเดียวกัน
 * และกดปุ่มย้อนกลับของเบราว์เซอร์แล้วกลับไปชุดกรองก่อนหน้าได้ตามปกติ
 */
class CohortHealthReportController extends Controller
{
    public function index(Request $request, CohortHealthReportService $service): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'area' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:complete,pending'],
        ]);

        return view('admin.reports.cohort-health', $service->report($filters));
    }

    /**
     * แนวโน้มของคนเดียว — เรียกเมื่อผู้ใช้เลือกชื่อจากตาราง
     *
     * คำนวณตอนขอ ไม่ได้ฝังมากับหน้าตั้งแต่แรก เพราะกลุ่มตัวอย่างหลายร้อยคน × หลายรอบ
     * ถ้าคำนวณล่วงหน้าทั้งหมดจะหนักโดยที่ผู้ใช้เปิดดูจริงแค่ไม่กี่คน
     */
    public function person(int $participant, CohortHealthReportService $service): JsonResponse
    {
        $trend = $service->personTrend($participant);

        abort_if($trend === null, 404, 'ยังไม่มีคำตอบของกลุ่มตัวอย่างรายนี้');

        return response()->json($trend);
    }
}
