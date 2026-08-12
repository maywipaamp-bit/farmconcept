<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 | สถานะพื้นที่ดำเนินงานเหลือสามค่าตามที่ใช้งานจริง
 |   รอดำเนินงาน → ดำเนินการอยู่ → สิ้นสุดแล้ว
 |
 | ของเดิมค่ากลางคือ "ระงับชั่วคราว" ซึ่งสื่อว่าเคยเริ่มแล้วหยุดกลางคัน
 | แต่ที่ต้องการคือพื้นที่ที่รับเข้ามาแล้วยังไม่เริ่ม จึงเปลี่ยนคำและย้ายไปอยู่หน้าสุด
 |
 | mst_areas.status เก็บเป็นข้อความ ไม่ใช่ id — เปลี่ยนชื่อตัวเลือกเฉย ๆ ไม่พอ
 | ต้องไล่แก้ข้อมูลที่ใช้คำเดิมด้วย ไม่งั้นแถวนั้นจะกลายเป็นสถานะที่ไม่มีอยู่ในระบบ
 */
return new class extends Migration
{
    private const OLD = 'ระงับชั่วคราว';

    private const NEW = 'รอดำเนินงาน';

    /* ลำดับที่ต้องการให้แสดง — ใช้ทั้งในชิปกรองและ dropdown ในฟอร์ม */
    private const ORDER = [self::NEW => 1, 'ดำเนินการอยู่' => 2, 'สิ้นสุดแล้ว' => 3];

    public function up(): void
    {
        $this->rename(self::OLD, self::NEW);
        $this->sort(self::ORDER);
    }

    public function down(): void
    {
        $this->rename(self::NEW, self::OLD);
        $this->sort(['ดำเนินการอยู่' => 1, self::OLD => 2, 'สิ้นสุดแล้ว' => 3]);
    }

    private function rename(string $from, string $to): void
    {
        DB::table('mst_options')
            ->where('option_group', 'area_status')
            ->where('label', $from)
            ->update(['label' => $to, 'updated_at' => now()]);

        DB::table('mst_areas')->where('status', $from)->update(['status' => $to]);
    }

    private function sort(array $order): void
    {
        foreach ($order as $label => $position) {
            DB::table('mst_options')
                ->where('option_group', 'area_status')
                ->where('label', $label)
                ->update(['sort_order' => $position]);
        }
    }
};
