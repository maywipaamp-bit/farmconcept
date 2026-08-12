<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | ข้อมูลโครงการบนหัวหน้าตรวจงาน
 |
 | ผู้ตรวจที่เปิดลิงก์เข้ามาต้องรู้ว่ากำลังดูงานของโครงการไหน และเหลือเวลาอีกเท่าไร
 | เก็บที่รอบการส่ง ไม่ใช่ config เพราะรอบถัดไปอาจเป็นคนละโครงการหรือคนละช่วงเวลา
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rev_review_rounds', function (Blueprint $table) {
            $table->string('project_name', 255)->nullable()->after('sender');
            $table->date('project_start')->nullable()->after('project_name');
            $table->date('project_end')->nullable()->after('project_start');

            /* ลิงก์แผนงานภายนอก (Google Sheets) — ปุ่มบนหัวหน้าพาไปที่นี่ */
            $table->string('action_plan_url', 500)->nullable()->after('project_end');
        });
    }

    public function down(): void
    {
        Schema::table('rev_review_rounds', function (Blueprint $table) {
            $table->dropColumn(['project_name', 'project_start', 'project_end', 'action_plan_url']);
        });
    }
};
