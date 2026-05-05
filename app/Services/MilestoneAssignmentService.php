<?php

namespace App\Services;

use App\Models\Group;
use App\Models\MilestoneTemplate;

class MilestoneAssignmentService
{
    /**
     * Metadata for coordinator UI and validation (one active milestone + optional sequence).
     *
     * @return array{
     *     can_assign: bool,
     *     block_message: ?string,
     *     allowed_template_id: ?int,
     *     sequencing_enabled: bool
     * }
     */
    public static function assignmentMeta(Group $group): array
    {
        $group->loadMissing(['groupMilestones.milestoneTemplate']);
        $milestones = $group->groupMilestones;

        $incomplete = $milestones->first(fn ($gm) => (int) $gm->progress_percentage < 100);
        if ($incomplete) {
            $label = $incomplete->milestoneTemplate->name ?? $incomplete->title;

            return [
                'can_assign' => false,
                'block_message' => "Finish the current milestone ({$label}) before assigning another.",
                'allowed_template_id' => null,
                'sequencing_enabled' => true,
            ];
        }

        $sequencingActive = MilestoneTemplate::query()
            ->where('status', 'active')
            ->whereNotNull('sequence_order')
            ->exists();

        if (! $sequencingActive) {
            return [
                'can_assign' => true,
                'block_message' => null,
                'allowed_template_id' => null,
                'sequencing_enabled' => false,
            ];
        }

        $next = self::resolveNextSequencedTemplate($milestones);
        if (! $next) {
            return [
                'can_assign' => false,
                'block_message' => 'All milestones in the configured sequence are complete for this group.',
                'allowed_template_id' => null,
                'sequencing_enabled' => true,
            ];
        }

        return [
            'can_assign' => true,
            'block_message' => null,
            'allowed_template_id' => (int) $next->getKey(),
            'sequencing_enabled' => true,
        ];
    }

    /**
     * Human-readable validation for POST assignToGroup.
     */
    public static function validateAssignment(Group $group, MilestoneTemplate $template): ?string
    {
        $meta = self::assignmentMeta($group);

        if (! $meta['can_assign']) {
            return $meta['block_message'];
        }

        if ($meta['sequencing_enabled']) {
            if ($template->sequence_order === null) {
                return 'This template has no sequence order. Set step order (1 = Proposal, 2 = 60%, 3 = 100%) on the template edit page.';
            }

            if ($meta['allowed_template_id'] !== null
                && (int) $template->getKey() !== (int) $meta['allowed_template_id']) {
                $expected = MilestoneTemplate::find($meta['allowed_template_id']);

                return $expected
                    ? "Milestones must follow order: Proposal → 60% → 100%. Next to assign: \"{$expected->name}\"."
                    : 'The next milestone in sequence could not be determined.';
            }
        }

        return null;
    }

    private static function resolveNextSequencedTemplate($groupMilestones): ?MilestoneTemplate
    {
        $sequenced = MilestoneTemplate::query()
            ->where('status', 'active')
            ->whereNotNull('sequence_order')
            ->orderBy('sequence_order')
            ->get();

        if ($sequenced->isEmpty()) {
            return null;
        }

        $completedOrders = $groupMilestones
            ->filter(function ($gm) {
                if ((int) $gm->progress_percentage < 100) {
                    return false;
                }
                $t = $gm->milestoneTemplate;

                return $t && $t->sequence_order !== null;
            })
            ->map(fn ($gm) => (int) $gm->milestoneTemplate->sequence_order);

        $nextOrder = $completedOrders->isEmpty()
            ? (int) $sequenced->min('sequence_order')
            : $completedOrders->max() + 1;

        $next = $sequenced->firstWhere('sequence_order', $nextOrder);
        if (! $next) {
            return null;
        }

        $alreadyAssigned = $groupMilestones->contains(
            fn ($gm) => (int) $gm->milestone_template_id === (int) $next->getKey()
        );

        return $alreadyAssigned ? null : $next;
    }
}
