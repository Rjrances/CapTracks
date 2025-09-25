<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\StudentAccount;
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
     * Create students with proper 10-digit IDs and offer codes for all 3 terms
     */
    private function createTestStudents()
    {
        // Students for First Semester (2024-2025 First Semester)
        $firstSemesterStudents = [
            [
                'name' => 'Alexandra Martinez',
                'email' => 'alexandra.martinez@student.university.edu',
                'student_id' => '2024000001',
                'semester' => '2024-2025 First Semester',
                'course' => 'BS Computer Science',
                'offer_code' => '11000'
            ],
            [
                'name' => 'David Thompson',
                'email' => 'david.thompson@student.university.edu',
                'student_id' => '2024000002',
                'semester' => '2024-2025 First Semester',
                'course' => 'BS Computer Science',
                'offer_code' => '11000'
            ],
            [
                'name' => 'Emily Rodriguez',
                'email' => 'emily.rodriguez@student.university.edu',
                'student_id' => '2024000003',
                'semester' => '2024-2025 First Semester',
                'course' => 'BS Information Technology',
                'offer_code' => '11001'
            ],
            [
                'name' => 'James Wilson',
                'email' => 'james.wilson@student.university.edu',
                'student_id' => '2024000004',
                'semester' => '2024-2025 First Semester',
                'course' => 'BS Information Technology',
                'offer_code' => '11001'
            ],
            [
                'name' => 'Sophia Kim',
                'email' => 'sophia.kim@student.university.edu',
                'student_id' => '2024000005',
                'semester' => '2024-2025 First Semester',
                'course' => 'BS Entertainment and Multimedia Computing',
                'offer_code' => '11002'
            ],
            [
                'name' => 'Ryan O\'Connor',
                'email' => 'ryan.oconnor@student.university.edu',
                'student_id' => '2024000006',
                'semester' => '2024-2025 First Semester',
                'course' => 'BS Entertainment and Multimedia Computing',
                'offer_code' => '11002'
            ],
            [
                'name' => 'Isabella Garcia',
                'email' => 'isabella.garcia@student.university.edu',
                'student_id' => '2024000007',
                'semester' => '2024-2025 First Semester',
                'course' => 'BS Computer Science',
                'offer_code' => '11003'
            ],
            [
                'name' => 'Lucas Anderson',
                'email' => 'lucas.anderson@student.university.edu',
                'student_id' => '2024000008',
                'semester' => '2024-2025 First Semester',
                'course' => 'BS Computer Science',
                'offer_code' => '11003'
            ],
            [
                'name' => 'Olivia White',
                'email' => 'olivia.white@student.university.edu',
                'student_id' => '2024000009',
                'semester' => '2024-2025 First Semester',
                'course' => 'BS Information Technology',
                'offer_code' => '11004'
            ],
            [
                'name' => 'Noah Johnson',
                'email' => 'noah.johnson@student.university.edu',
                'student_id' => '2024000010',
                'semester' => '2024-2025 First Semester',
                'course' => 'BS Information Technology',
                'offer_code' => '11004'
            ]
        ];

        // Students for Second Semester (2024-2025 Second Semester)
        $secondSemesterStudents = [
            [
                'name' => 'Gabriela Santos',
                'email' => 'gabriela.santos@student.university.edu',
                'student_id' => '2024000011',
                'semester' => '2024-2025 Second Semester',
                'course' => 'BS Computer Science',
                'offer_code' => '12000'
            ],
            [
                'name' => 'Marcus Chen',
                'email' => 'marcus.chen@student.university.edu',
                'student_id' => '2024000012',
                'semester' => '2024-2025 Second Semester',
                'course' => 'BS Computer Science',
                'offer_code' => '12000'
            ],
            [
                'name' => 'Natalie Foster',
                'email' => 'natalie.foster@student.university.edu',
                'student_id' => '2024000013',
                'semester' => '2024-2025 Second Semester',
                'course' => 'BS Information Technology',
                'offer_code' => '12001'
            ],
            [
                'name' => 'Tyler Richardson',
                'email' => 'tyler.richardson@student.university.edu',
                'student_id' => '2024000014',
                'semester' => '2024-2025 Second Semester',
                'course' => 'BS Information Technology',
                'offer_code' => '12001'
            ],
            [
                'name' => 'Zoe Mitchell',
                'email' => 'zoe.mitchell@student.university.edu',
                'student_id' => '2024000015',
                'semester' => '2024-2025 Second Semester',
                'course' => 'BS Entertainment and Multimedia Computing',
                'offer_code' => '12002'
            ],
            [
                'name' => 'Ethan Parker',
                'email' => 'ethan.parker@student.university.edu',
                'student_id' => '2024000016',
                'semester' => '2024-2025 Second Semester',
                'course' => 'BS Entertainment and Multimedia Computing',
                'offer_code' => '12002'
            ],
            [
                'name' => 'Ava Brown',
                'email' => 'ava.brown@student.university.edu',
                'student_id' => '2024000017',
                'semester' => '2024-2025 Second Semester',
                'course' => 'BS Computer Science',
                'offer_code' => '12003'
            ],
            [
                'name' => 'William Davis',
                'email' => 'william.davis@student.university.edu',
                'student_id' => '2024000018',
                'semester' => '2024-2025 Second Semester',
                'course' => 'BS Computer Science',
                'offer_code' => '12003'
            ],
            [
                'name' => 'Mia Taylor',
                'email' => 'mia.taylor@student.university.edu',
                'student_id' => '2024000019',
                'semester' => '2024-2025 Second Semester',
                'course' => 'BS Information Technology',
                'offer_code' => '12004'
            ],
            [
                'name' => 'Benjamin Miller',
                'email' => 'benjamin.miller@student.university.edu',
                'student_id' => '2024000020',
                'semester' => '2024-2025 Second Semester',
                'course' => 'BS Information Technology',
                'offer_code' => '12004'
            ]
        ];

        // Students for Summer (2024-2025 Summer)
        $summerStudents = [
            [
                'name' => 'Priya Patel',
                'email' => 'priya.patel@student.university.edu',
                'student_id' => '2024000021',
                'semester' => '2024-2025 Summer',
                'course' => 'BS Computer Science',
                'offer_code' => '13000'
            ],
            [
                'name' => 'Alexander Torres',
                'email' => 'alexander.torres@student.university.edu',
                'student_id' => '2024000022',
                'semester' => '2024-2025 Summer',
                'course' => 'BS Computer Science',
                'offer_code' => '13000'
            ],
            [
                'name' => 'Luna Zhang',
                'email' => 'luna.zhang@student.university.edu',
                'student_id' => '2024000023',
                'semester' => '2024-2025 Summer',
                'course' => 'BS Information Technology',
                'offer_code' => '13001'
            ],
            [
                'name' => 'Caleb Murphy',
                'email' => 'caleb.murphy@student.university.edu',
                'student_id' => '2024000024',
                'semester' => '2024-2025 Summer',
                'course' => 'BS Information Technology',
                'offer_code' => '13001'
            ],
            [
                'name' => 'Charlotte Wilson',
                'email' => 'charlotte.wilson@student.university.edu',
                'student_id' => '2024000025',
                'semester' => '2024-2025 Summer',
                'course' => 'BS Entertainment and Multimedia Computing',
                'offer_code' => '13002'
            ],
            [
                'name' => 'Henry Moore',
                'email' => 'henry.moore@student.university.edu',
                'student_id' => '2024000026',
                'semester' => '2024-2025 Summer',
                'course' => 'BS Entertainment and Multimedia Computing',
                'offer_code' => '13002'
            ],
            [
                'name' => 'Emma Thompson',
                'email' => 'emma.thompson@student.university.edu',
                'student_id' => '2024000027',
                'semester' => '2024-2025 Summer',
                'course' => 'BS Computer Science',
                'offer_code' => '13003'
            ],
            [
                'name' => 'Daniel Rodriguez',
                'email' => 'daniel.rodriguez@student.university.edu',
                'student_id' => '2024000028',
                'semester' => '2024-2025 Summer',
                'course' => 'BS Computer Science',
                'offer_code' => '13003'
            ]
        ];

        $allStudents = array_merge($firstSemesterStudents, $secondSemesterStudents, $summerStudents);

        foreach ($allStudents as $studentData) {
            // Create student
            $student = Student::create([
                'name' => $studentData['name'],
                'email' => $studentData['email'],
                'student_id' => $studentData['student_id'],
                'semester' => $studentData['semester'],
                'course' => $studentData['course'],
                'offer_code' => $studentData['offer_code']
            ]);

            // Create student account
            StudentAccount::create([
                'student_id' => $student->student_id,
                'email' => $studentData['email'],
                'password' => Hash::make('password'),
                'must_change_password' => false,
            ]);

            // Enroll student in offering based on offer_code
            $student->enrollInOfferingByCode();
        }

        echo "Created " . count($allStudents) . " students with accounts and offer codes for all 3 terms (First: 10, Second: 10, Summer: 8)\n";
    }
}
