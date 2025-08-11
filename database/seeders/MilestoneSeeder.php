<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MilestoneTemplate;
use App\Models\MilestoneTask;

class MilestoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createMilestoneTemplates();
    }

    /**
     * Create milestone templates with their associated tasks
     */
    private function createMilestoneTemplates()
    {
        // Milestone 1: Project Proposal
        $proposal = MilestoneTemplate::create([
            'name' => 'Project Proposal',
            'description' => 'Initial project proposal and requirements gathering',
            'status' => 'todo',
            'order' => 1
        ]);

        $this->createProposalTasks($proposal);

        // Milestone 2: System Design
        $design = MilestoneTemplate::create([
            'name' => 'System Design',
            'description' => 'System architecture and design documentation',
            'status' => 'todo',
            'order' => 2
        ]);

        $this->createDesignTasks($design);

        // Milestone 3: Implementation
        $implementation = MilestoneTemplate::create([
            'name' => 'Implementation',
            'description' => 'Core system development and coding',
            'status' => 'todo',
            'order' => 3
        ]);

        $this->createImplementationTasks($implementation);

        // Milestone 4: Testing & Documentation
        $testing = MilestoneTemplate::create([
            'name' => 'Testing & Documentation',
            'description' => 'System testing and final documentation',
            'status' => 'todo',
            'order' => 4
        ]);

        $this->createTestingTasks($testing);

        echo "✅ Created 4 milestone templates with tasks\n";
    }

    /**
     * Create tasks for Project Proposal milestone
     */
    private function createProposalTasks($milestone)
    {
        $tasks = [
            [
                'name' => 'Project Title and Description',
                'description' => 'Define the project title and provide a detailed description',
                'order' => 1
            ],
            [
                'name' => 'Problem Statement',
                'description' => 'Clearly define the problem the project aims to solve',
                'order' => 2
            ],
            [
                'name' => 'Objectives and Scope',
                'description' => 'List project objectives and define the scope',
                'order' => 3
            ],
            [
                'name' => 'Literature Review',
                'description' => 'Research existing solutions and related work',
                'order' => 4
            ]
        ];

        foreach ($tasks as $task) {
            MilestoneTask::create([
                'milestone_template_id' => $milestone->id,
                'name' => $task['name'],
                'description' => $task['description'],
                'order' => $task['order']
            ]);
        }
    }

    /**
     * Create tasks for System Design milestone
     */
    private function createDesignTasks($milestone)
    {
        $tasks = [
            [
                'name' => 'System Architecture',
                'description' => 'Design the overall system architecture',
                'order' => 1
            ],
            [
                'name' => 'Database Design',
                'description' => 'Design the database schema and relationships',
                'order' => 2
            ],
            [
                'name' => 'User Interface Design',
                'description' => 'Design the user interface mockups',
                'order' => 3
            ],
            [
                'name' => 'API Design',
                'description' => 'Design the API endpoints and structure',
                'order' => 4
            ]
        ];

        foreach ($tasks as $task) {
            MilestoneTask::create([
                'milestone_template_id' => $milestone->id,
                'name' => $task['name'],
                'description' => $task['description'],
                'order' => $task['order']
            ]);
        }
    }

    /**
     * Create tasks for Implementation milestone
     */
    private function createImplementationTasks($milestone)
    {
        $tasks = [
            [
                'name' => 'Backend Development',
                'description' => 'Implement the backend functionality',
                'order' => 1
            ],
            [
                'name' => 'Frontend Development',
                'description' => 'Implement the user interface',
                'order' => 2
            ],
            [
                'name' => 'Database Implementation',
                'description' => 'Create and populate the database',
                'order' => 3
            ],
            [
                'name' => 'Integration Testing',
                'description' => 'Test the integrated system',
                'order' => 4
            ]
        ];

        foreach ($tasks as $task) {
            MilestoneTask::create([
                'milestone_template_id' => $milestone->id,
                'name' => $task['name'],
                'description' => $task['description'],
                'order' => $task['order']
            ]);
        }
    }

    /**
     * Create tasks for Testing & Documentation milestone
     */
    private function createTestingTasks($milestone)
    {
        $tasks = [
            [
                'name' => 'Unit Testing',
                'description' => 'Write and run unit tests for all components',
                'order' => 1
            ],
            [
                'name' => 'Integration Testing',
                'description' => 'Test component integration and system flow',
                'order' => 2
            ],
            [
                'name' => 'User Acceptance Testing',
                'description' => 'Conduct UAT with stakeholders',
                'order' => 3
            ],
            [
                'name' => 'Documentation',
                'description' => 'Complete user manual and technical documentation',
                'order' => 4
            ]
        ];

        foreach ($tasks as $task) {
            MilestoneTask::create([
                'milestone_template_id' => $milestone->id,
                'name' => $task['name'],
                'description' => $task['description'],
                'order' => $task['order']
            ]);
        }
    }
}
