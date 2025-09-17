<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class ResetStudentPassword extends Command
{
    protected $signature = 'student:reset-password {school_id} {--password=}';
    protected $description = 'Reset student password by school ID';
    public function handle()
    {
        $schoolId = $this->argument('school_id');
        $newPassword = $this->option('password');
        $student = User::where('school_id', $schoolId)->first();
        if (!$student) {
            $this->error("Student with school ID {$schoolId} not found!");
            return 1;
        }
        if (!$newPassword) {
            $newPassword = 'password'; // Default password
            $this->warn("No password provided. Using default password: 'password'");
        }
        $hashedPassword = Hash::make($newPassword);
        $student->update([
            'password' => $hashedPassword,
            'must_change_password' => true // Force them to change it on next login
        ]);
        $this->info("Password reset successful for student:");
        $this->line("Name: {$student->name}");
        $this->line("School ID: {$student->school_id}");
        $this->line("New Password: {$newPassword}");
        $this->line("Must change password on next login: Yes");
        return 0;
    }
}
