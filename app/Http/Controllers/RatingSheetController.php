<?php

namespace App\Http\Controllers;

use App\Models\DefenseSchedule;
use App\Models\RatingSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingSheetController extends Controller
{
    public function showAdviserForm(DefenseSchedule $schedule)
    {
        $user = Auth::user();

        $isAssignedPanel = $schedule->defensePanels()
            ->whereIn('role', ['chair', 'member'])
            ->where('status', 'accepted')
            ->whereHas('faculty', function ($query) use ($user) {
                $query->where('faculty_id', $user->faculty_id);
            })
            ->exists();

        if (!$isAssignedPanel) {
            abort(403, 'You are not assigned to this defense panel.');
        }

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

        $defaultCriteria = $this->getDefaultCriteria();

        return view('adviser.rating-sheets.form', compact('schedule', 'existingRating', 'defaultCriteria'));
    }

    public function submitAdviserRating(Request $request, DefenseSchedule $schedule)
    {
        $user = Auth::user();

        $isAssignedPanel = $schedule->defensePanels()
            ->whereIn('role', ['chair', 'member'])
            ->where('status', 'accepted')
            ->whereHas('faculty', function ($query) use ($user) {
                $query->where('faculty_id', $user->faculty_id);
            })
            ->exists();

        if (!$isAssignedPanel) {
            abort(403, 'You are not assigned to this defense panel.');
        }

        $panelFacultyUserId = $this->resolvePanelFacultyUserId($schedule, $user);

        $validated = $request->validate([
            'criteria_names' => 'required|array|min:1',
            'criteria_names.*' => 'required|string|max:255',
            'criteria_scores' => 'required|array|min:1',
            'criteria_scores.*' => 'required|numeric|min:0|max:10',
            'recommendation' => 'required|in:pass,conditional_pass,redefend',
            'recommendation_reason' => 'nullable|string|max:2000|required_if:recommendation,redefend',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $criteria = collect($validated['criteria_names'])->values()->map(function ($name, $index) use ($validated) {
            return [
                'name' => $name,
                'score' => (float) ($validated['criteria_scores'][$index] ?? 0),
            ];
        })->toArray();

        $totalScore = collect($criteria)->sum('score');
        if ($totalScore <= 0) {
            return back()
                ->withErrors(['criteria_scores' => 'Please enter at least one non-zero criterion score before submitting.'])
                ->withInput();
        }

        RatingSheet::updateOrCreate(
            [
                'defense_schedule_id' => $schedule->id,
                'faculty_id' => $panelFacultyUserId,
            ],
            [
                'group_id' => $schedule->group_id,
                'criteria' => $criteria,
                'total_score' => $totalScore,
                'recommendation' => $validated['recommendation'],
                'recommendation_reason' => $validated['recommendation_reason'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'submitted_at' => now(),
            ]
        );

        $nextSchedule = DefenseSchedule::query()
            ->whereHas('defensePanels', function ($query) use ($user) {
                $query->whereIn('role', ['chair', 'member'])
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

        $redirect = redirect()
            ->route('adviser.rating-sheets.show', $schedule)
            ->with('success', 'Rating sheet submitted successfully.');

        if ($nextSchedule) {
            $redirect->with('next_rating_sheet_url', route('adviser.rating-sheets.show', $nextSchedule));
            $redirect->with('next_rating_group_name', $nextSchedule->group->name ?? 'next group');
        }

        return $redirect;
    }

    public function showCoordinatorRatings(DefenseSchedule $schedule)
    {
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

        return view('coordinator.rating-sheets.show', compact('schedule', 'ratingSheets', 'averageScore', 'recommendationCounts'));
    }

    private function getDefaultCriteria(): array
    {
        return [
            ['name' => 'Problem Definition', 'score' => 0],
            ['name' => 'Methodology', 'score' => 0],
            ['name' => 'Technical Implementation', 'score' => 0],
            ['name' => 'Documentation & Presentation', 'score' => 0],
        ];
    }

    private function resolvePanelFacultyUserId(DefenseSchedule $schedule, $user): int
    {
        // Match the accepted chair/member row by stable faculty code.
        return (int) (
            $schedule->defensePanels()
                ->whereIn('role', ['chair', 'member'])
                ->where('status', 'accepted')
                ->whereHas('faculty', function ($query) use ($user) {
                    $query->where('faculty_id', $user->faculty_id);
                })
                ->value('faculty_id') ?? $user->id
        );
    }
}
