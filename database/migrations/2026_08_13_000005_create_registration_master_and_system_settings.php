<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MENU_KEYS = [
        'master-data-registration-options',
        'master-data-consents',
        'master-data-system-settings',
    ];

    public function up(): void
    {
        Schema::table('mst_options', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('updated_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('mst_consent_documents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('consent_type', 30)->index();
            $table->string('title', 160);
            $table->string('version', 20);
            $table->longText('content');
            $table->date('effective_date')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(false);
            $table->unsignedTinyInteger('active_slot')->nullable();
            $table->timestamps();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['consent_type', 'version']);
            $table->unique(['consent_type', 'active_slot']);
        });

        Schema::table('ptp_consents', function (Blueprint $table) {
            $table->foreignId('consent_document_id')->nullable()->after('participant_id')
                ->constrained('mst_consent_documents')->restrictOnDelete();
        });

        Schema::create('sys_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 80)->unique();
            $table->longText('setting_value')->nullable();
            $table->string('setting_type', 20)->default('text');
            $table->timestamps();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        $now = now();
        foreach ([
            ['age_range', 'age_range-01', 'ต่ำกว่า 15 ปี', 1],
            ['age_range', 'age_range-02', '15–24 ปี', 2],
            ['age_range', 'age_range-03', '25–39 ปี', 3],
            ['age_range', 'age_range-04', '40–59 ปี', 4],
            ['age_range', 'age_range-05', '60 ปีขึ้นไป', 5],
            ['cohort_source', 'walk_in', 'สมัครหรือเข้าร่วมโดยตรง', 1],
            ['cohort_source', 'activity_registration', 'จากการลงทะเบียนกิจกรรม', 2],
            ['cohort_source', 'referral', 'ได้รับการแนะนำหรือส่งต่อ', 3],
            ['cohort_source', 'manual', 'เจ้าหน้าที่เพิ่มข้อมูล', 4],
        ] as [$group, $code, $label, $order]) {
            DB::table('mst_options')->updateOrInsert(
                ['option_group' => $group, 'code' => $code],
                ['label' => $label, 'sort_order' => $order, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $roleIds = DB::table('usr_roles')->pluck('id');
        foreach ($roleIds as $roleId) {
            $allowed = (bool) DB::table('usr_role_menu_permissions')
                ->where('role_id', $roleId)->where('menu_key', 'master-data')->value('is_allowed');

            foreach (self::MENU_KEYS as $menuKey) {
                DB::table('usr_role_menu_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'menu_key' => $menuKey],
                    ['is_allowed' => $allowed]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('usr_role_menu_permissions')->whereIn('menu_key', self::MENU_KEYS)->delete();

        Schema::dropIfExists('sys_settings');

        Schema::table('ptp_consents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('consent_document_id');
        });

        Schema::dropIfExists('mst_consent_documents');

        Schema::table('mst_options', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
        });
    }
};
