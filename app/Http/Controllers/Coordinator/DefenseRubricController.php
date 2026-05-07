<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\DefenseRubricTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DefenseRubricController extends Controller
{
    public function index()
    {
        $templates = DefenseRubricTemplate::query()
            ->with('criteria')
            ->orderByRaw("FIELD(stage, 'proposal', '60', '100')")
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->get();

        return view('coordinator.defense-rubrics.index', compact('templates'));
    }

    public function create()
    {
        $template = new DefenseRubricTemplate([
            'stage' => 'proposal',
        ]);
        $initialCriteria = $this->buildInitialCriteria($template);

        return view('coordinator.defense-rubrics.form', compact('template', 'initialCriteria'));
    }

    public function store(Request $request)
    {
        return $this->persist($request, new DefenseRubricTemplate());
    }

    public function edit(DefenseRubricTemplate $defenseRubric)
    {
        $defenseRubric->load('criteria');
        $template = $defenseRubric;
        $initialCriteria = $this->buildInitialCriteria($template);

        return view('coordinator.defense-rubrics.form', compact('template', 'initialCriteria'));
    }

    public function update(Request $request, DefenseRubricTemplate $defenseRubric)
    {
        return $this->persist($request, $defenseRubric);
    }

    public function destroy(DefenseRubricTemplate $defenseRubric)
    {
        if ($defenseRubric->is_active) {
            return redirect()
                ->route('coordinator.defense-rubrics.index')
                ->with('error', 'Active rubrics cannot be deleted. Set another rubric as active for this stage first.');
        }

        DB::transaction(function () use ($defenseRubric) {
            $defenseRubric->criteria()->delete();
            $defenseRubric->delete();
        });

        return redirect()
            ->route('coordinator.defense-rubrics.index')
            ->with('success', 'Defense rubric deleted successfully.');
    }

    private function persist(Request $request, DefenseRubricTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'stage' => 'required|in:proposal,60,100',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
            'criteria' => 'required|array|min:1',
            'criteria.*.name' => 'required|string|max:255',
            'criteria.*.scope' => 'required|in:group,individual',
            'criteria.*.max_points' => 'required|numeric|min:1|max:1000',
        ]);

        DB::transaction(function () use ($validated, $template) {
            $isActive = (bool) ($validated['is_active'] ?? false);

            $template->fill([
                'name' => $validated['name'],
                'stage' => $validated['stage'],
                'description' => $validated['description'] ?? null,
                'is_active' => $isActive,
            ])->save();

            if ($isActive) {
                DefenseRubricTemplate::query()
                    ->where('stage', $template->stage)
                    ->where('id', '!=', $template->id)
                    ->update(['is_active' => false]);
            }

            $template->criteria()->delete();

            foreach (array_values($validated['criteria']) as $index => $criterion) {
                $template->criteria()->create([
                    'name' => $criterion['name'],
                    'scope' => $criterion['scope'],
                    'max_points' => $criterion['max_points'],
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return redirect()
            ->route('coordinator.defense-rubrics.index')
            ->with('success', 'Defense rubric saved successfully.');
    }

    private function buildInitialCriteria(DefenseRubricTemplate $template): array
    {
        $criteriaCollection = $template->relationLoaded('criteria')
            ? $template->criteria
            : $template->criteria()->get();

        $criteria = old('criteria', $criteriaCollection->map(fn ($criterion) => [
            'name' => $criterion->name,
            'scope' => $criterion->scope,
            'max_points' => $criterion->max_points,
        ])->toArray());

        if (!empty($criteria)) {
            return $criteria;
        }

        return [
            ['name' => 'Software', 'scope' => 'group', 'max_points' => 50],
            ['name' => 'Document - Completeness', 'scope' => 'group', 'max_points' => 15],
            ['name' => 'Document - Acceptability', 'scope' => 'group', 'max_points' => 20],
            ['name' => 'Oral Presentation - Presentation', 'scope' => 'group', 'max_points' => 10],
            ['name' => 'Oral Presentation - Visual Tools', 'scope' => 'group', 'max_points' => 5],
            ['name' => 'Individual Contribution', 'scope' => 'individual', 'max_points' => 100],
        ];
    }
}

