<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityInsightsService;
use Illuminate\Contracts\View\View;

/**
 * รายงานที่มองข้ามกิจกรรมทั้งหมด (admin/reports/*) — สี่หน้าตามหัวข้อบริหาร
 *
 * แยกคนละ action ต่อหน้า (ไม่ใช่หน้าเดียวมีแท็บ) เพราะแต่ละหน้าเป็นคำถามคนละชุด
 * และมีสิทธิ์เมนูแยกกันได้ในอนาคตถ้าต้องการ (ตอนนี้ยังผูกกับ activities-list เดียวกัน)
 */
class ActivityInsightsController extends Controller
{
    public function overview(ActivityInsightsService $service): View
    {
        return view('admin.reports.activities-overview', $service->overview());
    }

    public function performance(ActivityInsightsService $service): View
    {
        return view('admin.reports.activities-performance', $service->performance());
    }

    public function participants(ActivityInsightsService $service): View
    {
        return view('admin.reports.activities-participants', $service->participants());
    }

    public function finance(ActivityInsightsService $service): View
    {
        return view('admin.reports.activities-finance', $service->finance());
    }
}
