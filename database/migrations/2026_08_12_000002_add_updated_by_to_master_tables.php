<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | คอลัมน์ "ใครแก้ล่าสุด" ให้ตารางพื้นฐานที่เหลือ
 |
 | mst_areas มีอยู่แล้ว ส่วนอีกสี่ตารางยังไม่มี ทำให้คอลัมน์ "ปรับปรุงล่าสุด" ในหน้าจอ
 | แสดงได้แค่วันที่ ไม่รู้ว่าต้องไปถามใคร — เป็นข้อมูลที่ต้องมีเหมือนกันทุกหน้าตามมาตรฐาน
 |
 | nullable เพราะแถวที่มีอยู่แล้วไม่มีทางรู้ย้อนหลังว่าใครเป็นคนสร้าง
 | หน้าจอแสดงขีดแทนจนกว่าจะมีคนแก้ครั้งถัดไป
 |
 | nullOnDelete ไม่ใช่ cascade — ลบผู้ใช้แล้วต้องไม่ลบข้อมูลพื้นฐานตามไปด้วย
 */
return new class extends Migration
{
    private const TABLES = ['mst_target_groups', 'mst_activity_formats', 'mst_programs', 'mst_instructors'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasColumn($table, 'updated_by')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('updated_by')->nullable()->after('updated_at')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasColumn($table, 'updated_by')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('updated_by');
            });
        }
    }
};
