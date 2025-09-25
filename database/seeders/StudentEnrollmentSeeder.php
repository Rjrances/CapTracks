<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Services\StudentEnrollmentService;

class StudentEnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->enrollStudentsByOfferCode();
    }

    /**
     * Enroll students in offerings based on their offer_code
     */
    private function enrollStudentsByOfferCode()
    {
        $enrollmentService = new StudentEnrollmentService();
        
        // Get all students with offer codes
        $students = Student::whereNotNull('offer_code')->get();
        
        if ($students->isEmpty()) {
            echo "Warning: No students with offer codes found for enrollment\n";
            return;
        }

        // Enroll students using the service
        $results = $enrollmentService->enrollStudentsByOfferCode($students);
        $stats = $enrollmentService->getEnrollmentStats($results);

        // Display results
        echo "Student Enrollment Results:\n";
        echo "   Total processed: {$stats['total_processed']}\n";
        echo "   Successfully enrolled: {$stats['successfully_enrolled']}\n";
        echo "   Failed enrollments: {$stats['failed_enrollments']}\n";
        echo "   Offerings not found: {$stats['offerings_not_found']}\n";
        echo "   Success rate: {$stats['success_rate']}%\n";

        // Log detailed results
        if (!empty($results['enrolled'])) {
            echo "\nSuccessfully enrolled students:\n";
            foreach ($results['enrolled'] as $result) {
                echo "   - {$result['student']->name} ({$result['student']->student_id}) in {$result['offering']->subject_code}\n";
            }
        }

        if (!empty($results['failed'])) {
            echo "\n❌ Failed enrollments:\n";
            foreach ($results['failed'] as $result) {
                echo "   - {$result['student']->name} ({$result['student']->student_id}): {$result['reason']}\n";
            }
        }

        if (!empty($results['not_found'])) {
            echo "\n⚠️  Offerings not found:\n";
            foreach ($results['not_found'] as $result) {
                echo "   - {$result['student']->name} ({$result['student']->student_id}) with offer code: {$result['offer_code']}\n";
            }
        }
    }
}