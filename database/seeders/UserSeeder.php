<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserAccount;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{

    public function run(): void
    {
        $this->createFacultyUsers();
    }


    private function createFacultyUsers()
    {
        // Create Department Chairperson
        $chairperson = User::create([
            'faculty_id' => '10001',
            'name' => 'Dr. Maria Santos',
            'email' => 'maria.santos@university.edu',
            'department' => 'Computer Science',
            'role' => 'chairperson'
        ]);

        // Create UserAccount for Chairperson
        UserAccount::create([
            'faculty_id' => $chairperson->faculty_id,
            'email' => 'maria.santos@university.edu',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);

        // Create Course Coordinator
        $coordinator = User::create([
            'faculty_id' => '10002',
            'name' => 'Prof. John Rodriguez',
            'email' => 'john.rodriguez@university.edu',
            'department' => 'Computer Science',
            'role' => 'coordinator'
        ]);

        // Create UserAccount for Coordinator
        UserAccount::create([
            'faculty_id' => $coordinator->faculty_id,
            'email' => 'john.rodriguez@university.edu',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);

        // Create Senior Adviser
        $adviser = User::create([
            'faculty_id' => '10003',
            'name' => 'Dr. Sarah Johnson',
            'email' => 'sarah.johnson@university.edu',
            'department' => 'Computer Science',
            'role' => 'adviser'
        ]);

        // Create UserAccount for Adviser
        UserAccount::create([
            'faculty_id' => $adviser->faculty_id,
            'email' => 'sarah.johnson@university.edu',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);

        // Create Faculty Members
        $teachers = [
            [
                'faculty_id' => '10004',
                'name' => 'Prof. Michael Chen',
                'email' => 'michael.chen@university.edu',
                'role' => 'teacher'
            ],
            [
                'faculty_id' => '10005',
                'name' => 'Dr. Patricia Williams',
                'email' => 'patricia.williams@university.edu',
                'role' => 'adviser'
            ],
            [
                'faculty_id' => '10006',
                'name' => 'Prof. Robert Davis',
                'email' => 'robert.davis@university.edu',
                'role' => 'adviser'
            ],
            [
                'faculty_id' => '10007',
                'name' => 'Dr. Lisa Anderson',
                'email' => 'lisa.anderson@university.edu',
                'role' => 'panelist'
            ],
            [
                'faculty_id' => '10008',
                'name' => 'Prof. Jennifer Lee',
                'email' => 'jennifer.lee@university.edu',
                'role' => 'teacher'
            ],
            [
                'faculty_id' => '10009',
                'name' => 'Dr. Christopher Brown',
                'email' => 'christopher.brown@university.edu',
                'role' => 'adviser'
            ],
            [
                'faculty_id' => '10010',
                'name' => 'Prof. Amanda Taylor',
                'email' => 'amanda.taylor@university.edu',
                'role' => 'panelist'
            ],
            [
                'faculty_id' => '10011',
                'name' => 'Dr. Kevin Martinez',
                'email' => 'kevin.martinez@university.edu',
                'role' => 'teacher'
            ],
            [
                'faculty_id' => '10012',
                'name' => 'Prof. Rachel Green',
                'email' => 'rachel.green@university.edu',
                'role' => 'adviser'
            ]
        ];

        foreach ($teachers as $teacherData) {
            $teacher = User::create([
                'faculty_id' => $teacherData['faculty_id'],
                'name' => $teacherData['name'],
                'email' => $teacherData['email'],
                'department' => 'Computer Science',
                'role' => $teacherData['role']
            ]);

            // Create UserAccount for Teacher
            UserAccount::create([
                'faculty_id' => $teacher->faculty_id,
                'email' => $teacherData['email'],
                'password' => Hash::make('password'),
                'must_change_password' => false,
            ]);
        }

        echo "✅ Created " . (count($teachers) + 3) . " faculty users with accounts (chairperson, coordinator, adviser, teachers, panelists)\n";
    }
}
