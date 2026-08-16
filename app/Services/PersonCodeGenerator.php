<?php

namespace App\Services;

use App\Models\CohortProfile;
use App\Models\Participant;
use Illuminate\Support\Str;

/**
 * รหัสวิ่งของโมดูลกลุ่มตัวอย่าง — ที่เดียวของทั้งระบบ
 *
 * รหัสบุคคล (P0001) และรหัสกลุ่มตัวอย่าง (CHT-0001) ออกเหมือนกันไม่ว่าจะเข้ามาทางไหน
 * ทั้งแอดมินเพิ่มจากหน้ากลุ่มตัวอย่าง และผู้เข้าร่วมลงทะเบียนเองผ่าน QR
 * เพราะรหัสบุคคลคือสิ่งที่พิมพ์ลงใบยินยอมและใช้อ้างถึงคนคนหนึ่งข้ามทั้งระบบ
 * ถ้าออกคนละแบบตามทางเข้า เจ้าหน้าที่จะอ่านใบยินยอมสองใบแล้วนึกว่าเป็นคนละระบบ
 */
class PersonCodeGenerator
{
    public const PREFIX = 'P';

    /**
     * รหัสบุคคลถัดไปที่ยังไม่มีใครใช้
     *
     * $lock = true ใช้ตอนบันทึกจริงเท่านั้น เพื่อกันสองคนกดบันทึกพร้อมกันแล้วได้เลขเดียวกัน
     * ต้องอยู่ใน transaction ถึงจะมีผล — ดู CohortController::store()
     */
    public function next(bool $lock = false): string
    {
        return self::PREFIX.str_pad((string) ($this->highest($lock) + 1), 4, '0', STR_PAD_LEFT);
    }

    /** รหัสกลุ่มตัวอย่างถัดไป — รูปแบบ CHT-0001 */
    public function nextCohortCode(bool $lock = false): string
    {
        $query = CohortProfile::where('cohort_code', 'like', 'CHT-%');

        if ($lock) {
            $query->lockForUpdate();
        }

        $running = $query->pluck('cohort_code')
            ->map(fn (string $code) => (int) Str::afterLast($code, '-'))
            ->max() ?? 0;

        return 'CHT-'.str_pad((string) ($running + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * เลขสูงสุดจาก "ตัวเลขท้ายรหัส" ไม่ใช่ max() ของข้อความ
     * เหตุผลเดียวกับ MasterDataController::runningCode() — P9 กับ P0010 เทียบเป็นข้อความแล้วผิด
     *
     * กรองด้วย regex ฝั่ง PHP ไม่ใช่ SQL เพราะข้อมูลเดิมมีรหัสคนละแบบปนอยู่
     * (PID-0009 ของหน้าจอรุ่นก่อน · DEMO-PSN-0001 ของชุดตัวอย่าง · PID-<สุ่ม> ของการลงทะเบียนกิจกรรม)
     * ซึ่งขึ้นต้นด้วย P เหมือนกันหมด ถ้านับรวมเข้ามาจะได้เลขที่กระโดดหรือชนกับของเดิม
     */
    private function highest(bool $lock): int
    {
        $query = Participant::withTrashed()->where('person_code', 'like', self::PREFIX.'%');

        if ($lock) {
            $query->lockForUpdate();
        }

        return (int) $query->pluck('person_code')
            ->map(fn (string $code) => preg_match('/^'.self::PREFIX.'(\d+)$/', $code, $m) ? (int) $m[1] : 0)
            ->max();
    }
}
