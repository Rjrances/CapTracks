<?php

namespace App\Console\Commands;

use App\Models\GroupMilestone;
use App\Models\GroupMilestoneTask;
use App\Models\MilestoneTemplate;
use Illuminate\Console\Command;

class SyncGroupMilestoneTasksFromTemplates extends Command
{
    protected $signature = 'milestones:sync-group-tasks';

    protected $description = 'Create missing group_milestone_tasks rows for template tasks on assigned group milestones (repair historical gaps).';

    public function handle(): int
    {
        $created = 0;

        foreach (MilestoneTemplate::with('tasks')->get() as $template) {
            $groupMilestones = GroupMilestone::where('milestone_template_id', $template->id)->get();

            foreach ($groupMilestones as $groupMilestone) {
                foreach ($template->tasks as $task) {
                    $row = GroupMilestoneTask::firstOrCreate(
                        [
                            'group_milestone_id' => $groupMilestone->id,
                            'milestone_task_id' => $task->id,
                        ],
                        [
                            'status' => 'pending',
                            'is_completed' => false,
                        ]
                    );

                    if ($row->wasRecentlyCreated) {
                        $created++;
                    }
                }

                $groupMilestone->calculateProgressPercentage();
            }
        }

        $this->info("Created {$created} missing group milestone task row(s).");

        return Command::SUCCESS;
    }
}
