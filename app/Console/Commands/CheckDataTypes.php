<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckDataTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:data-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check data types of student_id columns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking data types of student_id columns...');
        
        $tables = ['students', 'enrollments', 'group_members', 'offering_student', 'project_submissions', 'task_submissions'];
        
        foreach ($tables as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                $this->line("Table: {$table}");
                $columns = \Illuminate\Support\Facades\DB::select("DESCRIBE {$table}");
                foreach ($columns as $column) {
                    if (strpos($column->Field, 'student_id') !== false) {
                        $this->line("  - {$column->Field}: {$column->Type} {$column->Null} {$column->Key}");
                    }
                }
                $this->line('');
            }
        }
    }
}
