<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanStudentData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:student-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean student data before migration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning student data...');
        
        // Clear problematic data in group_milestone_tasks
        $this->line('Clearing completed_by data in group_milestone_tasks...');
        \Illuminate\Support\Facades\DB::table('group_milestone_tasks')->update(['completed_by' => null]);
        
        $this->line('Clearing assigned_to data in group_milestone_tasks...');
        \Illuminate\Support\Facades\DB::table('group_milestone_tasks')->update(['assigned_to' => null]);
        
        $this->info('Data cleaned successfully!');
    }
}
