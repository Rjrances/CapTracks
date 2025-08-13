<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GroupMilestoneTask;

class GroupMilestoneTaskStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing tasks with random statuses for testing
        $tasks = GroupMilestoneTask::all();
        
        foreach ($tasks as $task) {
            // Randomly assign statuses
            $statuses = ['pending', 'doing', 'done'];
            $randomStatus = $statuses[array_rand($statuses)];
            
            $task->update([
                'status' => $randomStatus,
                'is_completed' => $randomStatus === 'done'
            ]);
        }
        
        $this->command->info('Updated ' . $tasks->count() . ' group milestone tasks with random statuses.');
    }
}
