<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\Group;
use App\Models\Offering;
use App\Models\User;
use App\Models\ProjectSubmission;
use App\Models\AdviserInvitation;
use App\Models\GroupMilestone;
use App\Models\GroupMilestoneTask;
use App\Services\NotificationService;
use App\Services\StudentImportService;
use Illuminate\Support\Facades\DB;

class CoordinatorController extends Controller
{
    public function index()
    {
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        $user = auth()->user();
        $notifications = Notification::query()
            ->visibleToCoordinatorWorkspace($user)
            ->latest()
            ->take(5)
            ->get();
        $coordinatedOfferings = collect();
        $isTeacherCoordinator = false;
        if ($user && $user->hasRole('coordinator') && $user->offerings()->exists()) {
            $isTeacherCoordinator = true;
            $coordinatedOfferings = $user->getCoordinatedOfferings();
        }

        $stats = [
            'studentCount' => Student::count(),
            'groupCount' => Group::count(),
            'facultyCount' => User::whereHas('roles', function($query) {
                $query->whereIn('name', ['adviser', 'panelist']);
            })->when($activeTerm, function($query) use ($activeTerm) {
                return $query->where('semester', $activeTerm->semester);
            })->count(),
            'submissionCount' => ProjectSubmission::count(),
            'pendingSubmissions' => ProjectSubmission::where('status', 'pending')->count(),
            'totalGroupMembers' => Group::withCount('members')->get()->sum('members_count'),
            'groupsWithAdviser' => Group::whereNotNull('faculty_id')->count(),
            'groupsWithoutAdviser' => Group::whereNull('faculty_id')->count(),
        ];
        $recentActivities = collect();
        $recentGroups = Group::with(['adviser', 'members'])
            ->latest()
            ->take(5)
            ->get();
        $recentSubmissions = ProjectSubmission::latest()
            ->take(5)
            ->get();
        $pendingInvitations = AdviserInvitation::with(['faculty', 'group'])
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();
        $upcomingDeadlines = collect();
        return view('dashboards.coordinator', compact(
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
        $user = auth()->user();
        $sortBy = $request->get('sort', 'student_id');
        $sortDirection = $request->get('direction', 'asc');
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        $coordinatedOfferingIds = Offering::where('faculty_id', $user->faculty_id)
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->pluck('id');

        $coordinatedOfferings = Offering::whereIn('id', $coordinatedOfferingIds)
            ->orderBy('subject_code')
            ->get(['id', 'subject_code', 'subject_title']);

        $coordinatedOfferingCount = $coordinatedOfferingIds->count();
        $coordinatedStudentCount = 0;
        $totalSemesterStudents = 0;
        $groupedStudentCount = 0;
        $ungroupedStudentCount = 0;

        $courses = collect();
        if ($activeTerm && $coordinatedOfferingIds->isNotEmpty()) {
            $scopedStudentsQuery = Student::where('semester', $activeTerm->semester)
                ->whereHas('offerings', function ($query) use ($coordinatedOfferingIds) {
                    $query->whereIn('offerings.id', $coordinatedOfferingIds);
                });

            $coordinatedStudentCount = (clone $scopedStudentsQuery)->count();
            $totalSemesterStudents = $coordinatedStudentCount;
            $groupedStudentCount = (clone $scopedStudentsQuery)->whereHas('groups', function ($query) use ($activeTerm, $coordinatedOfferingIds) {
                $query->whereIn('offering_id', $coordinatedOfferingIds)
                    ->where('academic_term_id', $activeTerm->id);
            })->count();
            $ungroupedStudentCount = max(0, $totalSemesterStudents - $groupedStudentCount);

            $courses = (clone $scopedStudentsQuery)
                ->distinct()
                ->pluck('course')
                ->filter()
                ->sort()
                ->values();
        }
        
        $students = collect(); //null
        if ($activeTerm && $coordinatedOfferingIds->isNotEmpty()) {
            $studentsQuery = Student::with(['offerings' => function ($query) use ($coordinatedOfferingIds) {
                $query->whereIn('offerings.id', $coordinatedOfferingIds);
            }])->where('semester', $activeTerm->semester)
                ->whereHas('offerings', function ($query) use ($coordinatedOfferingIds) {
                    $query->whereIn('offerings.id', $coordinatedOfferingIds);
                });
                
            
            if ($request->filled('name')) {
                $name = $request->input('name');
                $studentsQuery->where('name', 'like', "%$name%");
            }
            
            
            if ($request->filled('course')) {
                $course = $request->input('course');
                $studentsQuery->where('course', $course);
            }

            if ($request->filled('offering') && $coordinatedOfferingIds->contains((int) $request->input('offering'))) {
                $selectedOfferingId = (int) $request->input('offering');
                $studentsQuery->whereHas('offerings', function ($query) use ($selectedOfferingId) {
                    $query->where('offerings.id', $selectedOfferingId);
                });
            }
            
            //Default search
            if ($request->filled('search') && !$request->filled('name') && !$request->filled('course')) {
                $search = $request->input('search');
                $studentsQuery->where(function($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('student_id', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%" );
                });
            }
            
            $students = $studentsQuery->orderBy($sortBy, $sortDirection)
                ->paginate(10)->appends($request->only(['name', 'course', 'offering', 'search', 'sort', 'direction']));
        }
        
        return view('coordinator.classlist.index', compact(
            'students',
            'activeTerm',
            'courses',
            'sortBy',
            'sortDirection',
            'coordinatedStudentCount',
            'coordinatedOfferingCount',
            'totalSemesterStudents',
            'groupedStudentCount',
            'ungroupedStudentCount',
            'coordinatedOfferings'
        ));
    }

    public function importStudentsForm(Request $request)
    {
        $user = auth()->user();
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        $offerings = Offering::where('faculty_id', $user->faculty_id)
            ->with('academicTerm')
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->orderBy('subject_code')
            ->get();
        $selectedOfferingId = $request->get('offering_id');

        return view('coordinator.classlist.import', compact('offerings', 'selectedOfferingId', 'activeTerm'));
    }

    public function importStudents(Request $request)
    {
        return app(StudentImportService::class)->importFromRequest($request, StudentImportService::MODE_COORDINATOR);
    }

    public function groups(Request $request)
    {
        $user = auth()->user();
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        $coordinatedOfferingIds = Offering::where('faculty_id', $user->faculty_id)
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->pluck('id');

        $query = Group::with(['adviser', 'members']);

        if ($coordinatedOfferingIds->isNotEmpty()) {
            $query->whereIn('offering_id', $coordinatedOfferingIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($activeTerm) {
            $query->where('academic_term_id', $activeTerm->id);
        }

        if ($request->filled('offering') && $coordinatedOfferingIds->contains((int) $request->input('offering'))) {
            $query->where('offering_id', (int) $request->input('offering'));
        }
        
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%" );
            });
        }
        
        $groups = $query->paginate(10)->appends($request->only(['search', 'offering']));
        return view('coordinator.groups.index', compact('groups', 'activeTerm'));
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
        $availableFaculty = User::whereIn('role', ['teacher', 'adviser', 'panelist', 'coordinator'])
            ->where('semester', $group->academicTerm->semester)
            ->where(function($query) use ($group) {
                $query->whereDoesntHave('offerings', function($q) use ($group) {
                    $q->where('id', $group->offering_id);
                });
            })
            ->orderBy('name')
            ->get();
        return view('coordinator.groups.assign_adviser', compact('group', 'availableFaculty'));
    }
    public function update(Request $request, $id)
    {
        $group = Group::with('offering')->findOrFail($id);

        // Assign-adviser form only posts faculty_id; keep existing group name/description for validation.
        if (!$request->has('name')) {
            $request->merge([
                'name' => $group->name,
                'description' => $group->description,
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'faculty_id' => 'nullable|exists:users,faculty_id',
        ]);
        if (isset($validated['faculty_id']) && $validated['faculty_id']) {
            $adviser = User::where('faculty_id', $validated['faculty_id'])->first();
            if ($adviser && $group->offering && $adviser->offerings()->where('id', $group->offering_id)->exists()) {
                return back()->withErrors(['faculty_id' => 'This faculty member coordinates this offering and cannot be assigned as an adviser due to conflict of interest.']);
            }
        }

        $previousFacultyId = $group->faculty_id;
        $group->update($validated);
        $group->refresh();

        $newFacultyId = $group->faculty_id;
        if ($newFacultyId && (string) $newFacultyId !== (string) $previousFacultyId) {
            $assignedUser = User::where('faculty_id', $newFacultyId)->first();
            if ($assignedUser) {
                NotificationService::adviserAssignedByCoordinator(
                    $assignedUser,
                    $group,
                    auth()->user()?->name
                );
            }
        }

        $message = 'Group updated successfully!';
        if (isset($validated['faculty_id'])) {
            if ($validated['faculty_id']) {
                $adviser = User::where('faculty_id', $validated['faculty_id'])->first();
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
        $group->members()->detach(); //Tangtang members
        $group->adviserInvitations()->delete();
        $group->delete();
        return redirect()->route('coordinator.groups.index')->with('success', 'Group deleted successfully!');
    }
    public function groupMilestones($id)
    {
        $group = Group::with(['adviser', 'members', 'groupMilestones.milestoneTemplate'])->findOrFail($id);
        return view('coordinator.groups.milestones', compact('group'));
    }
    public function notifications()
    {
        $user = auth()->user();
        $query = Notification::query()->visibleToCoordinatorWorkspace($user);
        $notifications = $query->orderBy('created_at', 'desc')->get();
        return view('coordinator.notifications', compact('notifications'));
    }

    public function markNotificationAsRead($notificationId)
    {
        $user = auth()->user();
        $notification = Notification::findOrFail($notificationId);
        
        $hasAccess = Notification::query()
            ->visibleToCoordinatorWorkspace($user)
            ->whereKey($notification->id)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Notification marked as read']);
    }

    public function markAllNotificationsAsRead()
    {
        $user = auth()->user();
        $notificationIds = Notification::query()
            ->visibleToCoordinatorWorkspace($user)
            ->where('is_read', false)
            ->pluck('id')
            ->toArray();

        if (empty($notificationIds)) {
            return response()->json(['success' => true, 'message' => 'No unread notifications found']);
        }

        Notification::whereIn('id', $notificationIds)
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => count($notificationIds) . ' notifications marked as read']);
    }

    public function deleteNotification($notificationId)
    {
        $user = auth()->user();
        $notification = Notification::findOrFail($notificationId);
        
        $hasAccess = Notification::query()
            ->visibleToCoordinatorWorkspace($user)
            ->whereKey($notification->id)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json(['success' => true, 'message' => 'Notification deleted successfully']);
    }

    public function markMultipleAsRead(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id'
        ]);

        $updated = Notification::whereIn('id', $request->notification_ids)
            ->visibleToCoordinatorWorkspace($user)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => $updated . ' notifications marked as read'
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id'
        ]);

