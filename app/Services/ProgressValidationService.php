<?php
namespace App\Services;
use App\Models\Group;
use App\Models\GroupMilestone;
use App\Models\MilestoneTemplate;
class ProgressValidationService
{
    public function isGroupReadyFor60PercentDefense(Group $group): bool
    {
        if ($group->overall_progress_percentage < 60) {
            return false;
        }
        if (!$group->faculty_id) {
            return false;
        }
        if (!$this->hasRequiredMilestonesCompleted($group)) {
            return false;
        }
        if (!$group->hasRequiredDocumentsFor60PercentDefense()) {
            return false;
        }
        return true;
    }
    public function get60PercentDefenseReadinessReport(Group $group): array
    {
        $report = [
            'is_ready' => false,
            'overall_progress' => $group->overall_progress_percentage,
            'issues' => [],
            'warnings' => [],
            'milestones_status' => [],
            'documents_status' => [],
            'recommendations' => []
        ];
        if ($group->overall_progress_percentage < 60) {
            $report['issues'][] = "Overall progress is {$group->overall_progress_percentage}% (needs 60%)";
        } else {
            $report['warnings'][] = "Overall progress is {$group->overall_progress_percentage}%";
        }
        if (!$group->faculty_id) {
            $report['issues'][] = "No adviser assigned to the group";
        }
        $milestonesStatus = $this->getMilestonesStatus($group);
        $report['milestones_status'] = $milestonesStatus;
        foreach ($milestonesStatus as $milestone) {
            if ($milestone['progress'] < 80) {
                $report['issues'][] = "Milestone '{$milestone['name']}' is only {$milestone['progress']}% complete (needs 80%)";
            }
        }
        $documentsStatus = $this->getDocumentsStatus($group);
        $report['documents_status'] = $documentsStatus;
        $submittedCount = count(array_filter($documentsStatus, fn($doc) => $doc['submitted']));
        if ($submittedCount < 4) {
            $report['issues'][] = "Only {$submittedCount}/6 required documents submitted (needs at least 4)";
        }
        $report['is_ready'] = empty($report['issues']);
        $report['recommendations'] = $this->generateRecommendations($report);
        return $report;
    }
    private function hasRequiredMilestonesCompleted(Group $group): bool
    {
        $requiredMilestones = $group->groupMilestones()
            ->whereIn('milestone_template_id', [1, 2, 3]) // Proposal, Literature Review, Methodology
            ->get();
        foreach ($requiredMilestones as $milestone) {
            if ($milestone->progress_percentage < 80) {
                return false;
            }
        }
        return true;
    }
    private function getMilestonesStatus(Group $group): array
    {
        $milestones = $group->groupMilestones()->with('milestoneTemplate')->get();
        $status = [];
        foreach ($milestones as $milestone) {
            $status[] = [
                'id' => $milestone->id,
                'name' => $milestone->milestoneTemplate->name,
                'progress' => $milestone->progress_percentage,
                'status' => $milestone->status_text,
                'is_required' => in_array($milestone->milestone_template_id, [1, 2, 3]),
                'target_date' => $milestone->target_date,
                'is_overdue' => $milestone->is_overdue
            ];
        }
        return $status;
    }
    private function getDocumentsStatus(Group $group): array
    {
        $requiredDocs = $group->getRequiredDocumentsFor60PercentDefense();
        $submittedDocs = $group->members->flatMap->submissions->pluck('type')->unique()->toArray();
        $status = [];
        foreach ($requiredDocs as $key => $name) {
            $status[] = [
                'key' => $key,
                'name' => $name,
                'submitted' => in_array($key, $submittedDocs),
                'submission_date' => $this->getSubmissionDate($group, $key)
            ];
        }
        return $status;
    }
    private function getSubmissionDate(Group $group, string $docType): ?string
    {
        $submission = $group->members->flatMap->submissions
            ->where('type', $docType)
            ->first();
        return $submission ? $submission->submitted_at : null;
    }
    private function generateRecommendations(array $report): array
    {
        $recommendations = [];
        if ($report['overall_progress'] < 60) {
            $recommendations[] = "Focus on completing more milestones to reach 60% overall progress";
        }
        if (!empty($report['milestones_status'])) {
            $incompleteMilestones = array_filter($report['milestones_status'], fn($m) => $m['progress'] < 80 && $m['is_required']);
            if (!empty($incompleteMilestones)) {
                $recommendations[] = "Complete required milestones to at least 80% before requesting 60% defense";
            }
        }
        $submittedDocs = count(array_filter($report['documents_status'], fn($doc) => $doc['submitted']));
        if ($submittedDocs < 4) {
            $recommendations[] = "Submit at least 4 out of 6 required documents for 60% defense";
        }
        if (empty($recommendations)) {
            $recommendations[] = "Group appears ready for 60% defense. Ensure all presentation materials are prepared.";
        }
        return $recommendations;
    }
    public function getGroupsReadyFor60PercentDefense(): \Illuminate\Database\Eloquent\Collection
    {
        return Group::with(['adviser', 'members', 'groupMilestones.milestoneTemplate'])
            ->whereHas('adviser')
            ->get()
            ->filter(function ($group) {
                return $this->isGroupReadyFor60PercentDefense($group);
            });
    }
    public function getGroupsNeedingAttentionFor60PercentDefense(): \Illuminate\Database\Eloquent\Collection
    {
        return Group::with(['adviser', 'members', 'groupMilestones.milestoneTemplate'])
            ->get()
            ->filter(function ($group) {
                $report = $this->get60PercentDefenseReadinessReport($group);
                return !$report['is_ready'] && $group->overall_progress_percentage >= 40; // At least 40% progress
            });
    }
    public function getFilteredGroupsForProgressValidation(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Group::with(['adviser', 'members', 'groupMilestoneTasks.milestoneTask.milestoneTemplate', 'academicTerm']);
        if (isset($filters['academic_term_id']) && $filters['academic_term_id']) {
            $query->where('academic_term_id', $filters['academic_term_id']);
        }
        if (isset($filters['faculty_id']) && $filters['faculty_id']) {
            $query->where('faculty_id', $filters['faculty_id']);
        }
        if (isset($filters['search']) && $filters['search']) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }
        return $query->get();
    }
    public function getFilterOptions(): array
    {
        return [
            'academic_terms' => \App\Models\AcademicTerm::orderBy('school_year', 'desc')
                ->get()
                ->mapWithKeys(function($term) {
                    return [$term->id => $term->full_name];
                })
                ->toArray(),
            'advisers' => \App\Models\User::whereHas('roles', function($query) {
                $query->where('name', 'adviser');
            })->get()
            ->mapWithKeys(function($adviser) {
                return [$adviser->id => $adviser->name];
            })
            ->toArray()
        ];
    }
}
