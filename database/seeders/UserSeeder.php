<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createFacultyUsers();
        $this->createMultiRoleUser();
    }

    /**
     * Create faculty users with proper 5-digit IDs
     */
    private function createFacultyUsers()
    {
        // Create Coordinator
        $coordinator = User::create([
            'school_id' => '12345', // 5 digits
            'name' => 'Test Coordinator',
            'email' => 'coordinator@test.com',
            'password' => Hash::make('password'),
            'department' => 'Computer Science',
            'position' => 'Course Coordinator'
        ]);

        // Create Chairperson
        $chairperson = User::create([
            'school_id' => '23456', // 5 digits
            'name' => 'Test Chairperson',
            'email' => 'chairperson@test.com',
            'password' => Hash::make('password'),
            'department' => 'Computer Science',
            'position' => 'Department Chair'
        ]);

        // Create Adviser
        $adviser = User::create([
            'school_id' => '34567', // 5 digits
            'name' => 'Test Adviser',
            'email' => 'adviser@test.com',
            'password' => Hash::make('password'),
            'department' => 'Computer Science',
            'position' => 'Faculty Adviser'
        ]);

        // Assign roles using the new multi-role system
        $this->assignRolesToUsers([
            'coordinator' => $coordinator,
            'chairperson' => $chairperson,
            'adviser' => $adviser
        ]);

        echo "✅ Created faculty users (chairperson, coordinator, adviser)\n";
    }

    /**
     * Create a test user with multiple roles (Coordinator + Adviser)
     */
    private function createMultiRoleUser()
    {
        $multiRoleUser = User::create([
            'school_id' => '45678', // 5 digits
            'name' => 'RJ Multi-Role User',
            'email' => 'rj@test.com',
            'password' => Hash::make('password'),
            'department' => 'Computer Science',
            'position' => 'Coordinator & Adviser'
        ]);

        // Assign both coordinator and adviser roles
        $coordinatorRole = Role::where('name', 'coordinator')->first();
        $adviserRole = Role::where('name', 'adviser')->first();

        if ($coordinatorRole) {
            $multiRoleUser->roles()->attach($coordinatorRole->id);
        }
        if ($adviserRole) {
            $multiRoleUser->roles()->attach($adviserRole->id);
        }

        echo "✅ Created multi-role user: RJ (Coordinator + Adviser)\n";
    }

    /**
     * Assign roles to users using the new multi-role system
     */
    private function assignRolesToUsers($users)
    {
        // Get role models
        $coordinatorRole = Role::where('name', 'coordinator')->first();
        $chairpersonRole = Role::where('name', 'chairperson')->first();
        $adviserRole = Role::where('name', 'adviser')->first();

        if ($coordinatorRole && isset($users['coordinator'])) {
            $users['coordinator']->roles()->attach($coordinatorRole->id);
        }

        if ($chairpersonRole && isset($users['chairperson'])) {
            $users['chairperson']->roles()->attach($chairpersonRole->id);
        }

        if ($adviserRole && isset($users['adviser'])) {
            $users['adviser']->roles()->attach($adviserRole->id);
        }

        echo "✅ Assigned roles to faculty users\n";
    }
}
