<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProjectSubmission;
use App\Models\Student;

class ProjectSubmissionSeeder extends Seeder
{
    public function run()
    {
        // Get some students to create submissions for
        $students = Student::take(5)->get();

        if ($students->count() > 0) {
            foreach ($students as $student) {
                // Create a proposal submission
                ProjectSubmission::create([
                    'student_id' => $student->id,
                    'file_path' => 'submissions/sample_proposal.pdf',
                    'type' => 'proposal',
                    'status' => 'pending',
                    'teacher_comment' => 'Good initial proposal, needs some refinement.',
                    'submitted_at' => now()->subDays(3),
                ]);

                // Create a final submission for some students
                if ($student->id % 2 == 0) {
                    ProjectSubmission::create([
                        'student_id' => $student->id,
                        'file_path' => 'submissions/sample_final.pdf',
                        'type' => 'final',
                        'status' => 'approved',
                        'teacher_comment' => 'Excellent work! Project completed successfully.',
                        'submitted_at' => now()->subDays(1),
                    ]);
                }
            }
        }
    }
} 