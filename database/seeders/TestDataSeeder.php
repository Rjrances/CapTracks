<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\Group;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        // Create test faculty members
        User::create([
            'name' => 'Dr. John Smith',
            'email' => 'john.smith@university.edu',
            'school_id' => 'FAC001',
            'password' => bcrypt('password123'),
            'role' => 'adviser',
            'must_change_password' => false,
        ]);

        User::create([
            'name' => 'Prof. Sarah Johnson',
            'email' => 'sarah.johnson@university.edu',
            'school_id' => 'FAC002',
            'password' => bcrypt('password123'),
            'role' => 'adviser',
            'must_change_password' => false,
        ]);

        User::create([
            'name' => 'Dr. Michael Brown',
            'email' => 'michael.brown@university.edu',
            'school_id' => 'FAC003',
            'password' => bcrypt('password123'),
            'role' => 'panelist',
            'must_change_password' => false,
        ]);

        // Create test students
        $alice = Student::create([
            'student_id' => '2021-0001',
            'name' => 'Alice Johnson',
            'email' => 'alice.johnson@student.edu',
            'semester' => '2024-1',
            'course' => 'BS Computer Science',
        ]);

        $bob = Student::create([
            'student_id' => '2021-0002',
            'name' => 'Bob Wilson',
            'email' => 'bob.wilson@student.edu',
            'semester' => '2024-1',
            'course' => 'BS Computer Science',
        ]);

        $carol = Student::create([
            'student_id' => '2021-0003',
            'name' => 'Carol Davis',
            'email' => 'carol.davis@student.edu',
            'semester' => '2024-1',
            'course' => 'BS Computer Science',
        ]);

        $david = Student::create([
            'student_id' => '2021-0004',
            'name' => 'David Miller',
            'email' => 'david.miller@student.edu',
            'semester' => '2024-1',
            'course' => 'BS Computer Science',
        ]);

        // Create corresponding user accounts for students
        User::create([
            'name' => 'Alice Johnson',
            'email' => 'alice.johnson@student.edu',
            'school_id' => '2021-0001',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'must_change_password' => false,
        ]);

        User::create([
            'name' => 'Bob Wilson',
            'email' => 'bob.wilson@student.edu',
            'school_id' => '2021-0002',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'must_change_password' => false,
        ]);

        User::create([
            'name' => 'Carol Davis',
            'email' => 'carol.davis@student.edu',
            'school_id' => '2021-0003',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'must_change_password' => false,
        ]);

        User::create([
            'name' => 'David Miller',
            'email' => 'david.miller@student.edu',
            'school_id' => '2021-0004',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'must_change_password' => false,
        ]);

        // Create test groups with members
        $group1 = Group::create([
            'name' => 'Team Alpha',
            'description' => 'Capstone project for AI-powered chatbot',
            'adviser_id' => 1, // Dr. John Smith
        ]);

        $group2 = Group::create([
            'name' => 'Team Beta',
            'description' => 'Mobile app for campus navigation',
            'adviser_id' => 2, // Prof. Sarah Johnson
        ]);

        // Add members to groups
        $group1->members()->attach([
            $alice->id => ['role' => 'leader'],
            $bob->id => ['role' => 'member'],
        ]);

        $group2->members()->attach([
            $carol->id => ['role' => 'leader'],
            $david->id => ['role' => 'member'],
        ]);
    }
} 