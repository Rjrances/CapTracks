<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckMySQLStudents extends Command
{
    protected $signature = 'mysql:check-students {school_id?}';
    protected $description = 'Check students in MySQL database directly';

    public function handle()
    {
        $schoolId = $this->argument('school_id');
        
        if ($schoolId) {
            // Check specific student
            $student = DB::table('users')->where('school_id', $schoolId)->first();
            
            if (!$student) {
                $this->error("Student with school ID {$schoolId} not found in MySQL database!");
                return 1;
            }
            
            $this->info("Student found in MySQL:");
            $this->line("Name: {$student->name}");
            $this->line("School ID: {$student->school_id}");
            $this->line("Email: {$student->email}");
            $this->line("Role: {$student->role}");
            $this->line("Password: {$student->password}");
            
        } else {
            // List all students
            $students = DB::table('users')->where('role', 'student')->get();
            
            if ($students->isEmpty()) {
                $this->error("No students found in MySQL database!");
                return 1;
            }
            
            $this->info("Students in MySQL database:");
            $this->table(
                ['Name', 'School ID', 'Email', 'Role'],
                $students->map(function($student) {
                    return [
                        $student->name,
                        $student->school_id,
                        $student->email,
                        $student->role
                    ];
                })->toArray()
            );
        }
        
        return 0;
    }
}
