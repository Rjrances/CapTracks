<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckForeignKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:foreign-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check foreign key constraints';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking foreign key constraints...');
        
        $constraints = \Illuminate\Support\Facades\DB::select("
            SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE REFERENCED_TABLE_NAME = 'students'
        ");
        
        foreach ($constraints as $constraint) {
            $this->line("{$constraint->TABLE_NAME}.{$constraint->COLUMN_NAME} -> {$constraint->REFERENCED_TABLE_NAME}.{$constraint->REFERENCED_COLUMN_NAME}");
        }
    }
}
