<?php

namespace App\Services;

use App\Models\Group;
use App\Models\ProjectSubmission;

class DefenseMilestoneGateService
{
    public function evaluate(Group $group, string $stage): array
    {
        if ($this->isProposalStage($stage)) {
            return $this->evaluateProposalStage($group);
        }

        $requiredOrder = $this->stageToOrder($stage);
        $label = $this->stageLabel($stage);

        $group->loadMissing('groupMilestones.milestoneTemplate');

        $milestone = $group->groupMilestones
            ->first(function ($groupMilestone) use ($requiredOrder) {
                return (int) ($groupMilestone->milestoneTemplate->sequence_order ?? 0) === $requiredOrder;
            });

        if (!$milestone) {
            return [
                'eligible' => false,
                'message' => "No assigned milestone template found for {$label}.",
            ];
        }

        $isComplete = ((int) $milestone->progress_percentage >= 100)
            || in_array((string) $milestone->status, ['completed', 'done'], true);

        return [
            'eligible' => $isComplete,
            'message' => $isComplete
                ? "{$label} milestone is complete."
                : "{$label} milestone is not complete yet (current progress: {$milestone->progress_percentage}%).",
        ];
    }

    private function evaluateProposalStage(Group $group): array
    {
        $group->loadMissing('members');
        $memberStudentIds = $group->members
            ->pluck('student_id')
            ->filter()
            ->values();

        if ($memberStudentIds->isEmpty()) {
            return [
                'eligible' => false,
                'message' => 'No group members found to validate proposal approval.',
            ];
        }

        $hasApprovedProposal = ProjectSubmission::query()
            ->whereIn('student_id', $memberStudentIds)
            ->where('type', 'proposal')
            ->where('status', 'approved')
            ->exists();

        return [
            'eligible' => $hasApprovedProposal,
            'message' => $hasApprovedProposal
                ? 'Proposal is approved.'
                : 'Proposal is not approved yet.',
        ];
    }

    private function isProposalStage(string $stage): bool
    {
        return $stage === 'proposal';
    }

    private function stageToOrder(string $stage): int
    {
        return match ($stage) {
            'proposal' => 1,
            '60', '60_percent' => 2,
            '100', '100_percent' => 3,
            default => 1,
        };
    }

    private function stageLabel(string $stage): string
    {
        return match ($stage) {
            'proposal' => 'Proposal',
            '60', '60_percent' => '60% Defense',
            '100', '100_percent' => '100% Defense',
            default => ucfirst((string) $stage),
        };
    }
}

