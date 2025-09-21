<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;
use App\Models\Student;
use App\Models\User;
use App\Models\AcademicTerm;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createGroupsForAllSemesters();
    }

    /**
     * Create groups for all semesters with different numbers
     */
    private function createGroupsForAllSemesters()
    {
        $academicTerms = AcademicTerm::all();
        
        foreach ($academicTerms as $term) {
            $this->createGroupsForSemester($term);
        }
    }

    /**
     * Create groups for a specific semester
     */
    private function createGroupsForSemester($term)
    {
        // Get students and advisers for this semester
        $students = Student::where('semester', $term->semester)->get();
        $advisers = User::where('role', 'adviser')
            ->where('semester', $term->semester)
            ->get();

        if ($advisers->isEmpty()) {
            echo "⚠️ No advisers found for {$term->semester}. Skipping groups.\n";
            return;
        }

        if ($students->count() < 2) {
            echo "⚠️ Not enough students for {$term->semester}. Skipping groups.\n";
            return;
        }

        // Determine number of groups based on semester
        $groupCount = $this->getGroupCountForSemester($term->semester);
        $adviserIndex = 0;

        echo "Creating {$groupCount} groups for {$term->semester}...\n";

        for ($i = 1; $i <= $groupCount; $i++) {
            $adviser = $advisers[$adviserIndex % $advisers->count()];
            $adviserIndex++;

            $group = Group::create([
                'name' => $this->getGroupName($term->semester, $i),
                'description' => $this->getGroupDescription($term->semester, $i),
                'faculty_id' => $adviser->faculty_id,
                'academic_term_id' => $term->id
            ]);

            // Add students to group
            $this->assignStudentsToGroup($group, $students, $i);
            
            // Assign offering based on group leader's enrollment
            $this->assignOfferingToGroup($group);
        }

        echo "✅ Created {$groupCount} groups for {$term->semester}\n";
    }

    /**
     * Get number of groups for each semester
     */
    private function getGroupCountForSemester($semester)
    {
        $counts = [
            '2024-2025 First Semester' => 3,
            '2024-2025 Second Semester' => 5,
            '2024-2025 Summer' => 2
        ];

        return $counts[$semester] ?? 3;
    }

    /**
     * Get group name based on semester and index
     */
    private function getGroupName($semester, $index)
    {
        $names = [
            '2024-2025 First Semester' => [
                'Smart Campus Management System',
                'Mobile Learning Assistant',
                'Intelligent Data Analytics Platform'
            ],
            '2024-2025 Second Semester' => [
                'E-Learning Platform with AI',
                'Smart Library Management System',
                'Student Performance Analytics',
                'Campus Security Monitoring System',
                'Digital Assignment Management'
            ],
            '2024-2025 Summer' => [
                'Virtual Reality Learning Environment',
                'Blockchain-based Certificate System'
            ]
        ];

        $semesterNames = $names[$semester] ?? ['Generic Project'];
        return $semesterNames[$index - 1] ?? "Project Group {$index}";
    }

    /**
     * Get group description based on semester and index
     */
    private function getGroupDescription($semester, $index)
    {
        $descriptions = [
            '2024-2025 First Semester' => [
                'Developing an integrated web-based platform for campus resource management and student services',
                'Creating an AI-powered mobile application for personalized learning and academic support',
                'Building a comprehensive data visualization and analytics platform for educational insights'
            ],
            '2024-2025 Second Semester' => [
                'An advanced e-learning platform with AI-powered personalized learning paths and real-time assessment',
                'A comprehensive library management system with RFID integration and automated book tracking',
                'A data analytics platform for tracking and improving student academic performance',
                'A campus-wide security monitoring system with facial recognition and emergency response',
                'A digital platform for assignment submission, grading, and feedback management'
            ],
            '2024-2025 Summer' => [
                'An immersive VR environment for interactive learning experiences in various subjects',
                'A secure blockchain-based system for issuing and verifying academic certificates'
            ]
        ];

        $semesterDescriptions = $descriptions[$semester] ?? ['A capstone project for academic completion'];
        return $semesterDescriptions[$index - 1] ?? "Description for Project Group {$index}";
    }

    /**
     * Assign students to a group
     */
    private function assignStudentsToGroup($group, $students, $groupIndex)
    {
        $studentsPerGroup = 2;
        $startIndex = ($groupIndex - 1) * $studentsPerGroup;
        
        for ($i = 0; $i < $studentsPerGroup && $startIndex + $i < $students->count(); $i++) {
            $student = $students[$startIndex + $i];
            $role = $i === 0 ? 'leader' : 'member';
            
            try {
                $group->members()->attach($student->student_id, ['role' => $role]);
            } catch (\Exception $e) {
                // Ignore duplicate entry errors
            }
        }
    }

    /**
     * Assign offering to group based on group leader's enrollment
     */
    private function assignOfferingToGroup($group)
    {
        // Get the group leader
        $leader = $group->members()->where('group_members.role', 'leader')->first();
        
        if (!$leader) {
            echo "⚠️  No leader found for group {$group->name}, skipping offering assignment\n";
            return;
        }
        
        // Get the leader's current offering
        $leaderOffering = $leader->offerings()->first();
        
        if (!$leaderOffering) {
            echo "⚠️  Leader {$leader->name} not enrolled in any offering, skipping offering assignment\n";
            return;
        }
        
        // Update group with offering
        $group->update([
            'offering_id' => $leaderOffering->id
        ]);
        
        echo "✅ Assigned offering {$leaderOffering->offer_code} to group {$group->name}\n";
    }

    /**
     * Create capstone project groups with students and advisers (legacy method)
     */
    private function createTestGroups()
    {
        // Get students and advisers
        $students = Student::all();
        $adviser = User::where('role', 'adviser')->first();
        
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        if (!$adviser) {
            echo "⚠️ No adviser found. Please run UserSeeder first.\n";
            return;
        }

        if (!$activeTerm) {
            echo "⚠️ No active academic term found. Please run AcademicTermSeeder first.\n";
            return;
        }

        // Group 1: Smart Campus Management System
        $group1 = Group::create([
            'name' => 'Smart Campus Management System',
            'description' => 'Developing an integrated web-based platform for campus resource management and student services',
            'faculty_id' => $adviser->faculty_id,
            'academic_term_id' => $activeTerm->id
        ]);

        // Add students to group 1
        try {
            $group1->members()->attach($students[0]->student_id, ['role' => 'leader']);
        } catch (\Exception $e) {
            // Ignore duplicate entry errors
        }
        try {
            $group1->members()->attach($students[1]->student_id, ['role' => 'member']);
        } catch (\Exception $e) {
            // Ignore duplicate entry errors
        }

        // Group 2: Mobile Learning Assistant
        $group2 = Group::create([
            'name' => 'Mobile Learning Assistant',
            'description' => 'Creating an AI-powered mobile application for personalized learning and academic support',
            'faculty_id' => $adviser->faculty_id,
            'academic_term_id' => $activeTerm->id
        ]);

        // Add students to group 2
        try {
            $group2->members()->attach($students[2]->student_id, ['role' => 'leader']);
        } catch (\Exception $e) {
            // Ignore duplicate entry errors
        }
        try {
            $group2->members()->attach($students[3]->student_id, ['role' => 'member']);
        } catch (\Exception $e) {
            // Ignore duplicate entry errors
        }

        // Group 3: Intelligent Data Analytics Platform
        $group3 = Group::create([
            'name' => 'Intelligent Data Analytics Platform',
            'description' => 'Building a comprehensive data visualization and analytics platform for educational insights',
            'faculty_id' => $adviser->faculty_id,
            'academic_term_id' => $activeTerm->id
        ]);

        // Add students to group 3
        try {
            $group3->members()->attach($students[4]->student_id, ['role' => 'leader']);
        } catch (\Exception $e) {
            // Ignore duplicate entry errors
        }
        try {
            $group3->members()->attach($students[5]->student_id, ['role' => 'member']);
        } catch (\Exception $e) {
            // Ignore duplicate entry errors
        }

        // Assign offerings to test groups
        $this->assignOfferingToGroup($group1);
        $this->assignOfferingToGroup($group2);
        $this->assignOfferingToGroup($group3);

        echo "✅ Created 3 capstone project groups with student assignments\n";
        echo "   - Smart Campus Management System\n";
        echo "   - Mobile Learning Assistant\n";
        echo "   - Intelligent Data Analytics Platform\n";
    }
}
