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
            // Drop the existing foreign key constraint
            $table->dropForeign(['student_id']);
            
            // Change student_id to reference students.student_id
            $table->dropColumn('student_id');
            $table->string('student_id', 20)->after('offering_id');
            
            // Add foreign key constraint to students.student_id
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offering_student', function (Blueprint $table) {
            // Drop the student_id foreign key
            $table->dropForeign(['student_id']);
            $table->dropColumn('student_id');
            
            // Add back student_id as foreignId
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
        });
    }
};