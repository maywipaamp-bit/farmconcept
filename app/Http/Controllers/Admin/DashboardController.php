<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * หน้าภาพรวมการดำเนินงาน (แดชบอร์ดผู้ดูแลระบบ)
 *
 * มีสองทางเข้าที่ใช้ view เดียวกัน
 *   index()    — โหลดหน้าเต็ม ค่าทุกตัวมาในHTML ชุดแรก ไม่มีจอว่างรอ JS
 *   fragment() — เฉพาะเนื้อในเมื่อผู้ใช้เปลี่ยนตัวกรองช่วงเวลา
 *
 * ตัวกรองส่งคืนเป็น HTML ไม่ใช่ JSON ตัวเลข เพราะเรขาคณิตของกราฟ (โดนัท เส้น treemap)
 * คำนวณอยู่ที่ฝั่งเซิร์ฟเวอร์แล้ว ถ้าส่ง JSON ไปให้ JS วาดใหม่ จะต้องเขียนสูตรเดิมซ้ำสองภาษา
 * แล้วสองฝั่งจะเพี้ยนออกจากกันทันทีที่แก้ข้างเดียว
 */
class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboard): View
    {
        return view('admin.dashboard', [
            'data' => $dashboard->overview((string) $request->query('range', '')),
        ]);
    }

    /** เนื้อในของแดชบอร์ดตามช่วงเวลาที่เลือก — ใช้กับการสลับตัวกรองโดยไม่โหลดหน้าใหม่ */
    public function fragment(Request $request, DashboardService $dashboard): JsonResponse
    {
        $data = $dashboard->overview((string) $request->query('range', ''));

        return response()->json([
            'range' => $data['range'],
            'generated_at' => $data['generated_at']->toIso8601String(),
            'html' => view('admin.dashboard.body', ['data' => $data])->render(),
        ]);
    }
}
