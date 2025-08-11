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
     * Create test groups with students and advisers
     */
    private function createTestGroups()
    {
        // Get students and advisers
        $students = Student::all();
        $adviser = User::whereHas('roles', function($query) {
            $query->where('name', 'adviser');
        })->first();
        
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        if (!$adviser) {
            echo "⚠️ No adviser found. Please run UserSeeder first.\n";
            return;
        }

        if (!$activeTerm) {
            echo "⚠️ No active academic term found. Please run AcademicTermSeeder first.\n";
            return;
        }

        // Group 1: Web Development Team
        $group1 = Group::create([
            'name' => 'Web Development Team',
            'description' => 'Building a modern web application for student management',
            'adviser_id' => $adviser->id,
            'academic_term_id' => $activeTerm->id,
            'capstone_phase' => 1,
            'overall_progress_percentage' => 75
        ]);

        // Add students to group 1
        $group1->members()->attach([
            $students[0]->id => ['role' => 'leader'],
            $students[1]->id => ['role' => 'member']
        ]);

        // Group 2: Mobile App Team
        $group2 = Group::create([
            'name' => 'Mobile App Team',
            'description' => 'Developing a mobile application for campus navigation',
            'adviser_id' => $adviser->id,
            'academic_term_id' => $activeTerm->id,
            'capstone_phase' => 2,
            'overall_progress_percentage' => 45
        ]);

        // Add students to group 2
        $group2->members()->attach([
            $students[2]->id => ['role' => 'leader'],
            $students[3]->id => ['role' => 'member']
        ]);

        // Group 3: AI Research Team
        $group3 = Group::create([
            'name' => 'AI Research Team',
            'description' => 'Research project on machine learning applications',
            'adviser_id' => $adviser->id,
            'academic_term_id' => $activeTerm->id,
            'capstone_phase' => 1,
            'overall_progress_percentage' => 90
        ]);

        // Add students to group 3
        $group3->members()->attach([
            $students[4]->id => ['role' => 'leader'],
            $students[5]->id => ['role' => 'member']
        ]);

        echo "✅ Created 3 test groups with student assignments\n";
        echo "   - Web Development Team (75% progress)\n";
        echo "   - Mobile App Team (45% progress)\n";
        echo "   - AI Research Team (90% progress)\n";
    }
}
