<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ListTables extends Command
{
    protected $signature = 'list:tables';
    protected $description = 'List all database tables';

    public function handle()
    {
        $this->info('Current database tables:');
        
        $tables = DB::select('SHOW TABLES');
        $databaseName = DB::getDatabaseName();
        $tableKey = "Tables_in_{$databaseName}";
        
        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            $this->line("  - {$tableName}");
        }
        
        $this->info('Total tables: ' . count($tables));
    }
}