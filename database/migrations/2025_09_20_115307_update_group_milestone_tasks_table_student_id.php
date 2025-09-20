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
        Schema::table('group_milestone_tasks', function (Blueprint $table) {
            // Drop the existing foreign key constraints
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['completed_by']);
            $table->dropColumn(['assigned_to', 'completed_by']);
        });
        
        Schema::table('group_milestone_tasks', function (Blueprint $table) {
            // Add student_id columns as string with foreign keys to students.student_id
            $table->string('assigned_to', 20)->nullable()->after('milestone_task_id');
            $table->string('completed_by', 20)->nullable()->after('completed_at');
            $table->foreign('assigned_to')->references('student_id')->on('students')->onDelete('set null');
            $table->foreign('completed_by')->references('student_id')->on('students')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_milestone_tasks', function (Blueprint $table) {
            // Drop the new foreign key constraints
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['completed_by']);
            $table->dropColumn(['assigned_to', 'completed_by']);
        });
        
        Schema::table('group_milestone_tasks', function (Blueprint $table) {
            // Restore the original foreign key constraints
            $table->foreignId('assigned_to')->nullable()->constrained('students')->onDelete('set null');
            $table->foreignId('completed_by')->nullable()->constrained('students')->onDelete('set null');
        });
    }
};