<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เติมส่วนที่ขาดของ "รอบติดตาม" — ตารางหลักสองตัวมีอยู่แล้วตั้งแต่ create_evl_tables
 * (evl_round_batches · evl_round_batch_members) ที่นี่จึงเพิ่มเฉพาะสามอย่างที่ยังไม่มี
 *
 * 1. notification_template — ข้อความ LINE ของรอบนั้น เก็บเป็น snapshot รายรอบ
 *    ไม่ใช่ค่ากลางค่าเดียว เพราะแอดมินแก้ข้อความก่อนกดสร้างได้ และรอบที่ส่งไปแล้ว
 *    ต้องพิสูจน์ย้อนหลังได้ว่าส่งข้อความหน้าตาแบบไหนออกไป
 * 2. pivot กลุ่มเป้าหมาย — รอบหนึ่งเลือกได้หลายกลุ่ม เก็บไว้เพื่อบอกว่ารอบนี้
 *    "ตั้งใจ" ครอบคลุมกลุ่มไหน ต่างจากสมาชิกจริงที่แอดมินติ๊กเลือกทีหลัง
 * 3. notify_channel — ช่องทางที่ใช้ส่ง แยกจาก notify_result ที่เป็น "ผลลัพธ์"
 *    ถ้าเก็บรวมกันจะแยกไม่ออกระหว่าง "ส่ง LINE แล้วล้มเหลว" กับ "ไม่มีช่องทางให้ส่ง"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evl_round_batches', function (Blueprint $table) {
            $table->text('notification_template')->nullable()->after('form_id');
        });

        Schema::create('evl_round_batch_target_group', function (Blueprint $table) {
            $table->foreignId('batch_id')->constrained('evl_round_batches')->cascadeOnDelete();
            $table->foreignId('target_group_id')->constrained('mst_target_groups')->cascadeOnDelete();
            $table->primary(['batch_id', 'target_group_id']);
        });

        Schema::table('evl_round_batch_members', function (Blueprint $table) {
            $table->string('notify_channel', 20)->nullable()->after('notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('evl_round_batch_members', function (Blueprint $table) {
            $table->dropColumn('notify_channel');
        });

        Schema::dropIfExists('evl_round_batch_target_group');

        Schema::table('evl_round_batches', function (Blueprint $table) {
            $table->dropColumn('notification_template');
        });
    }
};
