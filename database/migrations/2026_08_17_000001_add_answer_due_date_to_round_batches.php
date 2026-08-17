<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เส้นตายการตอบของรอบติดตาม
 *
 * ของเดิมมี due_from / due_to อยู่แล้ว แต่สองค่านั้นคือ "ช่วงวันครบกำหนดของใบติดตามรายคน"
 * ใช้กรองว่าใครเข้ารอบนี้บ้าง ไม่ใช่เส้นตายที่ผู้ตอบต้องตอบภายใน — คนละความหมายกัน
 *
 * ให้ว่างได้ รอบที่สร้างไว้ก่อนหน้านี้จึงไม่ต้องแก้ และยังใช้พฤติกรรมเดิม
 * (อ้างวันครบกำหนดของใบรายคน) ส่วนรอบใหม่ที่กำหนดวันไว้จะใช้วันนั้นแทน
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evl_round_batches', function (Blueprint $table) {
            $table->date('answer_due_date')->nullable()->after('due_to');
        });
    }

    public function down(): void
    {
        Schema::table('evl_round_batches', function (Blueprint $table) {
            $table->dropColumn('answer_due_date');
        });
    }
};
