<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Offering;
use App\Models\User;
use App\Models\AcademicTerm;
use App\Models\Role;

class OfferingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get active academic term
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        
        // Get teachers
        $teachers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['adviser', 'panelist']);
        })->get();
        
        if ($teachers->isEmpty()) {
            // Create some sample teachers if none exist
            $teachers = collect([
                User::create([
                    'school_id' => 'FAC001',
                    'name' => 'Dr. John Smith',
                    'email' => 'john.smith@university.edu',
                    'password' => bcrypt('password'),
                    'birthday' => now()->subYears(30),
                    'department' => 'Computer Science',
                    'position' => 'Associate Professor',
                    'must_change_password' => true,
                ]),
                User::create([
                    'school_id' => 'FAC002',
                    'name' => 'Prof. Jane Doe',
                    'email' => 'jane.doe@university.edu',
                    'password' => bcrypt('password'),
                    'birthday' => now()->subYears(35),
                    'department' => 'Computer Science',
                    'position' => 'Professor',
                    'must_change_password' => true,
                ]),
                User::create([
                    'school_id' => 'FAC003',
                    'name' => 'Dr. Mike Johnson',
                    'email' => 'mike.johnson@university.edu',
                    'password' => bcrypt('password'),
                    'birthday' => now()->subYears(28),
                    'department' => 'Computer Science',
                    'position' => 'Assistant Professor',
                    'must_change_password' => true,
                ]),
            ]);
            
            // Assign roles to the created users
            $teachers[0]->roles()->attach(Role::where('name', 'adviser')->first());
            $teachers[1]->roles()->attach(Role::where('name', 'adviser')->first());
            $teachers[2]->roles()->attach(Role::where('name', 'panelist')->first());
        }
        
        if ($activeTerm) {
            $offerings = [
                [
                    'subject_title' => 'Capstone Project I',
                    'subject_code' => 'CS 401',
                    'teacher_id' => $teachers->first()->id,
                    'academic_term_id' => $activeTerm->id,
                ],
                [
                    'subject_title' => 'Capstone Project II',
                    'subject_code' => 'CS 402',
                    'teacher_id' => $teachers->count() > 1 ? $teachers[1]->id : $teachers->first()->id,
                    'academic_term_id' => $activeTerm->id,
                ],
                [
                    'subject_title' => 'Software Engineering',
                    'subject_code' => 'CS 301',
                    'teacher_id' => $teachers->count() > 2 ? $teachers[2]->id : $teachers->first()->id,
                    'academic_term_id' => $activeTerm->id,
                ],
            ];

            foreach ($offerings as $offeringData) {
                Offering::create($offeringData);
            }
        }
    }
}
