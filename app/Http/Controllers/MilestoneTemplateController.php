<?php

namespace App\Http\Controllers;

use App\Models\MilestoneTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class MilestoneTemplateController extends Controller
{
    public function index()
    {
        $statuses = ['todo', 'in_progress', 'done'];
        $milestonesByStatus = [];
        foreach ($statuses as $status) {
            $milestonesByStatus[$status] = MilestoneTemplate::with('tasks')->where('status', $status)->get();
        }
        return view('coordinator.milestones.index', compact('milestonesByStatus'));
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

    public function updateStatus(Request $request, MilestoneTemplate $milestone)
    {
        $request->validate([
            'status' => 'required|in:todo,in_progress,done',
        ]);
        $milestone->status = $request->status;
        $milestone->save();
        return Response::json(['success' => true]);
    }
}
