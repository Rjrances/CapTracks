<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\Account;
use App\Models\Offering;
use App\Models\AcademicTerm;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create academic term
        $academicTerm = AcademicTerm::create([
            'school_year' => '2024-2025',
            'semester' => 'First Semester',
            'is_active' => true,
            'is_archived' => false,
        ]);

        // Create faculty users
        $facultyUsers = [
            ['name' => 'Test Coordinator', 'email' => 'coordinator@test.com', 'role' => 'coordinator'],
            ['name' => 'Test Chairperson', 'email' => 'chairperson@test.com', 'role' => 'chairperson'],
            ['name' => 'Test Adviser', 'email' => 'adviser@test.com', 'role' => 'adviser'],
            ['name' => 'Test Teacher', 'email' => 'teacher@test.com', 'role' => 'teacher'],
        ];

        foreach ($facultyUsers as $index => $facultyData) {
            $user = User::create([
                'name' => $facultyData['name'],
                'email' => $facultyData['email'],
                'birthday' => '1990-01-01',
                'department' => 'Computer Science',
                'role' => $facultyData['role'],
            ]);

            $account = Account::create([
                'faculty_id' => '100' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'email' => $facultyData['email'],
                'password' => 'password123',
                'user_type' => 'faculty',
                'user_id' => $user->id,
            ]);

            $user->update(['account_id' => $account->faculty_id]);
        }

        // Create students
        $students = [
            ['student_id' => '2024000007', 'name' => 'Ana Dela Cruz', 'email' => 'ana.dela.cruz@university.edu'],
            ['student_id' => '2024000008', 'name' => 'Mark Santos', 'email' => 'mark.santos@university.edu'],
            ['student_id' => '2024000009', 'name' => 'Liza Bautista', 'email' => 'liza.bautista@university.edu'],
            ['student_id' => '2024000010', 'name' => 'Ramon Garcia', 'email' => 'ramon.garcia@university.edu'],
            ['student_id' => '2024000011', 'name' => 'Ella Hernandez', 'email' => 'ella.hernandez@university.edu'],
        ];

        foreach ($students as $index => $studentData) {
            $student = Student::create([
                'student_id' => $studentData['student_id'],
                'name' => $studentData['name'],
                'email' => $studentData['email'],
                'course' => 'Bachelor of Science in Computer Science',
                'semester' => 1,
            ]);

            $account = Account::create([
                'student_account_id' => $studentData['student_id'],
                'email' => $studentData['email'],
                'password' => 'password123',
                'user_type' => 'student',
                'user_id' => $student->student_id,
            ]);

            $student->update(['account_id' => $account->student_account_id]);
        }

        // Create offerings
        $users = User::all();
        $offerings = [
            ['offer_code' => '1101', 'subject_title' => 'Capstone 1', 'subject_code' => 'CT1', 'teacher_id' => $users[0]->id],
            ['offer_code' => '1102', 'subject_title' => 'Capstone 2', 'subject_code' => 'CT2', 'teacher_id' => $users[1]->id],
            ['offer_code' => '1103', 'subject_title' => 'Thesis 1', 'subject_code' => 'T1', 'teacher_id' => $users[2]->id],
            ['offer_code' => '1104', 'subject_title' => 'Thesis 2', 'subject_code' => 'T2', 'teacher_id' => $users[3]->id],
        ];

        foreach ($offerings as $offeringData) {
            Offering::create([
                'offer_code' => $offeringData['offer_code'],
                'subject_title' => $offeringData['subject_title'],
                'subject_code' => $offeringData['subject_code'],
                'teacher_id' => $offeringData['teacher_id'],
                'academic_term_id' => $academicTerm->id,
            ]);
        }

        // Enroll students in offerings
        $offering = Offering::first();
        foreach ($students as $studentData) {
            $student = Student::where('student_id', $studentData['student_id'])->first();
            if ($student && $offering) {
                $offering->students()->attach($student->student_id);
            }
        }

        $this->command->info('Sample data created successfully!');
        $this->command->info('Faculty login: 10001, 10002, 10003, 10004 (password: password123)');
        $this->command->info('Student login: 2024000007, 2024000008, 2024000009, 2024000010, 2024000011 (password: password123)');
    }
}