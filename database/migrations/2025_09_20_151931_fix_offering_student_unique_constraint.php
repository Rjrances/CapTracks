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
            // Drop foreign keys first
            $table->dropForeign('offering_student_offering_id_foreign');
            $table->dropForeign('offering_student_student_id_foreign');
            
            // Drop the existing unique constraint on offering_id only
            $table->dropUnique('offering_student_offering_id_student_id_unique');
            
            // Add a new unique constraint on both offering_id and student_id
            $table->unique(['offering_id', 'student_id'], 'offering_student_offering_id_student_id_unique');
            
            // Recreate foreign keys
            $table->foreign('offering_id')->references('id')->on('offerings')->onDelete('cascade');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offering_student', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign('offering_student_offering_id_foreign');
            $table->dropForeign('offering_student_student_id_foreign');
            
            // Drop the composite unique constraint
            $table->dropUnique('offering_student_offering_id_student_id_unique');
            
            // Restore the original unique constraint on offering_id only
            $table->unique('offering_id', 'offering_student_offering_id_student_id_unique');
            
            // Recreate foreign keys
            $table->foreign('offering_id')->references('id')->on('offerings')->onDelete('cascade');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
        });
    }
};
