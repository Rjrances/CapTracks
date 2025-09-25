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
        // Create faculty for all 3 semesters with same faculty_id across semesters
        $this->createFacultyForSemester('2024-2025 First Semester');
        $this->createFacultyForSemester('2024-2025 Second Semester');
        $this->createFacultyForSemester('2024-2025 Summer');
    }

    private function createFacultyForSemester($semester)
    {
        echo "Creating faculty for {$semester}...\n";

        // Use same email format across all semesters
        $emailSuffix = '@university.edu';

        // Define faculty members with same faculty_id across all semesters
        // Note: Coordinators are specifically for Capstone project management
        // Teachers handle Thesis offerings and general teaching
        $facultyMembers = [
            [
                'faculty_id' => '10001',
                'name' => 'Dr. Maria Santos',
                'role' => 'chairperson'
            ],
            [
                'faculty_id' => '10002',
                'name' => 'Prof. John Rodriguez',
                'role' => 'coordinator'  // Will handle Capstone Project I & II
            ],
            [
                'faculty_id' => '10003',
                'name' => 'Dr. Sarah Johnson',
                'role' => 'adviser'
            ],
            [
                'faculty_id' => '10004',
                'name' => 'Prof. Michael Chen',
                'role' => 'teacher'  // Will handle Thesis I & II
            ],
            [
                'faculty_id' => '10005',
                'name' => 'Dr. Patricia Williams',
                'role' => 'adviser'
            ],
            [
                'faculty_id' => '10006',
                'name' => 'Prof. Robert Davis',
                'role' => 'adviser'
            ],
            [
                'faculty_id' => '10007',
                'name' => 'Dr. Lisa Anderson',
                'role' => 'panelist'
            ],
            [
                'faculty_id' => '10008',
                'name' => 'Prof. Jennifer Lee',
                'role' => 'teacher'  // Will handle Thesis I & II
            ],
            [
                'faculty_id' => '10009',
                'name' => 'Dr. Christopher Brown',
                'role' => 'adviser'
            ],
            [
                'faculty_id' => '10010',
                'name' => 'Prof. Amanda Taylor',
                'role' => 'panelist'
            ],
            [
                'faculty_id' => '10011',
                'name' => 'Dr. Kevin Martinez',
                'role' => 'teacher'  // Will handle Thesis I & II
            ],
            [
                'faculty_id' => '10012',
                'name' => 'Prof. Rachel Green',
                'role' => 'adviser'
            ]
        ];

        foreach ($facultyMembers as $member) {
            // Create email with same format across semesters
            $email = strtolower(str_replace([' ', '.'], ['', '.'], $member['name'])) . $emailSuffix;
            
            $user = User::create([
                'faculty_id' => $member['faculty_id'],
                'name' => $member['name'],
                'email' => $email,
                'department' => 'SCS',
                'role' => $member['role'],
                'semester' => $semester
            ]);

            // Create UserAccount
            UserAccount::create([
                'faculty_id' => $user->faculty_id,
                'email' => $email,
                'password' => Hash::make('password'),
                'must_change_password' => false,
            ]);
        }

        echo "Created " . count($facultyMembers) . " faculty users for {$semester}\n";
    }
}
