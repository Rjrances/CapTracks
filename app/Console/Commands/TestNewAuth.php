<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FacultyAccount;
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
        $facultyAccounts = FacultyAccount::take(3)->get(['id', 'faculty_id', 'user_id', 'email']);
        foreach ($facultyAccounts as $account) {
            $user = $account->user;
            $this->line("  Faculty Account ID: {$account->id}, Faculty ID: {$account->faculty_id}, User ID: {$account->user_id}, User Name: " . ($user ? $user->name : 'NOT FOUND'));
        }
        
        $this->line('Student Accounts:');
        $studentAccounts = StudentAccount::take(3)->get(['id', 'student_id', 'email']);
        foreach ($studentAccounts as $account) {
            $student = $account->student;
            $this->line("  Student Account ID: {$account->id}, Student ID: {$account->student_id}, Student Name: " . ($student ? $student->name : 'NOT FOUND'));
        }
        
        $this->line('Testing relationships...');
        
        // Test faculty relationship
        $facultyAccount = FacultyAccount::first();
        if ($facultyAccount) {
            $user = $facultyAccount->user;
            if ($user) {
                $this->line("✅ Faculty account {$facultyAccount->faculty_id} -> User {$user->name} relationship works!");
            } else {
                $this->error("❌ Faculty account relationship broken!");
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