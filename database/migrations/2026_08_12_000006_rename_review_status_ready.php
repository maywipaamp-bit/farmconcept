<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 | เปลี่ยนชื่อสถานะ "แจ้งทดสอบ" เป็น "ตรวจได้"
 |
 | คำเดิมมองจากมุมทีมพัฒนา (เราแจ้งให้ไปทดสอบ) คำใหม่มองจากมุมผู้ตรวจ (เปิดดูได้แล้ว)
 | หน้าตรวจงานทำมาให้ผู้ตรวจอ่าน จึงใช้คำของฝั่งผู้ตรวจ
 |
 | สถานะเก็บเป็นข้อความในคอลัมน์ status ไม่ใช่ id — ต้องไล่แก้ข้อมูลเดิมด้วย
 | ไม่งั้นแถวที่ใช้คำเก่าจะกลายเป็นสถานะที่ไม่มีอยู่ในระบบ
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('rev_review_items')->where('status', 'แจ้งทดสอบ')->update(['status' => 'ตรวจได้']);
    }

    public function down(): void
    {
        DB::table('rev_review_items')->where('status', 'ตรวจได้')->update(['status' => 'แจ้งทดสอบ']);
    }
};
