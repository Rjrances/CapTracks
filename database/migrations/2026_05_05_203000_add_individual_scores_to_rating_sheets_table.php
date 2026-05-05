<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rating_sheets', function (Blueprint $table) {
            $table->json('individual_scores')->nullable()->after('criteria');
        });
    }

    public function down(): void
    {
        Schema::table('rating_sheets', function (Blueprint $table) {
            $table->dropColumn('individual_scores');
        });
    }
};

