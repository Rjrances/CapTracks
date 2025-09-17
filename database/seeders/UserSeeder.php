<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->createFacultyUsers();
    }


    private function createFacultyUsers()
    {
        // Create Coordinator
        $coordinator = User::create([
            'school_id' => '12345', // 5 digits
            'name' => 'Test Coordinator',
            'email' => 'coordinator@test.com',
            'password' => Hash::make('password'),
            'department' => 'Computer Science',
            'role' => 'coordinator'
        ]);

        // Create Chairperson
        $chairperson = User::create([
            'school_id' => '23456', // 5 digits
            'name' => 'Test Chairperson',
            'email' => 'chairperson@test.com',
            'password' => Hash::make('password'),
            'department' => 'Computer Science',
            'role' => 'chairperson'
        ]);

        // Create Adviser
        $adviser = User::create([
            'school_id' => '34567', // 5 digits
            'name' => 'Test Adviser',
            'email' => 'adviser@test.com',
            'password' => Hash::make('password'),
            'department' => 'Computer Science',
            'role' => 'adviser'
        ]);

        // Create Teacher
        $teacher = User::create([
            'school_id' => '45678', // 5 digits
            'name' => 'Test Teacher',
            'email' => 'teacher@test.com',
            'password' => Hash::make('password'),
            'department' => 'Computer Science',
            'role' => 'teacher'
        ]);

        echo "✅ Created faculty users (chairperson, coordinator, adviser, teacher)\n";
    }
}
