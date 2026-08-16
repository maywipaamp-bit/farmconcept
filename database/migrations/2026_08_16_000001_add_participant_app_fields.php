<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ส่วนที่หน้าจอฝั่งผู้เข้าร่วม (QR ติดตามสุขภาพ) ต้องใช้เพิ่ม
 *
 * 1. line_notify — สวิตช์ "แจ้งเตือนรอบถัดไปผ่าน LINE" ที่แดชบอร์ดผู้เข้าร่วม
 *    ต้องเป็นค่าของแต่ละคน ไม่ใช่ค่ากลาง เพราะเป็นการยินยอมรับข้อความส่วนบุคคล
 *    ค่าเริ่มต้นเปิด — คนที่ผูก LINE ไว้แล้วแปลว่าตั้งใจให้ติดต่อทางนั้นอยู่แล้ว
 *
 * 2. submitted_by_participant_id — ใครเป็นคนกรอกให้ ถ้าไม่ใช่เจ้าตัว
 *    (อาสาสมัครหรือคนในบ้านกรอกแทนผู้สูงอายุ ซึ่งเป็นเรื่องปกติของงานภาคสนาม)
 *    ต้องรู้ว่าคำตอบชุดไหนมาจากปากเจ้าตัวเอง เพราะมีผลต่อการตีความข้อมูลวิจัย
 *    null = เจ้าตัวกรอกเอง
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ptp_participants', function (Blueprint $table) {
            $table->boolean('line_notify')->default(true)->after('line_user_id');
        });

        Schema::table('evl_survey_responses', function (Blueprint $table) {
            $table->foreignId('submitted_by_participant_id')->nullable()->after('participant_id')
                ->constrained('ptp_participants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('evl_survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by_participant_id');
        });

        Schema::table('ptp_participants', function (Blueprint $table) {
            $table->dropColumn('line_notify');
        });
    }
};
