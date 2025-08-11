<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run seeders in proper order for dependencies
        $this->call([
            RoleSeeder::class,           // 1. Create roles first
            AcademicTermSeeder::class,   // 2. Create academic terms
            UserSeeder::class,           // 3. Create faculty users with roles
            StudentSeeder::class,        // 4. Create students
            MilestoneSeeder::class,      // 5. Create milestone templates and tasks
            GroupSeeder::class,          // 6. Create groups and assignments
            NotificationSeeder::class,   // 7. Create test notifications
            // Keep existing seeders for additional data
            OfferingSeeder::class,
            DefenseScheduleSeeder::class
        ]);
    }
}
