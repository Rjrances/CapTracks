<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\User;
class CheckStudentPassword extends Command
{
    protected $signature = 'student:check-password {school_id}';
    protected $description = 'Check student password by school ID';
    public function handle()
    {
        $schoolId = $this->argument('school_id');
        $student = User::where('school_id', $schoolId)->first();
        if (!$student) {
            $this->error("Student with school ID {$schoolId} not found!");
            return 1;
        }
        $this->info("Student found:");
        $this->line("Name: {$student->name}");
        $this->line("School ID: {$student->school_id}");
        $this->line("Role: {$student->role}");
        $this->line("Password: {$student->password}");
        $this->line("Must change password: " . ($student->must_change_password ? 'Yes' : 'No'));
        return 0;
    }
}
