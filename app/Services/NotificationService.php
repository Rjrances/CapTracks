<?php
namespace App\Services;
use App\Models\Group;
use App\Models\GroupMilestone;
use App\Models\GroupMilestoneTask;
use App\Models\MilestoneTemplate;
use App\Models\Student;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
class NotificationService
{
    public static function newGroupRegistration(string $groupName, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'New Group Registration',
            "Group '{$groupName}' has registered for the current term",
            'coordinator',
            $redirectUrl ?? route('coordinator.groups.index')
        );
    }
    public static function progressReportAvailable(int $percentage, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'Progress Report Available',
            "{$percentage}% defense readiness report is now available for review",
            'coordinator',
            $redirectUrl ?? route('coordinator.progress-validation.dashboard')
        );
    }
    public static function defenseScheduleUpdated(?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'Defense Schedule Update',
            'New defense schedules have been added for next week',
            'coordinator',
            $redirectUrl ?? route('coordinator.defense.index')
        );
    }
    public static function newProjectSubmission(string $groupName, string $projectType, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'New Project Submission',
            "Project {$projectType} submitted by {$groupName}",
            'coordinator',
            $redirectUrl ?? route('coordinator.groups.index')
        );
    }
    public static function adviserAssigned(string $groupName, string $adviserName, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'Teacher Assignment',
            "{$adviserName} has been assigned as teacher for {$groupName}",
            'coordinator',
            $redirectUrl ?? route('coordinator.groups.index')
        );
    }
    public static function milestoneCompleted(string $groupName, string $milestoneName, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'Milestone Completed',
            "{$groupName} has completed the {$milestoneName} milestone",
            'coordinator',
            $redirectUrl ?? route('coordinator.groups.index')
        );
    }
    public static function studentTaskCompleted(User $adviser, string $studentName, string $taskName, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'Task Completed',
            "{$studentName} has completed the task: {$taskName}",
            'adviser',
            $redirectUrl ?? route('adviser.groups.index')
        );
    }
    public static function groupProgressUpdate(User $adviser, string $groupName, int $percentage, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'Group Progress Update',
            "{$groupName} has reached {$percentage}% completion",
            'adviser',
            $redirectUrl ?? route('adviser.groups.index')
        );
    }
    public static function newSubmissionReceived(User $adviser, string $groupName, string $projectType, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'New Submission Received',
            "{$groupName} has submitted {$projectType} for review",
            'adviser',
            $redirectUrl ?? route('adviser.project.index')
        );
    }
    public static function newAdviserInvitation(User $adviser, string $groupName, ?string $redirectUrl = null)
    {
        $role = $adviser->primary_role ?? 'teacher';
        try {
            return Notification::create([
                'title' => 'New Teacher Invitation',
                'description' => "You have received a teacher invitation from group: {$groupName}",
                'role' => $role, // Use the actual user role
                'redirect_url' => $redirectUrl ?? route('adviser.invitations'),
                'is_read' => false,
                'user_id' => $adviser->id, // Add specific user ID
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating adviser invitation notification', [
                'adviser_id' => $adviser->id,
                'group_name' => $groupName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Notify faculty when a coordinator assigns or changes the group's adviser (direct assignment).
     */
    public static function adviserAssignedByCoordinator(User $adviser, Group $group, ?string $coordinatorName = null, ?string $redirectUrl = null): ?Notification
    {
        $group->loadMissing('offering');
        $label = $coordinatorName ? "{$coordinatorName}" : 'A coordinator';
        $offeringText = $group->offering
            ? " — {$group->offering->subject_code} ({$group->offering->subject_title})"
            : '';
        $description = "{$label} assigned you as adviser for “{$group->name}”{$offeringText}.";

        return self::createSimpleNotification(
            'Assigned as group adviser',
            $description,
            $adviser->primary_role ?? 'teacher',
            $redirectUrl ?? route('adviser.groups.details', $group),
            $adviser->id
        );
    }

    public static function milestoneDeadlineApproaching(User $student, string $milestoneName, int $daysLeft, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'Milestone Deadline Approaching',
            "The {$milestoneName} milestone is due in {$daysLeft} days",
            'student',
            $redirectUrl ?? route('student.milestones.index')
        );
    }
    public static function newTaskAssigned(User $student, string $taskName, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'New Task Assigned',
            "You have been assigned a new task: {$taskName}",
            'student',
            $redirectUrl ?? route('student.milestones.index')
        );
    }
    public static function taskCompleted(User $student, string $taskName, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'Task Completed',
            "You have completed the task: {$taskName}",
            'student',
            $redirectUrl ?? route('student.milestones.index')
        );
    }
    public static function newPanelAssignment(User $panelist, string $groupName, string $defenseDate, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'New Panel Assignment',
            "You have been assigned to a defense panel for {$groupName} on {$defenseDate}",
            'panelist',
            $redirectUrl ?? route('dashboard')
        );
    }
    public static function defenseScheduleUpdate(User $panelist, string $groupName, string $defenseDate, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'Defense Schedule Update',
            "Defense schedule updated for {$groupName} on {$defenseDate}",
            'panelist',
            $redirectUrl ?? route('dashboard')
        );
    }
    public static function facultyRoleAssigned(string $facultyName, string $role, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'Faculty Role Assignment',
            "{$facultyName} has been assigned the role of {$role}",
            'chairperson',
            $redirectUrl ?? route('chairperson.roles.index')
        );
    }
    public static function academicTermStatusChanged(string $termName, string $status, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'Academic Term Status Changed',
            "Academic term '{$termName}' status has been changed to {$status}",
            'chairperson',
            $redirectUrl ?? route('chairperson.academic-terms.index')
        );
    }
    public static function proposalApproved($student, string $groupName, string $proposalTitle, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'Proposal Approved!',
            "Your proposal '{$proposalTitle}' for group {$groupName} has been approved by your adviser.",
            'student',
            $redirectUrl ?? route('student.proposal'),
            $student->id
        );
    }
    public static function proposalRejected($student, string $groupName, string $proposalTitle, string $feedback, ?string $redirectUrl = null)
    {
        return self::createSimpleNotification(
            'Proposal Needs Revision',
            "Your proposal '{$proposalTitle}' for group {$groupName} needs revision. Check feedback from your adviser.",
            'student',
            $redirectUrl ?? route('student.proposal'),
            $student->id
        );
    }

    /**
     * Notify each group member when a coordinator assigns a milestone template to their group.
     */
    public static function coordinatorAssignedMilestoneToGroup(
        Group $group,
        GroupMilestone $groupMilestone,
        MilestoneTemplate $template
    ): void {
        $group->loadMissing(['members.account']);
        $milestoneName = $template->name;
        $groupName = $group->name;
        $redirectUrl = route('student.milestones.show', $groupMilestone->getKey());
        $title = 'New milestone assigned';
        $description = "Your coordinator assigned the milestone \"{$milestoneName}\" to {$groupName}.";

        foreach ($group->members as $member) {
            $userId = self::studentNotificationUserId($member);
            if ($userId === null) {
                continue;
            }
            self::createSimpleNotification($title, $description, 'student', $redirectUrl, $userId);
        }
    }

    /**
     * Notify each group member when their adviser comments on a milestone task thread.
     */
    public static function adviserCommentOnMilestoneTask(User $adviser, GroupMilestoneTask $task): void
    {
        $task->loadMissing([
            'milestoneTask',
            'groupMilestone.group.members.account',
        ]);

        $group = $task->groupMilestone?->group;
        if (!$group) {
            return;
        }

        $taskLabel = $task->milestoneTask->name ?? 'Milestone task';
        $milestoneId = $task->groupMilestone->getKey();
        $fragment = '#taskCommentsModal' . $task->getKey();
        $redirectUrl = route('student.milestones.show', $milestoneId) . $fragment;

        $title = 'Adviser commented on your milestone task';
        $description = "{$adviser->name} commented on \"{$taskLabel}\" in {$group->name}.";

        foreach ($group->members as $member) {
            $userId = self::studentNotificationUserId($member);
            if ($userId === null) {
                continue;
            }
            self::createSimpleNotification($title, $description, 'student', $redirectUrl, $userId);
        }
    }

    /**
     * Match Notification::visibleToStudent targeting (student_accounts.id preferred).
     */
    private static function studentNotificationUserId(Student $student): ?int
    {
        $account = $student->relationLoaded('account')
            ? $student->account
            : $student->account()->first();

        if ($account) {
            return (int) $account->getKey();
        }

        if (is_numeric($student->student_id)) {
            return (int) $student->student_id;
        }

        return null;
    }

    public static function createSimpleNotification(string $title, string $description, string $role, ?string $redirectUrl = null, ?int $userId = null)
    {
        try {
            return Notification::create([
                'title' => $title,
                'description' => $description,
                'role' => $role,
                'redirect_url' => $redirectUrl,
                'is_read' => false,
                'user_id' => $userId, // Add user_id if provided
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating simple notification', [
                'title' => $title,
                'role' => $role,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    public static function markAsRead(int $notificationId): bool
    {
        try {
            $notification = Notification::find($notificationId);
            if ($notification) {
                $notification->update(['is_read' => true]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Error marking notification as read', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    public static function markMultipleAsRead(array $notificationIds): bool
    {
        try {
            Notification::whereIn('id', $notificationIds)->update(['is_read' => true]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error marking multiple notifications as read', [
                'notification_ids' => $notificationIds,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    public static function deleteNotification(int $notificationId): bool
    {
        try {
            $notification = Notification::find($notificationId);
            if ($notification) {
                $notification->delete();
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Error deleting notification', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    public static function deleteMultipleNotifications(array $notificationIds): bool
    {
        try {
            Notification::whereIn('id', $notificationIds)->delete();
            return true;
        } catch (\Exception $e) {
            Log::error('Error deleting multiple notifications', [
                'notification_ids' => $notificationIds,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
