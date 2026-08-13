<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MENU_KEY = 'master-data-payment-accounts';

    public function up(): void
    {
        Schema::create('mst_payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('bank_code', 20);
            $table->string('account_number', 30);
            $table->string('account_name', 150);
            $table->string('qr_code_path')->nullable();
            $table->boolean('is_active')->default(false)->index();

            /* ค่า 1 มีได้แถวเดียวจาก unique index ส่วนรายการปิดใช้เป็น null ได้หลายแถว
               จึงบังคับกติกา "เปิดได้เพียง 1 บัญชี" ที่ฐานข้อมูลด้วย ไม่ได้พึ่งหน้าเว็บอย่างเดียว */
            $table->unsignedTinyInteger('active_slot')->nullable()->unique();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['bank_code', 'account_number']);
        });

        /* ฐานที่ติดตั้งอยู่แล้วต้องเห็นเมนูใหม่ตามสิทธิ์กลุ่ม "พื้นฐาน" เดิม
           ส่วนฐานใหม่ RoleAndUserSeeder จะสร้างสิทธิ์นี้ให้อีกทางหนึ่ง */
        if (Schema::hasTable('usr_roles') && Schema::hasTable('usr_role_menu_permissions')) {
            $roles = DB::table('usr_roles')->pluck('id');

            foreach ($roles as $roleId) {
                $allowed = (bool) DB::table('usr_role_menu_permissions')
                    ->where('role_id', $roleId)
                    ->where('menu_key', 'master-data')
                    ->value('is_allowed');

                DB::table('usr_role_menu_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'menu_key' => self::MENU_KEY],
                    ['is_allowed' => $allowed]
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('usr_role_menu_permissions')) {
            DB::table('usr_role_menu_permissions')->where('menu_key', self::MENU_KEY)->delete();
        }

        Schema::dropIfExists('mst_payment_accounts');
    }
};
