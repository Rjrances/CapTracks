<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class ResetStudentPasswordInTable extends Command
{
    protected $signature = 'student:reset-password-table {student_id} {--password=}';
    protected $description = 'Reset student password in students table by student ID';
    public function handle()
    {
        $studentId = $this->argument('student_id');
        $newPassword = $this->option('password');
        $student = DB::table('students')->where('student_id', $studentId)->first();
        if (!$student) {
            $this->error("Student with ID {$studentId} not found in students table!");
            return 1;
        }
        if (!$newPassword) {
            $newPassword = 'password'; // Default password
            $this->warn("No password provided. Using default password: 'password'");
        }
        $hashedPassword = Hash::make($newPassword);
        DB::table('students')->where('student_id', $studentId)->update([
            'password' => $hashedPassword,
            'must_change_password' => true // Force them to change it on next login
        ]);
        $this->info("Password reset successful for student:");
        $this->line("Name: {$student->name}");
        $this->line("Student ID: {$student->student_id}");
        $this->line("Email: {$student->email}");
        $this->line("New Password: {$newPassword}");
        $this->line("Must change password on next login: Yes");
        return 0;
    }
}
