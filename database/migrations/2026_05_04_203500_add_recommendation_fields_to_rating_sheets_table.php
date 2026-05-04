<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rating_sheets', function (Blueprint $table) {
            $table->string('recommendation', 30)->nullable()->after('total_score');
            $table->text('recommendation_reason')->nullable()->after('recommendation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rating_sheets', function (Blueprint $table) {
            $table->dropColumn(['recommendation', 'recommendation_reason']);
        });
    }
};
