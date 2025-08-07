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
        // Use the comprehensive test data seeder
        $this->call([
            TestDataSeeder::class
        ]);
    }
}
