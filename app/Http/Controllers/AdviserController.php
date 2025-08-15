<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdviserInvitation;
use App\Models\Group;
use App\Models\User;
use App\Models\MilestoneTask;
use App\Models\GroupMilestoneTask;
use App\Models\ProjectSubmission;
use App\Models\AcademicTerm;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class AdviserController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        
        // Get current active term
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        
        // Get pending adviser invitations
        $pendingInvitations = AdviserInvitation::with(['group', 'group.members'])
            ->where('faculty_id', $user->id)
            ->pending()
            ->get();

        // Get groups where user is the adviser with comprehensive progress data
        $adviserGroups = Group::with([
            'members', 
            'adviserInvitations', 
            'groupMilestones.milestoneTemplate',
            'groupMilestoneTasks.milestoneTask',
            'academicTerm'
        ])
        ->where('adviser_id', $user->id)
        ->get()
        ->map(function ($group) {
            // Calculate comprehensive progress for each group
            $group->progress_percentage = $this->calculateGroupProgress($group);
            $group->completed_tasks = $this->getCompletedTasksCount($group);
            $group->total_tasks = $this->getTotalTasksCount($group);
            $group->submissions_count = $this->getSubmissionsCount($group);
            $group->milestone_progress = $this->getMilestoneProgress($group);
            $group->next_milestone = $this->getNextMilestone($group);
            $group->overdue_tasks = $this->getOverdueTasksCount($group);
            return $group;
        });

        // Calculate summary statistics
        $summaryStats = [
            'total_groups' => $adviserGroups->count(),
            'total_advisees' => $adviserGroups->sum(function($group) { return $group->members->count(); }),
            'groups_ready_for_defense' => $adviserGroups->filter(function($group) { return $group->progress_percentage >= 60; })->count(),
            'groups_needing_attention' => $adviserGroups->filter(function($group) { return $group->progress_percentage < 40; })->count(),
            'overdue_tasks_total' => $adviserGroups->sum('overdue_tasks'),
            'pending_invitations' => $pendingInvitations->count()
        ];

        // Get recent notifications with the user's actual role or common faculty roles
        $userRole = $user->role;
        $notifications = Notification::where('user_id', $user->id)
            ->where(function($query) use ($userRole) {
                $query->where('role', $userRole)
                      ->orWhereIn('role', ['teacher', 'adviser', 'panelist']);
            })
            ->latest()
            ->take(5)
            ->get();

        // Get recent activities
        $recentActivities = $this->getRecentActivities($user);

        // Get groups by progress category for quick overview
        $groupsByProgress = [
            'excellent' => $adviserGroups->filter(function($group) { return $group->progress_percentage >= 80; }),
            'good' => $adviserGroups->filter(function($group) { return $group->progress_percentage >= 60 && $group->progress_percentage < 80; }),
            'needs_attention' => $adviserGroups->filter(function($group) { return $group->progress_percentage < 60; })
        ];

        return view('adviser.dashboard', compact(
            'activeTerm', 
            'pendingInvitations', 
            'adviserGroups', 
            'notifications', 
            'recentActivities',
            'summaryStats',
            'groupsByProgress'
        ));
    }

    // ✅ NEW: Calculate group progress percentage
    private function calculateGroupProgress($group)
    {
        // Calculate progress based on group milestones
        $groupMilestones = $group->groupMilestones;
        
        if ($groupMilestones->isEmpty()) {
            return 0;
        }

        $totalProgress = $groupMilestones->sum('progress_percentage');
        return round($totalProgress / $groupMilestones->count());
    }

    // ✅ NEW: Get completed tasks count
    private function getCompletedTasksCount($group)
    {
        // Get completed tasks from group milestone tasks
        return GroupMilestoneTask::whereHas('groupMilestone', function($query) use ($group) {
            $query->where('group_id', $group->id);
        })->where('is_completed', true)->count();
    }

    // ✅ NEW: Get total tasks count
    private function getTotalTasksCount($group)
    {
        // Get total tasks from group milestone tasks
        return GroupMilestoneTask::whereHas('groupMilestone', function($query) use ($group) {
            $query->where('group_id', $group->id);
        })->count();
    }

    // ✅ NEW: Get submissions count
    private function getSubmissionsCount($group)
    {
        return ProjectSubmission::whereHas('student', function($query) use ($group) {
            $query->whereIn('id', $group->members->pluck('id'));
        })->count();
    }

    // ✅ NEW: Get milestone progress breakdown
    private function getMilestoneProgress($group)
    {
        $milestones = $group->groupMilestones;
        $progress = [];
        
        foreach ($milestones as $milestone) {
            $progress[] = [
                'name' => $milestone->milestoneTemplate->name,
                'progress' => $milestone->progress_percentage,
                'status' => $milestone->status,
                'target_date' => $milestone->target_date,
                'is_overdue' => $milestone->target_date && now()->isAfter($milestone->target_date)
            ];
        }
        
        return $progress;
    }

    // ✅ NEW: Get next milestone to focus on
    private function getNextMilestone($group)
    {
        $milestones = $group->groupMilestones->sortBy('order');
        
        foreach ($milestones as $milestone) {
            if ($milestone->progress_percentage < 100) {
                return [
                    'name' => $milestone->milestoneTemplate->name,
                    'progress' => $milestone->progress_percentage,
                    'target_date' => $milestone->target_date,
                    'remaining_tasks' => $this->getRemainingTasksCount($milestone)
                ];
            }
        }
        
        return null;
    }

    // ✅ NEW: Get overdue tasks count
    private function getOverdueTasksCount($group)
    {
        $overdueCount = 0;
        
        foreach ($group->groupMilestones as $milestone) {
            if ($milestone->target_date && now()->isAfter($milestone->target_date)) {
                $overdueCount += $milestone->groupMilestoneTasks
                    ->where('is_completed', false)
                    ->count();
            }
        }
        
        return $overdueCount;
    }

    // ✅ NEW: Get remaining tasks count for a milestone
    private function getRemainingTasksCount($milestone)
    {
        return $milestone->groupMilestoneTasks
            ->where('is_completed', false)
            ->count();
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
            Notification::create([
                'title' => 'Adviser Invitation Accepted',
                'description' => 'Your adviser invitation has been accepted by ' . Auth::user()->name,
                'role' => 'student',
            ]);
        } else {
            // Create notification for the group
            Notification::create([
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

    public function markAllNotificationsAsRead()
    {
        $user = Auth::user();
        
        $userRole = $user->role;
        Notification::where('user_id', $user->id)
            ->where(function($query) use ($userRole) {
                $query->where('role', $userRole)
                      ->orWhereIn('role', ['teacher', 'adviser', 'panelist', 'coordinator']);
            })
            ->where('is_read', false)
            ->update(['is_read' => true]);
            
        return back()->with('success', 'All notifications marked as read.');
    }

    public function markNotificationAsRead(Notification $notification)
    {
        $user = Auth::user();
        
        // Check if notification belongs to this user and has a valid faculty role
        $validRoles = ['teacher', 'adviser', 'panelist', 'coordinator'];
        if ($notification->user_id !== $user->id || !in_array($notification->role, $validRoles)) {
            abort(403, 'Unauthorized');
        }
        
        $notification->update(['is_read' => true]);
        
        // Redirect to the notification's redirect URL or invitations page
        return redirect($notification->redirect_url ?? route('adviser.invitations'));
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