<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_instructors', function (Blueprint $table) {
            $table->json('search_tags')->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('mst_instructors', function (Blueprint $table) {
            $table->dropColumn('search_tags');
        });
    }
};
