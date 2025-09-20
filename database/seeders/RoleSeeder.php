<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $roles = [
            ['name' => 'chairperson'],
            ['name' => 'coordinator'],
            ['name' => 'teacher'],
            ['name' => 'adviser'],
            ['name' => 'panelist'],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate($roleData);
        }

        echo "✅ Created " . count($roles) . " roles\n";

        // Assign roles to existing users
        $users = User::all();
        foreach ($users as $user) {
            $rolesToAssign = [];
            
            // Assign roles based on user's primary role
            if ($user->role === 'chairperson') {
                $rolesToAssign[] = 'chairperson';
            } elseif ($user->role === 'coordinator') {
                $rolesToAssign[] = 'coordinator';
            } elseif ($user->role === 'teacher') {
                $rolesToAssign[] = 'teacher';
            } elseif ($user->role === 'adviser') {
                $rolesToAssign[] = 'adviser';
            } elseif ($user->role === 'panelist') {
                $rolesToAssign[] = 'panelist';
            }

            // Assign additional roles based on user's effective roles
            if ($user->offerings()->exists() && !in_array('coordinator', $rolesToAssign)) {
                $rolesToAssign[] = 'coordinator';
            }
            
            if (\App\Models\Group::where('faculty_id', $user->faculty_id)->exists() && !in_array('adviser', $rolesToAssign)) {
                $rolesToAssign[] = 'adviser';
            }

            // Assign the roles
            if (!empty($rolesToAssign)) {
                $user->assignRoles($rolesToAssign);
                echo "✅ Assigned roles [" . implode(', ', $rolesToAssign) . "] to {$user->name}\n";
            }
        }
    }
}