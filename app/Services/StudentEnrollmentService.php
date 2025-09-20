<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Offering;
use Illuminate\Support\Collection;

class StudentEnrollmentService
{
    /**
     * Automatically enroll students in offerings based on their offer_code
     *
     * @param Collection $students
     * @return array
     */
    public function enrollStudentsByOfferCode(Collection $students): array
    {
        $results = [
            'enrolled' => [],
            'failed' => [],
            'not_found' => []
        ];

        foreach ($students as $student) {
            if (!$student->offer_code) {
                $results['failed'][] = [
                    'student' => $student,
                    'reason' => 'No offer code provided'
                ];
                continue;
            }

            $offering = Offering::where('offer_code', $student->offer_code)->first();
            
            if (!$offering) {
                $results['not_found'][] = [
                    'student' => $student,
                    'offer_code' => $student->offer_code,
                    'reason' => 'Offering not found with this offer code'
                ];
                continue;
            }

            try {
                $enrolledOffering = $student->enrollInOfferingByCode();
                if ($enrolledOffering) {
                    $results['enrolled'][] = [
                        'student' => $student,
                        'offering' => $enrolledOffering
                    ];
                } else {
                    $results['failed'][] = [
                        'student' => $student,
                        'reason' => 'Failed to enroll in offering'
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'student' => $student,
                    'reason' => 'Error: ' . $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Enroll a single student by offer code
     *
     * @param Student $student
     * @return array
     */
    public function enrollStudentByOfferCode(Student $student): array
    {
        if (!$student->offer_code) {
            return [
                'success' => false,
                'message' => 'No offer code provided for student'
            ];
        }

        $offering = Offering::where('offer_code', $student->offer_code)->first();
        
        if (!$offering) {
            return [
                'success' => false,
                'message' => "Offering not found with offer code: {$student->offer_code}"
            ];
        }

        try {
            $enrolledOffering = $student->enrollInOfferingByCode();
            if ($enrolledOffering) {
                return [
                    'success' => true,
                    'message' => "Successfully enrolled in offering: {$enrolledOffering->subject_code}",
                    'offering' => $enrolledOffering
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to enroll in offering'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get enrollment statistics
     *
     * @param array $results
     * @return array
     */
    public function getEnrollmentStats(array $results): array
    {
        return [
            'total_processed' => count($results['enrolled']) + count($results['failed']) + count($results['not_found']),
            'successfully_enrolled' => count($results['enrolled']),
            'failed_enrollments' => count($results['failed']),
            'offerings_not_found' => count($results['not_found']),
            'success_rate' => count($results['enrolled']) > 0 ? 
                round((count($results['enrolled']) / (count($results['enrolled']) + count($results['failed']) + count($results['not_found']))) * 100, 2) : 0
        ];
    }
}
