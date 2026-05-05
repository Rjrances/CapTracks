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

        return view('coordinator.defense-rubrics.form', compact('template'));
    }

    public function store(Request $request)
    {
        return $this->persist($request, new DefenseRubricTemplate());
    }

    public function edit(DefenseRubricTemplate $defenseRubric)
    {
        $defenseRubric->load('criteria');
        $template = $defenseRubric;

        return view('coordinator.defense-rubrics.form', compact('template'));
    }

    public function update(Request $request, DefenseRubricTemplate $defenseRubric)
    {
        return $this->persist($request, $defenseRubric);
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
}

