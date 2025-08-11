<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Notification;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\Group;
use App\Models\User;
use App\Models\ProjectSubmission;
use App\Models\AdviserInvitation;

use App\Models\GroupMilestone;
use App\Models\GroupMilestoneTask;
use Illuminate\Support\Facades\DB;

class CoordinatorController extends Controller
{
    public function index()
    {
        // Get current active term
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        
        // Get events
        $events = Event::whereDate('date', '>=', now())->orderBy('date')->get();
        
        // Get notifications
        $notifications = Notification::latest()->take(5)->get();
        
        // Get dashboard statistics
        $stats = [
            'studentCount' => Student::count(),
            'groupCount' => Group::count(),
            'facultyCount' => User::whereHas('roles', function($query) {
                $query->whereIn('name', ['adviser', 'panelist']);
            })->count(),
            'submissionCount' => ProjectSubmission::count(),
            'pendingSubmissions' => ProjectSubmission::where('status', 'pending')->count(),
            'totalGroupMembers' => Group::withCount('members')->get()->sum('members_count'),
            'groupsWithAdviser' => Group::whereNotNull('adviser_id')->count(),
            'groupsWithoutAdviser' => Group::whereNull('adviser_id')->count(),
        ];
        
        // Get recent activities (placeholder - can be enhanced later)
        $recentActivities = collect();
        
        // Get recent groups
        $recentGroups = Group::with(['adviser', 'members'])
            ->latest()
            ->take(5)
            ->get();
            
        // Get recent submissions
        $recentSubmissions = ProjectSubmission::with('student')
            ->latest()
            ->take(5)
            ->get();
            
        // Get pending invitations
        $pendingInvitations = AdviserInvitation::with(['faculty', 'group'])
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();
            
        // Get upcoming deadlines (placeholder - can be enhanced later)
        $upcomingDeadlines = collect();

        return view('coordinator.dashboard', compact(
            'activeTerm',
            'events',
            'notifications',
            'stats',
            'recentActivities',
            'recentGroups',
            'recentSubmissions',
            'pendingInvitations',
            'upcomingDeadlines'
        ));
    }

    /**
     * Show classlist page with semester selection and students for selected semester
     */
    public function classlist(Request $request)
    {
        // Get all distinct semesters from students table
        $semesters = DB::table('students')
            ->select('semester')
            ->distinct()
            ->orderBy('semester', 'desc')
            ->pluck('semester');

        // Get selected semester from query string, default to first semester if not provided
        $selectedSemester = $request->input('semester');
        if (!$selectedSemester && $semesters->count() > 0) {
            $selectedSemester = $semesters->first();
        }

        $students = collect(); // default to empty collection

        // If a semester is selected, get paginated students for that semester
        if ($selectedSemester) {
            $studentsQuery = DB::table('students')
                ->where('semester', $selectedSemester);
            if ($request->filled('search')) {
                $search = $request->input('search');
                $studentsQuery->where(function($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('student_id', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%" );
                });
            }
            $students = $studentsQuery->paginate(10)->appends($request->only(['semester', 'search']));
        }

        return view('coordinator.classlist.index', compact('semesters', 'students', 'selectedSemester'));
    }

    public function groups(Request $request)
    {
        $query = Group::with(['adviser', 'members']);
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%" );
            });
        }
        $groups = $query->paginate(10)->appends($request->only('search'));
        return view('coordinator.groups.index', compact('groups'));
    }

    public function create()
    {
        return view('coordinator.groups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Group::create($validated);

        return redirect()->route('coordinator.groups.index')->with('success', 'Group created successfully!');
    }

    public function show($id)
    {
        $group = Group::with(['adviser', 'members'])->findOrFail($id);
        return view('coordinator.groups.show', compact('group'));
    }

    public function edit($id)
    {
        $group = Group::findOrFail($id);
        return view('coordinator.groups.edit', compact('group'));
    }

    public function assignAdviser($id)
    {
        $group = Group::with(['adviser', 'members'])->findOrFail($id);
        return view('coordinator.groups.assign_adviser', compact('group'));
    }

    public function update(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group->update($validated);

        return redirect()->route('coordinator.groups.show', $group->id)->with('success', 'Group updated successfully!');
    }

    public function destroy($id)
    {
        $group = Group::findOrFail($id);
        
        // Delete related records first (due to foreign key constraints)
        $group->members()->detach(); // Remove all group members
        $group->adviserInvitations()->delete(); // Delete adviser invitations
        
        // Delete the group
        $group->delete();

        return redirect()->route('coordinator.groups.index')->with('success', 'Group deleted successfully!');
    }

    public function groupMilestones($id)
    {
        $group = Group::with(['adviser', 'members', 'groupMilestones.milestoneTemplate'])->findOrFail($id);
        
        return view('coordinator.groups.milestones', compact('group'));
    }



    public function events()
    {
        $events = Event::orderBy('date', 'desc')->get();
        return view('coordinator.events.index', compact('events'));
    }

    public function defenseScheduling(Request $request)
    {
        // Get filter parameters
        $filters = $request->only(['term']);
        $filters = array_merge(['term' => null], $filters);
        
        // Build query for defense schedules
        $query = \App\Models\DefenseSchedule::with([
            'group.members', 
            'group.adviser', 
            'defensePanels.faculty',
            'academicTerm'
        ]);
        
        // Apply filters
        if (isset($filters['term']) && $filters['term']) {
            $query->where('academic_term_id', $filters['term']);
        }
        
        // Note: Progress-based filtering removed as overall_progress_percentage is a computed attribute
        // Stage and phase filtering can be implemented later if needed
        
        $defenseSchedules = $query->orderBy('start_at')->get();
        
        // Get filter options
        $filterOptions = [
            'terms' => \App\Models\AcademicTerm::orderBy('school_year', 'desc')->get(),
        ];
        
        return view('coordinator.defense.scheduling', compact('defenseSchedules', 'filters', 'filterOptions'));
    }



    public function notifications()
    {
        $notifications = Notification::orderBy('created_at', 'desc')->get();
        return view('coordinator.notifications', compact('notifications'));
    }

    // Milestones method removed - coordinators no longer have access to milestone management
    // They can only view group-specific milestones through groupMilestones method
}
