<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\Group;
use App\Models\MilestoneTemplate;
use App\Models\MilestoneTask;
use App\Models\GroupMilestone;
use App\Models\GroupMilestoneTask;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        $this->createTestUsers();
        $this->createMilestoneTemplates();
        $this->createTestGroups();
        $this->assignMilestonesToGroups();
    }

    private function createTestUsers()
    {
        // Create Coordinator
        $coordinator = User::create([
            'school_id' => 'COORD-001',
            'name' => 'Test Coordinator',
            'email' => 'coordinator@test.com',
            'password' => Hash::make('password'),
            'role' => 'coordinator'
        ]);

        // Create Chairperson
        $chairperson = User::create([
            'school_id' => 'CHAIR-001',
            'name' => 'Test Chairperson',
            'email' => 'chairperson@test.com',
            'password' => Hash::make('password'),
            'role' => 'chairperson'
        ]);

        // Create Adviser
        $adviser = User::create([
            'school_id' => 'ADVISER-001',
            'name' => 'Test Adviser',
            'email' => 'adviser@test.com',
            'password' => Hash::make('password'),
            'role' => 'adviser'
        ]);

        // Create Students (directly in students table)
        $students = [
            ['name' => 'John Student', 'email' => 'john@test.com', 'student_id' => '2024-001'],
            ['name' => 'Jane Student', 'email' => 'jane@test.com', 'student_id' => '2024-002'],
            ['name' => 'Bob Student', 'email' => 'bob@test.com', 'student_id' => '2024-003'],
            ['name' => 'Alice Student', 'email' => 'alice@test.com', 'student_id' => '2024-004'],
        ];

        foreach ($students as $studentData) {
            Student::create([
                'name' => $studentData['name'],
                'email' => $studentData['email'],
                'student_id' => $studentData['student_id'],
                'semester' => '2024-1',
                'course' => 'BS Computer Science',
                'password' => Hash::make('password')
            ]);
        }

        echo "✅ Created test users (chairperson, coordinator, adviser, 4 students)\n";
    }

    private function createMilestoneTemplates()
    {
        // Milestone 1: Project Proposal
        $proposal = MilestoneTemplate::create([
            'name' => 'Project Proposal',
            'description' => 'Initial project proposal and requirements gathering',
            'status' => 'todo'
        ]);

        MilestoneTask::create([
            'milestone_template_id' => $proposal->id,
            'name' => 'Project Title and Description',
            'description' => 'Define the project title and provide a detailed description',
            'order' => 1
        ]);

        MilestoneTask::create([
            'milestone_template_id' => $proposal->id,
            'name' => 'Problem Statement',
            'description' => 'Clearly define the problem the project aims to solve',
            'order' => 2
        ]);

        MilestoneTask::create([
            'milestone_template_id' => $proposal->id,
            'name' => 'Objectives and Scope',
            'description' => 'List project objectives and define the scope',
            'order' => 3
        ]);

        // Milestone 2: System Design
        $design = MilestoneTemplate::create([
            'name' => 'System Design',
            'description' => 'System architecture and design documentation',
            'status' => 'todo'
        ]);

        MilestoneTask::create([
            'milestone_template_id' => $design->id,
            'name' => 'System Architecture',
            'description' => 'Design the overall system architecture',
            'order' => 1
        ]);

        MilestoneTask::create([
            'milestone_template_id' => $design->id,
            'name' => 'Database Design',
            'description' => 'Design the database schema and relationships',
            'order' => 2
        ]);

        // Milestone 3: Implementation
        $implementation = MilestoneTemplate::create([
            'name' => 'Implementation',
            'description' => 'Core system development and coding',
            'status' => 'todo'
        ]);

        MilestoneTask::create([
            'milestone_template_id' => $implementation->id,
            'name' => 'Backend Development',
            'description' => 'Implement the backend functionality',
            'order' => 1
        ]);

        MilestoneTask::create([
            'milestone_template_id' => $implementation->id,
            'name' => 'Frontend Development',
            'description' => 'Implement the user interface',
            'order' => 2
        ]);

        MilestoneTask::create([
            'milestone_template_id' => $implementation->id,
            'name' => 'Integration Testing',
            'description' => 'Test the integrated system',
            'order' => 3
        ]);

        echo "✅ Created 3 milestone templates with tasks\n";
    }

    private function createTestGroups()
    {
        // Get students
        $students = Student::all();
        $adviser = User::where('role', 'adviser')->first();

        // Group 1: Web Development Team
        $group1 = Group::create([
            'name' => 'Web Development Team',
            'description' => 'Building a modern web application',
            'adviser_id' => $adviser->id
        ]);

        // Add students to group 1
        $group1->members()->attach([
            $students[0]->id => ['role' => 'leader'],
            $students[1]->id => ['role' => 'member']
        ]);

        // Group 2: Mobile App Team
        $group2 = Group::create([
            'name' => 'Mobile App Team',
            'description' => 'Developing a mobile application',
            'adviser_id' => $adviser->id
        ]);

        // Add students to group 2
        $group2->members()->attach([
            $students[2]->id => ['role' => 'leader'],
            $students[3]->id => ['role' => 'member']
        ]);

        echo "✅ Created 2 test groups with students and adviser\n";
    }

    private function assignMilestonesToGroups()
    {
        $groups = Group::all();
        $templates = MilestoneTemplate::all();

        // Assign Project Proposal to Group 1
        $groupMilestone1 = GroupMilestone::create([
            'group_id' => $groups[0]->id,
            'milestone_template_id' => $templates[0]->id, // Project Proposal
            'progress_percentage' => 0,
            'start_date' => now(),
            'target_date' => now()->addDays(30),
            'status' => 'not_started',
            'notes' => 'Initial milestone for web development team'
        ]);

        // Create group milestone tasks for Group 1 (unassigned initially)
        $template1Tasks = MilestoneTask::where('milestone_template_id', $templates[0]->id)->get();
        foreach ($template1Tasks as $task) {
            GroupMilestoneTask::create([
                'group_milestone_id' => $groupMilestone1->id,
                'milestone_task_id' => $task->id,
                'assigned_to' => null, // Unassigned - group leader will assign
                'is_completed' => false
            ]);
        }

        // Assign System Design to Group 1 (with some progress)
        $groupMilestone2 = GroupMilestone::create([
            'group_id' => $groups[0]->id,
            'milestone_template_id' => $templates[1]->id, // System Design
            'progress_percentage' => 50, // 1 of 2 tasks completed
            'start_date' => now()->addDays(10),
            'target_date' => now()->addDays(45),
            'status' => 'in_progress',
            'notes' => 'System design in progress'
        ]);

        // Create group milestone tasks for Group 1 (System Design)
        $template2Tasks = MilestoneTask::where('milestone_template_id', $templates[1]->id)->get();
        $group1Members = $groups[0]->members;
        foreach ($template2Tasks as $index => $task) {
            GroupMilestoneTask::create([
                'group_milestone_id' => $groupMilestone2->id,
                'milestone_task_id' => $task->id,
                'assigned_to' => $index === 0 ? $group1Members->first()->id : null, // First task assigned, others unassigned
                'is_completed' => $index === 0, // First task completed
                'completed_at' => $index === 0 ? now() : null,
                'completed_by' => $index === 0 ? $group1Members->first()->id : null
            ]);
        }

        // Assign Implementation to Group 2
        $groupMilestone3 = GroupMilestone::create([
            'group_id' => $groups[1]->id,
            'milestone_template_id' => $templates[2]->id, // Implementation
            'progress_percentage' => 0,
            'start_date' => now()->addDays(5),
            'target_date' => now()->addDays(60),
            'status' => 'not_started',
            'notes' => 'Implementation phase for mobile app'
        ]);

        // Create group milestone tasks for Group 2 (unassigned initially)
        $template3Tasks = MilestoneTask::where('milestone_template_id', $templates[2]->id)->get();
        foreach ($template3Tasks as $task) {
            GroupMilestoneTask::create([
                'group_milestone_id' => $groupMilestone3->id,
                'milestone_task_id' => $task->id,
                'assigned_to' => null, // Unassigned - group leader will assign
                'is_completed' => false
            ]);
        }

        echo "✅ Assigned milestones to groups with varying progress\n";
        echo "   - Group 1: Project Proposal (0%), System Design (50%)\n";
        echo "   - Group 2: Implementation (0%)\n";
    }
}
