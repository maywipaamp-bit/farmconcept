<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * ฐานร่วมของทุกหน้าในเมนู "พื้นฐาน"
 *
 * ทั้งหกหน้าทำงานเหมือนกันหมด — แสดงรายการ · เพิ่ม · แก้ · ลบ ผ่านโมดัลใบเดียว
 * ต่างกันแค่ตาราง ฟิลด์ และเงื่อนไขว่าลบได้เมื่อไหร่ ที่เหลือ (สิทธิ์ · transaction ·
 * audit log · รูปแบบคำตอบ) เหมือนกันทุกหน้า จึงรวมไว้ที่นี่ที่เดียว
 * คลาสลูกประกาศเฉพาะสิ่งที่ต่างจริง
 *
 * หน้าจอเดิมอ้างอิงแถวด้วยรหัสข้อความ (AREA-001) ไม่ใช่ id ตัวเลข
 * ทุก endpoint จึงรับ-ส่ง `code` เสมอ ไม่เปิดเผยลำดับข้อมูลใน URL
 */
abstract class MasterDataController extends Controller
{
    /** ชื่อคลาส Model ของตารางนี้ */
    abstract protected function model(): string;

    /** ชื่อ view ของหน้าจอตารางนี้ */
    abstract protected function view(): string;

    /** คำเรียกในภาษาคน ใช้ประกอบข้อความและ audit log เช่น "กลุ่มเป้าหมาย" */
    abstract protected function label(): string;

    /** คำนำหน้ารหัสของตารางนี้ เช่น TG → TG-001 */
    abstract protected function codePrefix(): string;

    /** กฎตรวจข้อมูล — $current คือแถวที่กำลังแก้ (null = กำลังเพิ่มใหม่) */
    abstract protected function rules(?Model $current): array;

    /** ชื่อฟิลด์ภาษาไทย ใช้ในข้อความ error */
    abstract protected function attributes(): array;

    /** แปลงข้อมูลที่ผ่าน validate แล้วเป็นคอลัมน์จริงของตาราง */
    abstract protected function columns(array $data): array;

    /** แปลงแถวในฐานเป็นรูปแบบที่หน้าจอใช้ */
    abstract protected function toRow(Model $record): array;

    /** คิวรีตั้งต้นของการแสดงรายการ — คลาสลูก override เพื่อ eager load หรือ withCount */
    protected function query()
    {
        return $this->model()::query()->orderBy('id');
    }

    /**
     * เหตุผลที่ลบไม่ได้ — คืน null ถ้าลบได้
     *
     * ตรวจที่นี่แทนการปล่อยให้ FK ปฏิเสธ เพื่อให้ผู้ใช้ได้ข้อความเป็นภาษาคน
     * พร้อมจำนวนที่อ้างอิงอยู่ ไม่ใช่ error ของฐานข้อมูลดิบ ๆ
     */
    protected function blockedFromDelete(Model $record): ?string
    {
        return null;
    }

    /** ความสัมพันธ์แบบหลายต่อหลายหรือตารางลูก ที่ต้องเขียนตามหลังบันทึกแถวหลัก */
    protected function syncRelations(Model $record, array $data): void
    {
        //
    }

    /* ================= endpoint ================= */

    /**
     * URL เดียวทำสองหน้าที่ — เปิดจากเบราว์เซอร์ได้หน้าจอ ยิงด้วย Accept: application/json ได้ข้อมูล
     *
     * ทำแบบนี้เพื่อให้ลิงก์ในเมนูกับปลายทางที่ dataService เรียกเป็นที่เดียวกัน
     * ไม่ต้องจำสอง URL ต่อหนึ่งตาราง และเพิ่มตารางใหม่ก็ไม่มีอะไรให้ลืมผูก
     */
    public function index(Request $request): JsonResponse|View
    {
        if (! $request->expectsJson()) {
            return view($this->view());
        }

        return response()->json([
            'rows' => $this->query()->get()->map(fn (Model $r) => $this->toRow($r))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, null);

        $record = DB::transaction(function () use ($data): Model {
            $model = $this->model();
            $record = new $model($this->columns($data));
            $record->code = $this->nextCode();
            $record->save();

            $this->syncRelations($record, $data);
            $this->log($record, 'created', 'เพิ่ม');

            return $record;
        });

        return response()->json([
            'message' => 'เพิ่ม' . $this->label() . 'แล้ว',
            'row' => $this->toRow($this->query()->where('code', $record->code)->firstOrFail()),
        ], 201);
    }

    public function update(Request $request, string $code): JsonResponse
    {
        $record = $this->find($code);
        $data = $this->validated($request, $record);

        DB::transaction(function () use ($record, $data): void {
            $record->fill($this->columns($data))->save();

            $this->syncRelations($record, $data);
            $this->log($record, 'updated', 'แก้ไข');
        });

        return response()->json([
            'message' => 'บันทึก' . $this->label() . 'แล้ว',
            'row' => $this->toRow($this->query()->where('code', $code)->firstOrFail()),
        ]);
    }

    public function destroy(string $code): JsonResponse
    {
        $record = $this->find($code);

        if ($reason = $this->blockedFromDelete($record)) {
            return response()->json(['message' => $reason], 403);
        }

        DB::transaction(function () use ($record): void {
            /* บันทึก log ก่อนลบ เพราะหลังลบแล้วอ่านค่าจากแถวไม่ได้อีก */
            $this->log($record, 'deleted', 'ลบ');
            $record->delete();
        });

        return response()->json(['message' => 'ลบ' . $this->label() . 'แล้ว']);
    }

    /* ================= ตัวช่วยภายใน ================= */

    protected function find(string $code): Model
    {
        return $this->model()::where('code', $code)->firstOrFail();
    }

    protected function validated(Request $request, ?Model $current): array
    {
        return $request->validate($this->rules($current), [], $this->attributes());
    }

    /**
     * รหัสถัดไปของตารางนี้ — TG-001
     *
     * ต้องเรียกในtransaction เดียวกับการบันทึก และล็อกแถวไว้ กันสองคนกดพร้อมกันแล้วได้รหัสซ้ำ
     * ซึ่ง unique index จะปฏิเสธคนหลังทิ้งโดยที่ผู้ใช้ไม่เข้าใจว่าทำอะไรผิด
     */
    protected function nextCode(): string
    {
        $prefix = $this->codePrefix() . '-';

        $last = $this->model()::where('code', 'like', $prefix . '%')
            ->lockForUpdate()
            ->max('code');

        $running = $last ? (int) Str::afterLast($last, '-') : 0;

        return $prefix . str_pad((string) ($running + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * ไม่ผูก subject_type/subject_id เพราะ morph map มีไว้สำหรับข้อมูลที่ต้องเปิดดูย้อนหลังได้
     * (กิจกรรม · แบบประเมิน) ส่วน master data ดูจากข้อความใน detail ก็พอ
     * และการลงทะเบียนเข้า morph map จะทำให้ subject()->withTrashed() พังทันที
     * เพราะโมเดลกลุ่มนี้ไม่ได้ใช้ SoftDeletes
     */
    protected function log(Model $record, string $action, string $verb): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'master.' . Str::snake(class_basename($this->model())) . '.' . $action,
            'detail' => $verb . $this->label() . ' ' . $record->code . ' — ' . $record->name,
        ]);
    }
}
