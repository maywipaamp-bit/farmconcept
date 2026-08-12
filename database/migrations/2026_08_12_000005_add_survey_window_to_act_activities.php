<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('act_activities', function (Blueprint $table) {
            $table->timestamp('survey_start_at')->nullable()->after('checkin_end_at');
            $table->timestamp('survey_end_at')->nullable()->after('survey_start_at');
        });
    }

    public function down(): void
    {
        Schema::table('act_activities', function (Blueprint $table) {
            $table->dropColumn(['survey_start_at', 'survey_end_at']);
        });
    }
};
