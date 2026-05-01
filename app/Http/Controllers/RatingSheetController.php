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
            ->where('faculty_id', $user->id)
            ->exists();

        if (!$isAssignedPanel) {
            abort(403, 'You are not assigned to this defense panel.');
        }

        $existingRating = RatingSheet::where('defense_schedule_id', $schedule->id)
            ->where('faculty_id', $user->id)
            ->first();

        $defaultCriteria = $this->getDefaultCriteria();

        return view('adviser.rating-sheets.form', compact('schedule', 'existingRating', 'defaultCriteria'));
    }

    public function submitAdviserRating(Request $request, DefenseSchedule $schedule)
    {
        $user = Auth::user();

        $isAssignedPanel = $schedule->defensePanels()
            ->where('faculty_id', $user->id)
            ->exists();

        if (!$isAssignedPanel) {
            abort(403, 'You are not assigned to this defense panel.');
        }

        $validated = $request->validate([
            'criteria_names' => 'required|array|min:1',
            'criteria_names.*' => 'required|string|max:255',
            'criteria_scores' => 'required|array|min:1',
            'criteria_scores.*' => 'required|numeric|min:0|max:100',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $criteria = collect($validated['criteria_names'])->values()->map(function ($name, $index) use ($validated) {
            return [
                'name' => $name,
                'score' => (float) ($validated['criteria_scores'][$index] ?? 0),
            ];
        })->toArray();

        $totalScore = collect($criteria)->sum('score');

        RatingSheet::updateOrCreate(
            [
                'defense_schedule_id' => $schedule->id,
                'faculty_id' => $user->id,
            ],
            [
                'group_id' => $schedule->group_id,
                'criteria' => $criteria,
                'total_score' => $totalScore,
                'remarks' => $validated['remarks'] ?? null,
                'submitted_at' => now(),
            ]
        );

        return redirect()
            ->route('adviser.rating-sheets.show', $schedule)
            ->with('success', 'Rating sheet submitted successfully.');
    }

    public function showCoordinatorRatings(DefenseSchedule $schedule)
    {
        $coordinatorOfferings = Auth::user()->offerings()->pluck('id')->toArray();
        if (!in_array($schedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only view ratings for schedules in your offerings.');
        }

        $ratingSheets = RatingSheet::with('faculty')
            ->where('defense_schedule_id', $schedule->id)
            ->orderByDesc('submitted_at')
            ->get();

        $averageScore = $ratingSheets->count() > 0 ? $ratingSheets->avg('total_score') : null;

        return view('coordinator.rating-sheets.show', compact('schedule', 'ratingSheets', 'averageScore'));
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
}
