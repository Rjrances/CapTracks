<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanOrphanedData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:orphaned-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean orphaned data before adding foreign keys';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning orphaned data...');
        
        // Get all valid student_ids from students table
        $validStudentIds = \Illuminate\Support\Facades\DB::table('students')->pluck('student_id')->toArray();
        
        $this->line('Valid student IDs: ' . implode(', ', $validStudentIds));
        
        // Clean orphaned data from each table
        $tables = [
            'enrollments' => 'student_id',
            'group_members' => 'student_id', 
            'offering_student' => 'student_id',
            'project_submissions' => 'student_id',
            'task_submissions' => 'student_id'
        ];

        foreach ($tables as $table => $column) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                $this->line("Cleaning {$table}...");
                $deleted = \Illuminate\Support\Facades\DB::table($table)
                    ->whereNotIn($column, $validStudentIds)
                    ->delete();
                $this->line("Deleted {$deleted} orphaned records from {$table}");
            }
        }
        
        $this->info('Orphaned data cleaned successfully!');
    }
}
