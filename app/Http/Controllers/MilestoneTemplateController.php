<?php

namespace App\Http\Controllers;

use App\Models\MilestoneTemplate;
use Illuminate\Http\Request;

class MilestoneTemplateController extends Controller
{
    public function index()
    {
        $milestones = MilestoneTemplate::with('tasks')->paginate(10);
        return view('coordinator.milestones.index', compact('milestones'));
    }

    public function create()
    {
        return view('coordinator.milestones.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        MilestoneTemplate::create($data);

        return redirect()->route('coordinator.milestones.index')->with('success', 'Milestone created successfully.');
    }

    public function edit(MilestoneTemplate $milestone)
    {
        return view('coordinator.milestones.edit', compact('milestone'));
    }

    public function update(Request $request, MilestoneTemplate $milestone)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $milestone->update($data);

        return redirect()->route('coordinator.milestones.index')->with('success', 'Milestone updated successfully.');
    }

    public function destroy(MilestoneTemplate $milestone)
    {
        $milestone->delete();

        return redirect()->route('coordinator.milestones.index')->with('success', 'Milestone deleted successfully.');
    }
}
