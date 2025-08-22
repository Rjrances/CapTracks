<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckStudentsTable extends Command
{
    protected $signature = 'students:check-table {school_id?}';
    protected $description = 'Check students in students table';

    public function handle()
    {
        $schoolId = $this->argument('school_id');
        
        // Check the students table directly
        $studentsTable = 'students';
        
        try {
            // First, let's see the structure of the students table
            $columns = DB::select("DESCRIBE {$studentsTable}");
            $this->info("Structure of {$studentsTable} table:");
            foreach ($columns as $column) {
                $this->line("- {$column->Field}: {$column->Type}");
            }
            
            $this->newLine();
            
            if ($schoolId) {
                // Check specific student
                $student = DB::table($studentsTable)->where('student_id', $schoolId)->first();
                
                if (!$student) {
                    $this->error("Student with ID {$schoolId} not found in {$studentsTable} table!");
                    return 1;
                }
                
                $this->info("Student found in {$studentsTable}:");
                $this->line("Name: {$student->name}");
                $this->line("Student ID: {$student->student_id}");
                $this->line("Email: {$student->email}");
                $this->line("Password: {$student->password}");
                
            } else {
                // List all students
                $students = DB::table($studentsTable)->get();
                
                if ($students->isEmpty()) {
                    $this->error("No students found in {$studentsTable} table!");
                    return 1;
                }
                
                $this->info("Students in {$studentsTable} table:");
                $this->table(
                    ['Name', 'Student ID', 'Email', 'Password'],
                    $students->map(function($student) {
                        return [
                            $student->name,
                            $student->student_id,
                            $student->email,
                            substr($student->password, 0, 20) . '...'
                        ];
                    })->toArray()
                );
            }
            
        } catch (\Exception $e) {
            $this->error("Error accessing {$studentsTable} table: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
