<?php
namespace App\Http\Controllers\Coordinator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DefenseSchedule;
use App\Models\DefensePanel;
use App\Models\DefenseRequest;
use App\Models\Group;
use App\Models\User;
use App\Models\AcademicTerm;
use App\Models\Offering;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class DefenseScheduleController extends Controller
{
    public function index(Request $request)
    {
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        
        // Get defense requests
        $requestFilters = $request->only(['status', 'defense_type', 'search']);
        $requestFilters = array_merge([
            'status' => '',
            'defense_type' => '',
            'search' => ''
        ], $requestFilters);
        
        $defenseRequestsQuery = DefenseRequest::with(['group.members', 'group.adviser'])
            ->whereHas('group', function($q) use ($coordinatorOfferings) {
                $q->whereIn('offering_id', $coordinatorOfferings);
            })
            ->orderBy('created_at', 'desc');
            
        if (!empty($requestFilters['status'])) {
            $defenseRequestsQuery->where('status', $requestFilters['status']);
        }
        if (!empty($requestFilters['defense_type'])) {
            $defenseRequestsQuery->where('defense_type', $requestFilters['defense_type']);
        }
        if (!empty($requestFilters['search'])) {
            $defenseRequestsQuery->whereHas('group', function($q) use ($requestFilters) {
                $q->where('name', 'like', '%' . $requestFilters['search'] . '%');
            });
        }
        
        $defenseRequests = $defenseRequestsQuery->get();
        
        // Get defense schedules
        $scheduleQuery = DefenseSchedule::with(['group', 'academicTerm', 'defensePanels.faculty'])
            ->whereHas('group', function($q) use ($coordinatorOfferings) {
                $q->whereIn('offering_id', $coordinatorOfferings);
            })
            ->orderBy('start_at', 'asc');
            
        // Filter by active semester
        if ($activeTerm) {
            $scheduleQuery->where('academic_term_id', $activeTerm->id);
        }
        
        if ($request->filled('offering')) {
            $scheduleQuery->whereHas('group.offering', function ($q) use ($request, $coordinatorOfferings) {
                $q->where('id', $request->offering)->whereIn('id', $coordinatorOfferings);
            });
        }
        
        $defenseSchedules = $scheduleQuery->get();
        
        // Get quick stats
        $stats = [
            'pending_requests' => $defenseRequests->where('status', 'pending')->count(),
            'approved_requests' => $defenseRequests->where('status', 'approved')->count(),
            'scheduled_defenses' => $defenseSchedules->count(),
            'this_week_defenses' => $defenseSchedules->filter(function($schedule) {
                return $schedule->start_at && $schedule->start_at->isCurrentWeek();
            })->count()
        ];
        
        $academicTerms = AcademicTerm::orderBy('school_year', 'desc')->orderBy('semester', 'desc')->get();
        $offerings = Offering::whereIn('id', $coordinatorOfferings)->orderBy('subject_title')->get();
        $filterOptions = [
            'statuses' => ['pending', 'approved', 'rejected', 'scheduled'],
            'defense_types' => ['proposal', '60_percent', '100_percent']
        ];
        
        return view('coordinator.defense.index', compact(
            'defenseRequests', 'defenseSchedules', 'stats', 'academicTerms', 'offerings', 
            'activeTerm', 'requestFilters', 'filterOptions'
        ));
    }
    public function create()
    {
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        $groups = Group::with(['members', 'adviser', 'offering'])
            ->whereIn('offering_id', $coordinatorOfferings)
            ->get();
        $faculty = User::whereIn('role', ['teacher', 'chairperson'])->get();
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        return view('coordinator.defense.create', compact('groups', 'faculty', 'activeTerm'));
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'stage' => 'required|in:proposal,60,100',
            'room' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'panel_members' => 'required|array|min:1',
            'panel_members.*.faculty_id' => 'required|exists:users,id',
            'panel_members.*.role' => 'required|in:chair,member'
        ]);
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        if (!$activeTerm) {
            return back()->withErrors(['error' => 'No active academic term found. Please contact the chairperson to set an active term.'])->withInput();
        }
        $validated['academic_term_id'] = $activeTerm->id;
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        $group = Group::findOrFail($validated['group_id']);
        if (!in_array($group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only create defense schedules for groups in your offerings.');
        }
        $startAt = Carbon::parse($request->date . ' ' . $request->start_time);
        $endAt = Carbon::parse($request->date . ' ' . $request->end_time);
        $conflict = $this->checkDoubleBooking($startAt, $endAt, $request->room);
        if ($conflict) {
            return back()->withErrors(['room' => 'This room is already booked for the selected time slot.'])->withInput();
        }
        try {
            DB::beginTransaction();
            $schedule = DefenseSchedule::create([
                'group_id' => $validated['group_id'],
                'stage' => $validated['stage'],
                'academic_term_id' => $validated['academic_term_id'],
                'start_at' => $startAt,
                'end_at' => $endAt,
                'room' => $validated['room'],
                'status' => 'scheduled'
            ]);
            foreach ($validated['panel_members'] as $member) {
                DefensePanel::create([
                    'defense_schedule_id' => $schedule->id,
                    'faculty_id' => $member['faculty_id'],
                    'role' => $member['role']
                ]);
            }
            if ($schedule->group->faculty_id) {
                // Find the user ID that corresponds to this faculty_id
                $adviserUser = User::where('faculty_id', $schedule->group->faculty_id)->first();
                if ($adviserUser) {
                    DefensePanel::create([
                        'defense_schedule_id' => $schedule->id,
                        'faculty_id' => $adviserUser->id,
                        'role' => 'adviser'
                    ]);
                }
            }
            if ($schedule->group->offering && $schedule->group->offering->faculty_id) {
                // Find the user ID that corresponds to this faculty_id
                $coordinatorUser = User::where('faculty_id', $schedule->group->offering->faculty_id)->first();
                if ($coordinatorUser) {
                    DefensePanel::create([
                        'defense_schedule_id' => $schedule->id,
                        'faculty_id' => $coordinatorUser->id,
                        'role' => 'coordinator'
                    ]);
                }
            }
            DB::commit();
            $this->sendDefenseScheduleNotifications($schedule);
            return redirect()->route('coordinator.defense.index')->with('success', 'Defense schedule created successfully. All parties have been notified.');
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Defense schedule creation failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create defense schedule.'])->withInput();
        }
    }
    public function show($id)
    {
        $defenseSchedule = DefenseSchedule::with(['group.members', 'group.adviser', 'group.offering', 'academicTerm', 'defensePanels.faculty'])->findOrFail($id);
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        if (!in_array($defenseSchedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only view defense schedules for groups in your offerings.');
        }
        return view('coordinator.defense.show', compact('defenseSchedule'));
    }
    public function edit($id)
    {
        $defenseSchedule = DefenseSchedule::with(['group', 'academicTerm', 'defensePanels.faculty'])->findOrFail($id);
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        if (!in_array($defenseSchedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only edit defense schedules for groups in your offerings.');
        }
        $groups = Group::with(['members', 'adviser', 'offering'])
            ->whereIn('offering_id', $coordinatorOfferings)
            ->get();
        // Get faculty for panel selection - include existing panelists and available faculty
        $existingPanelistIds = $defenseSchedule->defensePanels->pluck('faculty_id')->toArray();
        
        $faculty = User::where(function ($query) use ($defenseSchedule, $existingPanelistIds) {
                // Include existing panelists (regardless of role)
                $query->whereIn('id', $existingPanelistIds)
                    // Or include available faculty (exclude adviser and offering coordinator)
                    ->orWhere(function ($subQuery) use ($defenseSchedule) {
                        $subQuery->whereIn('role', ['teacher', 'coordinator', 'chairperson'])
                            ->where('id', '!=', $defenseSchedule->group->faculty_id)
                            ->where('id', '!=', $defenseSchedule->group->offering->faculty_id ?? 0);
                    });
            })->get();
        $academicTerms = AcademicTerm::orderBy('school_year', 'desc')->orderBy('semester', 'desc')->get();
        return view('coordinator.defense.edit', compact('defenseSchedule', 'groups', 'faculty', 'academicTerms'));
    }
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'stage' => 'required|in:proposal,60,100',
            'academic_term_id' => 'required|exists:academic_terms,id',
            'room' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'panel_members' => 'required|array|min:1',
            'panel_members.*.faculty_id' => 'required|exists:users,id',
            'panel_members.*.role' => 'required|in:chair,member'
        ]);
        $schedule = DefenseSchedule::findOrFail($id);
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        $group = Group::findOrFail($validated['group_id']);
        if (!in_array($group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only edit defense schedules for groups in your offerings.');
        }
        $startAt = Carbon::parse($request->date . ' ' . $request->start_time);
        $endAt = Carbon::parse($request->date . ' ' . $request->end_time);
        $conflict = $this->checkDoubleBooking($startAt, $endAt, $request->room, $id);
        if ($conflict) {
            return back()->withErrors(['room' => 'This room is already booked for the selected time slot.'])->withInput();
        }
        try {
            DB::beginTransaction();
            $schedule->update([
                'group_id' => $validated['group_id'],
                'stage' => $validated['stage'],
                'academic_term_id' => $validated['academic_term_id'],
                'start_at' => $startAt,
                'end_at' => $endAt,
                'room' => $validated['room']
            ]);
            DefensePanel::where('defense_schedule_id', $schedule->id)->delete();
            foreach ($validated['panel_members'] as $member) {
                DefensePanel::create([
                    'defense_schedule_id' => $schedule->id,
                    'faculty_id' => $member['faculty_id'],
                    'role' => $member['role']
                ]);
            }
            if ($schedule->group->faculty_id) {
                // Find the user ID that corresponds to this faculty_id
                $adviserUser = User::where('faculty_id', $schedule->group->faculty_id)->first();
                if ($adviserUser) {
                    DefensePanel::create([
                        'defense_schedule_id' => $schedule->id,
                        'faculty_id' => $adviserUser->id,
                        'role' => 'adviser'
                    ]);
                }
            }
            if ($schedule->group->offering && $schedule->group->offering->faculty_id) {
                // Find the user ID that corresponds to this faculty_id
                $coordinatorUser = User::where('faculty_id', $schedule->group->offering->faculty_id)->first();
                if ($coordinatorUser) {
                    DefensePanel::create([
                        'defense_schedule_id' => $schedule->id,
                        'faculty_id' => $coordinatorUser->id,
                        'role' => 'coordinator'
                    ]);
                }
            }
            DB::commit();
            return redirect()->route('coordinator.defense.index')->with('success', 'Defense schedule updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Defense schedule update failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->withErrors(['error' => 'Failed to update defense schedule: ' . $e->getMessage()])->withInput();
        }
    }
    public function destroy($id)
    {
        $defenseSchedule = DefenseSchedule::with('group')->findOrFail($id);
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        if (!in_array($defenseSchedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only delete defense schedules for groups in your offerings.');
        }
        try {
            DB::beginTransaction();
            DefensePanel::where('defense_schedule_id', $id)->delete();
            $defenseSchedule->delete();
            DB::commit();
            return redirect()->route('coordinator.defense.index')->with('success', 'Defense schedule deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to delete defense schedule.'])->withInput();
        }
    }
    public function getAvailableFaculty(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'room' => 'required|string',
            'group_id' => 'required|exists:groups,id'
        ]);
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        $group = Group::with(['adviser', 'offering'])->find($request->group_id);
        if (!in_array($group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only access faculty for groups in your offerings.');
        }
        $startAt = Carbon::parse($request->date . ' ' . $request->start_time);
        $endAt = Carbon::parse($request->date . ' ' . $request->end_time);
        $conflict = $this->checkDoubleBooking($startAt, $endAt, $request->room);
        $availableFaculty = User::whereIn('role', ['teacher', 'chairperson'])
            ->where(function ($query) use ($group) {
                if ($group->faculty_id) {
                    $query->where('id', '!=', $group->faculty_id);
                }
                if ($group->offering_id) {
                    $query->whereDoesntHave('offerings', function ($q) use ($group) {
                        $q->where('id', $group->offering_id);
                    });
                }
            })->get();
        return response()->json([
            'availableFaculty' => $availableFaculty,
            'conflict' => $conflict,
            'message' => $conflict ? 'This room is already booked for the selected time slot.' : null
        ]);
    }
    private function checkDoubleBooking($startAt, $endAt, $room, $excludeId = null)
    {
        $query = DefenseSchedule::where('room', $room)
            ->where(function ($q) use ($startAt, $endAt) {
                $q->whereBetween('start_at', [$startAt, $endAt])
                  ->orWhereBetween('end_at', [$startAt, $endAt])
                  ->orWhere(function ($q2) use ($startAt, $endAt) {
                      $q2->where('start_at', '<=', $startAt)
                         ->where('end_at', '>=', $endAt);
                  });
            });
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }
    private function sendDefenseScheduleNotifications($schedule)
    {
        $group = $schedule->group;
        $formattedDate = $schedule->start_at->format('M d, Y \a\t h:i A');
        $stageLabel = $schedule->stage_label;
        if ($group->adviser) {
            NotificationService::createSimpleNotification(
                'Defense Schedule Created',
                "A {$stageLabel} defense has been scheduled for {$group->name} on {$formattedDate} in {$schedule->room}",
                'adviser',
                route('adviser.dashboard'),
                $group->adviser->id
            );
        }
        foreach ($schedule->defensePanels as $panel) {
            $panelMember = User::find($panel->faculty_id);
            if ($panelMember) {
                $roleLabel = ucfirst($panel->role);
                NotificationService::createSimpleNotification(
                    'Defense Panel Assignment',
                    "You have been assigned as {$roleLabel} for {$group->name}'s {$stageLabel} defense on {$formattedDate}",
                    $panelMember->role,
                    route('coordinator.defense.index'),
                    $panelMember->id
                );
            }
        }
        NotificationService::createSimpleNotification(
            'Defense Schedule Created',
            "A {$stageLabel} defense has been scheduled for {$group->name} on {$formattedDate} in {$schedule->room}",
            'student',
            route('student.dashboard')
        );
        NotificationService::createSimpleNotification(
            'New Defense Schedule',
            "A {$stageLabel} defense has been scheduled for {$group->name} on {$formattedDate}",
            'chairperson',
            route('chairperson.dashboard')
        );
    }

    // Defense Request Management Methods
    public function createSchedule(DefenseRequest $defenseRequest)
    {
        if (!$defenseRequest->isPending() && !$defenseRequest->isApproved()) {
            return back()->with('error', 'This defense request cannot be scheduled.');
        }
        
        // Load the defense request with group and adviser relationships
        $defenseRequest->load(['group.adviser', 'group.members']);
        
        // Get faculty for panelist selection (exclude adviser, chairperson, and coordinator who are pre-assigned)
        $availableFaculty = User::whereIn('role', ['teacher'])
            ->where('id', '!=', $defenseRequest->group->adviser->id) // Exclude the group's adviser
            ->where('role', '!=', 'chairperson') // Exclude any chairperson
            ->where('id', '!=', auth()->user()->id) // Exclude the current coordinator (you)
            ->get();
        
        return view('coordinator.defense-requests.create-schedule', compact('defenseRequest', 'availableFaculty'));
    }

    public function storeSchedule(Request $request, DefenseRequest $defenseRequest)
    {
        $request->validate([
            'scheduled_date' => 'required|date|after:today',
            'scheduled_time' => 'required',
            'room' => 'required|string|max:255',
            'coordinator_notes' => 'nullable|string|max:1000',
            'adviser_id' => 'required|exists:users,id',
            'subject_coordinator_id' => 'required|exists:users,id',
            'panelist_1_id' => 'required|exists:users,id',
            'panelist_2_id' => 'required|exists:users,id',
        ]);

        $startAt = Carbon::parse($request->scheduled_date . ' ' . $request->scheduled_time);
        $endAt = $startAt->copy()->addHours(2);

        $defenseSchedule = DefenseSchedule::create([
            'group_id' => $defenseRequest->group_id,
            'defense_type' => $defenseRequest->defense_type,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'room' => $request->room,
            'academic_term_id' => $defenseRequest->group->academic_term_id,
            'coordinator_notes' => $request->coordinator_notes,
        ]);

        $this->createDefensePanel($defenseSchedule, $request);
        $this->sendPanelNotifications($defenseSchedule);

        $defenseRequest->update([
            'status' => 'scheduled',
            'responded_at' => now(),
        ]);

        return redirect()->route('coordinator.defense.index')->with('success', 'Defense schedule created successfully!');
    }

    public function approve(DefenseRequest $defenseRequest)
    {
        if (!$defenseRequest->isPending()) {
            return back()->with('error', 'This defense request cannot be approved.');
        }
        $defenseRequest->update([
            'status' => 'approved',
            'responded_at' => now(),
        ]);
        return back()->with('success', 'Defense request approved successfully!');
    }

    public function reject(Request $request, DefenseRequest $defenseRequest)
    {
        $request->validate([
            'coordinator_notes' => 'required|string|max:1000',
        ]);
        if (!$defenseRequest->isPending()) {
            return back()->with('error', 'This defense request cannot be rejected.');
        }
        $defenseRequest->update([
            'status' => 'rejected',
            'coordinator_notes' => $request->coordinator_notes,
            'responded_at' => now(),
        ]);
        return back()->with('success', 'Defense request rejected successfully!');
    }

    private function createDefensePanel(DefenseSchedule $defenseSchedule, Request $request)
    {
        // Create adviser panel entry
        DefensePanel::create([
            'defense_schedule_id' => $defenseSchedule->id,
            'faculty_id' => $request->adviser_id,
            'role' => 'adviser',
        ]);
        
        // Create coordinator panel entry
        DefensePanel::create([
            'defense_schedule_id' => $defenseSchedule->id,
            'faculty_id' => $request->subject_coordinator_id,
            'role' => 'coordinator',
        ]);
        
        // Create panelist 1 entry
        DefensePanel::create([
            'defense_schedule_id' => $defenseSchedule->id,
            'faculty_id' => $request->panelist_1_id,
            'role' => 'member',
        ]);
        
        // Create panelist 2 entry
        DefensePanel::create([
            'defense_schedule_id' => $defenseSchedule->id,
            'faculty_id' => $request->panelist_2_id,
            'role' => 'member',
        ]);
    }

    private function sendPanelNotifications(DefenseSchedule $defenseSchedule)
    {
        $panelists = $defenseSchedule->defensePanels()->where('role', 'member')->get();
        foreach ($panelists as $panelist) {
            \App\Models\Notification::create([
                'title' => 'Defense Panel Assignment',
                'description' => 'You have been assigned to a defense panel for ' . $defenseSchedule->group->name,
                'role' => 'panelist',
                'redirect_url' => route('coordinator.defense.index'),
            ]);
        }
    }
}
