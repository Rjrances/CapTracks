<?php
namespace App\Http\Controllers;
use App\Models\MilestoneTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
class MilestoneTemplateController extends Controller
{
    public function index(Request $request)
    {
        $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
        $user = auth()->user();
        $coordinatedOfferingIds = collect();

        if ($user) {
            $coordinatedOfferingIds = \App\Models\Offering::where('faculty_id', $user->faculty_id)
                ->when($activeTerm, function ($query) use ($activeTerm) {
                    return $query->where('academic_term_id', $activeTerm->id);
                })
                ->pluck('id');
        }
        
        $milestoneTemplates = MilestoneTemplate::with('tasks')->get();
        
        //group filter
        $groupsQuery = \App\Models\Group::with(['members', 'adviser', 'milestones.template']);
        if ($activeTerm) {
            $groupsQuery->where('academic_term_id', $activeTerm->id);
        }
        if ($coordinatedOfferingIds->isNotEmpty()) {
            $groupsQuery->whereIn('offering_id', $coordinatedOfferingIds);
        } else {
            $groupsQuery->whereRaw('1 = 0');
        }
        $groups = $groupsQuery->get();
        
        return view('coordinator.milestones.index', compact('milestoneTemplates', 'groups', 'activeTerm'));
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
            'status' => 'required|in:active,inactive,draft',
        ]);
        MilestoneTemplate::create($data);
        return redirect()->route('coordinator.milestones.index')->with('success', 'Milestone created successfully.');
    }
    public function edit(MilestoneTemplate $milestone)
    {
        $milestone->load('tasks');
        return view('coordinator.milestones.edit', compact('milestone'));
    }
    public function update(Request $request, MilestoneTemplate $milestone)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive,draft',
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

    public function storeTask(Request $request, MilestoneTemplate $milestone)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $milestone->tasks()->create(['name' => $request->name]);
        return redirect()
            ->route('coordinator.milestones.edit', $milestone)
            ->with('success', 'Task added successfully.');
    }

    public function updateTask(Request $request, MilestoneTemplate $milestone, \App\Models\MilestoneTask $task)
    {
        abort_if($task->milestone_template_id !== $milestone->id, 403);
        $request->validate(['name' => 'required|string|max:255']);
        $task->update(['name' => $request->name]);
        return redirect()
            ->route('coordinator.milestones.edit', $milestone)
            ->with('success', 'Task updated successfully.');
    }

    public function destroyTask(MilestoneTemplate $milestone, \App\Models\MilestoneTask $task)
    {
        abort_if($task->milestone_template_id !== $milestone->id, 403);
        $task->delete();
        return redirect()
            ->route('coordinator.milestones.edit', $milestone)
            ->with('success', 'Task deleted successfully.');
    }
}