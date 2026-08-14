<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * แบบลงทะเบียนสาธารณะ (hifi) ถามช่วงอายุเป็น dropdown จาก master กลาง
 * เก็บเป็น FK → mst_options (option_group = age_range) เหมือน occupation_id
 * ไม่ใช่ birth_year เพราะผู้ลงทะเบียนหน้างานไม่รู้ปีเกิดของผู้ร่วมที่พามาด้วย
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('act_registrations', function (Blueprint $table) {
            $table->foreignId('age_range_id')->nullable()->after('birth_year')->constrained('mst_options');
        });
    }

    public function down(): void
    {
        Schema::table('act_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('age_range_id');
        });
    }
};
