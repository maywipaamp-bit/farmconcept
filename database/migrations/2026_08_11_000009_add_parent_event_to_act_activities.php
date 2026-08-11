<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * กิจกรรมอยู่ในอีเวนท์ได้
 *
 * อีเวนท์หนึ่งมีได้หลายกิจกรรม — เก็บเป็น FK ชี้กลับมาที่ตารางเดียวกัน
 * ไม่แยกตาราง act_events ต่างหาก เพราะอีเวนท์กับกิจกรรมใช้ฟิลด์เดียวกันเกือบทั้งหมด
 * (ชื่อ · วันที่ · สถานที่ · รอบ · QR · แบบประเมิน) แยกแล้วต้องทำหน้าจอและสิทธิ์ซ้ำอีกชุด
 * ต่างกันแค่ค่าในคอลัมน์ type ซึ่งมีอยู่แล้ว
 *
 * ON DELETE RESTRICT โดยตั้งใจ — ลบอีเวนท์ที่ยังมีกิจกรรมอยู่ข้างในไม่ได้
 * ต้องย้ายหรือลบกิจกรรมออกให้หมดก่อน กันข้อมูลหายโดยไม่ตั้งใจ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('act_activities', function (Blueprint $table) {
            $table->foreignId('parent_event_id')
                ->nullable()
                ->after('participant_type')
                ->constrained('act_activities')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('act_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_event_id');
        });
    }
};
