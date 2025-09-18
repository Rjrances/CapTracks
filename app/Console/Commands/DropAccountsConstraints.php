<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DropAccountsConstraints extends Command
{
    protected $signature = 'drop:accounts-constraints';
    protected $description = 'Drop foreign key constraints referencing the old accounts table';

    public function handle()
    {
        $this->info('Checking for foreign key constraints referencing accounts table...');
        
        // Get all foreign key constraints referencing the accounts table
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE REFERENCED_TABLE_NAME = 'accounts'
        ");
        
        if (empty($constraints)) {
            $this->info('No foreign key constraints found referencing accounts table.');
            return;
        }
        
        $this->info('Found ' . count($constraints) . ' foreign key constraints:');
        foreach ($constraints as $constraint) {
            $this->line("  {$constraint->TABLE_NAME}.{$constraint->COLUMN_NAME} -> {$constraint->REFERENCED_TABLE_NAME}.{$constraint->REFERENCED_COLUMN_NAME} (constraint: {$constraint->CONSTRAINT_NAME})");
        }
        
        $this->info('Dropping foreign key constraints...');
        
        foreach ($constraints as $constraint) {
            try {
                // Use raw SQL to drop the foreign key constraint
                DB::statement("ALTER TABLE {$constraint->TABLE_NAME} DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                $this->line("✅ Dropped constraint: {$constraint->CONSTRAINT_NAME}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to drop constraint {$constraint->CONSTRAINT_NAME}: " . $e->getMessage());
            }
        }
        
        $this->info('Foreign key constraints dropped successfully!');
        $this->info('You can now drop the accounts table.');
    }
}