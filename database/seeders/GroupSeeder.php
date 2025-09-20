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
        $this->createTestGroups();
    }

    /**
     * Create capstone project groups with students and advisers
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

        echo "✅ Created 3 capstone project groups with student assignments\n";
        echo "   - Smart Campus Management System\n";
        echo "   - Mobile Learning Assistant\n";
        echo "   - Intelligent Data Analytics Platform\n";
    }
}
