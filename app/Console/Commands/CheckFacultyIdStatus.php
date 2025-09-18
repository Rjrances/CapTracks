<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckFacultyIdStatus extends Command
{
    protected $signature = 'check:faculty-id';
    protected $description = 'Check faculty_id status for faculty and students';

    public function handle()
    {
        $this->info('Checking faculty_id status...');
        
        $this->line('Faculty accounts:');
        $facultyAccounts = \App\Models\Account::where('user_type', 'faculty')->get(['id', 'faculty_id', 'email', 'user_type']);
        foreach ($facultyAccounts as $account) {
            $this->line("  ID: {$account->id}, Faculty ID: {$account->faculty_id}, Email: {$account->email}");
        }
        
        $this->line('Student accounts (first 5):');
        $studentAccounts = \App\Models\Account::where('user_type', 'student')->take(5)->get(['id', 'faculty_id', 'student_id', 'email', 'user_type']);
        foreach ($studentAccounts as $account) {
            $facultyId = $account->faculty_id ?? 'NULL';
            $studentId = $account->student_id ?? 'NULL';
            $this->line("  ID: {$account->id}, Faculty ID: {$facultyId}, Student ID: {$studentId}, Email: {$account->email}");
        }
        
        $this->info('Check complete!');
    }
}