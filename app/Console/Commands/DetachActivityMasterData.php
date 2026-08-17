<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ตัดการอ้างอิงข้อมูลพื้นฐานออกจากกิจกรรม
 *
 * ใช้ก่อนจะลบและสร้างข้อมูลพื้นฐาน (mst_*) ใหม่ทั้งชุด
 * ถ้าไม่ตัดก่อน การลบข้อมูลพื้นฐานจะติด foreign key จากกิจกรรมที่ยังอ้างอยู่
 * และถ้าฝืนลบสำเร็จ กิจกรรมจะเหลือ id ที่ชี้ไปแถวที่ไม่มีแล้ว — หน้าจอจะขึ้นค่าว่างแบบไม่มีอะไรอธิบาย
 *
 * ตัดเฉพาะที่ชี้ไปข้อมูลพื้นฐานเท่านั้น:
 *   program_id · course_id · format_id · พื้นที่ · วิทยากร · กลุ่มเป้าหมาย
 *
 * ไม่แตะ parent_event_id เพราะชี้ไป "กิจกรรมอื่น" ไม่ใช่ข้อมูลพื้นฐาน
 * และไม่แตะชื่อ/รายละเอียด/รอบ/QR ของกิจกรรม
 */
class DetachActivityMasterData extends Command
{
    protected $signature = 'activities:detach-master-data
                            {--dry-run : แสดงจำนวนที่จะถูกตัด โดยยังไม่แก้จริง}
                            {--force : ข้ามคำถามยืนยัน}';

    protected $description = 'ล้างค่า id ของข้อมูลพื้นฐานออกจากกิจกรรม เพื่อให้ลบข้อมูลพื้นฐานใหม่ได้';

    /** คอลัมน์ใน act_activities ที่ชี้ไปข้อมูลพื้นฐาน — ทุกตัวเป็น nullable */
    private const NULLABLE_COLUMNS = [
        'program_id' => 'โปรแกรม',
        'course_id' => 'หลักสูตร',
        'format_id' => 'รูปแบบกิจกรรม',
    ];

    /** ตารางเชื่อมกิจกรรมกับข้อมูลพื้นฐาน — ล้างทั้งตาราง */
    private const PIVOTS = [
        'act_activity_area' => 'พื้นที่จัด',
        'act_activity_instructor' => 'วิทยากร',
        'act_activity_target_group' => 'กลุ่มเป้าหมาย',
    ];

    public function handle(): int
    {
        $rows = [];

        foreach (self::NULLABLE_COLUMNS as $column => $label) {
            $count = DB::table('act_activities')->whereNotNull($column)->count();
            if ($count > 0) {
                $rows[] = ['act_activities.'.$column, $label, number_format($count).' กิจกรรม', 'ตั้งเป็นค่าว่าง'];
            }
        }

        foreach (self::PIVOTS as $table => $label) {
            $count = DB::table($table)->count();
            if ($count > 0) {
                $rows[] = [$table, $label, number_format($count).' แถว', 'ล้างทั้งตาราง'];
            }
        }

        if ($rows === []) {
            $this->info('กิจกรรมไม่ได้อ้างข้อมูลพื้นฐานอยู่แล้ว');

            return self::SUCCESS;
        }

        $this->line('');
        $this->table(['ที่เก็บ', 'ข้อมูลพื้นฐาน', 'จำนวน', 'การกระทำ'], $rows);
        $this->info('ไม่แตะ: ชื่อ · รายละเอียด · รอบกิจกรรม · QR · อีเวนท์แม่ (parent_event_id)');
        $this->line('');

        if ($this->option('dry-run')) {
            $this->warn('โหมด --dry-run: ยังไม่ได้แก้อะไร');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('ยืนยันตัดการอ้างอิงข้างต้น?', false)) {
            $this->line('ยกเลิก ไม่มีอะไรถูกแก้');

            return self::SUCCESS;
        }

        DB::transaction(function () {
            DB::table('act_activities')->update(array_fill_keys(array_keys(self::NULLABLE_COLUMNS), null));

            foreach (array_keys(self::PIVOTS) as $table) {
                DB::table($table)->delete();
            }
        });

        $this->info('ตัดการอ้างอิงเรียบร้อย — ลบข้อมูลพื้นฐานได้แล้วโดยไม่ติด foreign key');

        return self::SUCCESS;
    }
}
