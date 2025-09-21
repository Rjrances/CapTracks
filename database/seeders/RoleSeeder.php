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
    }
}