<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckTableStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:tables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check table structures';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking table structures...');
        
        $tables = ['students', 'offering_student', 'group_members'];
        
        foreach ($tables as $table) {
            $this->line("Table: {$table}");
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
            foreach ($columns as $col) {
                $this->line("  - {$col}");
            }
            $this->line('');
        }
    }
}
