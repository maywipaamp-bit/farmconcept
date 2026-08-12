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

    /**
     * ข้อมูลเพิ่มเติมที่หน้าจอต้องใช้ตอนเปิดหน้า — ตัวเลือกใน dropdown, เพดานขนาดไฟล์ ฯลฯ
     *
     * แถวข้อมูลไม่ต้องส่งมาที่นี่ หน้าจอไปดึงเองผ่าน dataService
     *
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        return [];
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
        $rows = $this->query()->get()->map(fn (Model $r) => $this->toRow($r))->values();

        if (! $request->expectsJson()) {
            /* ฝังแถวชุดแรกไปกับหน้าเลย หน้าจอจะได้ไม่ต้องยิงคำขอซ้ำทันทีที่เปิด
               dev server ของ PHP รับได้ทีละคำขอ การเปิดหน้าแล้วยิง XHR ตาม
               ทำให้ทุกหน้ารู้สึกหน่วงหนึ่งจังหวะ */
            return view($this->view(), $this->viewData() + ['seedRows' => $rows]);
        }

        return response()->json(['rows' => $rows]);
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
            'message' => 'เพิ่ม'.$this->label().'แล้ว',
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
            'message' => 'บันทึก'.$this->label().'แล้ว',
            'row' => $this->toRow($this->query()->where('code', $code)->firstOrFail()),
        ]);
    }

    public function destroy(string $code): JsonResponse
    {
        $blocked = null;

        DB::transaction(function () use ($code, &$blocked): void {
            /* ล็อกแถวหลักก่อนตรวจจำนวนใช้งาน เพื่อไม่ให้มีรายการใหม่มาอ้างอิง
               แทรกระหว่างตรวจแล้วลบ ซึ่งจะทำให้ข้อมูลที่เพิ่งบันทึกหายตาม cascade */
            $record = $this->model()::where('code', $code)->lockForUpdate()->firstOrFail();

            if ($reason = $this->blockedFromDelete($record)) {
                $blocked = $reason;

                return;
            }

            /* บันทึก log ก่อนลบ เพราะหลังลบแล้วอ่านค่าจากแถวไม่ได้อีก */
            $this->log($record, 'deleted', 'ลบ');
            $record->delete();
        });

        if ($blocked !== null) {
            return response()->json(['message' => $blocked], 403);
        }

        return response()->json(['message' => 'ลบ'.$this->label().'แล้ว']);
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
        return static::runningCode($this->model(), $this->codePrefix());
    }

    /**
     * @param  class-string<Model>  $model
     */
    public static function runningCode(string $model, string $prefix): string
    {
        $prefix .= '-';

        /* หาเลขสูงสุดจาก "ตัวเลขท้ายรหัส" ไม่ใช่ max() ของข้อความ
           เพราะข้อมูลตั้งต้นบางตารางใช้เลขไม่เติมศูนย์ (FRT-1) ปนกับรหัสที่ระบบออกให้ (FRT-005)
           การเทียบแบบข้อความจะบอกว่า FRT-4 มากกว่า FRT-005 แล้วออกรหัสซ้ำเดิมทุกครั้ง */
        $codes = $model::where('code', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('code');

        $running = $codes->map(fn (string $code) => (int) Str::afterLast($code, '-'))->max() ?? 0;

        return $prefix.str_pad((string) ($running + 1), 3, '0', STR_PAD_LEFT);
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
            'action' => 'master.'.Str::snake(class_basename($this->model())).'.'.$action,
            'detail' => $verb.$this->label().' '.$record->code.' — '.$record->name,
        ]);
    }
}
