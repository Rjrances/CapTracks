<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\GroupMilestoneTask;
use App\Models\ProjectSubmission;
use App\Models\User;

class ActivityLogService
{
    public static function logTaskCompleted(GroupMilestoneTask $task): void
    {
        if (!$task->groupMilestone || !$task->groupMilestone->group) {
            return;
        }

        $group = $task->groupMilestone->group;
        $description = 'Completed task "' . ($task->milestoneTask->name ?? 'Milestone Task') . '" for group ' . $group->name;

        ActivityLog::create([
            'student_id' => $task->completed_by ?? $task->assigned_to,
            'action' => 'task_completed',
            'description' => $description,
            'loggable_type' => GroupMilestoneTask::class,
            'loggable_id' => $task->id,
        ]);
    }

    public static function logSubmissionCommentAdded(ProjectSubmission $submission, ?User $user = null, ?string $studentId = null): void
    {
        $commenterName = $user ? $user->name : ('Student ' . ($studentId ?: 'Unknown'));
        $submissionLabel = $submission->title ?: 'Project Submission';

        ActivityLog::create([
            'user_id' => $user?->id,
            'student_id' => $studentId,
            'action' => 'comment_added',
            'description' => $commenterName . ' added comment on "' . $submissionLabel . '"',
            'loggable_type' => ProjectSubmission::class,
            'loggable_id' => $submission->id,
        ]);
    }

    public static function logTaskCommentAdded(GroupMilestoneTask $task, ?User $user = null, ?string $studentId = null): void
    {
        if (!$task->groupMilestone || !$task->groupMilestone->group) {
            return;
        }

        $commenterName = $user ? $user->name : ($studentId ? 'Student ' . $studentId : 'Unknown');
        $taskLabel = $task->milestoneTask->name ?? 'Milestone task';
        $groupName = $task->groupMilestone->group->name;

        ActivityLog::create([
            'user_id' => $user?->id,
            'student_id' => $studentId,
            'action' => 'task_comment_added',
            'description' => $commenterName . ' commented on task "' . $taskLabel . '" (' . $groupName . ')',
            'loggable_type' => GroupMilestoneTask::class,
            'loggable_id' => $task->id,
        ]);
    }
}