        $deleted = Notification::whereIn('id', $request->notification_ids)
            ->visibleToCoordinatorWorkspace($user)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted . ' notifications deleted'
        ]);
    }

    public function activityLog(Request $request)
    {
        $user = auth()->user();

        $activeTerm = AcademicTerm::where('is_active', true)->first();

        $coordinatedOfferingIds = Offering::where('faculty_id', $user->faculty_id)
            ->when($activeTerm, fn($q) => $q->where('academic_term_id', $activeTerm->id))
            ->pluck('id');

        $studentIds = Student::whereHas('offerings', function ($query) use ($coordinatedOfferingIds) {
            $query->whereIn('offerings.id', $coordinatedOfferingIds);
        })->pluck('student_id');

        $filterStudentId = $request->get('student_id');

        $activityQuery = ActivityLog::with('student')
            ->whereIn('student_id', $studentIds);

        if ($filterStudentId && $studentIds->contains($filterStudentId)) {
            $activityQuery->where('student_id', $filterStudentId);
        }

        $activityLogs = $activityQuery->latest()
            ->paginate(20)
            ->appends($request->only('student_id'));

        $studentsForFilter = Student::whereIn('student_id', $studentIds)
            ->orderBy('name')
            ->get(['student_id', 'name']);

        return view('coordinator.activity-log', compact(
            'activityLogs',
            'studentsForFilter',
            'filterStudentId'
        ));
    }

    public function facultyMatrix()
    {
        $user = auth()->user();
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        $coordinatedOfferings = Offering::with(['teacher', 'academicTerm'])
            ->where('faculty_id', $user->faculty_id)
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->get();

        $coordinatedOfferingIds = $coordinatedOfferings->pluck('id');

        $groups = Group::with([
            'offering.teacher',
            'adviser',
            'defenseSchedules' => function ($query) {
                $query->latest('start_at');
            },
            'defenseSchedules.defensePanels.faculty',
        ])
            ->whereIn('offering_id', $coordinatedOfferingIds)
            ->get();

        $matrixRows = $groups->map(function ($group) {
            $latestSchedule = $group->defenseSchedules->first();
            $panelsByRole = $latestSchedule
                ? $latestSchedule->defensePanels->groupBy('role')
                : collect();

            $panelChairs = $panelsByRole->get('chair', collect())
                ->pluck('faculty.name')
                ->filter()
                ->unique()
                ->values();

            $panelMembers = $panelsByRole->get('member', collect())
                ->pluck('faculty.name')
                ->filter()
                ->unique()
                ->values();

            return [
                'group_name' => $group->name,
                'offering_label' => $group->offering
                    ? ($group->offering->subject_code . ' - ' . $group->offering->subject_title)
                    : 'No offering assigned',
                'coordinator_name' => $group->offering->teacher->name ?? 'Unassigned',
                'adviser_name' => $group->adviser->name ?? 'Unassigned',
                'panel_chairs' => $panelChairs,
                'panel_members' => $panelMembers,
                'schedule_stage' => $latestSchedule->stage_label ?? 'Not scheduled',
                'schedule_status' => $latestSchedule
                    ? ucfirst(str_replace('_', ' ', $latestSchedule->status))
                    : 'Not scheduled',
            ];
        });

        $summary = [
            'total_offerings' => $coordinatedOfferings->count(),
            'total_groups' => $groups->count(),
            'groups_with_adviser' => $groups->whereNotNull('faculty_id')->count(),
            'groups_with_schedule' => $groups->filter(function ($group) {
                return $group->defenseSchedules->isNotEmpty();
            })->count(),
        ];

        return view('coordinator.faculty-matrix', compact('matrixRows', 'summary'));
    }
}
