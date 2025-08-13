<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createTestStudents();
    }

    /**
     * Create test students with proper 10-digit IDs
     */
    private function createTestStudents()
    {
        $students = [
            [
                'name' => 'John Student',
                'email' => 'john@test.com',
                'student_id' => '2024000001', // 10 digits
                'semester' => '2024-1',
                'course' => 'BS Computer Science'
            ],
            [
                'name' => 'Jane Student',
                'email' => 'jane@test.com',
                'student_id' => '2024000002', // 10 digits
                'semester' => '2024-1',
                'course' => 'BS Computer Science'
            ],
            [
                'name' => 'Bob Student',
                'email' => 'bob@test.com',
                'student_id' => '2024000003', // 10 digits
                'semester' => '2024-1',
                'course' => 'BS Computer Science'
            ],
            [
                'name' => 'Alice Student',
                'email' => 'alice@test.com',
                'student_id' => '2024000004', // 10 digits
                'semester' => '2024-1',
                'course' => 'BS Computer Science'
            ],
            [
                'name' => 'Charlie Student',
                'email' => 'charlie@test.com',
                'student_id' => '2024000005', // 10 digits
                'semester' => '2024-1',
                'course' => 'BS Computer Science'
            ],
            [
                'name' => 'Diana Student',
                'email' => 'diana@test.com',
                'student_id' => '2024000006', // 10 digits
                'semester' => '2024-1',
                'course' => 'BS Computer Science'
            ]
        ];

        foreach ($students as $studentData) {
            Student::create([
                'name' => $studentData['name'],
                'email' => $studentData['email'],
                'student_id' => $studentData['student_id'],
                'semester' => $studentData['semester'],
                'course' => $studentData['course'],
                'password' => Hash::make('password')
            ]);
        }

        echo "✅ Created " . count($students) . " test students with 10-digit IDs\n";
    }
}
