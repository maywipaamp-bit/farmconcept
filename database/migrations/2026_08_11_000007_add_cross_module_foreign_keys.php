<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FK ที่ข้ามโมดูลและอ้างตารางที่สร้างทีหลัง — แยกไว้ท้ายสุดเพื่อไม่ให้ลำดับ Migration วนกลับ
 * docs/database-schema-proposal.md ส่วนที่ 4
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->after('status')->constrained('mst_areas')->nullOnDelete();
        });

        /* บันทึกว่าคนนี้เข้ากลุ่มตัวอย่างผ่านการลงทะเบียนกิจกรรมครั้งไหน
           ทำให้ตอบได้ว่า "กิจกรรมไหนป้อนคนเข้ากลุ่มตัวอย่างได้มากที่สุด" โดยไม่ต้องเดาจากชื่อ */
        Schema::table('ptp_cohort_profiles', function (Blueprint $table) {
            $table->foreignId('source_registration_id')->nullable()->after('source_type')
                ->constrained('act_registrations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ptp_cohort_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_registration_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_id');
        });
    }
};
