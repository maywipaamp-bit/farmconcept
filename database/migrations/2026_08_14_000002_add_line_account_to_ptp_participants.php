<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ชื่อและรูปโปรไฟล์ LINE ของผู้เข้าร่วม — ได้จากการกด "เข้าสู่ระบบด้วย LINE" ที่หน้าลงทะเบียน
 *
 * คอลัมน์ line_user_id (unique) มีอยู่แล้วตั้งแต่ 2026_08_11_000003_create_ptp_tables
 * และมีหน้าจออื่นอ่านอยู่ (CohortController · RoundBatchMember) จึงไม่แตะ
 * เพิ่มเฉพาะสองคอลัมน์ที่ยังไม่มี เพื่อให้แสดงว่าใครเป็นเจ้าของบัญชีได้โดยไม่ต้องยิงถาม LINE ซ้ำ
 *
 * ทั้งคู่เป็น nullable — ผู้เข้าร่วมเดิมและคนที่ลงทะเบียนด้วยเบอร์โทรไม่ได้รับผลกระทบ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ptp_participants', function (Blueprint $table) {
            $table->string('line_display_name', 160)->nullable()->after('line_user_id');
            $table->string('line_picture_url', 512)->nullable()->after('line_display_name');
        });
    }

    public function down(): void
    {
        Schema::table('ptp_participants', function (Blueprint $table) {
            $table->dropColumn(['line_display_name', 'line_picture_url']);
        });
    }
};
