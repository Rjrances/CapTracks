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
            $group->submissions_count = $this->getSubmissionsCount($group);
            $group->milestone_progress = $this->getMilestoneProgress($group);
            $group->next_milestone = $this->getNextMilestone($group);
            return $group;
        });

        // Get groups where user is panel member
        $panelGroups = Group::with(['academicTerm', 'defenseSchedules.defensePanels'])
            ->whereHas('defenseSchedules.defensePanels', function($query) use ($user) {
                $query->where('faculty_id', $user->id);
            })
            ->get();

        // Calculate summary statistics
        $summaryStats = [
            'total_groups' => $adviserGroups->count(),
            'panel_groups' => $panelGroups->count(),
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

        return view('adviser.dashboard', compact(
            'activeTerm', 
            'pendingInvitations', 
            'adviserGroups', 
            'notifications', 
            'recentActivities',
            'summaryStats'
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
                    'target_date' => $milestone->target_date
                ];
            }
        }
        
        return null;
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

    // ✅ NEW: Get overdue tasks count for a group
    private function getOverdueTasks($group)
    {
        $overdueCount = 0;
        
        // Count overdue milestone tasks
        foreach ($group->groupMilestoneTasks as $task) {
            if ($task->milestoneTask->due_date && 
                now()->isAfter($task->milestoneTask->due_date) && 
                $task->status !== 'completed') {
                $overdueCount++;
            }
        }
        
        return $overdueCount;
    }

    // ✅ NEW: Get recent activities for a specific group
    private function getGroupRecentActivities($group)
    {
        $activities = collect();
        
        // Add recent submissions
        $recentSubmissions = ProjectSubmission::whereHas('student', function($query) use ($group) {
            $query->whereIn('id', $group->members->pluck('id'));
        })->latest()->take(3)->get();
        
        foreach ($recentSubmissions as $submission) {
            $activities->push((object)[
                'title' => 'New submission from ' . $submission->student->name,
                'description' => $submission->title,
                'icon' => 'file-alt',
                'created_at' => $submission->created_at,
                'type' => 'submission'
            ]);
        }
        
        // Add milestone completions
        $recentMilestones = $group->groupMilestones()
            ->where('status', 'completed')
            ->latest()
            ->take(2)
            ->get();
            
        foreach ($recentMilestones as $milestone) {
            $activities->push((object)[
                'title' => 'Milestone completed: ' . $milestone->milestoneTemplate->name,
                'description' => 'Progress: ' . $milestone->progress_percentage . '%',
                'icon' => 'check-circle',
                'created_at' => $milestone->updated_at,
                'type' => 'milestone'
            ]);
        }
        
        return $activities->sortByDesc('created_at')->take(5);
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
            
            // Update the user's role to include 'adviser' if they don't already have it
            $user = Auth::user();
            if ($user->role !== 'adviser') {
                // If they're currently 'teacher', update to 'adviser' (since adviser is more specific)
                // If they're already 'coordinator' or 'chairperson', keep that role
                if ($user->role === 'teacher') {
                    $user->update(['role' => 'adviser']);
                    \Log::info("User {$user->name} role updated from 'teacher' to 'adviser' after accepting adviser invitation");
                }
            }
            
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
        
        // Get groups with comprehensive data for detailed workspace
        $groupsQuery = Group::with([
            'members', 
            'adviserInvitations', 
            'groupMilestones.milestoneTemplate',
            'groupMilestoneTasks.milestoneTask',
            'academicTerm',
            'offering',
            'defenseSchedules'
        ])
        ->where('adviser_id', $user->id);

        // Get all groups for statistics calculation
        $allGroups = $groupsQuery->get()->map(function ($group) {
            // Calculate comprehensive progress for each group
            $group->progress_percentage = $this->calculateGroupProgress($group);
            $group->submissions_count = $this->getSubmissionsCount($group);
            $group->milestone_progress = $this->getMilestoneProgress($group);
            $group->next_milestone = $this->getNextMilestone($group);
            $group->overdue_tasks = $this->getOverdueTasks($group);
            $group->recent_activities = $this->getGroupRecentActivities($group);
            return $group;
        });

        // Get panel groups count for statistics
        $panelGroupsCount = Group::whereHas('defenseSchedules.defensePanels', function($query) use ($user) {
            $query->where('faculty_id', $user->id);
        })->count();

        // Calculate simplified statistics for workspace
        $workspaceStats = [
            'total_adviser_groups' => $allGroups->count(),
            'total_panel_groups' => $panelGroupsCount,
            'average_progress' => $allGroups->avg('progress_percentage') ?? 0,
        ];

        // Get paginated groups for display
        $groups = $groupsQuery->paginate(10);
        
        // Apply the same transformations to paginated groups
        $groups->getCollection()->transform(function ($group) {
            $group->progress_percentage = $this->calculateGroupProgress($group);
            $group->submissions_count = $this->getSubmissionsCount($group);
            $group->milestone_progress = $this->getMilestoneProgress($group);
            $group->next_milestone = $this->getNextMilestone($group);
            $group->overdue_tasks = $this->getOverdueTasks($group);
            $group->recent_activities = $this->getGroupRecentActivities($group);
            return $group;
        });

        return view('adviser.groups', compact('groups', 'workspaceStats'));
    }

    public function groupDetails(Group $group)
    {
        // Check if user is the adviser of this group
        if ($group->adviser_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('adviser.group-details', compact('group'));
    }

    public function allGroups()
    {
        $user = Auth::user();
        
        // Get groups where user is adviser
        $adviserGroups = Group::with([
            'members', 
            'members.submissions',
            'adviserInvitations', 
            'groupMilestones.milestoneTemplate',
            'groupMilestoneTasks.milestoneTask',
            'academicTerm',
            'offering',
            'defenseSchedules'
        ])
        ->where('adviser_id', $user->id)
        ->get()
        ->map(function ($group) {
            $group->role_type = 'adviser';
            $group->progress_percentage = $this->calculateGroupProgress($group);
            $group->submissions_count = $this->getSubmissionsCount($group);
            $group->milestone_progress = $this->getMilestoneProgress($group);
            $group->next_milestone = $this->getNextMilestone($group);
            $group->overdue_tasks = $this->getOverdueTasks($group);
            $group->recent_activities = $this->getGroupRecentActivities($group);
            return $group;
        });
        
        // Get groups where user is panel member
        $panelGroups = Group::with([
            'members', 
            'members.submissions',
            'academicTerm', 
            'defenseSchedules.defensePanels'
        ])
        ->whereHas('defenseSchedules.defensePanels', function($query) use ($user) {
            $query->where('faculty_id', $user->id);
        })
        ->get()
        ->map(function ($group) use ($user) {
            $group->role_type = 'panel';
            $panelAssignment = $group->defenseSchedules->first()
                ->defensePanels->where('faculty_id', $user->id)->first();
            $group->panel_role = $panelAssignment->role ?? 'member';
            $group->defense_schedule = $group->defenseSchedules->first();
            $group->recent_activities = $this->getGroupRecentActivities($group);
            return $group;
        });
        
        // Combine and sort by creation date
        $allGroups = $adviserGroups->concat($panelGroups)->sortByDesc('created_at');
        
        // Get all submissions from these groups
        $memberIds = collect();
        foreach ($allGroups as $group) {
            $memberIds = $memberIds->merge($group->members->pluck('id'));
        }
        
        $submissions = ProjectSubmission::with(['student'])
            ->whereIn('student_id', $memberIds)
            ->orderBy('submitted_at', 'desc')
            ->get();
        
        // Group submissions by group
        $submissionsByGroup = $allGroups->mapWithKeys(function ($group) {
            $groupSubmissions = $group->members->flatMap(function ($member) {
                return $member->submissions ?? collect();
            });
            
            return [$group->id => [
                'group' => $group,
                'submissions' => $groupSubmissions->sortByDesc('submitted_at')->take(5), // Show only latest 5
                'user_role' => $group->role_type
            ]];
        });
        
        // Calculate summary statistics
        $summaryStats = [
            'total_groups' => $allGroups->count(),
            'adviser_groups' => $adviserGroups->count(),
            'panel_groups' => $panelGroups->count(),
            'total_submissions' => $submissions->count(),
            'pending_submissions' => $submissions->where('status', 'pending')->count(),
        ];

        return view('adviser.all-groups', compact('allGroups', 'adviserGroups', 'panelGroups', 'submissions', 'submissionsByGroup', 'summaryStats'));
    }

    public function panelSubmissions()
    {
        $user = Auth::user();
        
        // Get only groups where user is panel member
        $panelGroups = Group::with([
            'members', 
            'members.submissions',
            'academicTerm', 
            'defenseSchedules.defensePanels'
        ])
        ->whereHas('defenseSchedules.defensePanels', function($query) use ($user) {
            $query->where('faculty_id', $user->id);
        })
        ->get()
        ->map(function ($group) use ($user) {
            $group->role_type = 'panel';
            $panelAssignment = $group->defenseSchedules->first()
                ->defensePanels->where('faculty_id', $user->id)->first();
            $group->panel_role = $panelAssignment->role ?? 'member';
            $group->defense_schedule = $group->defenseSchedules->first();
            return $group;
        });

        // Get all submissions from panel groups only
        $memberIds = collect();
        foreach ($panelGroups as $group) {
            $memberIds = $memberIds->merge($group->members->pluck('id'));
        }
        
        $submissions = ProjectSubmission::with(['student'])
            ->whereIn('student_id', $memberIds)
            ->orderBy('submitted_at', 'desc')
            ->get();
        
        // Group submissions by group
        $submissionsByGroup = $panelGroups->mapWithKeys(function ($group) {
            $groupSubmissions = $group->members->flatMap(function ($member) {
                return $member->submissions ?? collect();
            });
            
            return [$group->id => [
                'group' => $group,
                'submissions' => $groupSubmissions->sortByDesc('submitted_at'),
                'user_role' => 'panel'
            ]];
        });
        
        // Calculate statistics for panel groups only
        $summaryStats = [
            'total_groups' => $panelGroups->count(),
            'total_submissions' => $submissions->count(),
            'pending_submissions' => $submissions->where('status', 'pending')->count(),
        ];

        return view('adviser.project.index', compact('panelGroups', 'submissions', 'submissionsByGroup', 'summaryStats'))
            ->with('allGroups', $panelGroups)
            ->with('adviserGroups', collect()); // Empty collection since this is panel-only view
    }




    public function markAllNotificationsAsRead()
    {
        try {
            $user = Auth::user();
            
            // Mark all notifications as read that are either:
            // 1. Specifically for this user (user_id matches)
            // 2. Match their role
            // 3. Are for common faculty roles they can see
            $updatedCount = Notification::where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('role', $user->role)
                      ->orWhereIn('role', ['teacher', 'adviser', 'panelist', 'coordinator']);
            })
            ->where('is_read', false)
            ->update(['is_read' => true]);
                
            return response()->json(['success' => true, 'message' => $updatedCount . ' notifications marked as read']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating notifications'], 500);
        }
    }

    public function markNotificationAsRead(Notification $notification)
    {
        try {
            $user = Auth::user();
            
            // Check if notification belongs to this user OR matches their role OR is for a valid faculty role
            $validRoles = ['teacher', 'adviser', 'panelist', 'coordinator'];
            $hasAccess = $notification->user_id === $user->id || 
                        $notification->role === $user->role || 
                        in_array($notification->role, $validRoles);
            
            if (!$hasAccess) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $notification->update(['is_read' => true]);
            
            return response()->json(['success' => true, 'message' => 'Notification marked as read']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating notification'], 500);
        }
    }


} 