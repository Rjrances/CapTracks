<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserAccount;
use App\Models\StudentAccount;
use App\Models\User;
use App\Models\Student;

class TestNewAuth extends Command
{
    protected $signature = 'test:new-auth';
    protected $description = 'Test the new separate account system';

    public function handle()
    {
        $this->info('Testing new separate account system...');
        
        $this->line('Faculty Accounts:');
        $userAccounts = UserAccount::take(3)->get(['id', 'faculty_id', 'email']);
        foreach ($userAccounts as $account) {
            $user = $account->user;
            $this->line("  User Account ID: {$account->id}, Faculty ID: {$account->faculty_id}, User Name: " . ($user ? $user->name : 'NOT FOUND'));
        }
        
        $this->line('Student Accounts:');
        $studentAccounts = StudentAccount::take(3)->get(['id', 'student_id', 'email']);
        foreach ($studentAccounts as $account) {
            $student = $account->student;
            $this->line("  Student Account ID: {$account->id}, Student ID: {$account->student_id}, Student Name: " . ($student ? $student->name : 'NOT FOUND'));
        }
        
        $this->line('Testing relationships...');
        
        // Test faculty relationship
        $userAccount = UserAccount::first();
        if ($userAccount) {
            $user = $userAccount->user;
            if ($user) {
                $this->line("✅ User account {$userAccount->faculty_id} -> User {$user->name} relationship works!");
            } else {
                $this->error("❌ User account relationship broken!");
            }
        }
        
        // Test student relationship
        $studentAccount = StudentAccount::first();
        if ($studentAccount) {
            $student = $studentAccount->student;
            if ($student) {
                $this->line("✅ Student account {$studentAccount->student_id} -> Student {$student->name} relationship works!");
            } else {
                $this->error("❌ Student account relationship broken!");
            }
        }
        
        $this->info('Test complete!');
    }
}