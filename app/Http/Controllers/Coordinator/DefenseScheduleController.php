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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
class DefenseScheduleController extends Controller
{
    public function defenseRequestsIndex()
    {
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        $coordinatorOfferings = auth()->user()->offerings()
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->pluck('id')
            ->toArray();

        $pendingRequests = DefenseRequest::with(['group.members', 'group.adviser'])
            ->where('status', 'pending')
            ->whereHas('group', function ($query) use ($coordinatorOfferings) {
                $query->whereIn('offering_id', $coordinatorOfferings);
            })
            ->latest('requested_at')
            ->get();

        return view('coordinator.defense-requests.index', compact('pendingRequests'));
    }

    public function index(Request $request)
    {
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        $coordinatorOfferings = auth()->user()->offerings()
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->pluck('id')
            ->toArray();

        DefenseRequest::where('status', 'scheduled')
            ->whereDoesntHave('defenseSchedule')
            ->update(['status' => 'approved']);
        
        $requestFilters = $request->only(['defense_type', 'search']);
        $requestFilters = array_merge([
            'defense_type' => '',
            'search' => '',
        ], $requestFilters);
        
        $defenseRequestsQuery = DefenseRequest::with(['group.members', 'group.adviser', 'group.defenseSchedules'])
            ->whereHas('group', function($q) use ($coordinatorOfferings, $activeTerm) {
                $q->whereIn('offering_id', $coordinatorOfferings);
                if ($activeTerm) {
                    $q->where('academic_term_id', $activeTerm->id);
                }
            })
            ->orderBy('created_at', 'desc');
            
        if (!empty($requestFilters['defense_type'])) {
            $defenseRequestsQuery->where('defense_type', $requestFilters['defense_type']);
        }
        if (!empty($requestFilters['search'])) {
            $defenseRequestsQuery->whereHas('group', function($q) use ($requestFilters) {
                $q->where('name', 'like', '%' . $requestFilters['search'] . '%');
            });
        }
        
        $defenseRequests = $defenseRequestsQuery->get()->filter(function ($requestItem) {
            return $requestItem->group->defenseSchedules
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->isEmpty();
        })->values();
        
        $scheduleQuery = DefenseSchedule::with(['group', 'academicTerm', 'defensePanels.faculty', 'defenseRequest'])
            ->whereHas('group', function($q) use ($coordinatorOfferings) {
                $q->whereIn('offering_id', $coordinatorOfferings);
            })
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->orderBy('start_at', 'asc');

        if (!empty($requestFilters['defense_type'])) {
            $defenseType = $requestFilters['defense_type'];
            $stageForManual = match ($defenseType) {
                'proposal' => 'proposal',
                '60_percent' => '60',
                '100_percent' => '100',
                default => null,
            };
            $scheduleQuery->where(function ($q) use ($defenseType, $stageForManual) {
                $q->whereHas('defenseRequest', function ($rq) use ($defenseType) {
                    $rq->where('defense_type', $defenseType);
                });
                if ($stageForManual !== null) {
                    $q->orWhere(function ($sub) use ($stageForManual) {
                        $sub->whereNull('defense_request_id')
                            ->where('stage', $stageForManual);
                    });
                }
            });
        }

        if (!empty($requestFilters['search'])) {
            $search = $requestFilters['search'];
            $scheduleQuery->whereHas('group', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }
            
        if ($request->filled('offering')) {
            $scheduleQuery->whereHas('group.offering', function ($q) use ($request, $coordinatorOfferings) {
                $q->where('id', $request->offering)->whereIn('id', $coordinatorOfferings);
            });
        }

        $defenseSchedules = $scheduleQuery->get();

        $stats = [
            'pending_requests' => $defenseRequests->where('status', 'pending')->count(),
            'approved_requests' => $defenseRequests->where('status', 'approved')->count(),
            // Keep stats consistent with the currently filtered schedule list.
            'active_defenses' => $defenseSchedules->whereIn('status', ['scheduled', 'in_progress'])->count(),
            'completed_defenses' => $defenseSchedules->where('status', 'completed')->count(),
            'this_week_defenses' => $defenseSchedules->filter(function ($schedule) {
                return $schedule->start_at
                    && $schedule->start_at->isCurrentWeek()
                    && in_array($schedule->status, ['scheduled', 'in_progress'], true);
            })->count(),
        ];
        
        $academicTerms = AcademicTerm::orderBy('school_year', 'desc')->orderBy('semester', 'desc')->get();
        $offerings = Offering::whereIn('id', $coordinatorOfferings)->orderBy('subject_title')->get();
        $filterOptions = [
            'defense_types' => ['proposal', '60_percent', '100_percent'],
        ];
        
        return view('coordinator.defense.index', compact(
            'defenseRequests', 'defenseSchedules', 'stats', 'academicTerms', 'offerings', 
            'activeTerm', 'requestFilters', 'filterOptions'
        ));
    }
    public function create()
    {
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        $coordinatorOfferings = auth()->user()->offerings()
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->pluck('id')
            ->toArray();

        $allScopedGroups = Group::whereIn('offering_id', $coordinatorOfferings)
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->get(['id']);

        $groups = Group::with(['members', 'adviser', 'offering'])
            ->whereIn('offering_id', $coordinatorOfferings)
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->whereDoesntHave('defenseSchedules', function ($query) use ($activeTerm) {
                if ($activeTerm) {
                    $query->where('academic_term_id', $activeTerm->id);
                }
            })
            ->get();

        $groupAvailability = [
            'total_scoped_groups' => $allScopedGroups->count(),
            'available_groups' => $groups->count(),
        ];
        $groupAvailability['scheduled_groups'] = max(0, $groupAvailability['total_scoped_groups'] - $groupAvailability['available_groups']);

        $panelFacultyByGroupId = $groups->mapWithKeys(function (Group $group) {
            return [
                $group->id => $this->panelChairMemberCandidates($group)
                    ->map(fn (User $u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'faculty_id' => $u->faculty_id,
                    ])
                    ->values()
                    ->all(),
            ];
        });

        return view('coordinator.defense.create', compact('groups', 'activeTerm', 'panelFacultyByGroupId', 'groupAvailability'));
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
            'panel_members' => 'required|array|size:2',
            'panel_members.*.faculty_id' => 'required|distinct|exists:users,id',
            'panel_members.*.role' => 'required|in:chair,member'
        ]);
        $panelValidationError = $this->validatePanelComposition($validated['panel_members']);
        if ($panelValidationError) {
            return back()->withErrors(['panel_members' => $panelValidationError])->withInput();
        }
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        if (!$activeTerm) {
            return back()->withErrors(['error' => 'No active academic term found. Please contact the chairperson to set an active term.'])->withInput();
        }
        $validated['academic_term_id'] = $activeTerm->id;
        $coordinatorOfferings = auth()->user()->offerings()
            ->where('academic_term_id', $activeTerm->id)
            ->pluck('id')
            ->toArray();
        $group = Group::with('offering')->findOrFail($validated['group_id']);
        if (!in_array($group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only create defense schedules for groups in your offerings.');
        }
        if ((int) $group->academic_term_id !== (int) $activeTerm->id) {
            abort(403, 'You can only create defense schedules for groups in the active term.');
        }

        $ineligiblePick = $this->panelMembersMustNotIncludeAdviserOrCoordinator($group, $validated['panel_members']);
        if ($ineligiblePick) {
            return back()->withErrors(['panel_members' => $ineligiblePick])->withInput();
        }
        $startAt = Carbon::parse($request->date . ' ' . $request->start_time);
        $endAt = Carbon::parse($request->date . ' ' . $request->end_time);

        if ($this->hasGroupScheduleOnDate($validated['group_id'], $request->date)) {
            return back()->withErrors([
                'date' => 'This group already has a defense schedule on the selected date.'
            ])->withInput();
        }

        $conflict = $this->checkDoubleBooking($startAt, $endAt, $request->room);
        if ($conflict) {
            return back()->withErrors(['room' => 'This room is already booked for the selected time slot.'])->withInput();
        }

        //check member conflict
        $panelFacultyIds = collect($validated['panel_members'])->pluck('faculty_id')->toArray();
        $panelConflict = $this->checkPanelMemberConflicts($panelFacultyIds, $startAt, $endAt);
        if ($panelConflict) {
            return back()->withErrors(['error' => 'One or more panel members are already scheduled for another defense at the selected time.'])->withInput();
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
                    'role' => $member['role'],
                    'status' => 'pending',
                ]);
            }
            if ($schedule->group->faculty_id) {
                $adviserUser = User::where('faculty_id', $schedule->group->faculty_id)->first();
                if ($adviserUser) {
                    DefensePanel::create([
                        'defense_schedule_id' => $schedule->id,
                        'faculty_id' => $adviserUser->id,
                        'role' => 'adviser',
                        // Group adviser is auto-included, not invitation-based.
                        'status' => 'accepted',
                        'responded_at' => now(),
                    ]);
                }
            }
            if ($schedule->group->offering && $schedule->group->offering->faculty_id) {
                $coordinatorUser = User::where('faculty_id', $schedule->group->offering->faculty_id)->first();
                if ($coordinatorUser) {
                    DefensePanel::create([
                        'defense_schedule_id' => $schedule->id,
                        'faculty_id' => $coordinatorUser->id,
                        'role' => 'coordinator',
                        // Subject coordinator is auto-included, not invitation-based.
                        'status' => 'accepted',
                        'responded_at' => now(),
                    ]);
                }
            }
            DB::commit();
            $this->sendDefenseScheduleNotifications($schedule);
            return redirect()->route('coordinator.defense.index')->with('success', 'Defense schedule created successfully. All parties have been notified.');
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Failed to create defense schedule: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create defense schedule. Please try again or contact support if the problem persists.'])->withInput();
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
        $existingPanelistIds = $defenseSchedule->defensePanels->pluck('faculty_id')->toArray();
        
        $faculty = User::where(function ($query) use ($defenseSchedule, $existingPanelistIds) {
                $query->whereIn('id', $existingPanelistIds)
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
            'panel_members' => 'required|array|size:2',
            'panel_members.*.faculty_id' => 'required|distinct|exists:users,id',
            'panel_members.*.role' => 'required|in:chair,member'
        ]);
        $panelValidationError = $this->validatePanelComposition($validated['panel_members']);
        if ($panelValidationError) {
            return back()->withErrors(['panel_members' => $panelValidationError])->withInput();
        }
        $schedule = DefenseSchedule::findOrFail($id);
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        $group = Group::with('offering')->findOrFail($validated['group_id']);
        if (!in_array($group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only edit defense schedules for groups in your offerings.');
        }

        $ineligiblePick = $this->panelMembersMustNotIncludeAdviserOrCoordinator($group, $validated['panel_members']);
        if ($ineligiblePick) {
            return back()->withErrors(['panel_members' => $ineligiblePick])->withInput();
        }
        $startAt = Carbon::parse($request->date . ' ' . $request->start_time);
        $endAt = Carbon::parse($request->date . ' ' . $request->end_time);

        if ($this->hasGroupScheduleOnDate($validated['group_id'], $request->date, $id)) {
            return back()->withErrors([
                'date' => 'This group already has a defense schedule on the selected date.'
            ])->withInput();
        }

        $conflict = $this->checkDoubleBooking($startAt, $endAt, $request->room, $id);
        if ($conflict) {
            return back()->withErrors(['room' => 'This room is already booked for the selected time slot.'])->withInput();
        }

        //check member conflict
        $panelFacultyIds = collect($validated['panel_members'])->pluck('faculty_id')->toArray();
        $panelConflict = $this->checkPanelMemberConflicts($panelFacultyIds, $startAt, $endAt, $id);
        if ($panelConflict) {
            return back()->withErrors(['error' => 'One or more panel members are already scheduled for another defense at the selected time.'])->withInput();
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
                    'role' => $member['role'],
                    'status' => 'pending',
                ]);
            }
            if ($schedule->group->faculty_id) {
                $adviserUser = User::where('faculty_id', $schedule->group->faculty_id)->first();
                if ($adviserUser) {
                    DefensePanel::create([
                        'defense_schedule_id' => $schedule->id,
                        'faculty_id' => $adviserUser->id,
                        'role' => 'adviser',
                        'status' => 'accepted',
                        'responded_at' => now(),
                    ]);
                }
            }
            if ($schedule->group->offering && $schedule->group->offering->faculty_id) {
                $coordinatorUser = User::where('faculty_id', $schedule->group->offering->faculty_id)->first();
                if ($coordinatorUser) {
                    DefensePanel::create([
                        'defense_schedule_id' => $schedule->id,
                        'faculty_id' => $coordinatorUser->id,
                        'role' => 'coordinator',
                        'status' => 'accepted',
                        'responded_at' => now(),
                    ]);
                }
            }
            DB::commit();
            return redirect()->route('coordinator.defense.index')->with('success', 'Defense schedule updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Failed to update defense schedule: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update defense schedule. Please try again or contact support if the problem persists.'])->withInput();
        }
    }
    public function destroy($id)
    {
        $defenseSchedule = DefenseSchedule::with('group')->findOrFail($id);
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        if (!in_array($defenseSchedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only delete defense schedules for groups in your offerings.');
        }

        $groupName = $defenseSchedule->group->name;

        try {
            DB::beginTransaction();

            if ($defenseSchedule->defense_request_id) {
                DefenseRequest::where('id', $defenseSchedule->defense_request_id)->delete();
            }

            DefensePanel::where('defense_schedule_id', $id)->delete();
            $defenseSchedule->delete();
            DB::commit();

            NotificationService::createSimpleNotification(
                'Defense Schedule Removed',
                "The defense schedule for {$groupName} was removed. Please submit a new defense request.",
                'student',
                route('student.defense-requests.create')
            );

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
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        $group = Group::with(['adviser', 'offering'])->find($request->group_id);
        if (!in_array($group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only access faculty for groups in your offerings.');
        }

        $startAt = Carbon::parse($request->date . ' ' . $request->start_time);
        $endAt = Carbon::parse($request->date . ' ' . $request->end_time);
        $conflict = $this->checkDoubleBooking($startAt, $endAt, $request->room);
        $conflictingFacultyIds = $this->getConflictingFacultyIds($startAt, $endAt);

        $availableFaculty = $this->panelChairMemberCandidates($group)
            ->whereNotIn('id', $conflictingFacultyIds)
            ->values();

        $assignmentCounts = DefensePanel::select('faculty_id', DB::raw('COUNT(*) as assignment_count'))
            ->whereHas('defenseSchedule', function ($query) use ($activeTerm) {
                if ($activeTerm) {
                    $query->where('academic_term_id', $activeTerm->id);
                }
            })
            ->groupBy('faculty_id')
            ->pluck('assignment_count', 'faculty_id');

        $availableFaculty = $availableFaculty
            ->map(function ($facultyMember) use ($assignmentCounts) {
                $facultyMember->assignment_count = (int) ($assignmentCounts[$facultyMember->id] ?? 0);
                return $facultyMember;
            })
            ->sortBy([
                ['assignment_count', 'asc'],
                ['name', 'asc'],
            ])
            ->values();

        return response()->json([
            'availableFaculty' => $availableFaculty,
            'conflict' => $conflict,
            'message' => $conflict ? 'This room is already booked for the selected time slot.' : null
        ]);
    }

    private function getConflictingFacultyIds($startAt, $endAt)
    {
        return DefensePanel::whereHas('defenseSchedule', function ($query) use ($startAt, $endAt) {
            $query->where(function ($overlapQuery) use ($startAt, $endAt) {
                $overlapQuery->whereBetween('start_at', [$startAt, $endAt])
                    ->orWhereBetween('end_at', [$startAt, $endAt])
                    ->orWhere(function ($containQuery) use ($startAt, $endAt) {
                        $containQuery->where('start_at', '<=', $startAt)
                            ->where('end_at', '>=', $endAt);
                    });
            });
        })->pluck('faculty_id')->unique()->values()->all();
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

    private function hasGroupScheduleOnDate($groupId, $date, $excludeId = null)
    {
        $query = DefenseSchedule::where('group_id', $groupId)
            ->whereDate('start_at', $date);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function checkPanelMemberConflicts($facultyIds, $startAt, $endAt, $excludeId = null)
    {
        $query = DefenseSchedule::where(function ($q) use ($startAt, $endAt) {
                $q->whereBetween('start_at', [$startAt, $endAt])
                  ->orWhereBetween('end_at', [$startAt, $endAt])
                  ->orWhere(function ($q2) use ($startAt, $endAt) {
                      $q2->where('start_at', '<=', $startAt)
                         ->where('end_at', '>=', $endAt);
                  });
            })
            ->whereHas('defensePanels', function ($q) use ($facultyIds) {
                $q->whereIn('faculty_id', $facultyIds);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function validatePanelComposition(array $panelMembers): ?string
    {
        $roles = collect($panelMembers)
            ->pluck('role')
            ->filter()
            ->values();

        if ($roles->count() !== 2) {
            return 'Panel must contain exactly two selectable slots: one Chair and one Member.';
        }

        $chairCount = $roles->filter(function ($role) {
            return $role === 'chair';
        })->count();
        $memberCount = $roles->filter(function ($role) {
            return $role === 'member';
        })->count();

        if ($chairCount !== 1 || $memberCount !== 1) {
            return 'Panel must contain exactly one Chair and one Member.';
        }

        return null;
    }

    /**
     * Chair/Member dropdown pool only — adviser and subject coordinator are appended in store()/update().
     */
    private function panelChairMemberCandidates(Group $group): Collection
    {
        $group->loadMissing('offering');
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        return User::query()
            ->whereIn('role', ['teacher', 'chairperson', 'panelist', 'adviser', 'coordinator'])
            ->when($activeTerm, fn ($q) => $q->where('semester', $activeTerm->semester))
            ->when($group->faculty_id, fn ($q) => $q->where('faculty_id', '!=', $group->faculty_id))
            ->when($group->offering && $group->offering->faculty_id, fn ($q) => $q->where('faculty_id', '!=', $group->offering->faculty_id))
            ->orderBy('name')
            ->get()
            ->unique('faculty_id')
            ->values();
    }

    private function panelMembersMustNotIncludeAdviserOrCoordinator(Group $group, array $panelMembers): ?string
    {
        $group->loadMissing('offering');
        $blockedIds = [];
        if ($group->faculty_id) {
            $adviserUser = User::where('faculty_id', $group->faculty_id)->first();
            if ($adviserUser) {
                $blockedIds[] = (int) $adviserUser->id;
            }
        }
        if ($group->offering && $group->offering->faculty_id) {
            $coordinatorUser = User::where('faculty_id', $group->offering->faculty_id)->first();
            if ($coordinatorUser) {
                $blockedIds[] = (int) $coordinatorUser->id;
            }
        }
        foreach ($panelMembers as $row) {
            $pickId = (int) ($row['faculty_id'] ?? 0);
            if ($pickId !== 0 && in_array($pickId, $blockedIds, true)) {
                return 'The group adviser and subject coordinator cannot be Chair or Member — they are added to the panel automatically.';
            }
        }

        return null;
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
                $isInvitedPanelRole = in_array($panel->role, ['chair', 'member'], true);
                NotificationService::createSimpleNotification(
                    $isInvitedPanelRole ? 'Defense Panel Invitation' : 'Defense Panel Assignment',
                    $isInvitedPanelRole
                        ? "You have been invited as {$roleLabel} for {$group->name}'s {$stageLabel} defense on {$formattedDate}. Please respond in Panel Invitations."
                        : "You have been assigned as {$roleLabel} for {$group->name}'s {$stageLabel} defense on {$formattedDate}",
                    $panelMember->role,
                    $isInvitedPanelRole
                        ? route('adviser.panel-invitations')
                        : $this->getDashboardRouteForRole($panelMember->role),
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

    
    public function createSchedule(DefenseRequest $defenseRequest)
    {
        if (!$defenseRequest->isPending() && !$defenseRequest->isApproved()) {
            return back()->with('error', 'This defense request cannot be scheduled.');
        }

        if ($this->hasActiveScheduleForGroup($defenseRequest->group_id)) {
            return back()->with('error', 'This group already has an active defense schedule.');
        }
        
        //group and adviser relationships
        $defenseRequest->load(['group.adviser', 'group.members']);
        
        //panelist selection
        $availableFaculty = User::whereIn('role', ['teacher'])
            ->where('id', '!=', $defenseRequest->group->adviser->id)
            ->where('role', '!=', 'chairperson')
            ->where('id', '!=', auth()->user()->id)
            ->get();
        
        return view('coordinator.defense-requests.create-schedule', compact('defenseRequest', 'availableFaculty'));
    }

    public function storeSchedule(Request $request, DefenseRequest $defenseRequest)
    {
        if ($this->hasActiveScheduleForGroup($defenseRequest->group_id)) {
            return back()->withErrors(['error' => 'This group already has an active defense schedule.'])->withInput();
        }

        $request->validate([
            'scheduled_date' => 'required|date|after:today',
            'scheduled_time' => 'required',
            'room' => 'required|string|max:255',
            'coordinator_notes' => 'nullable|string|max:1000',
            'adviser_id' => 'required|exists:users,id',
            'subject_coordinator_id' => 'required|exists:users,id',
            'panelist_1_id' => 'required|exists:users,id|different:panelist_2_id',
            'panelist_2_id' => 'required|exists:users,id|different:panelist_1_id',
        ]);

        $startAt = Carbon::parse($request->scheduled_date . ' ' . $request->scheduled_time);
        $endAt = $startAt->copy()->addHours(2);

        $defenseSchedule = DefenseSchedule::create([
            'defense_request_id' => $defenseRequest->id,
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
        
        // Can reject if pending or approved (but not yet scheduled)
        if ($defenseRequest->isScheduled() || $defenseRequest->isRejected()) {
            return back()->with('error', 'This defense request cannot be rejected.');
        }
        
        $defenseRequest->update([
            'status' => 'rejected',
            'coordinator_notes' => $request->coordinator_notes,
            'responded_at' => now(),
        ]);
        
        return back()->with('success', 'Defense request rejected successfully!');
    }

    public function markAsCompleted(DefenseSchedule $defenseSchedule)
    {
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();

        if (!in_array($defenseSchedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only update defense schedules for groups in your offerings.');
        }

        if (!in_array($defenseSchedule->status, ['scheduled', 'in_progress'])) {
            return back()->with('error', 'Only scheduled or in-progress defenses can be marked as completed.');
        }

        $defenseSchedule->update([
            'status' => 'completed',
        ]);

        return back()->with('success', 'Defense marked as completed successfully.');
    }

    private function createDefensePanel(DefenseSchedule $defenseSchedule, Request $request)
    {

        DefensePanel::create([
            'defense_schedule_id' => $defenseSchedule->id,
            'faculty_id' => $request->adviser_id,
            'role' => 'adviser',
        ]);
        
        DefensePanel::create([
            'defense_schedule_id' => $defenseSchedule->id,
            'faculty_id' => $request->subject_coordinator_id,
            'role' => 'coordinator',
        ]);
        
        DefensePanel::create([
            'defense_schedule_id' => $defenseSchedule->id,
            'faculty_id' => $request->panelist_1_id,
            'role' => 'chair',
        ]);
        
        DefensePanel::create([
            'defense_schedule_id' => $defenseSchedule->id,
            'faculty_id' => $request->panelist_2_id,
            'role' => 'member',
        ]);
    }

    private function sendPanelNotifications(DefenseSchedule $defenseSchedule)
    {
        $panelists = $defenseSchedule->defensePanels()->whereIn('role', ['chair', 'member'])->get();

        foreach ($panelists as $panelist) {
            $panelUser = User::find($panelist->faculty_id);

            if (!$panelUser) {
                continue;
            }

            NotificationService::createSimpleNotification(
                'Defense Panel Assignment',
                'You have been assigned to a defense panel for ' . $defenseSchedule->group->name,
                $panelUser->role,
                $this->getDashboardRouteForRole($panelUser->role),
                $panelUser->id
            );
        }
    }

    /**
     * Returns the correct dashboard route for a given user role.
     * Prevents coordinators/chairpersons from being sent to the adviser dashboard.
     */
    private function getDashboardRouteForRole(string $userRole): string
    {
        return match ($userRole) {
            'coordinator' => route('coordinator.dashboard'),
            'chairperson' => route('chairperson.dashboard'),
            default       => route('adviser.dashboard'),
        };
    }

    private function hasActiveScheduleForGroup($groupId)
    {
        return DefenseSchedule::where('group_id', $groupId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->exists();
    }
}
