<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\User;
use App\Models\Student;

class CheckUserIds extends Command
{
    protected $signature = 'check:user-ids';
    protected $description = 'Check user_id values in accounts table';

    public function handle()
    {
        $this->info('Checking user_id values in accounts table...');
        
        $this->line('Faculty accounts:');
        $facultyAccounts = Account::where('user_type', 'faculty')->take(3)->get(['id', 'faculty_id', 'user_id', 'user_type']);
        foreach ($facultyAccounts as $account) {
            $this->line("  Account ID: {$account->id}, Faculty ID: {$account->faculty_id}, User ID: {$account->user_id}");
        }
        
        $this->line('Student accounts:');
        $studentAccounts = Account::where('user_type', 'student')->take(3)->get(['id', 'student_id', 'user_id', 'user_type']);
        foreach ($studentAccounts as $account) {
            $this->line("  Account ID: {$account->id}, Student ID: {$account->student_id}, User ID: {$account->user_id}");
        }
        
        $this->line('Checking if user_id references correct records:');
        
        // Check faculty
        $facultyAccount = Account::where('user_type', 'faculty')->first();
        if ($facultyAccount) {
            $user = User::find($facultyAccount->user_id);
            if ($user) {
                $this->line("✅ Faculty account {$facultyAccount->id} -> User {$facultyAccount->user_id} exists: {$user->name}");
            } else {
                $this->error("❌ Faculty account {$facultyAccount->id} -> User {$facultyAccount->user_id} NOT FOUND");
            }
        }
        
        // Check student
        $studentAccount = Account::where('user_type', 'student')->first();
        if ($studentAccount) {
            $student = Student::where('student_id', $studentAccount->student_id)->first();
            if ($student) {
                $this->line("✅ Student account {$studentAccount->id} -> Student {$studentAccount->student_id} exists: {$student->name}");
                $this->line("   But user_id is {$studentAccount->user_id} (should probably be NULL or reference a User record)");
            } else {
                $this->error("❌ Student account {$studentAccount->id} -> Student {$studentAccount->student_id} NOT FOUND");
            }
        }
        
        $this->info('Check complete!');
    }
}