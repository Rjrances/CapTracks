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
        // Step 1: Make columns nullable first
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE group_milestone_tasks MODIFY COLUMN assigned_to BIGINT UNSIGNED NULL');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE group_milestone_tasks MODIFY COLUMN completed_by BIGINT UNSIGNED NULL');
        
        // Step 2: Clear any remaining data
        \Illuminate\Support\Facades\DB::table('group_milestone_tasks')->update(['assigned_to' => null, 'completed_by' => null]);
        
        // Step 3: Convert to varchar
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE group_milestone_tasks MODIFY COLUMN assigned_to VARCHAR(20) NULL');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE group_milestone_tasks MODIFY COLUMN completed_by VARCHAR(20) NULL');
        
        // Step 4: Convert other tables
        $tables = [
            'enrollments' => 'student_id',
            'group_members' => 'student_id', 
            'offering_student' => 'student_id',
            'project_submissions' => 'student_id',
            'task_submissions' => 'student_id'
        ];

        foreach ($tables as $table => $column) {
            if (Schema::hasTable($table)) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE {$table} MODIFY COLUMN {$column} VARCHAR(20) NOT NULL");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to bigint unsigned
        $tables = [
            'enrollments' => 'student_id',
            'group_members' => 'student_id', 
            'offering_student' => 'student_id',
            'project_submissions' => 'student_id',
            'task_submissions' => 'student_id'
        ];

        foreach ($tables as $table => $column) {
            if (Schema::hasTable($table)) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE {$table} MODIFY COLUMN {$column} BIGINT UNSIGNED NOT NULL");
            }
        }
        
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE group_milestone_tasks MODIFY COLUMN assigned_to BIGINT UNSIGNED NULL');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE group_milestone_tasks MODIFY COLUMN completed_by BIGINT UNSIGNED NULL');
    }
};
