<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\User;
class ListStudents extends Command
{
    protected $signature = 'students:list';
    protected $description = 'List all students in the database';
    public function handle()
    {
        $students = User::where('role', 'student')->get();
        if ($students->isEmpty()) {
            $this->error("No students found in the database!");
            return 1;
        }
        $this->info("Students in database:");
        $this->table(
            ['Name', 'School ID', 'Email', 'Must Change Password'],
            $students->map(function($student) {
                return [
                    $student->name,
                    $student->school_id,
                    $student->email,
                    $student->must_change_password ? 'Yes' : 'No'
                ];
            })->toArray()
        );
        return 0;
    }
}
