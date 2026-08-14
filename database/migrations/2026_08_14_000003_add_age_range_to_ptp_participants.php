<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * หน้ากลุ่มตัวอย่างถามช่วงอายุเป็น dropdown จาก master กลาง ไม่ได้ถามปีเกิด
 * เจ้าหน้าที่ที่กรอกแทนหน้างานมักไม่รู้ปีเกิดของคนที่พามา แต่ตอบช่วงอายุได้
 *
 * เก็บเป็น FK → mst_options (option_group = age_range) ชุดเดียวกับ
 * act_registrations.age_range_id เพื่อให้รายงานรวมข้ามสองทางเข้าได้
 * birth_year ยังอยู่เหมือนเดิมสำหรับคนที่กรอกปีเกิดมาจริง — Participant::ageBand()
 * ยังคำนวณจาก birth_year ก่อนเสมอ แล้วค่อยตกมาที่คอลัมน์นี้
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ptp_participants', function (Blueprint $table) {
            $table->foreignId('age_range_id')->nullable()->after('birth_year')->constrained('mst_options');
        });
    }

    public function down(): void
    {
        Schema::table('ptp_participants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('age_range_id');
        });
    }
};
