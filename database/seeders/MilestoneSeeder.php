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
        // Template 1: Standard Capstone Project
        $standardCapstone = MilestoneTemplate::create([
            'name' => 'Standard Capstone Project',
            'description' => 'Complete capstone project following the standard milestone structure with must-haves, 60% screenshots, 100% screenshots, and final documentation',
            'status' => 'active'
        ]);

        $this->createStandardCapstoneTasks($standardCapstone);

        // Template 2: Software Development Project
        $softwareDev = MilestoneTemplate::create([
            'name' => 'Software Development Project',
            'description' => 'Software-focused capstone project with requirements, design, development phases, and testing',
            'status' => 'active'
        ]);

        $this->createSoftwareDevTasks($softwareDev);

        // Template 3: Research Project
        $research = MilestoneTemplate::create([
            'name' => 'Research Project',
            'description' => 'Research-focused capstone project with literature review, data collection, analysis, and results',
            'status' => 'active'
        ]);

        $this->createResearchTasks($research);

        // Template 4: Design Project
        $design = MilestoneTemplate::create([
            'name' => 'Design Project',
            'description' => 'Design-focused capstone project with user research, design development, prototyping, and evaluation',
            'status' => 'active'
        ]);

        $this->createDesignTasks($design);

        echo "✅ Created 4 milestone templates with tasks\n";
    }

    /**
     * Create tasks for Standard Capstone Project template
     */
    private function createStandardCapstoneTasks($milestone)
    {
        $tasks = [
            [
                'name' => 'Must Haves → Document (Chapter 1 & 2)',
                'description' => 'Complete project requirements analysis, write Chapter 1: Introduction and Background, write Chapter 2: Literature Review and Related Works, submit initial document for adviser review, revise based on feedback',
                'order' => 1
            ],
            [
                'name' => 'Screenshots 60%',
                'description' => 'Complete 60% of system implementation, capture screenshots of key features, document implementation progress, test core functionality, prepare progress presentation',
                'order' => 2
            ],
            [
                'name' => 'Screenshots 100%',
                'description' => 'Complete full system implementation, capture comprehensive screenshots, conduct thorough testing, prepare final system demo, document all features and functionality',
                'order' => 3
            ],
            [
                'name' => 'Document (Chapter 3 & 4)',
                'description' => 'Write Chapter 3: System Design and Implementation, write Chapter 4: Testing, Results, and Conclusion, prepare final documentation, submit complete capstone document, prepare for final defense',
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
     * Create tasks for Software Development Project template
     */
    private function createSoftwareDevTasks($milestone)
    {
        $tasks = [
            [
                'name' => 'Requirements & Design → Document (Chapter 1 & 2)',
                'description' => 'Gather and analyze requirements, create system architecture design, write Chapter 1: Project Overview, write Chapter 2: System Design and Architecture, create technical specifications',
                'order' => 1
            ],
            [
                'name' => 'Development 60% → Screenshots',
                'description' => 'Implement core modules (60% complete), create database schema, develop user interface, capture development screenshots, conduct unit testing',
                'order' => 2
            ],
            [
                'name' => 'Development 100% → Screenshots',
                'description' => 'Complete all system modules, integrate all components, capture final system screenshots, conduct integration testing, prepare system documentation',
                'order' => 3
            ],
            [
                'name' => 'Testing & Documentation → Document (Chapter 3 & 4)',
                'description' => 'Write Chapter 3: Implementation Details, write Chapter 4: Testing, Results, and Evaluation, conduct user acceptance testing, prepare deployment documentation, submit final deliverables',
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
     * Create tasks for Research Project template
     */
    private function createResearchTasks($milestone)
    {
        $tasks = [
            [
                'name' => 'Literature Review → Document (Chapter 1 & 2)',
                'description' => 'Conduct comprehensive literature review, identify research gaps, write Chapter 1: Introduction and Problem Statement, write Chapter 2: Literature Review and Theoretical Framework, develop research methodology',
                'order' => 1
            ],
            [
                'name' => 'Data Collection 60% → Evidence',
                'description' => 'Collect 60% of required data, document data collection process, create preliminary analysis, capture research progress evidence, prepare interim report',
                'order' => 2
            ],
            [
                'name' => 'Data Collection 100% → Evidence',
                'description' => 'Complete all data collection, conduct comprehensive analysis, capture final research evidence, validate research findings, prepare research presentation',
                'order' => 3
            ],
            [
                'name' => 'Analysis & Results → Document (Chapter 3 & 4)',
                'description' => 'Write Chapter 3: Research Methodology and Data Analysis, write Chapter 4: Results, Discussion, and Conclusions, prepare final research report, submit complete research document, prepare for research defense',
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
     * Create tasks for Design Project template
     */
    private function createDesignTasks($milestone)
    {
        $tasks = [
            [
                'name' => 'Design Brief → Document (Chapter 1 & 2)',
                'description' => 'Complete design brief and requirements, conduct user research, write Chapter 1: Design Problem and Context, write Chapter 2: Design Research and Methodology, create design specifications',
                'order' => 1
            ],
            [
                'name' => 'Design Development 60% → Screenshots',
                'description' => 'Create initial design concepts, develop wireframes and prototypes, capture design process screenshots, conduct user testing, refine design based on feedback',
                'order' => 2
            ],
            [
                'name' => 'Design Development 100% → Screenshots',
                'description' => 'Complete final design, create high-fidelity prototypes, capture final design screenshots, conduct final user testing, prepare design documentation',
                'order' => 3
            ],
            [
                'name' => 'Design Documentation → Document (Chapter 3 & 4)',
                'description' => 'Write Chapter 3: Design Process and Development, write Chapter 4: Design Evaluation and Results, prepare design portfolio, submit complete design document, prepare for design presentation',
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
