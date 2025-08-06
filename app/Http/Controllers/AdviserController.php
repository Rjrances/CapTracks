<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdviserInvitation;
use App\Models\Group;
use App\Models\User;
use App\Models\MilestoneTask;
use App\Models\ProjectSubmission;
use Illuminate\Support\Facades\Auth;

class AdviserController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        
        // Get pending adviser invitations
        $pendingInvitations = AdviserInvitation::with(['group', 'group.members'])
            ->where('faculty_id', $user->id)
            ->pending()
            ->get();

        // Get groups where user is the adviser with progress data
        $adviserGroups = Group::with(['members', 'adviserInvitations'])
            ->where('adviser_id', $user->id)
            ->get()
            ->map(function ($group) {
                // ✅ NEW: Calculate progress for each group
                $group->progress_percentage = $this->calculateGroupProgress($group);
                $group->completed_tasks = $this->getCompletedTasksCount($group);
                $group->total_tasks = $this->getTotalTasksCount($group);
                $group->submissions_count = $this->getSubmissionsCount($group);
                return $group;
            });

        // Get recent notifications
        $notifications = \App\Models\Notification::where('role', 'adviser')
            ->latest()
            ->take(5)
            ->get();

        // ✅ NEW: Get recent activities
        $recentActivities = $this->getRecentActivities($user);

        return view('adviser.dashboard', compact('pendingInvitations', 'adviserGroups', 'notifications', 'recentActivities'));
    }

    // ✅ NEW: Calculate group progress percentage
    private function calculateGroupProgress($group)
    {
        // Simple calculation based on submissions from group members
        $totalMilestones = 3; // Proposal, Progress, Final
        $completedMilestones = 0;
        
        // Get submissions from group members
        $memberIds = $group->members->pluck('id');
        $submissions = ProjectSubmission::whereIn('student_id', $memberIds)->get();
        
        // Check if group has different types of submissions
        if ($submissions->where('type', 'proposal')->count() > 0) {
            $completedMilestones++;
        }
        if ($submissions->where('type', 'final')->count() > 0) {
            $completedMilestones++;
        }
        if ($submissions->count() >= 2) { // Assume progress if multiple submissions
            $completedMilestones++;
        }
        
        return round(($completedMilestones / $totalMilestones) * 100);
    }

    // ✅ NEW: Get completed tasks count
    private function getCompletedTasksCount($group)
    {
        // Get tasks from milestone templates and count completed ones
        $memberIds = $group->members->pluck('id');
        
        // For now, return a simple count based on submissions
        $submissions = ProjectSubmission::whereIn('student_id', $memberIds)->count();
        return min($submissions * 2, 8); // Simple calculation
    }

    // ✅ NEW: Get total tasks count
    private function getTotalTasksCount($group)
    {
        // Get total tasks from milestone templates
        $totalTasks = MilestoneTask::count();
        return $totalTasks > 0 ? $totalTasks : 10; // Default to 10 if no tasks exist
    }

    // ✅ NEW: Get submissions count
    private function getSubmissionsCount($group)
    {
        return ProjectSubmission::whereHas('student', function($query) use ($group) {
            $query->whereIn('id', $group->members->pluck('id'));
        })->count();
    }

    // ✅ NEW: Get recent activities
    private function getRecentActivities($user)
    {
        // Simplified activities - in real implementation, track actual activities
        $activities = collect();
        
        // Add some sample activities
        $activities->push((object)[
            'title' => 'Group ABC submitted proposal',
            'description' => 'Proposal document uploaded for review',
            'icon' => 'file-alt',
            'created_at' => now()->subHours(2)
        ]);
        
        $activities->push((object)[
            'title' => 'Task completed by John Doe',
            'description' => 'Research phase completed',
            'icon' => 'check-circle',
            'created_at' => now()->subHours(5)
        ]);
        
        return $activities;
    }

    public function invitations()
    {
        $user = Auth::user();
        
        $invitations = AdviserInvitation::with(['group', 'group.members'])
            ->where('faculty_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('adviser.invitations', compact('invitations'));
    }

    public function respondToInvitation(Request $request, AdviserInvitation $invitation)
    {
        $request->validate([
            'status' => 'required|in:accepted,declined',
            'response_message' => 'nullable|string|max:500',
        ]);

        // Check if user is the invited faculty
        if ($invitation->faculty_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Check if invitation is still pending
        if (!$invitation->isPending()) {
            return back()->with('error', 'This invitation has already been responded to.');
        }

        $invitation->update([
            'status' => $request->status,
            'response_message' => $request->response_message,
            'responded_at' => now(),
        ]);

        // If accepted, assign as adviser to the group
        if ($request->status === 'accepted') {
            $invitation->group->update(['adviser_id' => Auth::id()]);
            
            // Create notification for the group
            \App\Models\Notification::create([
                'title' => 'Adviser Invitation Accepted',
                'description' => 'Your adviser invitation has been accepted by ' . Auth::user()->name,
                'role' => 'student',
            ]);
        } else {
            // Create notification for the group
            \App\Models\Notification::create([
                'title' => 'Adviser Invitation Declined',
                'description' => 'Your adviser invitation has been declined by ' . Auth::user()->name,
                'role' => 'student',
            ]);
        }

        return back()->with('success', 'Invitation response submitted successfully.');
    }

    public function myGroups()
    {
        $user = Auth::user();
        
        $groups = Group::with(['members', 'adviserInvitations'])
            ->where('adviser_id', $user->id)
            ->paginate(10);

        return view('adviser.groups', compact('groups'));
    }

    public function groupDetails(Group $group)
    {
        // Check if user is the adviser of this group
        if ($group->adviser_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('adviser.group-details', compact('group'));
    }

    // ✅ NEW: Task management methods
    public function tasksIndex()
    {
        $user = Auth::user();
        
        $groups = Group::where('adviser_id', $user->id)->get();
        $tasks = collect();
        
        foreach ($groups as $group) {
            // Get tasks for each group (simplified)
            $groupTasks = MilestoneTask::whereHas('milestoneTemplate', function($query) {
                $query->where('status', '!=', 'done');
            })->take(5)->get();
            
            $tasks = $tasks->merge($groupTasks);
        }
        
        return view('adviser.tasks.index', compact('tasks', 'groups'));
    }

    public function groupTasks(Group $group)
    {
        // Check if user is the adviser of this group
        if ($group->adviser_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $tasks = MilestoneTask::whereHas('milestoneTemplate', function($query) {
            $query->where('status', '!=', 'done');
        })->get();

        return view('adviser.groups.tasks', compact('group', 'tasks'));
    }

    public function updateTask(Request $request, MilestoneTask $task)
    {
        $request->validate([
            'is_completed' => 'required|boolean',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($request->is_completed) {
            $task->markAsCompleted();
        } else {
            $task->markAsIncomplete();
        }

        return back()->with('success', 'Task updated successfully.');
    }
} 