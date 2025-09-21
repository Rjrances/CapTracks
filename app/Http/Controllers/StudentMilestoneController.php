<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Group;
use App\Models\GroupMilestone;
use App\Models\GroupMilestoneTask;
use App\Models\MilestoneTemplate;
use App\Models\ProjectSubmission;
class StudentMilestoneController extends Controller
{
    public function index()
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return view('student.milestones.index', [
                'student' => $student,
                'group' => null,
                'groupMilestones' => collect(),
                'overallProgress' => 0,
                'milestoneTemplates' => collect(),
                'message' => 'You are not part of any group yet. Please join or create a group to view milestones.'
            ]);
        }
        $groupMilestones = $group->groupMilestones()->with('milestoneTemplate')->get();
        $milestoneTemplates = MilestoneTemplate::with('tasks')->get();
        $overallProgress = $this->calculateGroupProgress($group);
        $studentTasks = $this->getStudentTasks($student, $group);
        $recentSubmissions = $this->getRecentSubmissions($student);
        return view('student.milestones.index', compact(
            'student',
            'group',
            'groupMilestones',
            'overallProgress',
            'milestoneTemplates',
            'studentTasks',
            'recentSubmissions'
        ));
    }
    public function create()
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $isGroupLeader = $group->members()->where('group_members.student_id', $student->student_id)->where('group_members.role', 'leader')->exists();
        if (!$isGroupLeader) {
            return redirect()->route('student.milestones')->withErrors(['auth' => 'Only group leaders can create milestones.']);
        }
        $milestoneTemplates = MilestoneTemplate::with('tasks')->get();
        return view('student.milestones.create', compact(
            'student',
            'group',
            'milestoneTemplates'
        ));
    }
    public function store(Request $request)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $isGroupLeader = $group->members()->where('group_members.student_id', $student->student_id)->where('group_members.role', 'leader')->exists();
        if (!$isGroupLeader) {
            return redirect()->route('student.milestones')->withErrors(['auth' => 'Only group leaders can create milestones.']);
        }
        $request->validate([
            'milestone_template_id' => 'required|exists:milestone_templates,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date|after:today',
        ]);
        $milestoneTemplate = MilestoneTemplate::with('tasks')->findOrFail($request->milestone_template_id);
        $groupMilestone = GroupMilestone::create([
            'group_id' => $group->id,
            'milestone_template_id' => $milestoneTemplate->id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'progress_percentage' => 0,
        ]);
        foreach ($milestoneTemplate->tasks as $templateTask) {
            GroupMilestoneTask::create([
                'group_milestone_id' => $groupMilestone->id,
                'milestone_task_id' => $templateTask->id,
                'title' => $templateTask->title,
                'description' => $templateTask->description,
                'status' => 'pending',
                'is_completed' => false,
            ]);
        }
        return redirect()->route('student.milestones')
            ->with('success', 'Milestone created successfully!');
    }
    public function show($milestoneId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $groupMilestone = $group->groupMilestones()->with(['milestoneTemplate.tasks', 'groupTasks.submissions'])->find($milestoneId);
        if (!$groupMilestone) {
            return redirect()->route('student.milestones')->withErrors(['milestone' => 'Milestone not found.']);
        }
        $tasks = $this->getMilestoneTasksByStatus($groupMilestone, $student);
        $progress = $this->calculateMilestoneProgress($groupMilestone);
        $isGroupLeader = $group->members()->where('group_members.student_id', $student->student_id)->where('group_members.role', 'leader')->exists();
        return view('student.milestones.show', compact(
            'student',
            'group',
            'groupMilestone',
            'tasks',
            'progress',
            'isGroupLeader'
        ));
    }
    public function edit($milestoneId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $isGroupLeader = $group->members()->where('group_members.student_id', $student->student_id)->where('group_members.role', 'leader')->exists();
        if (!$isGroupLeader) {
            return redirect()->route('student.milestones')->withErrors(['auth' => 'Only group leaders can edit milestones.']);
        }
        $groupMilestone = $group->groupMilestones()->with(['milestoneTemplate', 'groupTasks'])->find($milestoneId);
        if (!$groupMilestone) {
            return redirect()->route('student.milestones')->withErrors(['milestone' => 'Milestone not found.']);
        }
        return view('student.milestones.edit', compact(
            'student',
            'group',
            'groupMilestone'
        ));
    }
    public function update(Request $request, $milestoneId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $isGroupLeader = $group->members()->where('group_members.student_id', $student->student_id)->where('group_members.role', 'leader')->exists();
        if (!$isGroupLeader) {
            return redirect()->route('student.milestones')->withErrors(['auth' => 'Only group leaders can edit milestones.']);
        }
        $groupMilestone = $group->groupMilestones()->find($milestoneId);
        if (!$groupMilestone) {
            return redirect()->route('student.milestones')->withErrors(['milestone' => 'Milestone not found.']);
        }
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date|after:today',
        ]);
        $groupMilestone->update([
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
        ]);
        return redirect()->route('student.milestones')
            ->with('success', 'Milestone updated successfully!');
    }
    public function destroy($milestoneId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $isGroupLeader = $group->members()->where('group_members.student_id', $student->student_id)->where('group_members.role', 'leader')->exists();
        if (!$isGroupLeader) {
            return redirect()->route('student.milestones')->withErrors(['auth' => 'Only group leaders can delete milestones.']);
        }
        $groupMilestone = $group->groupMilestones()->find($milestoneId);
        if (!$groupMilestone) {
            return redirect()->route('student.milestones')->withErrors(['milestone' => 'Milestone not found.']);
        }
        $groupMilestone->groupTasks()->delete();
        $groupMilestone->delete();
        return redirect()->route('student.milestones')
            ->with('success', 'Milestone deleted successfully!');
    }
    public function moveTask(Request $request, $taskId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Not authenticated']);
        }
        $task = GroupMilestoneTask::find($taskId);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found']);
        }
        $group = $student->groups()->first();
        if (!$group || $task->groupMilestone->group_id !== $group->id) {
            return response()->json(['success' => false, 'message' => 'Not authorized']);
        }
        $request->validate([
            'status' => 'required|in:pending,doing,done'
        ]);
        $task->updateStatus($request->status);
        return response()->json([
            'success' => true,
            'message' => 'Task moved successfully',
            'task' => $task->fresh(),
            'milestone_progress' => $task->groupMilestone->calculateProgressPercentage()
        ]);
    }
    public function bulkUpdateTasks(Request $request, $milestoneId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Not authenticated']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return response()->json(['success' => false, 'message' => 'You are not part of any group']);
        }
        $groupMilestone = $group->groupMilestones()->find($milestoneId);
        if (!$groupMilestone) {
            return response()->json(['success' => false, 'message' => 'Milestone not found']);
        }
        $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:group_milestone_tasks,id',
            'tasks.*.status' => 'required|in:pending,doing,done'
        ]);
        foreach ($request->tasks as $taskData) {
            $task = GroupMilestoneTask::find($taskData['id']);
            if ($task && $task->groupMilestone->group_id === $group->id) {
                $task->updateStatus($taskData['status']);
            }
        }
        $groupMilestone->calculateProgressPercentage();
        return response()->json([
            'success' => true,
            'message' => 'Tasks updated successfully',
            'milestone_progress' => $groupMilestone->progress_percentage
        ]);
    }
    public function recomputeProgress($milestoneId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Not authenticated']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return response()->json(['success' => false, 'message' => 'You are not part of any group']);
        }
        $groupMilestone = $group->groupMilestones()->find($milestoneId);
        if (!$groupMilestone) {
            return response()->json(['success' => false, 'message' => 'Milestone not found']);
        }
        $progress = $groupMilestone->calculateProgressPercentage();
        return response()->json([
            'success' => true,
            'message' => 'Progress recomputed successfully',
            'progress' => $progress
        ]);
    }
    public function updateTask(Request $request, $taskId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Not authenticated']);
        }
        $task = GroupMilestoneTask::find($taskId);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found']);
        }
        $group = $student->groups()->first();
        if (!$group || $task->groupMilestone->group_id !== $group->id) {
            return response()->json(['success' => false, 'message' => 'Not authorized']);
        }
        $task->update([
            'is_completed' => $request->input('is_completed', false),
            'status' => $request->input('is_completed', false) ? 'done' : 'pending',
            'completed_at' => $request->input('is_completed', false) ? now() : null,
            'completed_by' => $request->input('is_completed', false) ? $student->student_id : null,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'task' => $task
        ]);
    }
    private function getAuthenticatedStudent()
    {
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            return $studentAccount->student;
        }
        return null;
    }
    private function calculateGroupProgress($group)
    {
        $groupMilestones = $group->groupMilestones;
        if ($groupMilestones->isEmpty()) {
            return 0;
        }
        $totalProgress = $groupMilestones->sum('progress_percentage');
        return round($totalProgress / $groupMilestones->count());
    }
    private function getStudentTasks($student, $group)
    {
        $assignedTasks = GroupMilestoneTask::whereHas('groupMilestone', function($query) use ($group) {
            $query->where('group_id', $group->id);
        })->where('assigned_to', $student->student_id)->get();
        if ($assignedTasks->isEmpty()) {
            $assignedTasks = GroupMilestoneTask::whereHas('groupMilestone', function($query) use ($group) {
                $query->where('group_id', $group->id);
            })->get();
        }
        return $assignedTasks;
    }
    private function getRecentSubmissions($student)
    {
        return ProjectSubmission::where('student_id', $student->student_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }
    private function getMilestoneTasksByStatus($groupMilestone, $student)
    {
        $tasks = GroupMilestoneTask::where('group_milestone_id', $groupMilestone->id)
            ->with(['milestoneTask', 'assignedStudent'])
            ->get()
            ->map(function($task) use ($student) {
                $task->is_assigned_to_me = $task->assigned_to == $student->student_id;
                if (!$task->status) {
                    $task->status = $task->is_completed ? 'done' : 'pending';
                    $task->save();
                }
                return $task;
            });
        return [
            'pending' => $tasks->where('status', 'pending'),
            'doing' => $tasks->where('status', 'doing'),
            'done' => $tasks->where('status', 'done')
        ];
    }
    private function getMilestoneTasks($groupMilestone, $student)
    {
        return GroupMilestoneTask::where('group_milestone_id', $groupMilestone->id)
            ->with('milestoneTask')
            ->get()
            ->map(function($task) use ($student) {
                $task->is_assigned_to_me = $task->assigned_to == $student->student_id;
                return $task;
            });
    }
    private function calculateMilestoneProgress($groupMilestone)
    {
        $tasks = GroupMilestoneTask::where('group_milestone_id', $groupMilestone->id)->get();
        if ($tasks->isEmpty()) {
            return 0;
        }
        $completedTasks = $tasks->where('status', 'done')->count();
        return round(($completedTasks / $tasks->count()) * 100);
    }
    public function updateMultipleTasks(Request $request, $milestoneId)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $groupMilestone = $group->groupMilestones()->find($milestoneId);
        if (!$groupMilestone) {
            return redirect()->route('student.milestones')->withErrors(['milestone' => 'Milestone not found.']);
        }
        $tasks = GroupMilestoneTask::where('group_milestone_id', $groupMilestone->id)->get();
        $completedTaskIds = $request->input('completed_tasks', []);
        foreach ($tasks as $task) {
            $isCompleted = in_array($task->id, $completedTaskIds);
            if ($task->assigned_to === null || $task->assigned_to === $student->student_id) {
                $task->update([
                    'is_completed' => $isCompleted,
                    'status' => $isCompleted ? 'done' : 'pending',
                    'completed_at' => $isCompleted ? now() : null,
                    'completed_by' => $isCompleted ? $student->student_id : null,
                ]);
            }
        }
        $groupMilestone->calculateProgressPercentage();
        return redirect()->route('student.milestones.show', $milestoneId)
            ->with('success', 'Task progress updated successfully!');
    }
    public function assignTask(Request $request, GroupMilestoneTask $groupMilestoneTask)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $isLeader = $group->members()->where('group_members.student_id', $student->student_id)->where('group_members.role', 'leader')->exists();
        if (!$isLeader) {
            return redirect()->back()->withErrors(['auth' => 'Only group leaders can assign tasks.']);
        }
        $request->validate([
            'assigned_to' => 'required|exists:students,student_id',
        ]);
        $isGroupMember = $group->members()->where('group_members.student_id', $request->assigned_to)->exists();
        if (!$isGroupMember) {
            return redirect()->back()->withErrors(['assigned_to' => 'Student must be a member of this group.']);
        }
        $groupMilestoneTask->update([
            'assigned_to' => $request->assigned_to,
        ]);
        return redirect()->back()->with('success', 'Task assigned successfully.');
    }
    public function unassignTask(GroupMilestoneTask $groupMilestoneTask)
    {
        $student = $this->getAuthenticatedStudent();
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $group = $student->groups()->first();
        if (!$group) {
            return redirect()->route('student.milestones')->withErrors(['group' => 'You are not part of any group.']);
        }
        $isLeader = $group->members()->where('group_members.student_id', $student->student_id)->where('group_members.role', 'leader')->exists();
        if (!$isLeader) {
            return redirect()->back()->withErrors(['auth' => 'Only group leaders can unassign tasks.']);
        }
        $groupMilestoneTask->update([
            'assigned_to' => null,
        ]);
        return redirect()->back()->with('success', 'Task unassigned successfully.');
    }
}
