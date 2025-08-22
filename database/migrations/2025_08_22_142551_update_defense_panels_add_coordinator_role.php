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
        Schema::table('defense_panels', function (Blueprint $table) {
            // Update the role enum to include 'coordinator'
            $table->enum('role', ['chair', 'member', 'adviser', 'coordinator'])->default('member')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defense_panels', function (Blueprint $table) {
            // Revert back to original enum
            $table->enum('role', ['chair', 'member', 'adviser'])->default('member')->change();
        });
    }
};
