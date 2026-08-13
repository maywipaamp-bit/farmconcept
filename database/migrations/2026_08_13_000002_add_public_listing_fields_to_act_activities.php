<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('act_activities', function (Blueprint $table) {
            $table->timestamp('registration_start_at')->nullable()->after('publish_end_at');
            $table->timestamp('registration_end_at')->nullable()->after('registration_start_at');
            $table->unsignedInteger('public_sort_order')->default(0)->after('is_featured');

            $table->index(
                ['is_published', 'public_sort_order'],
                'act_activities_public_listing_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('act_activities', function (Blueprint $table) {
            $table->dropIndex('act_activities_public_listing_index');
            $table->dropColumn([
                'registration_start_at',
                'registration_end_at',
                'public_sort_order',
            ]);
        });
    }
};
