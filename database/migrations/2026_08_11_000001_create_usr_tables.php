<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ผู้ใช้และสิทธิ์ — docs/database-schema-proposal.md ข้อ 2.2
 *
 * ตาราง `users` เป็นตารางแกนของ Laravel จึงคงชื่อเดิมไม่ใส่ prefix (ปม G)
 * ที่เหลือใช้ prefix `usr_` ตาม docs/database-standard.md
 *
 * สิทธิ์แบบกว้าง (activities/payments/reports) ไม่มีคอลัมน์เก็บ — คำนวณจาก
 * usr_role_menu_permissions เหมือนที่ TFC.hasPermission() ทำอยู่ในต้นแบบ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->unique()->after('id');
            $table->string('username', 60)->nullable()->unique()->after('name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('phone');
            $table->string('status', 30)->default('ใช้งานอยู่')->index()->after('avatar_path');
            $table->timestamp('last_login_at')->nullable()->after('status');
        });

        Schema::create('usr_roles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('usr_role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('usr_roles')->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        /* menu_key ตรงกับคีย์ใน assets/js/menu-config.js — เป็นสิทธิ์ระดับเมนู
           ที่ Permission Matrix ในป๊อปอัปบทบาทใช้ */
        Schema::create('usr_role_menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('usr_roles')->cascadeOnDelete();
            $table->string('menu_key', 60);
            $table->boolean('is_allowed')->default(false);
            $table->unique(['role_id', 'menu_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usr_role_menu_permissions');
        Schema::dropIfExists('usr_role_user');
        Schema::dropIfExists('usr_roles');

        /* ต้องถอด index ก่อนถอดคอลัมน์ — ไม่งั้น SQLite ล้มด้วย
           "error in index users_code_unique after drop column" และ MySQL ทิ้ง index กำพร้าไว้ */
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_code_unique');
            $table->dropUnique('users_username_unique');
            $table->dropIndex('users_status_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['code', 'username', 'phone', 'avatar_path', 'status', 'last_login_at']);
        });
    }
};
