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
        // Get the authenticated student
        $student = $this->getAuthenticatedStudent();
        
        if (!$student) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }

        // Get student's group
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

        // Get group milestones
        $groupMilestones = $group->groupMilestones()->with('milestoneTemplate')->get();
        
        // Get all milestone templates for reference
        $milestoneTemplates = MilestoneTemplate::with('tasks')->get();
        
        // Calculate overall progress
        $overallProgress = $this->calculateGroupProgress($group);
        
        // Get student's assigned tasks
        $studentTasks = $this->getStudentTasks($student, $group);
        
        // Get recent submissions
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

        $groupMilestone = $group->groupMilestones()->with('milestoneTemplate.tasks')->find($milestoneId);
        
        if (!$groupMilestone) {
            return redirect()->route('student.milestones')->withErrors(['milestone' => 'Milestone not found.']);
        }

        // Get tasks for this milestone grouped by status
        $tasks = $this->getMilestoneTasksByStatus($groupMilestone, $student);
        
        // Get milestone progress
        $progress = $this->calculateMilestoneProgress($groupMilestone);

        // Check if student is group leader
        $isGroupLeader = $group->members()->where('group_members.student_id', $student->id)->where('group_members.role', 'leader')->exists();

        return view('student.milestones.show', compact(
            'student',
            'group',
            'groupMilestone',
            'tasks',
            'progress',
            'isGroupLeader'
        ));
    }

    // ✅ NEW: Move task between status columns
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

        // Check if student is part of the group
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

    // ✅ NEW: Bulk update tasks
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

        // Recalculate milestone progress
        $groupMilestone->calculateProgressPercentage();

        return response()->json([
            'success' => true,
            'message' => 'Tasks updated successfully',
            'milestone_progress' => $groupMilestone->progress_percentage
        ]);
    }

    // ✅ NEW: Recompute progress
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

        // Check if student is assigned to this task or is part of the group
        $group = $student->groups()->first();
        if (!$group || $task->groupMilestone->group_id !== $group->id) {
            return response()->json(['success' => false, 'message' => 'Not authorized']);
        }

        $task->update([
            'is_completed' => $request->input('is_completed', false),
            'status' => $request->input('is_completed', false) ? 'done' : 'pending',
            'completed_at' => $request->input('is_completed', false) ? now() : null,
            'completed_by' => $request->input('is_completed', false) ? $student->id : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'task' => $task
        ]);
    }

    private function getAuthenticatedStudent()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return $user->student;
        } elseif (session('is_student') && session('student_id')) {
            return Student::find(session('student_id'));
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
        // Get tasks assigned to this student
        $assignedTasks = GroupMilestoneTask::whereHas('groupMilestone', function($query) use ($group) {
            $query->where('group_id', $group->id);
        })->where('assigned_to', $student->id)->get();

        // If no specific assignments, get all tasks from the group's milestones
        if ($assignedTasks->isEmpty()) {
            $assignedTasks = GroupMilestoneTask::whereHas('groupMilestone', function($query) use ($group) {
                $query->where('group_id', $group->id);
            })->get();
        }

        return $assignedTasks;
    }

    private function getRecentSubmissions($student)
    {
        return ProjectSubmission::where('student_id', $student->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    // ✅ NEW: Get tasks grouped by status for Kanban (with fallback)
    private function getMilestoneTasksByStatus($groupMilestone, $student)
    {
        $tasks = GroupMilestoneTask::where('group_milestone_id', $groupMilestone->id)
            ->with(['milestoneTask', 'assignedStudent'])
            ->get()
            ->map(function($task) use ($student) {
                $task->is_assigned_to_me = $task->assigned_to == $student->id;
                // Ensure status is set (fallback for existing data)
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
                $task->is_assigned_to_me = $task->assigned_to == $student->id;
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

        // Get all tasks for this milestone
        $tasks = GroupMilestoneTask::where('group_milestone_id', $groupMilestone->id)->get();
        
        // Get completed task IDs from form
        $completedTaskIds = $request->input('completed_tasks', []);
        
        // Update tasks (only those assigned to this student or unassigned)
        foreach ($tasks as $task) {
            $isCompleted = in_array($task->id, $completedTaskIds);
            
            // Only allow updates if task is assigned to this student or unassigned
            if ($task->assigned_to === null || $task->assigned_to === $student->id) {
                $task->update([
                    'is_completed' => $isCompleted,
                    'status' => $isCompleted ? 'done' : 'pending',
                    'completed_at' => $isCompleted ? now() : null,
                    'completed_by' => $isCompleted ? $student->id : null,
                ]);
            }
        }

        // Recalculate milestone progress
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

        // Check if student is the group leader
        $isLeader = $group->members()->where('group_members.student_id', $student->id)->where('group_members.role', 'leader')->exists();
        if (!$isLeader) {
            return redirect()->back()->withErrors(['auth' => 'Only group leaders can assign tasks.']);
        }

        $request->validate([
            'assigned_to' => 'required|exists:students,id',
        ]);

        // Check if the assigned student is a member of this group
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

        // Check if student is the group leader
        $isLeader = $group->members()->where('group_members.student_id', $student->id)->where('group_members.role', 'leader')->exists();
        if (!$isLeader) {
            return redirect()->back()->withErrors(['auth' => 'Only group leaders can unassign tasks.']);
        }

        $groupMilestoneTask->update([
            'assigned_to' => null,
        ]);

        return redirect()->back()->with('success', 'Task unassigned successfully.');
    }
}
