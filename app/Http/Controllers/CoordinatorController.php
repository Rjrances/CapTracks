<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\Group;
use App\Models\User;
use App\Models\ProjectSubmission;
use App\Models\AdviserInvitation;
use App\Models\GroupMilestone;
use App\Models\GroupMilestoneTask;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
class CoordinatorController extends Controller
{
    public function index()
    {
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        $notifications = Notification::latest()->take(5)->get();
        $user = auth()->user();
        $coordinatedOfferings = collect();
        $isTeacherCoordinator = false;
        if ($user && $user->hasRole('coordinator') && $user->offerings()->exists()) {
            $isTeacherCoordinator = true;
            $coordinatedOfferings = $user->getCoordinatedOfferings();
        }
        $coordinatedOfferings = $coordinatedOfferings ?? collect();
        $isTeacherCoordinator = $isTeacherCoordinator ?? false;
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
        $recentActivities = collect();
        $recentGroups = Group::with(['adviser', 'members'])
            ->latest()
            ->take(5)
            ->get();
        $recentSubmissions = ProjectSubmission::with('student')
            ->latest()
            ->take(5)
            ->get();
        $pendingInvitations = AdviserInvitation::with(['faculty', 'group'])
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();
        $upcomingDeadlines = collect();
        return view('coordinator.dashboard', compact(
            'activeTerm',
            'notifications',
            'stats',
            'recentActivities',
            'recentGroups',
            'recentSubmissions',
            'pendingInvitations',
            'upcomingDeadlines',
            'coordinatedOfferings',
            'isTeacherCoordinator'
        ));
    }
    public function classlist(Request $request)
    {
        $semesters = DB::table('students')
            ->select('semester')
            ->distinct()
            ->orderBy('semester', 'desc')
            ->pluck('semester');
        $selectedSemester = $request->input('semester');
        if (!$selectedSemester && $semesters->count() > 0) {
            $selectedSemester = $semesters->first();
        }
        $students = collect(); // default to empty collection
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
        $group = Group::with(['adviser', 'members', 'offering'])->findOrFail($id);
        $availableFaculty = User::whereIn('role', ['teacher', 'adviser', 'panelist'])->where(function($query) use ($group) {
            $query->whereDoesntHave('offerings', function($q) use ($group) {
                $q->where('id', $group->offering_id);
            });
        })->get();
        return view('coordinator.groups.assign_adviser', compact('group', 'availableFaculty'));
    }
    public function update(Request $request, $id)
    {
        $group = Group::with('offering')->findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'adviser_id' => 'nullable|exists:users,id',
        ]);
        if (isset($validated['adviser_id']) && $validated['adviser_id']) {
            $adviser = User::find($validated['adviser_id']);
            if ($adviser && $group->offering && $adviser->offerings()->where('id', $group->offering_id)->exists()) {
                return back()->withErrors(['adviser_id' => 'This faculty member coordinates this offering and cannot be assigned as an adviser due to conflict of interest.']);
            }
        }
        $group->update($validated);
        $message = 'Group updated successfully!';
        if (isset($validated['adviser_id'])) {
            if ($validated['adviser_id']) {
                $adviser = User::find($validated['adviser_id']);
                $message = "Adviser assigned successfully to {$adviser->name}!";
            } else {
                $message = "Adviser removed successfully!";
            }
        }
        return redirect()->route('coordinator.groups.show', $group->id)->with('success', $message);
    }
    public function destroy($id)
    {
        $group = Group::findOrFail($id);
        $group->members()->detach(); // Remove all group members
        $group->adviserInvitations()->delete(); // Delete adviser invitations
        $group->delete();
        return redirect()->route('coordinator.groups.index')->with('success', 'Group deleted successfully!');
    }
    public function groupMilestones($id)
    {
        $group = Group::with(['adviser', 'members', 'groupMilestones.milestoneTemplate'])->findOrFail($id);
        return view('coordinator.groups.milestones', compact('group'));
    }
    public function defenseScheduling(Request $request)
    {
        $filters = $request->only(['term']);
        $filters = array_merge(['term' => null], $filters);
        $query = \App\Models\DefenseSchedule::with([
            'group.members', 
            'group.adviser', 
            'defensePanels.faculty'
        ]);
        if (isset($filters['term']) && $filters['term']) {
            $query->where('group.academic_term_id', $filters['term']);
        }
        $defenseSchedules = $query->orderBy('start_at')->get();
        $filterOptions = [
            'terms' => \App\Models\AcademicTerm::orderBy('school_year', 'desc')->get(),
        ];
        return view('coordinator.defense.scheduling', compact('defenseSchedules', 'filters', 'filterOptions'));
    }
    public function notifications(Request $request)
    {
        $query = Notification::where('role', 'coordinator');
        if ($request->filled('role') && $request->role !== 'coordinator') {
            $query->where('role', $request->role);
        }
        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->where('is_read', false);
            } elseif ($request->status === 'read') {
                $query->where('is_read', true);
            }
        }
        if ($request->filled('date')) {
            $now = now();
            switch ($request->date) {
                case 'today':
                    $query->whereDate('created_at', $now->toDateString());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
                    break;
            }
        }
        $notifications = $query->orderBy('created_at', 'desc')->get();
        return view('coordinator.notifications', compact('notifications'));
    }
}
