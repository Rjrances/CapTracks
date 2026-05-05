<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DefenseSchedule;
use App\Models\RatingSheet;
use App\Services\DefenseEvaluationService;
use App\Services\DefenseRubricService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingSheetController extends Controller
{
    public function __construct(
        private readonly DefenseRubricService $defenseRubricService,
        private readonly DefenseEvaluationService $defenseEvaluationService
    ) {
    }

    public function showAdviserForm(DefenseSchedule $schedule)
    {
        $user = Auth::user();
        if ($redirect = $this->redirectToPreferredScheduleIfNeeded($schedule, $user)) {
            return $redirect;
        }
        $schedule->loadMissing('group.members');

        $isAssignedPanel = $schedule->defensePanels()
            ->whereIn('role', ['coordinator', 'chair', 'member'])
            ->where('status', 'accepted')
            ->whereHas('faculty', function ($query) use ($user) {
                $query->where('faculty_id', $user->faculty_id);
            })
            ->exists();

        if (!$isAssignedPanel) {
            abort(403, 'You are not assigned to this defense panel.');
        }

        $schedule->load('evaluationSummary');
        $isFinalized = $schedule->status === 'completed' && (bool) $schedule->evaluationSummary;

        $panelFacultyUserId = $this->resolvePanelFacultyUserId($schedule, $user);
        $existingRating = RatingSheet::where('defense_schedule_id', $schedule->id)
            ->where('faculty_id', $panelFacultyUserId)
            ->first();

        // Treat legacy placeholder rows (all-zero, no recommendation/remarks) as empty state.
        if (
            $existingRating &&
            (float) $existingRating->total_score <= 0 &&
            empty($existingRating->recommendation) &&
            empty($existingRating->remarks)
        ) {
            $allZeroCriteria = collect($existingRating->criteria ?? [])->every(function ($criterion) {
                return ((float) ($criterion['score'] ?? 0)) <= 0;
            });

            if ($allZeroCriteria) {
                $existingRating = null;
            }
        }

        $rubricTemplate = $this->defenseRubricService->getActiveTemplateForStage($schedule->stage);
        $defaultCriteria = $this->getDefaultCriteria($rubricTemplate?->criteria);
        $groupCriteria = collect($defaultCriteria)->filter(fn ($criterion) => ($criterion['scope'] ?? 'group') !== 'individual')->values()->all();
        $individualCriterion = collect($defaultCriteria)->first(fn ($criterion) => ($criterion['scope'] ?? 'group') === 'individual')
            ?? ['name' => 'Individual Contribution', 'max_points' => 100, 'scope' => 'individual'];
        $groupMembers = $schedule->group?->members ?? collect();

        return view('adviser.rating-sheets.form', compact(
            'schedule',
            'existingRating',
            'defaultCriteria',
            'rubricTemplate',
            'isFinalized',
            'groupCriteria',
            'individualCriterion',
            'groupMembers'
        ));
    }

    public function submitAdviserRating(Request $request, DefenseSchedule $schedule)
    {
        $user = Auth::user();

        $isAssignedPanel = $schedule->defensePanels()
            ->whereIn('role', ['coordinator', 'chair', 'member'])
            ->where('status', 'accepted')
            ->whereHas('faculty', function ($query) use ($user) {
                $query->where('faculty_id', $user->faculty_id);
            })
            ->exists();

        if (!$isAssignedPanel) {
            abort(403, 'You are not assigned to this defense panel.');
        }

        if ($schedule->status === 'completed' && $schedule->evaluationSummary()->exists()) {
            return back()->withErrors(['rating' => 'Ratings are locked because this defense has already been finalized.']);
        }

        $panelFacultyUserId = $this->resolvePanelFacultyUserId($schedule, $user);

        $validated = $request->validate([
            'criteria_ids' => 'nullable|array',
            'criteria_ids.*' => 'nullable|integer',
            'criteria_scopes' => 'required|array|min:1',
            'criteria_scopes.*' => 'required|in:group,individual',
            'criteria_names' => 'required|array|min:1',
            'criteria_names.*' => 'required|string|max:255',
            'criteria_max_points' => 'required|array|min:1',
            'criteria_max_points.*' => 'required|numeric|min:1|max:1000',
            'criteria_scores' => 'required|array|min:1',
            'criteria_scores.*' => 'required|numeric|min:0',
            'individual_scores' => 'required|array|min:1',
            'individual_scores.*' => 'required|numeric|min:0|max:100',
            'recommendation' => 'required|in:pass,conditional_pass,redefend',
            'recommendation_reason' => 'nullable|string|max:2000|required_if:recommendation,redefend',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $criteria = [];
        foreach (collect($validated['criteria_names'])->values() as $index => $name) {
            $maxPoints = (float) ($validated['criteria_max_points'][$index] ?? 0);
            $score = (float) ($validated['criteria_scores'][$index] ?? 0);
            if ($score > $maxPoints) {
                return back()
                    ->withErrors(['criteria_scores' => "Criterion '{$name}' score cannot exceed {$maxPoints}."])
                    ->withInput();
            }

            $criteria[] = [
                'criterion_id' => isset($validated['criteria_ids'][$index]) ? (int) $validated['criteria_ids'][$index] : null,
                'scope' => $validated['criteria_scopes'][$index] ?? 'group',
                'name' => $name,
                'max_points' => $maxPoints,
                'score' => $score,
            ];
        }

        $totalScore = collect($criteria)->sum('score');
        if ($totalScore <= 0) {
            return back()
                ->withErrors(['criteria_scores' => 'Please enter at least one non-zero criterion score before submitting.'])
                ->withInput();
        }

        $members = $schedule->group?->members ?? collect();
        $individualScores = $members->map(function ($member) use ($validated) {
            $score = (float) ($validated['individual_scores'][$member->student_id] ?? 0);
            if ($score > 100) {
                $score = 100;
            }

            return [
                'student_id' => $member->student_id,
                'student_name' => $member->name,
                'max_points' => 100,
                'score' => $score,
            ];
        })->values()->all();

        RatingSheet::updateOrCreate(
            [
                'defense_schedule_id' => $schedule->id,
                'faculty_id' => $panelFacultyUserId,
            ],
            [
                'group_id' => $schedule->group_id,
                'criteria' => $criteria,
                'individual_scores' => $individualScores,
                'total_score' => $totalScore,
                'recommendation' => $validated['recommendation'],
                'recommendation_reason' => $validated['recommendation_reason'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'submitted_at' => now(),
            ]
        );

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'defense_rating_submitted',
            'description' => $user->name . ' submitted rating sheet for group ' . ($schedule->group->name ?? 'Unknown Group') . ' (' . $schedule->stage_label . ')',
            'loggable_type' => DefenseSchedule::class,
            'loggable_id' => $schedule->id,
        ]);

        $nextSchedule = DefenseSchedule::query()
            ->whereHas('defensePanels', function ($query) use ($user) {
                $query->whereIn('role', ['coordinator', 'chair', 'member'])
                    ->where('status', 'accepted')
                    ->whereHas('faculty', function ($facultyQuery) use ($user) {
                        $facultyQuery->where('faculty_id', $user->faculty_id);
                    });
            })
            ->where('id', '!=', $schedule->id)
            ->whereDoesntHave('ratingSheets', function ($query) use ($user) {
                $query->whereHas('faculty', function ($facultyQuery) use ($user) {
                    $facultyQuery->where('faculty_id', $user->faculty_id);
                });
            })
            ->orderBy('start_at')
            ->first();

        // After submitting, coordinators go back to the overview/results page.
        // Panelists/advisers go back to their own rating form view.
        $isCoordinatorRoute = request()->routeIs('coordinator.*');
        $redirectRoute = $isCoordinatorRoute
            ? 'coordinator.rating-sheets.show'
            : 'adviser.rating-sheets.show';

        // If there is a next unrated schedule, the "next" link uses the rating form route.
        $nextRatingRoute = $isCoordinatorRoute
            ? 'coordinator.rating-sheets.rate.show'
            : 'adviser.rating-sheets.show';

        $redirect = redirect()
            ->route($redirectRoute, $schedule)
            ->with('success', 'Rating sheet submitted successfully.');

        if ($nextSchedule) {
            $redirect->with('next_rating_sheet_url', route($nextRatingRoute, $nextSchedule));
            $redirect->with('next_rating_group_name', $nextSchedule->group->name ?? 'next group');
        }

        return $redirect;
    }

    public function showCoordinatorRatings(DefenseSchedule $schedule)
    {
        $user = Auth::user();
        if ($redirect = $this->redirectToPreferredScheduleIfNeeded($schedule, $user)) {
            return $redirect;
        }

        $coordinatorOfferings = Auth::user()->offerings()->pluck('id')->toArray();
        if (!in_array($schedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only view ratings for schedules in your offerings.');
        }

        $ratingSheets = RatingSheet::with('faculty')
            ->where('defense_schedule_id', $schedule->id)
            ->where(function ($query) {
                $query->where('total_score', '>', 0)
                    ->orWhereNotNull('recommendation')
                    ->orWhereNotNull('remarks');
            })
            ->orderByDesc('submitted_at')
            ->get();

        $averageScore = $ratingSheets->count() > 0 ? $ratingSheets->avg('total_score') : null;
        $recommendationCounts = [
            'pass' => $ratingSheets->where('recommendation', 'pass')->count(),
            'conditional_pass' => $ratingSheets->where('recommendation', 'conditional_pass')->count(),
            'redefend' => $ratingSheets->where('recommendation', 'redefend')->count(),
        ];
        $schedule->load('evaluationSummary');
        $isFinalized = $schedule->status === 'completed' && (bool) $schedule->evaluationSummary;
        $schedule->loadMissing('group.members');
        $memberResults = $this->buildMemberResults($schedule, $ratingSheets);
        $readiness = $this->defenseEvaluationService->readiness($schedule);
        $missingPanelNames = collect($readiness['missing_panels'])->map(function ($panel) {
            $facultyName = \App\Models\User::query()->whereKey($panel->faculty_id)->value('name') ?? 'Unknown Faculty';
            $roleLabel = ucfirst((string) $panel->role);

            return "{$facultyName} ({$roleLabel})";
        })->values();

        return view('coordinator.rating-sheets.show', compact('schedule', 'ratingSheets', 'averageScore', 'recommendationCounts', 'readiness', 'missingPanelNames', 'isFinalized', 'memberResults'));
    }

    public function finalizeCoordinatorRatings(Request $request, DefenseSchedule $schedule)
    {
        $coordinatorOfferings = Auth::user()->offerings()->pluck('id')->toArray();
        if (!in_array($schedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only finalize ratings for schedules in your offerings.');
        }

        $validated = $request->validate([
            'final_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->defenseEvaluationService->finalize(
                $schedule,
                Auth::user(),
                $validated['final_notes'] ?? null
            );
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['finalize' => $exception->getMessage()]);
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'defense_result_finalized',
            'description' => Auth::user()->name . ' finalized defense result for group ' . ($schedule->group->name ?? 'Unknown Group') . ' (' . $schedule->stage_label . ')',
            'loggable_type' => DefenseSchedule::class,
            'loggable_id' => $schedule->id,
        ]);

        return redirect()
            ->route('coordinator.rating-sheets.show', $schedule)
            ->with('success', 'Final defense result has been finalized successfully.');
    }

    public function reopenCoordinatorRatings(Request $request, DefenseSchedule $schedule)
    {
        $coordinatorOfferings = Auth::user()->offerings()->pluck('id')->toArray();
        if (!in_array($schedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only reopen ratings for schedules in your offerings.');
        }

        $validated = $request->validate([
            'reopen_reason' => 'required|string|max:2000',
        ]);

        try {
            $this->defenseEvaluationService->reopen(
                $schedule,
                Auth::user(),
                $validated['reopen_reason']
            );
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['finalize' => $exception->getMessage()]);
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'defense_result_reopened',
            'description' => Auth::user()->name . ' reopened defense result for group ' . ($schedule->group->name ?? 'Unknown Group') . '. Reason: ' . $validated['reopen_reason'],
            'loggable_type' => DefenseSchedule::class,
            'loggable_id' => $schedule->id,
        ]);

        return redirect()
            ->route('coordinator.rating-sheets.show', $schedule)
            ->with('success', 'Finalized result has been reopened. Panel ratings can now be updated.');
    }

    public function printCoordinatorRatings(DefenseSchedule $schedule)
    {
        $coordinatorOfferings = Auth::user()->offerings()->pluck('id')->toArray();
        if (!in_array($schedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only print ratings for schedules in your offerings.');
        }

        $schedule->load(['group', 'evaluationSummary.finalizedBy']);
        $ratingSheets = RatingSheet::with('faculty')
            ->where('defense_schedule_id', $schedule->id)
            ->orderByDesc('submitted_at')
            ->get();
        $averageScore = $ratingSheets->count() > 0 ? $ratingSheets->avg('total_score') : 0;
        $schedule->loadMissing('group.members');
        $memberResults = $this->buildMemberResults($schedule, $ratingSheets);

        return view('coordinator.rating-sheets.print', compact('schedule', 'ratingSheets', 'averageScore', 'memberResults'));
    }

    private function getDefaultCriteria($rubricCriteria = null): array
    {
        if ($rubricCriteria && $rubricCriteria->count() > 0) {
            return $rubricCriteria->map(function ($criterion) {
                return [
                    'criterion_id' => $criterion->id,
                    'scope' => $criterion->scope,
                    'name' => $criterion->name,
                    'max_points' => (float) $criterion->max_points,
                    'score' => 0,
                ];
            })->toArray();
        }

        return [
            ['criterion_id' => null, 'scope' => 'group', 'name' => 'Problem Definition', 'max_points' => 10, 'score' => 0],
            ['criterion_id' => null, 'scope' => 'group', 'name' => 'Methodology', 'max_points' => 10, 'score' => 0],
            ['criterion_id' => null, 'scope' => 'group', 'name' => 'Technical Implementation', 'max_points' => 10, 'score' => 0],
            ['criterion_id' => null, 'scope' => 'group', 'name' => 'Documentation & Presentation', 'max_points' => 10, 'score' => 0],
            ['criterion_id' => null, 'scope' => 'individual', 'name' => 'Individual Contribution', 'max_points' => 100, 'score' => 0],
        ];
    }

    private function resolvePanelFacultyUserId(DefenseSchedule $schedule, $user): int
    {
        // Match accepted panel row by stable faculty code.
        return (int) (
            $schedule->defensePanels()
                ->whereIn('role', ['coordinator', 'chair', 'member'])
                ->where('status', 'accepted')
                ->whereHas('faculty', function ($query) use ($user) {
                    $query->where('faculty_id', $user->faculty_id);
                })
                ->value('faculty_id') ?? $user->id
        );
    }

    private function buildMemberResults(DefenseSchedule $schedule, $ratingSheets): array
    {
        $members = $schedule->group?->members ?? collect();

        return $members->map(function ($member) use ($ratingSheets) {
            $panelScores = collect($ratingSheets)->map(function ($sheet) use ($member) {
                $criteria = collect($sheet->criteria ?? []);
                $groupScore = (float) $criteria->filter(function ($criterion) {
                    $scope = strtolower((string) ($criterion['scope'] ?? 'group'));
                    $name = strtolower((string) ($criterion['name'] ?? ''));

                    return $scope !== 'individual' && !str_contains($name, 'individual contribution');
                })->sum(function ($criterion) {
                    return (float) ($criterion['score'] ?? 0);
                });
                if ($groupScore <= 0) {
                    $groupScore = (float) $sheet->total_score;
                }
                $individualRow = collect($sheet->individual_scores ?? [])
                    ->firstWhere('student_id', $member->student_id);
                $individualScore = (float) ($individualRow['score'] ?? 0);

                return ($groupScore + $individualScore) / 2;
            })->values();

            $finalScore = $panelScores->count() > 0 ? (float) $panelScores->avg() : 0;

            return [
                'student_id' => $member->student_id,
                'student_name' => $member->name,
                'final_score' => round($finalScore, 2),
                'grade_label' => $this->gradeLabel($finalScore),
                'status' => $finalScore >= 75 ? 'Passed' : 'Failed',
            ];
        })->values()->all();
    }

    private function gradeLabel(float $score): string
    {
        if ($score >= 96) {
            return 'Excellent';
        }
        if ($score >= 90) {
            return 'Very Good';
        }
        if ($score >= 85) {
            return 'Good';
        }
        if ($score >= 80) {
            return 'Fair';
        }
        if ($score >= 75) {
            return 'Passed';
        }

        return 'Failed';
    }

    private function redirectToPreferredScheduleIfNeeded(DefenseSchedule $schedule, $user)
    {
        $preferredScheduleId = DefenseSchedule::query()
            ->where('group_id', $schedule->group_id)
            ->whereHas('defensePanels', function ($query) use ($user) {
                $query->whereIn('role', ['coordinator', 'chair', 'member'])
                    ->where('status', 'accepted')
                    ->whereHas('faculty', function ($facultyQuery) use ($user) {
                        $facultyQuery->where('faculty_id', $user->faculty_id);
                    });
            })
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 3 WHEN 'scheduled' THEN 2 WHEN 'completed' THEN 1 ELSE 0 END DESC")
            ->orderByDesc('start_at')
            ->orderByDesc('id')
            ->value('id');

        if (!$preferredScheduleId || (int) $preferredScheduleId === (int) $schedule->id) {
            return null;
        }

        $targetRoute = request()->routeIs('coordinator.rating-sheets.show')
            ? 'coordinator.rating-sheets.show'
            : (request()->routeIs('coordinator.*')
                ? 'coordinator.rating-sheets.rate.show'
                : 'adviser.rating-sheets.show');

        return redirect()
            ->route($targetRoute, $preferredScheduleId)
            ->with('info', 'You were redirected to the current defense schedule for this group.');
    }
}
