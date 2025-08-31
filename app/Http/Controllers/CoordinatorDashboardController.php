<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;

use App\Models\Notification;
use App\Models\Group;
use App\Models\ProjectSubmission;
use App\Models\User;
use App\Models\MilestoneTemplate;
use App\Models\MilestoneTask;
use App\Models\AdviserInvitation;
use App\Models\AcademicTerm;
use Carbon\Carbon;

class CoordinatorDashboardController extends Controller
{
    // Dashboard page for coordinator
    public function index()
    {
        // Get current active academic term
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        
        // Basic statistics
        $studentCount = Student::count();
        $groupCount = Group::count();
        $facultyCount = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['adviser', 'panelist']);
        })->count();
        $submissionCount = ProjectSubmission::count();

        // Group statistics
        $groupsWithAdviser = Group::whereNotNull('adviser_id')->count();
        $groupsWithoutAdviser = $groupCount - $groupsWithAdviser;
        $totalGroupMembers = Group::withCount('members')->get()->sum('members_count');

        // Submission statistics
        $pendingSubmissions = ProjectSubmission::where('status', 'pending')->count();
        $approvedSubmissions = ProjectSubmission::where('status', 'approved')->count();
        $rejectedSubmissions = ProjectSubmission::where('status', 'rejected')->count();

        // Milestone statistics
        $milestoneTemplates = MilestoneTemplate::count();
        $activeMilestones = MilestoneTemplate::where('status', 'active')->count();
        $totalTasks = MilestoneTask::count();
        $completedTasks = MilestoneTask::where('is_completed', true)->count();

        // Recent data
        $recentStudents = Student::latest()->take(5)->get();
        $recentGroups = Group::with(['adviser', 'members'])->latest()->take(5)->get();
        $recentSubmissions = ProjectSubmission::with('student')->latest()->take(5)->get();



        // Latest notifications
        $notifications = Notification::latest()->take(5)->get();

        // Pending adviser invitations
        $pendingInvitations = AdviserInvitation::where('status', 'pending')
                                              ->with(['group', 'faculty'])
                                              ->latest()
                                              ->take(5)
                                              ->get();

        // Recent activities (simulated for now)
        $recentActivities = $this->getRecentActivities();

        // Upcoming deadlines (based on milestone templates)
        $upcomingDeadlines = $this->getUpcomingDeadlines();

        // Check if current user is a teacher-coordinator (has offerings)
        $user = auth()->user();
        $coordinatedOfferings = collect();
        $isTeacherCoordinator = false;
        
        if ($user && $user->hasRole('coordinator') && $user->offerings()->exists()) {
            $isTeacherCoordinator = true;
            $coordinatedOfferings = $user->getCoordinatedOfferings();
        }
        
        // Ensure variables are always defined to prevent undefined variable errors
        $coordinatedOfferings = $coordinatedOfferings ?? collect();
        $isTeacherCoordinator = $isTeacherCoordinator ?? false;

        return view('coordinator.dashboard', compact(
            'activeTerm',
            'studentCount',
            'groupCount',
            'facultyCount',
            'submissionCount',
            'groupsWithAdviser',
            'groupsWithoutAdviser',
            'totalGroupMembers',
            'pendingSubmissions',
            'approvedSubmissions',
            'rejectedSubmissions',
            'milestoneTemplates',
            'activeMilestones',
            'totalTasks',
            'completedTasks',
            'recentStudents',
            'recentGroups',
            'recentSubmissions',
            'notifications',
            'pendingInvitations',
            'recentActivities',
            'upcomingDeadlines',
            'coordinatedOfferings',
            'isTeacherCoordinator'
        ));
    }

    private function getRecentActivities()
    {
        // This would typically come from an activities log table
        // For now, we'll create some sample activities based on recent data
        $activities = collect();

        // Add recent group creations
        $recentGroups = Group::latest()->take(3)->get();
        foreach ($recentGroups as $group) {
            $activities->push((object)[
                'title' => "New group created: {$group->name}",
                'description' => "Group with {$group->members->count()} members",
                'icon' => 'users',
                'created_at' => $group->created_at,
                'type' => 'group_created'
            ]);
        }

        // Add recent submissions
        $recentSubs = ProjectSubmission::with('student')->latest()->take(3)->get();
        foreach ($recentSubs as $submission) {
            $activities->push((object)[
                'title' => "New submission: {$submission->type}",
                'description' => "Submitted by {$submission->student->name}",
                'icon' => 'file-alt',
                'created_at' => $submission->created_at,
                'type' => 'submission'
            ]);
        }

        // Add recent adviser invitations
        $recentInvites = AdviserInvitation::with(['group', 'faculty'])->latest()->take(3)->get();
        foreach ($recentInvites as $invitation) {
            $activities->push((object)[
                'title' => "Adviser invitation sent",
                'description' => "{$invitation->faculty->name} invited to {$invitation->group->name}",
                'icon' => 'envelope',
                'created_at' => $invitation->created_at,
                'type' => 'invitation'
            ]);
        }

        return $activities->sortByDesc('created_at')->take(8);
    }

    private function getUpcomingDeadlines()
    {
        // This would typically come from milestone templates and their due dates
        // For now, we'll create some sample deadlines
        $deadlines = collect();

        // Add milestone template deadlines
        $milestones = MilestoneTemplate::where('status', 'active')->get();
        foreach ($milestones as $milestone) {
            $deadlines->push((object)[
                'title' => $milestone->name,
                'description' => $milestone->description,
                'due_date' => Carbon::now()->addDays(rand(7, 30)),
                'type' => 'milestone',
                'is_overdue' => false,
                'is_due_soon' => false
            ]);
        }

        // Add some sample submission deadlines
        $deadlines->push((object)[
            'title' => 'Proposal Submission Deadline',
            'description' => 'All groups must submit their project proposals',
            'due_date' => Carbon::now()->addDays(14),
            'type' => 'submission',
            'is_overdue' => false,
            'is_due_soon' => true
        ]);

        $deadlines->push((object)[
            'title' => 'Final Defense Schedule',
            'description' => 'Final project defense presentations',
            'due_date' => Carbon::now()->addDays(45),
            'type' => 'defense',
            'is_overdue' => false,
            'is_due_soon' => false
        ]);

        return $deadlines->sortBy('due_date')->take(5);
    }
}
