<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ล้างข้อมูลทดสอบออกจากระบบ โดยเก็บสิ่งที่ต้องใช้งานต่อไว้
 *
 * เก็บไว้ (ตามที่ตกลง):
 *   1. ข้อมูลพื้นฐานทั้งหมด (mst_*) — พื้นที่ กลุ่มเป้าหมาย โปรแกรม หลักสูตร วิทยากร ฯลฯ
 *   2. ผู้ใช้งานและบทบาท (users, usr_*)
 *   3. กิจกรรมที่ "เผยแพร่อยู่" เฉพาะตัวกิจกรรม พร้อมรอบ/พื้นที่/วิทยากร/กลุ่มเป้าหมาย/QR ของมัน
 *
 * ลบทิ้ง:
 *   - ผู้ลงทะเบียน เช็คอิน สลิป และคำตอบแบบประเมินทั้งหมด (รวมของกิจกรรมที่เผยแพร่)
 *   - แบบฟอร์มประเมินและคำถาม/คำตอบทั้งชุด
 *   - กลุ่มตัวอย่าง ผู้เข้าร่วม และรอบติดตามทั้งหมด (โมดูลประเมินสุขภาพ)
 *   - กิจกรรมที่ยังไม่เผยแพร่ (รวมที่ถูกลบแบบ soft delete ไว้แล้ว)
 *
 * ทั้งหมดอยู่ใน transaction เดียว — ถ้าตารางใดพลาดจะย้อนกลับทั้งชุด ไม่เหลือสภาพครึ่ง ๆ กลาง ๆ
 */
class ResetTestData extends Command
{
    protected $signature = 'db:reset-test-data
                            {--dry-run : แสดงจำนวนแถวที่จะถูกลบ โดยยังไม่ลบจริง}
                            {--force : ข้ามคำถามยืนยัน (ใช้ตอนรันแบบไม่มีคนเฝ้า)}';

    protected $description = 'ล้างข้อมูลทดสอบ เก็บข้อมูลพื้นฐาน ผู้ใช้งาน และกิจกรรมที่เผยแพร่อยู่';

    /**
     * ตารางที่ล้างทั้งตาราง เรียงจากลูกไปหาแม่
     * ลำดับสำคัญ — ล้างผิดลำดับจะติด foreign key
     */
    private const TRUNCATE_IN_ORDER = [
        /* คำตอบและแบบฟอร์มประเมิน */
        'evl_answers',
        'evl_satisfaction_receipts',
        'evl_satisfaction_responses',
        'evl_survey_responses',
        'evl_round_batch_members',
        'evl_round_batch_target_group',
        'evl_round_batches',
        'evl_question_options',
        'evl_questions',
        'evl_form_activity',
        'evl_form_fields',
        'act_activity_reg_fields',
        'evl_forms',

        /* โมดูลประเมินสุขภาพ — ต้องมาก่อน act_registrations
           เพราะ ptp_cohort_profiles.source_registration_id ชี้ไปที่การลงทะเบียน */
        'ptp_follow_up_notes',
        'ptp_follow_up_rounds',
        'ptp_cohort_profiles',

        /* ผู้ลงทะเบียนและสิ่งที่ผูกอยู่ */
        'act_registration_interests',
        'act_payment_slips',
        'act_checkin_logs',
        'act_registrations',

        /* ตัวคน — ต้องมาหลัง act_registrations ที่อ้าง participant_id */
        'ptp_consents',
        'ptp_verification_codes',
        'ptp_purchase_items',
        'ptp_purchases',
        'ptp_participants',

        /* บันทึกการทำงานของแอดมินระหว่างทดสอบ */
        'sys_activity_logs',
    ];

    /** ตารางลูกของกิจกรรม — ลบเฉพาะแถวของกิจกรรมที่จะถูกเอาออก */
    private const ACTIVITY_CHILDREN = [
        'act_activity_area',
        'act_activity_instructor',
        'act_activity_target_group',
        'act_activity_rounds',
        'act_qr_codes',
    ];

    public function handle(): int
    {
        /* กิจกรรมที่เก็บไว้ = เผยแพร่อยู่ และยังไม่ถูกลบ */
        $keepIds = DB::table('act_activities')
            ->where('is_published', 1)
            ->whereNull('deleted_at')
            ->pluck('id');

        $dropIds = DB::table('act_activities')
            ->whereNotIn('id', $keepIds)
            ->pluck('id');

        $this->line('');
        $this->info('เก็บไว้: ข้อมูลพื้นฐาน (mst_*) · ผู้ใช้งานและบทบาท · กิจกรรมที่เผยแพร่อยู่ '.$keepIds->count().' รายการ');
        $this->line('');

        $rows = [];

        foreach (self::TRUNCATE_IN_ORDER as $table) {
            $count = DB::table($table)->count();
            if ($count > 0) {
                $rows[] = [$table, number_format($count), 'ล้างทั้งตาราง'];
            }
        }

        foreach (self::ACTIVITY_CHILDREN as $table) {
            $count = DB::table($table)->whereIn('activity_id', $dropIds)->count();
            if ($count > 0) {
                $rows[] = [$table, number_format($count), 'เฉพาะกิจกรรมที่ถูกลบ'];
            }
        }

        if ($dropIds->isNotEmpty()) {
            $rows[] = ['act_activities', number_format($dropIds->count()), 'กิจกรรมที่ไม่ได้เผยแพร่'];
        }

        if ($rows === []) {
            $this->info('ไม่มีข้อมูลทดสอบให้ลบแล้ว');

            return self::SUCCESS;
        }

        $this->table(['ตาราง', 'จำนวนแถวที่จะลบ', 'ขอบเขต'], $rows);

        if ($this->option('dry-run')) {
            $this->warn('โหมด --dry-run: ยังไม่ได้ลบอะไร');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('ยืนยันลบข้อมูลข้างต้น? การลบนี้ย้อนกลับไม่ได้', false)) {
            $this->line('ยกเลิก ไม่มีอะไรถูกลบ');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($dropIds) {
            foreach (self::TRUNCATE_IN_ORDER as $table) {
                DB::table($table)->delete();
            }

            if ($dropIds->isNotEmpty()) {
                foreach (self::ACTIVITY_CHILDREN as $table) {
                    DB::table($table)->whereIn('activity_id', $dropIds)->delete();
                }

                /* กิจกรรมที่เผยแพร่บางรายการอาจชี้ parent_event_id ไปที่อีเวนท์ที่กำลังจะถูกลบ
                   ตัดความสัมพันธ์ก่อน ไม่งั้นจะติด foreign key */
                DB::table('act_activities')->whereIn('parent_event_id', $dropIds)->update(['parent_event_id' => null]);

                DB::table('act_activities')->whereIn('id', $dropIds)->delete();
            }
        });

        $this->line('');
        $this->info('ล้างข้อมูลทดสอบเรียบร้อย');
        $this->line('เหลือกิจกรรม '.DB::table('act_activities')->count().' รายการ · ผู้ใช้งาน '.DB::table('users')->count().' คน');

        return self::SUCCESS;
    }
}
