<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ช่วงเวลาเปิด-ปิดแบบประเมินหลังกิจกรรม
 *
 * ฟอร์มสร้างกิจกรรมมีช่วงเวลา 3 ชุด (ลงทะเบียน · Check-in · แบบประเมิน)
 * แต่ตารางมีคอลัมน์แค่ 2 ชุด ค่าที่แอดมินกรอกในชุดที่สามจึงไม่มีที่เก็บและหายไปเงียบ ๆ
 *
 * docs/activity-module.md ข้อ 5 เคยตีความว่า "ใช้ช่วงเดียวกับ Check-in"
 * แต่ในทางปฏิบัติแบบประเมินมักเปิดหลังกิจกรรมจบและเปิดยาวกว่า Check-in มาก
 * จึงแยกคอลัมน์ออกมา ให้ตั้งได้อิสระ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('act_activities', function (Blueprint $table) {
            $table->timestamp('survey_start_at')->nullable()->after('checkin_end_at');
            $table->timestamp('survey_end_at')->nullable()->after('survey_start_at');
        });
    }

    public function down(): void
    {
        Schema::table('act_activities', function (Blueprint $table) {
            $table->dropColumn(['survey_start_at', 'survey_end_at']);
        });
    }
};
