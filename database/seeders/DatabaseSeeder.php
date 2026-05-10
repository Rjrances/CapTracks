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
        $seeders = [
            RoleSeeder::class,              // 1. Create roles first
            AcademicTermSeeder::class,      // 2. Create academic terms
            UserSeeder::class,              // 3. Create faculty users with accounts
            OfferingSeeder::class,          // 4. Create offerings with offer codes (before students)
            StudentSeeder::class,           // 5. Create students with accounts and offer codes
            StudentEnrollmentSeeder::class, // 6. Enroll students based on offer codes
            MilestoneSeeder::class,         // 7. Create milestone templates and tasks
            GroupSeeder::class,             // 8. Create groups and assignments
            NotificationSeeder::class,      // 9. Create test notifications
            DefenseScheduleSeeder::class    // 10. Create defense schedules
        ];

        // Optional defense roster dataset (groups + members); set DEFENSE_DATASET_SEED=true when running db:seed.
        if (filter_var(env('DEFENSE_DATASET_SEED', false), FILTER_VALIDATE_BOOL)) {
            $seeders[] = DefenseDatasetSeeder::class;
        }

        $this->call($seeders);
    }
}
