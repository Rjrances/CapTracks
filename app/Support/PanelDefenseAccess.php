<?php

namespace App\Support;

use App\Models\DefensePanel;
use App\Models\Group;
use App\Models\ProjectSubmission;
use App\Models\User;

final class PanelDefenseAccess
{
    /**
     * Invited panel (chair/member/panelist) on any defense schedule for this group with given statuses.
     */
    public static function userIsInvitedPanelOnGroup(User $user, Group $group, array $statuses): bool
    {
        return $group->defenseSchedules()
            ->whereHas('defensePanels', function ($query) use ($user, $statuses) {
                $query->whereIn('role', DefensePanel::INVITED_ROLES)
                    ->whereIn('status', $statuses)
                    ->whereHas('faculty', function ($facultyQuery) use ($user) {
                        $facultyQuery->where('faculty_id', $user->faculty_id);
                    });
            })
            ->exists();
    }

    public static function userHasAcceptedPanelAccessToSubmission(User $user, ProjectSubmission $submission): bool
    {
        return Group::query()
            ->whereHas('members', function ($query) use ($submission) {
                $query->where('students.student_id', $submission->student_id);
            })
            ->whereHas('defenseSchedules.defensePanels', function ($query) use ($user) {
                $query->whereIn('role', DefensePanel::INVITED_ROLES)
                    ->where('status', 'accepted')
                    ->whereHas('faculty', function ($facultyQuery) use ($user) {
                        $facultyQuery->where('faculty_id', $user->faculty_id);
                    });
            })
            ->exists();
    }

    /**
     * Pending panel may preview proposal or final report files (same group as the submitting student).
     */
    public static function userHasPendingSubmissionPreviewForSubmission(User $user, ProjectSubmission $submission): bool
    {
        if (! in_array($submission->type, ['proposal', 'final'], true)) {
            return false;
        }

        return Group::query()
            ->whereHas('members', function ($query) use ($submission) {
                $query->where('students.student_id', $submission->student_id);
            })
            ->whereHas('defenseSchedules.defensePanels', function ($query) use ($user) {
                $query->whereIn('role', DefensePanel::INVITED_ROLES)
                    ->where('status', 'pending')
                    ->whereHas('faculty', function ($facultyQuery) use ($user) {
                        $facultyQuery->where('faculty_id', $user->faculty_id);
                    });
            })
            ->exists();
    }
}
