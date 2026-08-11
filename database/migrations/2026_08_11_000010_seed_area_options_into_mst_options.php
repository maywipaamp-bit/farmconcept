<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ย้าย "ประเภทพื้นที่ · กลุ่มพื้นที่ · สถานะพื้นที่" เข้าตาราง mst_options
 *
 * เดิมสามชุดนี้อยู่ใน config/farmconcept.php เพราะตอนออกแบบคิดว่าเป็นคำที่ทีมกำหนดตายตัว
 * แต่ทีมถามหาว่าอยู่ตารางไหน แปลว่าคาดหวังว่าแก้ได้โดยไม่ต้องแก้โค้ด — จึงย้ายลงฐานข้อมูล
 *
 * ไม่สร้างตารางใหม่ ใช้ mst_options ที่มีอยู่แล้ว (ปม F.3 — รายการตัวเลือกแบนทั้งหมด
 * อยู่ตารางเดียว แยกด้วย option_group) เหมือนอาชีพและช่องทางที่รู้จักกิจกรรม
 *
 * ใส่เป็น migration ไม่ใช่ seeder เพราะเป็นข้อมูลที่ระบบต้องมีจึงจะทำงานได้
 * ไม่ใช่ข้อมูลตัวอย่างสำหรับทดลอง
 */
return new class extends Migration
{
    /** ต้องตรงกับค่าที่มีอยู่ในคอลัมน์ mst_areas.area_type / area_group / status ตอนนี้ */
    private const GROUPS = [
        'area_type' => ['เอกชน', 'ชุมชน/หมู่บ้าน', 'โรงเรียน', 'สถานประกอบการเอกชน', 'โรงพยาบาล'],
        'area_group' => ['พื้นที่ต้นแบบ', 'พื้นที่ต้นแบบส่วนขยาย', 'พื้นที่จัดกิจกรรม'],
        'area_status' => ['ดำเนินการอยู่', 'ระงับชั่วคราว', 'สิ้นสุดแล้ว'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::GROUPS as $group => $labels) {
            foreach ($labels as $index => $label) {
                DB::table('mst_options')->updateOrInsert(
                    ['option_group' => $group, 'label' => $label],
                    [
                        'code' => $group . '-' . ($index + 1),
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('mst_options')->whereIn('option_group', array_keys(self::GROUPS))->delete();
    }
};
