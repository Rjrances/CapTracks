<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MilestoneTemplate;
use App\Models\MilestoneTask;

class MilestoneTaskSeeder extends Seeder
{
    public function run()
    {
        // Get existing milestone templates
        $proposal = MilestoneTemplate::where('name', 'Proposal')->first();
        $finalDefense = MilestoneTemplate::where('name', 'Final Defense')->first();

        if ($proposal) {
            // Add tasks to Proposal milestone with completion status
            $proposal->tasks()->createMany([
                [
                    'name' => 'Research Topic',
                    'description' => 'Identify and research project topic',
                    'order' => 1,
                    'is_completed' => true,
                    'completed_at' => now()->subDays(5),
                ],
                [
                    'name' => 'Proposal Draft',
                    'description' => 'Write initial proposal draft',
                    'order' => 2,
                    'is_completed' => true,
                    'completed_at' => now()->subDays(3),
                ],
                [
                    'name' => 'Submit Proposal',
                    'description' => 'Submit final proposal document',
                    'order' => 3,
                    'is_completed' => false,
                ],
            ]);
        }

        if ($finalDefense) {
            // Add tasks to Final Defense milestone
            $finalDefense->tasks()->createMany([
                [
                    'name' => 'Prepare Presentation',
                    'description' => 'Create slides and demo materials',
                    'order' => 1,
                    'is_completed' => false,
                ],
                [
                    'name' => 'Rehearse Defense',
                    'description' => 'Practice defense presentation',
                    'order' => 2,
                    'is_completed' => false,
                ],
                [
                    'name' => 'Final Submission',
                    'description' => 'Submit final project documents',
                    'order' => 3,
                    'is_completed' => false,
                ],
            ]);
        }

        // Create additional milestone templates if they don't exist
        $progressReport = MilestoneTemplate::firstOrCreate([
            'name' => 'Progress Report'
        ], [
            'description' => 'Mid-term progress report submission',
            'status' => 'todo'
        ]);

        $progressReport->tasks()->createMany([
            [
                'name' => 'Implementation Review',
                'description' => 'Review current implementation progress',
                'order' => 1,
                'is_completed' => false,
            ],
            [
                'name' => 'Write Progress Report',
                'description' => 'Document current progress and challenges',
                'order' => 2,
                'is_completed' => false,
            ],
            [
                'name' => 'Submit Progress Report',
                'description' => 'Submit progress report for review',
                'order' => 3,
                'is_completed' => false,
            ],
        ]);
    }
} 