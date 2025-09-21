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
    public function index()
    {
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        $studentCount = Student::count();
        $groupCount = Group::count();
        $facultyCount = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['adviser', 'panelist']);
        })->when($activeTerm, function($query) use ($activeTerm) {
            return $query->where('semester', $activeTerm->semester);
        })->count();
        $submissionCount = ProjectSubmission::count();
        $groupsWithAdviser = Group::whereNotNull('faculty_id')->count();
        $groupsWithoutAdviser = $groupCount - $groupsWithAdviser;
        $totalGroupMembers = Group::withCount('members')->get()->sum('members_count');
        $pendingSubmissions = ProjectSubmission::where('status', 'pending')->count();
        $approvedSubmissions = ProjectSubmission::where('status', 'approved')->count();
        $rejectedSubmissions = ProjectSubmission::where('status', 'rejected')->count();
        $milestoneTemplates = MilestoneTemplate::count();
        $activeMilestones = MilestoneTemplate::where('status', 'active')->count();
        $totalTasks = MilestoneTask::count();
        $completedTasks = MilestoneTask::where('is_completed', true)->count();
        $recentStudents = Student::latest()->take(5)->get();
        $recentGroups = Group::with(['adviser', 'members'])->latest()->take(5)->get();
        $recentSubmissions = ProjectSubmission::with('student')->latest()->take(5)->get();
        $notifications = Notification::latest()->take(5)->get();
        $pendingInvitations = AdviserInvitation::where('status', 'pending')
                                              ->with(['group', 'faculty'])
                                              ->latest()
                                              ->take(5)
                                              ->get();
        $recentActivities = $this->getRecentActivities();
        $upcomingDeadlines = $this->getUpcomingDeadlines();
        $user = auth()->user();
        $coordinatedOfferings = collect();
        $isTeacherCoordinator = false;
        if ($user && $user->hasRole('coordinator') && $user->offerings()->exists()) {
            $isTeacherCoordinator = true;
            $coordinatedOfferings = $user->getCoordinatedOfferings();
        }
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
        $activities = collect();
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
        $deadlines = collect();
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
