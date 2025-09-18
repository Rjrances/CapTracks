<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckAccountsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check accounts table data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking accounts table...');
        
        $count = \Illuminate\Support\Facades\DB::table('accounts')->count();
        $this->line("Rows in accounts: {$count}");
        
        if ($count > 0) {
            $sample = \Illuminate\Support\Facades\DB::table('accounts')->take(5)->get();
            $this->line("Sample data:");
            foreach ($sample as $row) {
                $this->line("  ID: {$row->id}, Faculty ID: {$row->faculty_id}, Email: {$row->email}, Type: {$row->user_type}, User ID: {$row->user_id}");
            }
        }
        
        $this->info('Check complete!');
    }
}
