<?php

namespace App\Observers;

use App\Models\GroupMilestoneTask;
use App\Services\ActivityLogService;

class GroupMilestoneTaskObserver
{
    public function updated(GroupMilestoneTask $groupMilestoneTask): void
    {
        if ($groupMilestoneTask->wasChanged('status') && $groupMilestoneTask->status === 'done') {
            ActivityLogService::logTaskCompleted($groupMilestoneTask);
        }
    }
}
