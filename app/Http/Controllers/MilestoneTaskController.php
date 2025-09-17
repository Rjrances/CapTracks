<?php
namespace App\Http\Controllers;
use App\Models\MilestoneTemplate;
use App\Models\MilestoneTask;
use Illuminate\Http\Request;
class MilestoneTaskController extends Controller
{
    public function index($milestoneId)
    {
        $milestone = MilestoneTemplate::findOrFail($milestoneId);
        $tasks = $milestone->tasks()->orderBy('order')->get();
        return view('coordinator.milestones.tasks.index', compact('milestone', 'tasks'));
    }
    public function create($milestoneId)
    {
        $milestone = MilestoneTemplate::findOrFail($milestoneId);
        return view('coordinator.milestones.tasks.create', compact('milestone'));
    }
    public function store(Request $request, $milestoneId)
    {
        $milestone = MilestoneTemplate::findOrFail($milestoneId);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);
        $milestone->tasks()->create($data);
        return redirect()
            ->route('coordinator.milestones.tasks.index', $milestone->id)
            ->with('success', 'Task created successfully.');
    }
    public function edit($milestoneId, $taskId)
    {
        $milestone = MilestoneTemplate::findOrFail($milestoneId);
        $task = MilestoneTask::findOrFail($taskId);
        return view('coordinator.milestones.tasks.edit', compact('milestone', 'task'));
    }
    public function update(Request $request, $milestoneId, $taskId)
    {
        $milestone = MilestoneTemplate::findOrFail($milestoneId);
        $task = MilestoneTask::findOrFail($taskId);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);
        $task->update($data);
        return redirect()
            ->route('coordinator.milestones.tasks.index', $milestone->id)
            ->with('success', 'Task updated successfully.');
    }
    public function destroy($milestoneId, $taskId)
    {
        $milestone = MilestoneTemplate::findOrFail($milestoneId);
        $task = MilestoneTask::findOrFail($taskId);
        $task->delete();
        return redirect()
            ->route('coordinator.milestones.tasks.index', $milestone->id)
            ->with('success', 'Task deleted successfully.');
    }
}
