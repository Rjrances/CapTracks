<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DropConstraints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drop:constraints';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop foreign key constraints';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dropping foreign key constraints...');
        
        // Get all foreign key constraints that reference students.id
        $constraints = \Illuminate\Support\Facades\DB::select("
            SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE REFERENCED_TABLE_NAME = 'students' AND REFERENCED_COLUMN_NAME = 'id'
        ");
        
        foreach ($constraints as $constraint) {
            $this->line("Dropping constraint: {$constraint->CONSTRAINT_NAME} from {$constraint->TABLE_NAME}");
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE {$constraint->TABLE_NAME} DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
                $this->info("✅ Dropped {$constraint->CONSTRAINT_NAME}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to drop {$constraint->CONSTRAINT_NAME}: " . $e->getMessage());
            }
        }
        
        $this->info('Done!');
    }
}
