<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ระบบ — docs/database-schema-proposal.md ข้อ 2.6
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('detail', 500)->nullable();
            $table->string('type', 30)->default('info');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });

        /* subject_type/subject_id เป็น morph key — ไม่ใช้ FK เพราะชี้ได้หลายตาราง */
        Schema::create('sys_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('detail', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_activity_logs');
        Schema::dropIfExists('sys_notifications');
    }
};
