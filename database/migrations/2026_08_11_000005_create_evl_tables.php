<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * แบบประเมิน — docs/database-schema-proposal.md ปม C และข้อ 2.5
 *
 * ปม C: แบบประเมินความพึงพอใจกิจกรรม "นิรนาม" เป็นข้อกำหนด ไม่ใช่ตัวเลือก
 *       จึงแยกตารางแทนการใช้ flag — evl_satisfaction_responses ไม่มีคอลัมน์ใด
 *       ชี้ไปยังคนเลย ทำให้ระบุตัวตนย้อนกลับไม่ได้แม้จะอยากทำ
 *       (CHECK constraint ทำแทนไม่ได้ เพราะ MySQL อ้างข้ามตารางไม่ได้)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evl_forms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 255);
            $table->string('type', 60)->nullable();
            $table->string('status', 40)->default('ฉบับร่าง');
            $table->boolean('is_anonymous')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('status');
        });

        /* dimension = การจัดกลุ่มรายด้านสำหรับ Radar Chart (evaluationTopics[].dimension)
           หัวข้อความพึงพอใจทั้ง 5/6 ข้อเป็นแถวในตารางนี้ ไม่ต้องมีตารางหัวข้อแยก */
        Schema::create('evl_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('evl_forms')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('question_type', 60);
            $table->string('text', 500);
            $table->string('dimension', 120)->nullable();
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->index(['form_id', 'sort_order']);
        });

        /* slot แยกแบบฟอร์ม "ตอนลงทะเบียน" (ระบุตัวตนได้) ออกจาก "หลังจบกิจกรรม" (นิรนาม)
           ตรงกับที่ assets/js/activity-create.js บรรทัด 66,73 แยกไว้แล้ว
           นี่คือชั้นที่ 3 ของแบบลงทะเบียน — คำถามอิสระเฉพาะกิจกรรมนั้น */
        Schema::create('evl_form_activity', function (Blueprint $table) {
            $table->foreignId('form_id')->constrained('evl_forms')->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained('act_activities')->cascadeOnDelete();
            $table->enum('slot', ['registration', 'post_survey']);
            $table->primary(['form_id', 'activity_id', 'slot']);
        });

        /* ห้ามเพิ่มคอลัมน์ใดที่ชี้ไปยังคนในตารางนี้ ไม่ว่ากรณีใด */
        Schema::create('evl_satisfaction_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('evl_forms');
            $table->foreignId('activity_id')->constrained('act_activities')->cascadeOnDelete();
            $table->foreignId('activity_round_id')->nullable()->constrained('act_activity_rounds')->nullOnDelete();
            $table->timestamp('submitted_at');

            $table->index(['activity_id', 'submitted_at']);
        });

        /* บอกได้แค่ว่า "คนนี้ตอบแล้ว" ไว้ทวงคนที่ยังไม่ตอบและกันตอบซ้ำ
           ห้ามมีคอลัมน์ใดเชื่อมไปยัง evl_satisfaction_responses — ถ้ามีเมื่อไหร่ ความนิรนามหายทันที
           กิจกรรมที่ไม่เปิดลงทะเบียนจะไม่มีแถวที่นี่ ยอมรับว่าไม่มีอัตราการตอบ (ข้อ 7.8) */
        Schema::create('evl_satisfaction_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('evl_forms')->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained('act_registrations')->cascadeOnDelete();
            $table->timestamp('submitted_at');

            $table->unique(['form_id', 'registration_id']);
        });

        /* แบบติดตามสุขภาพ — ระบุตัวตนได้และต้องได้
           ตรงกับโครงที่ assets/js/survey-data.js บรรทัด 7 เขียนไว้ */
        Schema::create('evl_survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('evl_forms');
            $table->foreignId('participant_id')->constrained('ptp_participants')->cascadeOnDelete();
            $table->foreignId('cohort_round_id')->unique()->constrained('ptp_follow_up_rounds')->cascadeOnDelete();
            $table->timestamp('submitted_at');

            $table->index(['participant_id', 'submitted_at']);
        });

        /* ใช้ร่วมกันทั้งสองชนิดผ่าน response_type — ไม่ใช้ FK จริงเพราะชี้ได้สองตาราง
           ความถูกต้องของคู่ (type, id) บังคับที่ Service layer */
        Schema::create('evl_answers', function (Blueprint $table) {
            $table->id();
            $table->enum('response_type', ['satisfaction', 'survey']);
            $table->unsignedBigInteger('response_id');
            $table->foreignId('question_id')->constrained('evl_questions');
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('text_value')->nullable();

            $table->index(['response_type', 'response_id']);
            $table->index('question_id');
        });

        Schema::create('evl_round_batches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 200);
            $table->date('due_from');
            $table->date('due_to');
            $table->foreignId('form_id')->nullable()->constrained('evl_forms');
            $table->string('state', 40)->default('รอเริ่ม');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('state');
            $table->index(['due_from', 'due_to']);
        });

        Schema::create('evl_round_batch_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('evl_round_batches')->cascadeOnDelete();
            $table->foreignId('cohort_profile_id')->constrained('ptp_cohort_profiles')->cascadeOnDelete();
            $table->foreignId('follow_up_round_id')->constrained('ptp_follow_up_rounds')->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->string('notify_result', 60)->nullable();
            $table->string('offline_kind', 60)->nullable();
            $table->string('offline_note', 500)->nullable();
            $table->timestamp('offline_at')->nullable();
            $table->foreignId('offline_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['batch_id', 'follow_up_round_id']);
            $table->index('cohort_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evl_round_batch_members');
        Schema::dropIfExists('evl_round_batches');
        Schema::dropIfExists('evl_answers');
        Schema::dropIfExists('evl_survey_responses');
        Schema::dropIfExists('evl_satisfaction_receipts');
        Schema::dropIfExists('evl_satisfaction_responses');
        Schema::dropIfExists('evl_form_activity');
        Schema::dropIfExists('evl_questions');
        Schema::dropIfExists('evl_forms');
    }
};
