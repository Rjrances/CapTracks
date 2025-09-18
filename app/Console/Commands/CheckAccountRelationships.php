<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckAccountRelationships extends Command
{
    protected $signature = 'check:account-relationships';
    protected $description = 'Check account relationships between users, students, and accounts';

    public function handle()
    {
        $this->info('Checking account relationships...');
        
        $this->line('Faculty Users (users table):');
        $users = \App\Models\User::take(5)->get(['id', 'account_id', 'name', 'email']);
        foreach ($users as $user) {
            $this->line("  User ID: {$user->id}, Account ID: {$user->account_id}, Name: {$user->name}");
        }
        
        $this->line('Students (students table):');
        $students = \App\Models\Student::take(5)->get(['student_id', 'account_id', 'name', 'email']);
        foreach ($students as $student) {
            $this->line("  Student ID: {$student->student_id}, Account ID: {$student->account_id}, Name: {$student->name}");
        }
        
        $this->line('Accounts (accounts table):');
        $facultyAccounts = \App\Models\Account::where('user_type', 'faculty')->take(3)->get(['id', 'faculty_id', 'student_id', 'email']);
        foreach ($facultyAccounts as $account) {
            $this->line("  Account ID: {$account->id}, Faculty ID: {$account->faculty_id}, Student ID: " . ($account->student_id ?? 'NULL') . ", Email: {$account->email}");
        }
        
        $studentAccounts = \App\Models\Account::where('user_type', 'student')->take(3)->get(['id', 'faculty_id', 'student_id', 'email']);
        foreach ($studentAccounts as $account) {
            $this->line("  Account ID: {$account->id}, Faculty ID: " . ($account->faculty_id ?? 'NULL') . ", Student ID: {$account->student_id}, Email: {$account->email}");
        }
        
        $this->info('Check complete!');
    }
}