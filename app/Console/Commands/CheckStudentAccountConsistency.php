<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Account;

class CheckStudentAccountConsistency extends Command
{
    protected $signature = 'check:student-consistency';
    protected $description = 'Check that student_id and account_id are consistent';

    public function handle()
    {
        $this->info('Checking student account consistency...');
        
        $students = Student::all();
        $inconsistent = 0;
        
        foreach ($students as $student) {
            if ($student->student_id !== $student->account_id) {
                $this->error("❌ Inconsistent: Student {$student->student_id} has account_id {$student->account_id}");
                $inconsistent++;
            } else {
                $this->line("✅ Consistent: Student {$student->student_id} has account_id {$student->account_id}");
            }
        }
        
        $this->info("\nSummary:");
        $this->info("Total students: {$students->count()}");
        $this->info("Inconsistent: {$inconsistent}");
        $this->info("Consistent: " . ($students->count() - $inconsistent));
        
        if ($inconsistent === 0) {
            $this->info("🎉 All students have consistent IDs!");
        } else {
            $this->error("⚠️  Some students have inconsistent IDs.");
        }
        
        // Also check accounts table
        $this->info("\nChecking accounts table...");
        $studentAccounts = Account::where('user_type', 'student')->get();
        
        foreach ($studentAccounts as $account) {
            $student = Student::where('student_id', $account->student_id)->first();
            if ($student && $student->account_id === $account->student_id) {
                $this->line("✅ Account {$account->student_id} matches student {$student->student_id}");
            } else {
                $this->error("❌ Account {$account->student_id} does not match any student");
            }
        }
    }
}