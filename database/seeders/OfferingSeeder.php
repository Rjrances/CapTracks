<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Offering;
use App\Models\User;
use App\Models\UserAccount;
use App\Models\AcademicTerm;

class OfferingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all academic terms
        $terms = AcademicTerm::all();
        
        // Get teachers (users with adviser, panelist, or teacher roles)
        $teachers = User::whereIn('role', ['adviser', 'panelist', 'teacher'])->get();
        
        if ($teachers->isEmpty()) {
            echo "❌ No teachers found. Please run UserSeeder first.\n";
            return;
        }
        
        $totalOfferings = 0;
        
        foreach ($terms as $term) {
            $offerings = [];
            
            if ($term->semester === '2024-2025 First Semester') {
                $offerings = [
                    [
                        'subject_title' => 'Capstone Project I',
                        'subject_code' => 'CS-CAP-401',
                        'offer_code' => '11000',
                        'faculty_id' => $teachers[0]->faculty_id,
                        'academic_term_id' => $term->id,
                    ],
                    [
                        'subject_title' => 'Capstone Project I',
                        'subject_code' => 'CS-CAP-401',
                        'offer_code' => '11001',
                        'faculty_id' => $teachers[1]->faculty_id ?? $teachers[0]->faculty_id,
                        'academic_term_id' => $term->id,
                    ],
                    [
                        'subject_title' => 'Capstone Project II',
                        'subject_code' => 'CS-CAP-402',
                        'offer_code' => '11002',
                        'faculty_id' => $teachers[2]->faculty_id ?? $teachers[0]->faculty_id,
                        'academic_term_id' => $term->id,
                    ],
                    [
                        'subject_title' => 'Thesis I',
                        'subject_code' => 'CS-THS-301',
                        'offer_code' => '11003',
                        'faculty_id' => $teachers[3]->faculty_id ?? $teachers[0]->faculty_id,
                        'academic_term_id' => $term->id,
                    ],
                    [
                        'subject_title' => 'Thesis II',
                        'subject_code' => 'CS-THS-302',
                        'offer_code' => '11004',
                        'faculty_id' => $teachers[4]->faculty_id ?? $teachers[0]->faculty_id,
                        'academic_term_id' => $term->id,
                    ],
                ];
            } elseif ($term->semester === '2024-2025 Second Semester') {
                $offerings = [
                    [
                        'subject_title' => 'Capstone Project I',
                        'subject_code' => 'CS-CAP-401',
                        'offer_code' => '12000',
                        'faculty_id' => $teachers[4]->faculty_id ?? $teachers[0]->faculty_id,
                        'academic_term_id' => $term->id,
                    ],
                    [
                        'subject_title' => 'Capstone Project I',
                        'subject_code' => 'CS-CAP-401',
                        'offer_code' => '12001',
                        'faculty_id' => $teachers[5]->faculty_id ?? $teachers[1]->faculty_id ?? $teachers[0]->faculty_id,
                        'academic_term_id' => $term->id,
                    ],
                    [
                        'subject_title' => 'Capstone Project II',
                        'subject_code' => 'CS-CAP-402',
                        'offer_code' => '12002',
                        'faculty_id' => $teachers[6]->faculty_id ?? $teachers[2]->faculty_id ?? $teachers[0]->faculty_id,
                        'academic_term_id' => $term->id,
                    ],
                    [
                        'subject_title' => 'Thesis I',
                        'subject_code' => 'CS-THS-301',
                        'offer_code' => '12003',
                        'faculty_id' => $teachers[7]->faculty_id ?? $teachers[3]->faculty_id ?? $teachers[0]->faculty_id,
                        'academic_term_id' => $term->id,
                    ],
                    [
                        'subject_title' => 'Thesis II',
                        'subject_code' => 'CS-THS-302',
                        'offer_code' => '12004',
                        'faculty_id' => $teachers[8]->faculty_id ?? $teachers[4]->faculty_id ?? $teachers[0]->faculty_id,
                        'academic_term_id' => $term->id,
                    ],
                ];
            } elseif ($term->semester === '2024-2025 Summer') {
                $offerings = [
                    [
                        'subject_title' => 'Capstone Project I',
                        'subject_code' => 'CS-CAP-401',
                        'offer_code' => '13000',
                        'faculty_id' => $teachers[8]->faculty_id ?? $teachers[0]->faculty_id,
                        'academic_term_id' => $term->id,
                    ],
                    [
                        'subject_title' => 'Capstone Project I',
                        'subject_code' => 'CS-CAP-401',
                        'offer_code' => '13001',
                        'faculty_id' => $teachers[0]->faculty_id,
                        'academic_term_id' => $term->id,
                    ],
                    [
                        'subject_title' => 'Thesis I',
                        'subject_code' => 'CS-THS-301',
                        'offer_code' => '13002',
                        'faculty_id' => $teachers[1]->faculty_id ?? $teachers[0]->faculty_id,
                        'academic_term_id' => $term->id,
                    ],
                    [
                        'subject_title' => 'Thesis II',
                        'subject_code' => 'CS-THS-302',
                        'offer_code' => '13003',
                        'faculty_id' => $teachers[2]->faculty_id ?? $teachers[0]->faculty_id,
                        'academic_term_id' => $term->id,
                    ],
                ];
            }

            foreach ($offerings as $offeringData) {
                Offering::firstOrCreate(
                    [
                        'offer_code' => $offeringData['offer_code'],
                        'academic_term_id' => $offeringData['academic_term_id']
                    ],
                    $offeringData
                );
                $totalOfferings++;
            }
        }
        
        echo "Created {$totalOfferings} offerings with offer codes for all 3 terms (First: 5, Second: 5, Summer: 4)\n";
    }
}
