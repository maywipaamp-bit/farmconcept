<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evl_forms', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('registration_mode', 20)->nullable()->after('is_anonymous');
            $table->unsignedTinyInteger('max_participants')->nullable()->after('registration_mode');
            $table->timestamp('published_at')->nullable()->after('max_participants');
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->index('type', 'evl_forms_type_index');
            $table->index(['type', 'status'], 'evl_forms_type_status_index');
        });

        Schema::create('evl_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('evl_forms')->cascadeOnDelete();
            $table->string('field_key', 60);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['form_id', 'field_key']);
            $table->index(['form_id', 'sort_order']);
        });

        Schema::create('evl_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('evl_questions')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('label', 255);
            $table->string('value', 255);
            $table->boolean('is_other')->default(false);
            $table->timestamps();

            $table->unique(['question_id', 'sort_order']);
            $table->unique(['question_id', 'value']);
        });

        Schema::table('evl_answers', function (Blueprint $table) {
            $table->foreignId('option_id')->nullable()->after('question_id')
                ->constrained('evl_question_options')->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE evl_answers MODIFY response_type ENUM('registration','satisfaction','survey') NOT NULL");
        }

        DB::table('evl_forms')->whereIn('type', ['ตอนลงทะเบียน', 'แบบลงทะเบียน'])->update(['type' => 'registration']);
        DB::table('evl_forms')->whereIn('type', ['หลังกิจกรรม', 'แบบประเมินความพึงพอใจ'])->update(['type' => 'post_activity']);
        DB::table('evl_forms')->whereIn('type', ['ติดตามสุขภาพ', 'แบบติดตามสุขภาพ'])->update(['type' => 'health_follow_up']);
        DB::table('evl_forms')->whereIn('status', ['เปิดใช้งาน', 'เผยแพร่แล้ว'])->update(['status' => 'active']);
        DB::table('evl_forms')->where('status', 'ฉบับร่าง')->update(['status' => 'draft']);
        DB::table('evl_forms')->where('status', 'ปิดใช้งาน')->update(['status' => 'inactive']);
        DB::table('evl_forms')->where('status', 'active')->whereNull('published_at')->update(['published_at' => now()]);
    }

    public function down(): void
    {
        if (DB::table('evl_answers')->where('response_type', 'registration')->exists()) {
            throw new RuntimeException('Rollback ไม่ได้: มีคำตอบแบบลงทะเบียน กรุณาสำรองและย้ายข้อมูลก่อน');
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE evl_answers MODIFY response_type ENUM('satisfaction','survey') NOT NULL");
        }

        Schema::table('evl_answers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('option_id');
        });

        Schema::dropIfExists('evl_question_options');
        Schema::dropIfExists('evl_form_fields');

        Schema::table('evl_forms', function (Blueprint $table) {
            $table->dropIndex('evl_forms_type_status_index');
            $table->dropIndex('evl_forms_type_index');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['description', 'registration_mode', 'max_participants', 'published_at']);
        });

        DB::table('evl_forms')->where('type', 'registration')->update(['type' => 'ตอนลงทะเบียน']);
        DB::table('evl_forms')->where('type', 'post_activity')->update(['type' => 'หลังกิจกรรม']);
        DB::table('evl_forms')->where('type', 'health_follow_up')->update(['type' => 'แบบติดตามสุขภาพ']);
        DB::table('evl_forms')->where('status', 'active')->update(['status' => 'เปิดใช้งาน']);
        DB::table('evl_forms')->where('status', 'draft')->update(['status' => 'ฉบับร่าง']);
        DB::table('evl_forms')->where('status', 'inactive')->update(['status' => 'ปิดใช้งาน']);
    }
};
