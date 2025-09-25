<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createTestNotifications();
    }

    /**
     * Create sample notifications for different roles
     */
    private function createTestNotifications()
    {
        $notifications = [
            // Coordinator notifications
            [
                'title' => 'New Group Registration',
                'description' => 'Smart Campus Management System has registered for the current term',
                'role' => 'coordinator',
                'is_read' => false
            ],
            [
                'title' => 'Progress Report Available',
                'description' => '60% defense readiness report is now available for review',
                'role' => 'coordinator',
                'is_read' => false
            ],
            [
                'title' => 'Defense Schedule Update',
                'description' => 'New defense schedules have been added for next week',
                'role' => 'coordinator',
                'is_read' => true
            ],

            // Adviser notifications
            [
                'title' => 'Student Task Completed',
                'description' => 'Alexandra Martinez completed the Project Title task',
                'role' => 'adviser',
                'is_read' => false
            ],
            [
                'title' => 'Group Progress Update',
                'description' => 'Smart Campus Management System reached 75% completion',
                'role' => 'adviser',
                'is_read' => false
            ],
            [
                'title' => 'New Submission Received',
                'description' => 'Project proposal submitted by Mobile Learning Assistant',
                'role' => 'adviser',
                'is_read' => true
            ],

            // Chairperson notifications
            [
                'title' => 'Faculty Role Assignment',
                'description' => 'New faculty member assigned as adviser',
                'role' => 'chairperson',
                'is_read' => false
            ],
            [
                'title' => 'Academic Term Status',
                'description' => 'First Semester 2024-2025 is now active',
                'role' => 'chairperson',
                'is_read' => true
            ],

            // Student notifications
            [
                'title' => 'Milestone Deadline',
                'description' => 'Project Proposal milestone is due in 3 days',
                'role' => 'student',
                'is_read' => false
            ],
            [
                'title' => 'Task Assigned',
                'description' => 'New task assigned: Problem Statement',
                'role' => 'student',
                'is_read' => false
            ]
        ];

        foreach ($notifications as $notificationData) {
            Notification::firstOrCreate(
                [
                    'title' => $notificationData['title'],
                    'role' => $notificationData['role']
                ],
                [
                    'description' => $notificationData['description'],
                    'is_read' => $notificationData['is_read']
                ]
            );
        }

        echo "Created " . count($notifications) . " sample notifications for different roles\n";
    }
}
