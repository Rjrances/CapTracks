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
        Schema::table('group_members', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['student_id']);
            $table->dropColumn('student_id');
        });
        
        Schema::table('group_members', function (Blueprint $table) {
            // Add student_id as string column with foreign key to students.student_id
            $table->string('student_id', 20)->after('group_id');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_members', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['student_id']);
            $table->dropColumn('student_id');
        });
        
        Schema::table('group_members', function (Blueprint $table) {
            // Restore the original foreign key constraint
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
        });
    }
};