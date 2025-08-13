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
        Schema::table('offering_student', function (Blueprint $table) {
            // Add unique constraint to ensure each student can only be enrolled in one offering
            $table->unique('student_id', 'offering_student_student_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offering_student', function (Blueprint $table) {
            $table->dropUnique('offering_student_student_unique');
        });
    }
};
