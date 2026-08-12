<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | คอลัมน์ "ใครแก้ล่าสุด" ให้ตารางบทบาท
 |
 | ตารางรายการทุกหน้าในระบบแสดงคอลัมน์ปรับปรุงล่าสุดเป็น "ชื่อคนแก้ + วันเวลา" เหมือนกันหมด
 | หน้าบทบาทเป็นหน้าสุดท้ายที่ยังไม่มีคอลัมน์นี้
 |
 | nullable เพราะแถวที่มีอยู่แล้วไม่มีทางรู้ย้อนหลังว่าใครสร้าง
 | nullOnDelete ไม่ใช่ cascade — ลบผู้ใช้แล้วต้องไม่ลบบทบาทตามไปด้วย
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('usr_roles', 'updated_by')) {
            return;
        }

        Schema::table('usr_roles', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('updated_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('usr_roles', 'updated_by')) {
            return;
        }

        Schema::table('usr_roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
        });
    }
};
