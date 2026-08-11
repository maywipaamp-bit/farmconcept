<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ข้อมูลพื้นฐาน — docs/database-schema-proposal.md ข้อ 2.1
 *
 * ค่าที่คำนวณได้ (activityCount / totalParticipants / avgSatisfaction / memberCount)
 * ไม่มีคอลัมน์เก็บโดยเจตนา — ดูส่วนที่ 3 ของเอกสาร
 */
return new class extends Migration
{
    public function up(): void
    {
        /* รายการแบนที่ไม่มีคุณสมบัติอื่น รวมไว้ตารางเดียว (ปม F.3)
           แทนการแตกเป็น 6 ตาราง 6 หน้าจอที่มีพฤติกรรมเหมือนกันหมด */
        Schema::create('mst_options', function (Blueprint $table) {
            $table->id();
            $table->string('option_group', 40);
            $table->string('code', 60);
            $table->string('label', 160);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['option_group', 'code']);
            $table->index(['option_group', 'is_active', 'sort_order']);
        });

        Schema::create('mst_districts', function (Blueprint $table) {
            $table->id();
            $table->string('province', 120);
            $table->string('name', 120);
            $table->unique(['province', 'name']);
        });

        Schema::create('mst_areas', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 160);
            $table->string('province', 120)->nullable();
            $table->string('district', 120)->nullable();
            $table->string('area_type', 60)->nullable();
            $table->string('area_group', 60)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('partner_org', 160)->nullable();
            $table->string('coordinator_name', 120)->nullable();
            $table->string('coordinator_phone', 30)->nullable();
            $table->string('coordinator_position', 120)->nullable();
            $table->string('map_url')->nullable();
            $table->string('status', 40)->default('ดำเนินการอยู่');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('status');
            $table->index(['province', 'district']);
        });

        Schema::create('mst_target_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 120);
            $table->string('age_range', 60)->nullable();
            $table->unsignedInteger('target_count')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('mst_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 160);
            $table->string('category', 120)->nullable();
            $table->string('status', 40)->default('ดำเนินการอยู่');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('mst_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('mst_programs')->cascadeOnDelete();
            $table->string('name', 160);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['program_id', 'sort_order']);
        });

        Schema::create('mst_instructors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 160);
            $table->string('phone', 30)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('expertise', 255)->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('mst_instructor_expertises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('mst_instructors')->cascadeOnDelete();
            $table->string('name', 120);
        });

        Schema::create('mst_instructor_course', function (Blueprint $table) {
            $table->foreignId('instructor_id')->constrained('mst_instructors')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('mst_courses')->cascadeOnDelete();
            $table->primary(['instructor_id', 'course_id']);
        });

        Schema::create('mst_activity_formats', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 60);
            $table->string('icon', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /* offset_days ต้อง UNIQUE — ถ้าซ้ำ คนหนึ่งคนจะได้รอบครบกำหนดวันเดียวกันสองรอบ
           กติกานี้บังคับอยู่แล้วฝั่งหน้าจอที่ followup-template-service.js บรรทัด 142 */
        Schema::create('mst_follow_up_round_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 120);
            $table->unsignedSmallInteger('offset_days')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('line_notify')->default(true);
            $table->unsignedSmallInteger('notify_days_before')->default(7);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_follow_up_round_templates');
        Schema::dropIfExists('mst_activity_formats');
        Schema::dropIfExists('mst_instructor_course');
        Schema::dropIfExists('mst_instructor_expertises');
        Schema::dropIfExists('mst_instructors');
        Schema::dropIfExists('mst_courses');
        Schema::dropIfExists('mst_programs');
        Schema::dropIfExists('mst_target_groups');
        Schema::dropIfExists('mst_areas');
        Schema::dropIfExists('mst_districts');
        Schema::dropIfExists('mst_options');
    }
};
