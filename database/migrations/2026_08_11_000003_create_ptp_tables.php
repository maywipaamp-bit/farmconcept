<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ผู้เข้าร่วมและกลุ่มตัวอย่าง — docs/database-schema-proposal.md ข้อ 2.3
 *
 * ปม A: คนหนึ่งคน = หนึ่งแถวใน ptp_participants เสมอ
 *       สถานะ "เป็นกลุ่มตัวอย่าง" แยกเป็น ptp_cohort_profiles แบบ 1:1
 *       ผู้เข้าร่วมทั่วไปจึงไม่มีคอลัมน์ของกลุ่มตัวอย่างค้างเป็น NULL
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ptp_participants', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('person_code', 30)->unique();

            /* ชั้นที่ 1 ของแบบลงทะเบียน — แกนของตัวตน ปิดไม่ได้ แก้ไม่ได้
               ถ้าปล่อยเป็นคำถามอิสระ ระบบจะ dedupe คนไม่ได้และออก person_code ไม่ได้ */
            $table->string('name', 160);
            $table->string('phone', 30);
            $table->string('email', 160)->nullable();

            /* ชั้นที่ 2 — โครงตายตัว เปิด/ปิดรายกิจกรรมได้ที่ act_activity_reg_fields
               ค่าทั้งหมดมาจาก master กลาง กิจกรรมตั้งชื่อตัวเลือกเองไม่ได้
               ไม่งั้นรายงานรวมข้ามกิจกรรมไม่ได้ */
            $table->enum('gender', ['male', 'female', 'other', 'undisclosed'])->nullable();
            $table->unsignedSmallInteger('birth_year')->nullable();
            $table->foreignId('occupation_id')->nullable()->constrained('mst_options');
            $table->string('occupation_raw', 160)->nullable();
            $table->foreignId('area_id')->nullable()->constrained('mst_areas');
            $table->foreignId('target_group_id')->nullable()->constrained('mst_target_groups');
            $table->foreignId('source_channel_id')->nullable()->constrained('mst_options');
            $table->foreignId('contact_channel_id')->nullable()->constrained('mst_options');

            $table->string('source', 60)->nullable();
            $table->boolean('has_caregiver')->default(false);
            $table->string('caregiver_name', 160)->nullable();
            $table->string('caregiver_relation', 60)->nullable();
            $table->string('caregiver_phone', 30)->nullable();

            $table->string('status', 40)->default('ใช้งานอยู่');
            $table->string('project_status', 40)->default('เข้าร่วม');

            /* สถานะความยินยอมปัจจุบัน — ประวัติเต็มอยู่ที่ ptp_consents
               คอลัมน์นี้มีไว้กรองในตารางเท่านั้น ห้ามใช้เป็นหลักฐาน */
            $table->string('consent_status', 40)->default('รอยืนยัน');

            /* บัญชี LINE หนึ่งบัญชีผูกได้คนเดียว — ผูกซ้อนต้องถูกปฏิเสธ
               ตามเหตุผลที่ assets/js/cohort-data.js บรรทัด 156-165 อธิบายไว้ */
            $table->string('line_user_id', 80)->nullable()->unique();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone');
            $table->index('status');
            $table->index(['area_id', 'target_group_id']);
        });

        /* ความยินยอม PDPA ต้องเป็นระเบียนที่ตรวจย้อนหลังได้ ไม่ใช่คำตอบข้อหนึ่งในฟอร์ม
           ตารางนี้ append-only — เปลี่ยนสถานะเมื่อไหร่ให้ INSERT แถวใหม่ ห้าม UPDATE แถวเดิม
           ไม่งั้นพิสูจน์ไม่ได้ว่า ณ วันที่เก็บข้อมูล เขายินยอมไว้จริง */
        Schema::create('ptp_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('ptp_participants')->cascadeOnDelete();
            $table->string('status', 40);
            $table->string('consent_version', 20)->nullable();
            $table->date('consented_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('note', 255)->nullable();
            $table->string('recorded_via', 30)->default('registration');
            $table->foreignId('recorded_by')->nullable()->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['participant_id', 'created_at']);
        });

        Schema::create('ptp_cohort_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->unique()->constrained('ptp_participants')->cascadeOnDelete();
            $table->string('cohort_code', 20)->unique();
            $table->date('entry_date');
            $table->string('source_type', 30)->default('walk_in');
            $table->timestamp('stopped_at')->nullable();
            $table->string('stopped_reason', 255)->nullable();
            $table->foreignId('stopped_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('entry_date');
        });

        /* name และ offset_days เป็น snapshot ห้าม join กลับไปที่ template
           ถ้าอ่าน template สด พอแอดมินแก้จำนวนวัน วันครบกำหนดของคนที่ตอบไปแล้วจะขยับทั้งกระดาน
           เหตุผลเต็มอยู่ที่ assets/js/cohort-data.js บรรทัด 94-97 */
        Schema::create('ptp_follow_up_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cohort_profile_id')->constrained('ptp_cohort_profiles')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('mst_follow_up_round_templates')->nullOnDelete();
            $table->string('name', 120);
            $table->unsignedSmallInteger('offset_days');
            $table->date('due_date');
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['cohort_profile_id', 'offset_days']);
            $table->index('due_date');
            $table->index('answered_at');
        });

        Schema::create('ptp_follow_up_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('ptp_participants')->cascadeOnDelete();
            $table->string('source', 40);
            $table->string('kind', 60)->nullable();
            $table->timestamp('noted_at');
            $table->text('body');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['participant_id', 'noted_at']);
        });

        Schema::create('ptp_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('ptp_participants')->cascadeOnDelete();
            $table->string('store_name', 160);
            $table->foreignId('channel_id')->nullable()->constrained('mst_options');
            $table->date('order_date');
            $table->string('status', 40)->default('รอดำเนินการ');
            $table->decimal('amount', 10, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['participant_id', 'order_date']);
        });

        /* แยก items ออกมาตั้งแต่ต้น เพื่อให้เฟสถัดไปเปลี่ยน product_name เป็น product_id
           ได้โดยไม่ต้องรื้อ UI — ตามหมายเหตุใน assets/js/mock-data.js บรรทัด 800-802 */
        Schema::create('ptp_purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('ptp_purchases')->cascadeOnDelete();
            $table->string('product_name', 160);
            $table->unsignedInteger('quantity')->default(1);
        });

        /* ยืนยันตัวตนก่อนตอบแบบประเมินสุขภาพ (QR เดียวทั้งระบบ)
           ถ้าให้กรอกแค่เบอร์แล้วเข้าได้เลย ใครก็ตอบแทนคนอื่นได้ด้วยการเดาเบอร์ */
        Schema::create('ptp_verification_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('ptp_participants')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('destination', 160);
            $table->string('code_hash', 255);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['participant_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ptp_verification_codes');
        Schema::dropIfExists('ptp_purchase_items');
        Schema::dropIfExists('ptp_purchases');
        Schema::dropIfExists('ptp_follow_up_notes');
        Schema::dropIfExists('ptp_follow_up_rounds');
        Schema::dropIfExists('ptp_cohort_profiles');
        Schema::dropIfExists('ptp_consents');
        Schema::dropIfExists('ptp_participants');
    }
};
