<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * กิจกรรม — docs/database-schema-proposal.md ข้อ 2.4 และส่วนที่ 7
 *
 * `registered` ไม่มีคอลัมน์เก็บ — นับจาก act_registrations เสมอ (ส่วนที่ 3)
 */
return new class extends Migration
{
    /** ชั้นที่ 2 ของแบบลงทะเบียน — คีย์ชุดปิดตาย กิจกรรมเพิ่มคีย์ใหม่เองไม่ได้ */
    private const REG_FIELD_KEYS = ['gender', 'birth_year', 'occupation', 'area', 'target_group', 'source_channel'];

    public function up(): void
    {
        Schema::create('act_activities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('type', 40)->nullable();
            $table->string('participant_type', 40)->nullable();
            $table->foreignId('program_id')->nullable()->constrained('mst_programs');
            $table->foreignId('course_id')->nullable()->constrained('mst_courses');
            $table->foreignId('format_id')->nullable()->constrained('mst_activity_formats');
            $table->string('data_source', 60)->nullable();
            $table->string('venue_mode', 60)->nullable();
            $table->string('registration_mode', 60)->nullable();
            $table->string('status', 40)->default('ฉบับร่าง');

            /* สี่สวิตช์ที่เปิด/ปิดอิสระต่อกัน ตาม JOIN_FLAGS ใน assets/js/activity-create.js บรรทัด 53-57 */
            $table->boolean('requires_registration')->default(false);
            $table->boolean('requires_checkin')->default(false);
            $table->boolean('has_post_survey')->default(false);
            $table->boolean('has_fee')->default(false);
            $table->decimal('fee', 10, 2)->default(0);

            $table->unsignedInteger('capacity')->default(0);
            $table->string('organizer', 200)->nullable();
            $table->string('cover_image_path')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('checkin_start_at')->nullable();
            $table->timestamp('checkin_end_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('publish_start_at')->nullable();
            $table->timestamp('publish_end_at')->nullable();
            $table->string('visibility', 40)->default('สาธารณะ');
            $table->boolean('is_featured')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('start_date');
            $table->index('participant_type');
            /* ตั้งชื่อเอง — ชื่ออัตโนมัติของ Laravel ยาว 66 ตัว เกินเพดาน 64 ตัวของ MySQL */
            $table->index(['is_published', 'publish_start_at', 'publish_end_at'], 'act_activities_publish_window_index');
        });

        /* ชั้นที่ 2 ของแบบลงทะเบียน — กิจกรรมเลือกได้แค่ "ถามหรือไม่ถาม" และ "บังคับหรือไม่"
           ตัวเลือกของแต่ละฟิลด์มาจาก master กลาง กิจกรรมตั้งชื่อตัวเลือกเองไม่ได้
           ไม่งั้นกิจกรรม A เขียน "วัยทำงาน" กิจกรรม B เขียน "คนทำงาน" แล้วรวมรายงานไม่ได้ */
        Schema::create('act_activity_reg_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('act_activities')->cascadeOnDelete();
            $table->enum('field_key', self::REG_FIELD_KEYS);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->unique(['activity_id', 'field_key']);
        });

        Schema::create('act_activity_area', function (Blueprint $table) {
            $table->foreignId('activity_id')->constrained('act_activities')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('mst_areas')->cascadeOnDelete();
            $table->primary(['activity_id', 'area_id']);
        });

        Schema::create('act_activity_instructor', function (Blueprint $table) {
            $table->foreignId('activity_id')->constrained('act_activities')->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('mst_instructors')->cascadeOnDelete();
            $table->primary(['activity_id', 'instructor_id']);
        });

        Schema::create('act_activity_target_group', function (Blueprint $table) {
            $table->foreignId('activity_id')->constrained('act_activities')->cascadeOnDelete();
            $table->foreignId('target_group_id')->constrained('mst_target_groups')->cascadeOnDelete();
            $table->primary(['activity_id', 'target_group_id']);
        });

        /* ชื่อ "rounds" ตามที่ assets/js/satisfaction-service.js บรรทัด 103 คาดไว้
           (TFC_MOCK.activitySessions เป็นชื่อฝั่งหน้าจอเท่านั้น) */
        Schema::create('act_activity_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('act_activities')->cascadeOnDelete();
            $table->date('round_date');
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->string('location', 200)->nullable();
            $table->unsignedInteger('capacity')->default(0);
            $table->timestamps();

            $table->index(['activity_id', 'round_date']);
        });

        Schema::create('act_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();
            $table->foreignId('activity_id')->constrained('act_activities')->cascadeOnDelete();
            $table->foreignId('activity_round_id')->nullable()->constrained('act_activity_rounds')->nullOnDelete();

            /* Walk-in ที่ยังไม่มี profile ปล่อยว่างไว้ได้ (ปม E)
               ระบบจับคู่ให้อัตโนมัติด้วยเบอร์โทรซึ่งเป็นแกนตัวตนของชั้นที่ 1 */
            $table->foreignId('participant_id')->nullable()->constrained('ptp_participants')->nullOnDelete();

            /* ชั้นที่ 1 — ฟิกตายตัว ทุกกิจกรรมต้องถาม */
            $table->string('name', 160);
            $table->string('phone', 30);
            $table->string('email', 160)->nullable();

            /* ชั้นที่ 2 — เก็บคอลัมน์เดียวกับ ptp_participants เสมอ
               กิจกรรมที่ปิดฟิลด์ไว้จะเป็น NULL ไม่ใช่เก็บที่อื่นคนละรูปแบบ */
            $table->enum('gender', ['male', 'female', 'other', 'undisclosed'])->nullable();
            $table->unsignedSmallInteger('birth_year')->nullable();
            $table->foreignId('occupation_id')->nullable()->constrained('mst_options');
            $table->string('occupation_raw', 160)->nullable();
            $table->foreignId('area_id')->nullable()->constrained('mst_areas');
            $table->foreignId('target_group_id')->nullable()->constrained('mst_target_groups');
            $table->foreignId('source_channel_id')->nullable()->constrained('mst_options');

            $table->string('dietary_note', 255)->nullable();
            $table->string('payment_status', 40)->default('ยังไม่ชำระ');
            $table->string('checkin_status', 40)->default('ยังไม่เข้าร่วม');
            $table->timestamp('registered_at');
            $table->timestamp('checked_in_at')->nullable();
            $table->boolean('is_manual_entry')->default(false);
            $table->timestamps();

            $table->index(['activity_id', 'checkin_status']);
            $table->index(['activity_id', 'payment_status']);
            $table->index('phone');
            $table->index('registered_at');
        });

        Schema::create('act_registration_interests', function (Blueprint $table) {
            $table->foreignId('registration_id')->constrained('act_registrations')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('mst_options')->cascadeOnDelete();
            $table->primary(['registration_id', 'option_id']);
        });

        /* ไม่ UNIQUE ที่ registration_id — สลิปที่ถูกปฏิเสธต้องแนบใหม่ได้ ประวัติเดิมต้องอยู่ครบ
           ไฟล์เก็บนอก public/ และเสิร์ฟผ่าน route ที่ตรวจสิทธิ์เท่านั้น */
        Schema::create('act_payment_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('act_registrations')->cascadeOnDelete();
            $table->string('file_path');
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->string('status', 40)->default('รอตรวจสอบ');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reject_reason', 255)->nullable();
            $table->timestamps();

            $table->index('status');
        });

        /* audit log ของการเช็คอิน — assets/js/checkin-service.js บรรทัด 12-14 บังคับไว้
           performed_at ต้องมาจากนาฬิกาเซิร์ฟเวอร์ ห้ามรับเวลาจากเครื่องหน้างาน */
        Schema::create('act_checkin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('act_registrations')->cascadeOnDelete();
            $table->enum('action', ['check_in', 'undo']);
            $table->enum('method', ['scan', 'staff']);
            $table->foreignId('performed_by')->nullable()->constrained('users');
            $table->timestamp('performed_at')->useCurrent();

            $table->index(['registration_id', 'performed_at']);
        });

        /* activity_id เป็น NULL ได้ เพื่อรองรับ QR ถาวรของระบบติดตามสุขภาพ (แถวเดียวทั้งระบบ)
           token ต้องสุ่ม ห้าม derive จาก activity_id ไม่งั้นเดา URL ของกิจกรรมที่ยังไม่เผยแพร่ได้ */
        Schema::create('act_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->nullable()->constrained('act_activities')->cascadeOnDelete();
            $table->enum('purpose', ['public', 'checkin', 'post_survey', 'health']);
            $table->string('token', 64)->unique();
            $table->string('target_url');
            $table->boolean('is_active')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('scan_count')->default(0);
            $table->timestamps();

            $table->unique(['activity_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('act_qr_codes');
        Schema::dropIfExists('act_checkin_logs');
        Schema::dropIfExists('act_payment_slips');
        Schema::dropIfExists('act_registration_interests');
        Schema::dropIfExists('act_registrations');
        Schema::dropIfExists('act_activity_rounds');
        Schema::dropIfExists('act_activity_target_group');
        Schema::dropIfExists('act_activity_instructor');
        Schema::dropIfExists('act_activity_area');
        Schema::dropIfExists('act_activity_reg_fields');
        Schema::dropIfExists('act_activities');
    }
};
