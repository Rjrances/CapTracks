<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixForeignKeys extends Command
{
    protected $signature = 'fix:foreign-keys';
    protected $description = 'Fix foreign key constraints';

    public function handle()
    {
        $this->info('Fixing foreign key constraints...');
        
        // Drop existing foreign keys
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE users DROP FOREIGN KEY users_account_id_foreign');
            $this->line('Dropped users_account_id_foreign');
        } catch (\Exception $e) {
            $this->line('users_account_id_foreign not found or already dropped');
        }
        
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE students DROP FOREIGN KEY students_account_id_foreign');
            $this->line('Dropped students_account_id_foreign');
        } catch (\Exception $e) {
            $this->line('students_account_id_foreign not found or already dropped');
        }
        
        // Change column types
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE users MODIFY COLUMN account_id VARCHAR(20)');
        $this->line('Changed users.account_id to VARCHAR(20)');
        
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE students MODIFY COLUMN account_id VARCHAR(20)');
        $this->line('Changed students.account_id to VARCHAR(20)');
        
        // Add new foreign keys
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE users ADD CONSTRAINT users_account_id_foreign FOREIGN KEY (account_id) REFERENCES accounts(faculty_id) ON DELETE CASCADE');
        $this->line('Added users foreign key to accounts.faculty_id');
        
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE students ADD CONSTRAINT students_account_id_foreign FOREIGN KEY (account_id) REFERENCES accounts(student_account_id) ON DELETE CASCADE');
        $this->line('Added students foreign key to accounts.student_account_id');
        
        $this->info('Foreign key constraints fixed!');
    }
}