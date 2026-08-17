<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * คืนบัญชี LINE ที่ยังติดอยู่กับกลุ่มตัวอย่างที่ถูกลบไปแล้ว
 *
 * ptp_participants.line_user_id มี unique index ซึ่ง "นับแถวที่ soft delete ด้วย"
 * แถวที่ถูกลบไปแล้วจึงยังจองบัญชี LINE นั้นไว้ ทำให้เจ้าของตัวจริงเชื่อมไม่ได้อีกเลย
 * (โค้ดเก่าจะได้ 500 duplicate entry · โค้ดใหม่ไม่พังแต่ก็ผูกไม่ได้)
 *
 * ล้างเฉพาะแถวที่ถูกลบแล้วเท่านั้น ระเบียนที่ยังใช้งานอยู่ไม่ถูกแตะ
 * ตัวป้องกันไม่ให้เกิดซ้ำอยู่ที่ CohortController::destroy() ซึ่งล้างค่านี้ตั้งแต่ตอนลบ
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('ptp_participants')
            ->whereNotNull('deleted_at')
            ->whereNotNull('line_user_id')
            ->update(['line_user_id' => null]);
    }

    /**
     * ย้อนกลับไม่ได้ — ค่าเดิมถูกล้างทิ้งแล้วและไม่ได้เก็บสำเนาไว้
     *
     * ตั้งใจให้เป็นแบบนี้: ข้อมูลที่ล้างคือค่าที่ค้างอยู่กับระเบียนที่ถูกลบแล้ว
     * เก็บสำเนาไว้เพื่อ rollback เท่ากับสร้างที่ซ่อนของข้อมูลที่ตั้งใจทิ้งไปแล้ว
     */
    public function down(): void
    {
        // ไม่มีอะไรให้ย้อน
    }
};
