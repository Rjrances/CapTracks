<?php

namespace App\Services;

use App\Models\DefenseEvaluationSummary;
use App\Models\DefensePanel;
use App\Models\DefenseSchedule;
use App\Models\RatingSheet;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DefenseEvaluationService
{
    private const REQUIRED_PANEL_ROLES = ['coordinator', 'chair', 'member'];

    public function requiredPanels(DefenseSchedule $schedule): Collection
    {
        return DefensePanel::query()
            ->where('defense_schedule_id', $schedule->id)
            ->where('status', 'accepted')
            ->whereIn('role', self::REQUIRED_PANEL_ROLES)
            ->get(['id', 'defense_schedule_id', 'faculty_id', 'role', 'status']);
    }

    public function submittedRatings(DefenseSchedule $schedule): Collection
    {
        return RatingSheet::query()
            ->where('defense_schedule_id', $schedule->id)
            ->whereNotNull('submitted_at')
            ->where(function ($query) {
                $query->where('total_score', '>', 0)
                    ->orWhereNotNull('recommendation')
                    ->orWhereNotNull('remarks');
            })
            ->get();
    }

    public function readiness(DefenseSchedule $schedule): array
    {
        $schedule->loadMissing('group.members');
        $required = $this->requiredPanels($schedule);
        $submitted = $this->submittedRatings($schedule);

        $submittedByFacultyId = $submitted->pluck('faculty_id')->map(fn ($id) => (int) $id)->unique()->values();
        $missingPanels = $required->filter(function ($panel) use ($submittedByFacultyId) {
            return !$submittedByFacultyId->contains((int) $panel->faculty_id);
        })->values();
        $missingIndividualPanels = $required->filter(function ($panel) use ($submitted, $schedule) {
            $sheet = $submitted->firstWhere('faculty_id', $panel->faculty_id);
            if (!$sheet) {
                return false;
            }

            $individualScores = collect($sheet->individual_scores ?? []);
            $requiredStudentIds = collect($schedule->group?->members ?? [])->pluck('student_id')->map(fn ($id) => (string) $id);
            $scoredStudentIds = $individualScores->pluck('student_id')->map(fn ($id) => (string) $id);

            return $requiredStudentIds->isNotEmpty()
                && $requiredStudentIds->diff($scoredStudentIds)->isNotEmpty();
        })->values();

        return [
            'required_count' => $required->count(),
            'submitted_count' => $required->count() - $missingPanels->count(),
            'is_ready' => $required->count() > 0 && $missingPanels->isEmpty() && $missingIndividualPanels->isEmpty(),
            'missing_panels' => $missingPanels,
            'missing_individual_panels' => $missingIndividualPanels,
        ];
    }

    public function finalize(DefenseSchedule $schedule, User $finalizedBy, ?string $finalNotes = null): DefenseEvaluationSummary
    {
        $readiness = $this->readiness($schedule);
        if (!$readiness['is_ready']) {
            throw new \RuntimeException('Cannot finalize yet. Some required panelists have not submitted ratings.');
        }

        $ratings = $this->submittedRatings($schedule);
        $averageScore = (float) ($ratings->avg('total_score') ?? 0);

        $recommendationCounts = [
            'pass' => $ratings->where('recommendation', 'pass')->count(),
            'conditional_pass' => $ratings->where('recommendation', 'conditional_pass')->count(),
            'redefend' => $ratings->where('recommendation', 'redefend')->count(),
        ];

        $finalRecommendation = collect($recommendationCounts)
            ->sortDesc()
            ->keys()
            ->first() ?? 'pass';

        return DB::transaction(function () use ($schedule, $finalizedBy, $averageScore, $finalRecommendation, $finalNotes) {
            $summary = DefenseEvaluationSummary::query()->updateOrCreate(
                ['defense_schedule_id' => $schedule->id],
                [
                    'group_id' => $schedule->group_id,
                    'finalized_by' => $finalizedBy->id,
                    'final_average_score' => $averageScore,
                    'final_recommendation' => $finalRecommendation,
                    'final_notes' => $finalNotes,
                    'reopen_reason' => null,
                    'reopened_at' => null,
                    'reopened_by' => null,
                    'finalized_at' => now(),
                ]
            );

            $schedule->update(['status' => 'completed']);

            return $summary;
        });
    }

    public function reopen(DefenseSchedule $schedule, User $reopenedBy, string $reason): void
    {
        $summary = $schedule->evaluationSummary;
        if (!$summary) {
            throw new \RuntimeException('No finalized summary exists for this defense yet.');
        }

        DB::transaction(function () use ($schedule, $summary, $reopenedBy, $reason) {
            $summary->update([
                'reopen_reason' => $reason,
                'reopened_at' => now(),
                'reopened_by' => $reopenedBy->id,
            ]);

            $schedule->update(['status' => 'scheduled']);
        });
    }
}

