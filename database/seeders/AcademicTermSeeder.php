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
                'school_year' => '2024-2025',
                'semester' => '2024-2025 First Semester',
                'is_active' => true,
                'is_archived' => false
            ],
            [
                'school_year' => '2024-2025',
                'semester' => '2024-2025 Second Semester',
                'is_active' => false,
                'is_archived' => false
            ],
            [
                'school_year' => '2024-2025',
                'semester' => '2024-2025 Summer',
                'is_active' => false,
                'is_archived' => false
            ]
        ];

        foreach ($terms as $term) {
            AcademicTerm::create($term);
        }
    }
}
