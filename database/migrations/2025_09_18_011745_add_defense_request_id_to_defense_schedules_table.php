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
        Schema::table('defense_schedules', function (Blueprint $table) {
            $table->foreignId('defense_request_id')->nullable()->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defense_schedules', function (Blueprint $table) {
            $table->dropForeign(['defense_request_id']);
            $table->dropColumn('defense_request_id');
        });
    }
};
