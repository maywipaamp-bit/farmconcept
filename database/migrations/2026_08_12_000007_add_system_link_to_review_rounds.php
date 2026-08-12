<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | ลิงก์ระบบที่ใช้ทดสอบ พร้อมบัญชีทดลอง
 |
 | เก็บที่รอบการส่ง ไม่ใช่ config เพราะแต่ละรอบอาจชี้ไปคนละเซิร์ฟเวอร์
 | (เครื่องทดสอบ · เครื่องสาธิต · เครื่องจริง) และบัญชีทดลองก็เปลี่ยนได้ตามรอบ
 |
 | login_hint เป็นข้อความแสดงผลอย่างเดียว ไม่ใช่ข้อมูลยืนยันตัวตนของระบบนี้
 | ระบบนี้ไม่ได้ใช้ค่านี้ล็อกอินให้ใคร แค่พิมพ์ให้ผู้ตรวจเห็นว่าจะเข้าด้วยบัญชีไหน
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rev_review_rounds', function (Blueprint $table) {
            $table->string('system_url', 500)->nullable()->after('action_plan_url');
            $table->string('login_hint', 120)->nullable()->after('system_url');
        });
    }

    public function down(): void
    {
        Schema::table('rev_review_rounds', function (Blueprint $table) {
            $table->dropColumn(['system_url', 'login_hint']);
        });
    }
};
