<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\FollowUpRoundTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * ตั้งค่ารอบติดตาม
 *
 * หน้านี้ไม่เข้าแม่พิมพ์ของ MasterDataController เพราะไม่ได้แก้ทีละแถวผ่านโมดัล
 * แต่แก้ทั้งตารางบนหน้าจอแล้วกดบันทึกครั้งเดียว — ต้องเป็น PUT ก้อนเดียวใน transaction เดียว
 * ถ้าแยกเป็นคำขอต่อแถว การบันทึกที่สำเร็จครึ่งทางจะทำให้ลำดับรอบเพี้ยนโดยไม่มีใครรู้
 *
 * รอบของแต่ละคน (FollowUpRound) snapshot ค่า name/offset_days ไปแล้วตอนสร้าง
 * แก้ที่นี่จึงมีผลเฉพาะคนที่เพิ่มใหม่ ไม่ขยับวันครบกำหนดของคนเก่า
 */
class FollowUpRoundTemplateController extends Controller
{
    /** URL เดียวทำสองหน้าที่ — เปิดจากเบราว์เซอร์ได้หน้าจอ ยิงด้วย Accept: application/json ได้ข้อมูล */
    public function index(Request $request): JsonResponse|View
    {
        if (! $request->expectsJson()) {
            return view('admin.master.follow-up-rounds');
        }

        return response()->json($this->rows());
    }

    /**
     * บันทึกทั้งตาราง
     *
     * แถวที่ไม่มี id = แถวใหม่ · แถวที่หายไปจากรายการ = ถูกลบ
     * ลบได้เฉพาะรอบที่ยังไม่เคยสร้าง record ให้ใคร — ที่มีคนใช้แล้วให้ปิดใช้งานแทน
     * ไม่งั้นวันครบกำหนดที่คำนวณไว้แล้วจะกลายเป็นรอบที่อ้างอิงไม่ได้
     */
    public function bulkSave(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:20'],
            'rows.*.id' => ['nullable', 'string', 'max:20'],
            'rows.*.name' => ['required', 'string', 'max:100'],

            /* ระยะห่างเป็นวันนับจากวันเข้าร่วม ห้ามซ้ำกัน ไม่งั้นจะได้รอบสองรอบที่ครบกำหนดวันเดียวกัน */
            'rows.*.offsetDays' => ['required', 'integer', 'min:0', 'max:3650', 'distinct'],
            'rows.*.isActive' => ['required', 'boolean'],
        ], [], [
            'rows' => 'รอบติดตาม',
            'rows.*.name' => 'ชื่อรอบ',
            'rows.*.offsetDays' => 'จำนวนวัน',
            'rows.*.isActive' => 'สถานะ',
        ]);

        $rows = $data['rows'];

        if (! collect($rows)->contains(fn (array $r) => $r['isActive'])) {
            return response()->json([
                'message' => 'ต้องเปิดใช้งานอย่างน้อยหนึ่งรอบ ไม่งั้นระบบจะไม่สร้างรอบติดตามให้ใครเลย',
            ], 422);
        }

        $blocked = $this->blockedFromDelete($rows);

        if ($blocked !== null) {
            return response()->json(['message' => $blocked], 403);
        }

        DB::transaction(function () use ($rows): void {
            $keptCodes = [];

            foreach ($rows as $order => $row) {
                $template = $row['id']
                    ? FollowUpRoundTemplate::where('code', $row['id'])->firstOrFail()
                    : new FollowUpRoundTemplate(['code' => $this->nextCode()]);

                $template->fill([
                    'name' => $row['name'],
                    'offset_days' => $row['offsetDays'],
                    'is_active' => $row['isActive'],
                    'sort_order' => $order + 1,
                ])->save();

                $keptCodes[] = $template->code;
            }

            FollowUpRoundTemplate::whereNotIn('code', $keptCodes)->delete();

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'master.follow_up_round_template.saved',
                'detail' => 'บันทึกการตั้งค่ารอบติดตาม ' . count($keptCodes) . ' รอบ — ' . implode(', ', $keptCodes),
            ]);
        });

        return response()->json(['message' => 'บันทึกการตั้งค่ารอบติดตามแล้ว'] + $this->rows());
    }

    /**
     * รอบที่หายไปจากรายการแต่ยังมีคนใช้อยู่
     *
     * ตรวจก่อนเข้า transaction เพื่อให้ปฏิเสธทั้งก้อน ไม่ใช่บันทึกไปครึ่งหนึ่งแล้วค่อยล้ม
     */
    private function blockedFromDelete(array $rows): ?string
    {
        $keptCodes = array_filter(array_column($rows, 'id'));

        $used = FollowUpRoundTemplate::whereNotIn('code', $keptCodes)
            ->withCount('rounds')
            ->get()
            ->filter(fn (FollowUpRoundTemplate $t) => $t->rounds_count > 0);

        if ($used->isEmpty()) {
            return null;
        }

        $names = $used->map(fn (FollowUpRoundTemplate $t) => '"' . $t->name . '" (' . $t->rounds_count . ' รายการ)');

        return 'ลบรอบที่มีข้อมูลผู้เข้าร่วมอยู่แล้วไม่ได้ — ' . $names->join(' · ')
            . ' ให้ปิดใช้งานแทนการลบ วันครบกำหนดที่คำนวณไว้แล้วจะได้ไม่กลายเป็นรอบที่อ้างอิงไม่ได้';
    }

    /**
     * ข้อมูลทั้งหมดที่หน้าจอต้องใช้
     *
     * ส่ง usage กับ today มาด้วย เพราะหน้าจอต้องตัดสินว่าปุ่มลบกดได้ไหม
     * และคำนวณวันครบกำหนดตัวอย่าง — เดิมสองค่านี้เขียนตายตัวอยู่ในไฟล์ JS
     */
    private function rows(): array
    {
        $templates = FollowUpRoundTemplate::withCount('rounds')->orderBy('sort_order')->orderBy('id')->get();

        return [
            'today' => now()->toDateString(),
            'usage' => $templates->pluck('rounds_count', 'code'),
            'rows' => $templates->map(fn (FollowUpRoundTemplate $t) => [
                'id' => $t->code,
                'name' => $t->name,
                'offsetDays' => $t->offset_days,
                'isActive' => $t->is_active,
                'sortOrder' => $t->sort_order,
            ])->values(),
        ];
    }

    /** ล็อกแถวไว้ระหว่างออกรหัส กันสองคนกดบันทึกพร้อมกันแล้วได้รหัสซ้ำ */
    private function nextCode(): string
    {
        return MasterDataController::runningCode(FollowUpRoundTemplate::class, 'FRT');
    }
}
