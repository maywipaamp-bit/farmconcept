<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Contracts\View\View;

/**
 * ผลการวิเคราะห์ — ภาพรวมสุขภาวะระดับโครงการ
 *
 * การ์ดชุดเดียวกับแดชบอร์ด (โดนัทกลุ่มเป้าหมาย · การตอบรายรอบ · ก่อน–หลัง)
 * ใช้ตัวคำนวณเดียวกันผ่าน DashboardService ตัวเลขสองหน้าจึงไม่มีวันเถียงกันเอง
 *
 * รายงานเจาะรายแบบประเมิน (ตารางความถี่ x̄ S.D. กราฟเปลี่ยนแปลงสุทธิ)
 * แยกไปอยู่เมนู "สรุปผลแบบประเมิน" (EvaluationSummaryController)
 */
class EvaluationAnalysisController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    public function index(): View
    {
        return view('admin.evaluations.analysis', [
            'overview' => $this->dashboard->healthOverview(),
        ]);
    }
}
