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
        
        $milestoneTemplates = MilestoneTemplate::with('tasks')->get();
        
        // Filter groups by active semester
        $groupsQuery = \App\Models\Group::with(['members', 'adviser', 'milestones.template']);
        if ($activeTerm) {
            $groupsQuery->where('academic_term_id', $activeTerm->id);
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
}
