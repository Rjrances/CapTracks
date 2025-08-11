<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AcademicTerm;

class AcademicTermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $terms = [
            [
                'name' => 'First Semester',
                'academic_year' => '2024-2025',
                'start_date' => '2024-08-01',
                'end_date' => '2024-12-15',
                'is_active' => true,
                'is_archived' => false
            ],
            [
                'name' => 'Second Semester',
                'academic_year' => '2024-2025',
                'start_date' => '2025-01-15',
                'end_date' => '2025-05-30',
                'is_active' => false,
                'is_archived' => false
            ],
            [
                'name' => 'Summer Term',
                'academic_year' => '2024-2025',
                'start_date' => '2025-06-01',
                'end_date' => '2025-07-31',
                'is_active' => false,
                'is_archived' => false
            ]
        ];

        foreach ($terms as $term) {
            AcademicTerm::create($term);
        }
    }
}
