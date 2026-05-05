<?php
namespace App\Http\Controllers;
use App\Models\MilestoneTemplate;
use App\Models\GroupMilestone;
use App\Models\GroupMilestoneTask;
use App\Services\MilestoneAssignmentService;
use App\Services\NotificationService;
use App\Models\Group;
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

        $groupAssignmentMeta = $groups->mapWithKeys(function (Group $group) {
            return [$group->id => MilestoneAssignmentService::assignmentMeta($group)];
        });

        return view('coordinator.milestones.index', compact(
            'milestoneTemplates',
            'groups',
            'activeTerm',
            'groupAssignmentMeta'
        ));
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
            'sequence_order' => 'nullable|integer|min:1|max:255',
        ]);
        if ($request->input('sequence_order') === '' || $request->input('sequence_order') === null) {
            $data['sequence_order'] = null;
        }
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
            'sequence_order' => 'nullable|integer|min:1|max:255',
        ]);
        if ($request->input('sequence_order') === '' || $request->input('sequence_order') === null) {
            $data['sequence_order'] = null;
        }
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

    public function assignToGroup(Request $request)
    {
        $request->validate([
            'group_id'            => 'required|exists:groups,id',
            'milestone_template_id' => 'required|exists:milestone_templates,id',
            'due_date'            => 'nullable|date|after:today',
        ]);

        $template = MilestoneTemplate::with('tasks')->findOrFail($request->milestone_template_id);
        $group    = Group::findOrFail($request->group_id);

        // Prevent assigning the same template twice to the same group
        $alreadyAssigned = GroupMilestone::where('group_id', $group->id)
            ->where('milestone_template_id', $template->id)
            ->exists();

        if ($alreadyAssigned) {
            return redirect()->route('coordinator.milestones.index')
                ->withErrors(['assign' => "\"{$template->name}\" is already assigned to {$group->name}."]);
        }

        $assignmentError = MilestoneAssignmentService::validateAssignment($group, $template);
        if ($assignmentError !== null) {
            return redirect()->route('coordinator.milestones.index')
                ->withErrors(['assign' => $assignmentError]);
        }

        $groupMilestone = GroupMilestone::create([
            'group_id'              => $group->id,
            'milestone_template_id' => $template->id,
            'title'                 => $template->name,
            'description'           => $template->description,
            'due_date'              => $request->due_date,
            'progress_percentage'   => 0,
            'status'                => 'not_started',
        ]);

        foreach ($template->tasks as $task) {
            GroupMilestoneTask::create([
                'group_milestone_id' => $groupMilestone->id,
                'milestone_task_id'  => $task->id,
                'status'             => 'pending',
                'is_completed'       => false,
            ]);
        }

        $group->load(['members.account']);
        NotificationService::coordinatorAssignedMilestoneToGroup($group, $groupMilestone, $template);

        return redirect()->route('coordinator.milestones.index')
            ->with('success', "\"{$template->name}\" assigned to {$group->name} with {$template->tasks->count()} tasks.");
    }

    public function removeAssignmentFromGroup(Group $group, GroupMilestone $groupMilestone)
    {
        abort_unless($this->coordinatorMayAccessGroup($group), 403);
        abort_if((int) $groupMilestone->group_id !== (int) $group->getKey(), 404);

        $templateName = $groupMilestone->milestoneTemplate?->name ?? $groupMilestone->title ?? 'Milestone';
        $groupMilestone->delete();

        return redirect()->back()->with('success', "Removed \"{$templateName}\" assignment from {$group->name}.");
    }

    private function coordinatorMayAccessGroup(Group $group): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
        $offeringIds = \App\Models\Offering::query()
            ->where('faculty_id', $user->faculty_id)
            ->when($activeTerm, fn ($q) => $q->where('academic_term_id', $activeTerm->id))
            ->pluck('id');

        return $offeringIds->contains($group->offering_id);
    }
}