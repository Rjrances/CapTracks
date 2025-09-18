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
        // Recreate foreign key constraints pointing to students.student_id
        $tables = [
            'enrollments' => 'student_id',
            'group_members' => 'student_id', 
            'group_milestone_tasks' => 'assigned_to',
            'group_milestone_tasks' => 'completed_by',
            'offering_student' => 'student_id',
            'project_submissions' => 'student_id',
            'task_submissions' => 'student_id'
        ];

        foreach ($tables as $table => $column) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($column) {
                    $table->foreign($column)->references('student_id')->on('students')->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints pointing to student_id
        $tables = [
            'enrollments' => 'student_id',
            'group_members' => 'student_id', 
            'group_milestone_tasks' => 'assigned_to',
            'group_milestone_tasks' => 'completed_by',
            'offering_student' => 'student_id',
            'project_submissions' => 'student_id',
            'task_submissions' => 'student_id'
        ];

        foreach ($tables as $table => $column) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($column) {
                    $table->dropForeign([$column]);
                });
            }
        }
    }
};
