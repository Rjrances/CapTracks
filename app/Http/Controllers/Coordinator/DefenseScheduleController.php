<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\DefensePanel;
use App\Models\DefenseRequest;
use App\Models\DefenseSchedule;
use App\Models\Group;
use App\Models\Offering;
use App\Models\User;
use App\Services\DefenseEvaluationService;
use App\Services\DefenseMilestoneGateService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DefenseScheduleController extends Controller
{
    public function __construct(
        private readonly DefenseMilestoneGateService $defenseMilestoneGateService,
        private readonly DefenseEvaluationService $defenseEvaluationService
    ) {}

    private function panelSlotCount(): int
    {
        return max(2, min(10, (int) config('defense.panel_slots', 4)));
    }

    /** @return list<string> */
    private function invitedRolesOrdered(int $slotCount): array
    {
        $roles = ['chair', 'member'];
        for ($i = 2; $i < $slotCount; $i++) {
            $roles[] = 'panelist';
        }

        return $roles;
    }

    /**
     * Ordered slots for edit form: chair, member, then panelists (by panel id).
     *
     * @return list<array{role: string, selected_id: string}>
     */
    private function invitedPanelsForEditSlots(DefenseSchedule $schedule, int $slotCount): array
    {
        $panels = $schedule->defensePanels;
        $chair = $panels->first(fn ($p) => $p->role === 'chair' && $p->status !== 'declined');
        $member = $panels->first(fn ($p) => $p->role === 'member' && $p->status !== 'declined');
        $panelists = $panels
            ->filter(fn ($p) => $p->role === 'panelist' && $p->status !== 'declined')
            ->sortBy('id')
            ->values();

        $slots = [
            ['role' => 'chair', 'selected_id' => $chair ? (string) $chair->faculty_id : ''],
            ['role' => 'member', 'selected_id' => $member ? (string) $member->faculty_id : ''],
        ];
        foreach ($panelists as $p) {
            if (count($slots) >= $slotCount) {
                break;
            }
            $slots[] = ['role' => 'panelist', 'selected_id' => (string) $p->faculty_id];
        }

        return $slots;
    }

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

        $adviserFilterChoices = $this->scopedAdviserUsersForFilters($coordinatorOfferings, $activeTerm);

        $requestFilters = $request->only(['defense_type', 'search', 'adviser_faculty_id']);
        $requestFilters = array_merge([
            'defense_type' => '',
            'search' => '',
            'adviser_faculty_id' => '',
        ], $requestFilters);

        $allowedAdviserFacultyIds = $adviserFilterChoices->pluck('faculty_id')->unique()->filter()->values()->all();
        $selectedAdviserFacultyId = $requestFilters['adviser_faculty_id'] !== ''
            ? (int) $requestFilters['adviser_faculty_id']
            : null;
        if ($selectedAdviserFacultyId !== null && ! in_array($selectedAdviserFacultyId, $allowedAdviserFacultyIds, true)) {
            $selectedAdviserFacultyId = null;
            $requestFilters['adviser_faculty_id'] = '';
        }

        $defenseRequestsQuery = DefenseRequest::with(['group.members', 'group.adviser', 'group.defenseSchedules'])
            ->whereHas('group', function ($q) use ($coordinatorOfferings, $activeTerm) {
                $q->whereIn('offering_id', $coordinatorOfferings);
                if ($activeTerm) {
                    $q->where('academic_term_id', $activeTerm->id);
                }
            })
            ->orderBy('created_at', 'desc');

        if (! empty($requestFilters['defense_type'])) {
            $defenseRequestsQuery->where('defense_type', $requestFilters['defense_type']);
        }
        if (! empty($requestFilters['search'])) {
            $defenseRequestsQuery->whereHas('group', function ($q) use ($requestFilters) {
                $q->where('name', 'like', '%'.$requestFilters['search'].'%');
            });
        }

        if ($selectedAdviserFacultyId !== null) {
            $defenseRequestsQuery->whereHas('group', function ($q) use ($selectedAdviserFacultyId) {
                $q->where('faculty_id', $selectedAdviserFacultyId);
            });
        }

        $defenseRequests = $defenseRequestsQuery->get()->filter(function ($requestItem) {
            return $requestItem->group->defenseSchedules
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->isEmpty();
        })->values();

        $scheduleQuery = DefenseSchedule::with(['group.adviser', 'academicTerm', 'defensePanels.faculty', 'defenseRequest'])
            ->whereHas('group', function ($q) use ($coordinatorOfferings) {
                $q->whereIn('offering_id', $coordinatorOfferings);
            })
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->orderBy('start_at', 'asc');

        if (! empty($requestFilters['defense_type'])) {
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

        if (! empty($requestFilters['search'])) {
            $search = $requestFilters['search'];
            $scheduleQuery->whereHas('group', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%');
            });
        }

        if ($selectedAdviserFacultyId !== null) {
            $scheduleQuery->whereHas('group', function ($q) use ($selectedAdviserFacultyId) {
                $q->where('faculty_id', $selectedAdviserFacultyId);
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
            'activeTerm', 'requestFilters', 'filterOptions', 'adviserFilterChoices'
        ));
    }

    /**
     * Advisers who head at least one group in the coordinator's offerings (optionally limited to the active term).
     *
     * @param  array<int, int>  $coordinatorOfferingIds
     * @return Collection<int, User>
     */
    private function scopedAdviserUsersForFilters(array $coordinatorOfferingIds, ?AcademicTerm $activeTerm): Collection
    {
        if ($coordinatorOfferingIds === []) {
            return collect();
        }

        $groupQuery = Group::query()
            ->whereIn('offering_id', $coordinatorOfferingIds)
            ->whereNotNull('faculty_id');

        if ($activeTerm) {
            $groupQuery->where('academic_term_id', $activeTerm->id);
        }

        $facultyIds = $groupQuery->distinct()->pluck('faculty_id')->filter()->values();
        if ($facultyIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('faculty_id', $facultyIds)
            ->orderBy('name')
            ->get();
    }

    public function create(Request $request)
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

        $panelFacultyByGroupId = $this->panelFacultyJsonByGroupIds($groups);

        $panelSlotCount = $this->panelSlotCount();

        $prefillGroupId = null;
        $prefillStage = null;
        $prefillGroupUnavailable = false;

        $fromRequestId = $request->query('from_request');
        if ($fromRequestId !== null && $fromRequestId !== '' && ctype_digit((string) $fromRequestId)) {
            $approvedRequest = DefenseRequest::query()
                ->with('group')
                ->where('id', (int) $fromRequestId)
                ->where('status', 'approved')
                ->first();

            if ($approvedRequest && $approvedRequest->group) {
                $group = $approvedRequest->group;
                $inScope = in_array((int) $group->offering_id, array_map('intval', $coordinatorOfferings), true)
                    && (! $activeTerm || (int) $group->academic_term_id === (int) $activeTerm->id);

                if ($inScope) {
                    $prefillStage = match ($approvedRequest->defense_type) {
                        'proposal' => 'proposal',
                        '60_percent' => '60',
                        '100_percent' => '100',
                        default => null,
                    };
                    if ($groups->contains(fn (Group $g) => (int) $g->id === (int) $group->id)) {
                        $prefillGroupId = (int) $group->id;
                    } else {
                        $prefillGroupUnavailable = true;
                    }
                }
            }
        }

        $optionalPanelistCapacity = max(0, $this->panelSlotCount() - 2);

        return view('coordinator.defense.create', compact(
            'groups',
            'activeTerm',
            'panelFacultyByGroupId',
            'groupAvailability',
            'panelSlotCount',
            'optionalPanelistCapacity',
            'prefillGroupId',
            'prefillStage',
            'prefillGroupUnavailable'
        ));
    }

    public function store(Request $request)
    {
        $slotCount = $this->panelSlotCount();
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'stage' => 'required|in:proposal,60,100',
            'room' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'milestone_override_reason' => 'nullable|string|max:1000',
            'panel_members' => ['required', 'array', 'min:2', 'max:'.$slotCount],
            'panel_members.*.faculty_id' => 'required|exists:users,id',
            'panel_members.*.role' => 'required|in:chair,member,panelist',
        ]);
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        if (! $activeTerm) {
            return back()->withErrors(['error' => 'No active academic term found. Please contact the chairperson to set an active term.'])->withInput();
        }
        $validated['academic_term_id'] = $activeTerm->id;
        $coordinatorOfferings = auth()->user()->offerings()
            ->where('academic_term_id', $activeTerm->id)
            ->pluck('id')
            ->toArray();
        $group = Group::with('offering')->findOrFail($validated['group_id']);
        if (! in_array($group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only create defense schedules for groups in your offerings.');
        }
        if ((int) $group->academic_term_id !== (int) $activeTerm->id) {
            abort(403, 'You can only create defense schedules for groups in the active term.');
        }
        $gate = $this->defenseMilestoneGateService->evaluate($group, $validated['stage']);
        $gateOverridden = false;
        if (! $gate['eligible']) {
            if (blank($validated['milestone_override_reason'] ?? null)) {
                return back()->withErrors([
                    'milestone_override_reason' => $gate['message'].' Add override reason to schedule anyway.',
                ])->withInput();
            }
            $gateOverridden = true;
        }

        [$startAt, $endAt] = $this->parseDefenseWindow($request->date, $request->start_time, $request->end_time);

        if ($msg = $this->defenseEndMustBeAfterStart($startAt, $endAt)) {
            return back()->withErrors(['end_time' => $msg])->withInput();
        }

        if ($msg = $this->defenseStartMustBeInFuture($startAt)) {
            return back()->withErrors(['start_time' => $msg])->withInput();
        }

        if ($this->hasGroupScheduleOnDate($validated['group_id'], $request->date)) {
            return back()->withErrors([
                'date' => 'This group already has a defense schedule on the selected date.',
            ])->withInput();
        }

        $conflict = $this->checkDoubleBooking($startAt, $endAt, $request->room);
        if ($conflict) {
            return back()->withErrors(['room' => 'This room is already booked for the selected time slot.'])->withInput();
        }

        $requestedPanelMembers = collect($validated['panel_members'] ?? [])
            ->map(function ($row) {
                return [
                    'faculty_id' => (int) ($row['faculty_id'] ?? 0),
                    'role' => (string) ($row['role'] ?? ''),
                ];
            })
            ->values()
            ->all();

        $panelError = $this->assertInvitedPanelValidForCreateOrUpdate(
            $requestedPanelMembers,
            $group,
            $startAt,
            $endAt,
            null,
            []
        );
        if ($panelError) {
            return back()->withErrors(['panel_members' => $panelError])->withInput();
        }

        try {
            DB::beginTransaction();
            $requestedDefenseType = match ($validated['stage']) {
                'proposal' => 'proposal',
                '60' => '60_percent',
                '100' => '100_percent',
                default => null,
            };
            $linkedDefenseRequest = DefenseRequest::query()
                ->where('group_id', $validated['group_id'])
                ->whereIn('status', ['pending', 'approved'])
                ->when($requestedDefenseType, function ($query) use ($requestedDefenseType) {
                    $query->where('defense_type', $requestedDefenseType);
                })
                ->orderByDesc('requested_at')
                ->first();

            $schedule = DefenseSchedule::create([
                'defense_request_id' => $linkedDefenseRequest?->id,
                'group_id' => $validated['group_id'],
                'stage' => $validated['stage'],
                'academic_term_id' => $validated['academic_term_id'],
                'start_at' => $startAt,
                'end_at' => $endAt,
                'room' => $validated['room'],
                'status' => 'scheduled',
                'milestone_gate_overridden' => $gateOverridden,
                'milestone_override_reason' => $gateOverridden ? $validated['milestone_override_reason'] : null,
            ]);
            foreach ($requestedPanelMembers as $member) {
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
            if ($linkedDefenseRequest) {
                $linkedDefenseRequest->update([
                    'status' => 'scheduled',
                    'responded_at' => now(),
                ]);
            }
            DB::commit();
            $this->sendDefenseScheduleNotifications($schedule);

            return redirect()->route('coordinator.defense.index')->with('success', 'Defense schedule created successfully. All parties have been notified.');
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Failed to create defense schedule: '.$e->getMessage());

            return back()->withErrors(['error' => 'Failed to create defense schedule. Please try again or contact support if the problem persists.'])->withInput();
        }
    }

    public function show($id)
    {
        $defenseSchedule = DefenseSchedule::with(['group.members', 'group.adviser', 'group.offering', 'academicTerm', 'defensePanels.faculty'])->findOrFail($id);
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        if (! in_array($defenseSchedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only view defense schedules for groups in your offerings.');
        }

        return view('coordinator.defense.show', compact('defenseSchedule'));
    }

    public function edit($id)
    {
        $defenseSchedule = DefenseSchedule::with(['group', 'academicTerm', 'defensePanels.faculty'])->findOrFail($id);
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        if (! in_array($defenseSchedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only edit defense schedules for groups in your offerings.');
        }
        $groups = Group::with(['members', 'adviser', 'offering'])
            ->whereIn('offering_id', $coordinatorOfferings)
            ->get();
        $panelSlotCount = $this->panelSlotCount();
        $invitedEditSlots = $this->invitedPanelsForEditSlots($defenseSchedule, $panelSlotCount);

        $panelFacultyByGroupId = $this->panelFacultyJsonByGroupIds($groups);
        if (isset($panelFacultyByGroupId[$defenseSchedule->group_id])) {
            $panelFacultyByGroupId[$defenseSchedule->group_id] = $this->mergeLegacyUsersIntoPanelFacultyJson(
                $panelFacultyByGroupId[$defenseSchedule->group_id],
                $defenseSchedule->defensePanels->whereIn('role', DefensePanel::INVITED_ROLES)->pluck('faculty_id')
            );
        }
        $currentPanelFacultyOptions = $panelFacultyByGroupId[$defenseSchedule->group_id] ?? [];

        $academicTerms = AcademicTerm::orderBy('school_year', 'desc')->orderBy('semester', 'desc')->get();

        return view('coordinator.defense.edit', compact(
            'defenseSchedule',
            'groups',
            'academicTerms',
            'panelSlotCount',
            'invitedEditSlots',
            'panelFacultyByGroupId',
            'currentPanelFacultyOptions'
        ));
    }

    public function update(Request $request, $id)
    {
        $slotCount = $this->panelSlotCount();
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'stage' => 'required|in:proposal,60,100',
            'academic_term_id' => 'required|exists:academic_terms,id',
            'room' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'milestone_override_reason' => 'nullable|string|max:1000',
            'panel_members' => ['required', 'array', 'min:2', 'max:'.$slotCount],
            'panel_members.*.faculty_id' => 'required|exists:users,id',
            'panel_members.*.role' => 'required|in:chair,member,panelist',
        ]);
        $schedule = DefenseSchedule::findOrFail($id);
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        $group = Group::with('offering')->findOrFail($validated['group_id']);
        if (! in_array($group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only edit defense schedules for groups in your offerings.');
        }
        $gate = $this->defenseMilestoneGateService->evaluate($group, $validated['stage']);
        $gateOverridden = false;
        if (! $gate['eligible']) {
            if (blank($validated['milestone_override_reason'] ?? null)) {
                return back()->withErrors([
                    'milestone_override_reason' => $gate['message'].' Add override reason to schedule anyway.',
                ])->withInput();
            }
            $gateOverridden = true;
        }

        [$startAt, $endAt] = $this->parseDefenseWindow($request->date, $request->start_time, $request->end_time);

        if ($msg = $this->defenseEndMustBeAfterStart($startAt, $endAt)) {
            return back()->withErrors(['end_time' => $msg])->withInput();
        }

        if ($msg = $this->defenseStartMustBeInFuture($startAt)) {
            return back()->withErrors(['start_time' => $msg])->withInput();
        }

        if ($this->hasGroupScheduleOnDate($validated['group_id'], $request->date, $id)) {
            return back()->withErrors([
                'date' => 'This group already has a defense schedule on the selected date.',
            ])->withInput();
        }

        $conflict = $this->checkDoubleBooking($startAt, $endAt, $request->room, $id);
        if ($conflict) {
            return back()->withErrors(['room' => 'This room is already booked for the selected time slot.'])->withInput();
        }

        $requestedPanelMembers = collect($validated['panel_members'] ?? [])
            ->map(function ($row) {
                return [
                    'faculty_id' => (int) ($row['faculty_id'] ?? 0),
                    'role' => (string) ($row['role'] ?? ''),
                ];
            })
            ->values()
            ->all();

        $schedule->loadMissing('defensePanels');
        $declinedPanelistIds = $schedule->defensePanels
            ->whereIn('role', DefensePanel::INVITED_ROLES)
            ->where('status', 'declined')
            ->pluck('faculty_id')
            ->unique()
            ->values()
            ->all();

        $panelError = $this->assertInvitedPanelValidForCreateOrUpdate(
            $requestedPanelMembers,
            $group,
            $startAt,
            $endAt,
            $id,
            $declinedPanelistIds
        );
        if ($panelError) {
            return back()->withErrors(['panel_members' => $panelError])->withInput();
        }

        $existingInvitedPanelsOrdered = $schedule->defensePanels
            ->filter(fn ($p) => in_array($p->role, DefensePanel::INVITED_ROLES, true))
            ->sortBy(fn ($p) => [$p->role === 'chair' ? 0 : ($p->role === 'member' ? 1 : 2), $p->id])
            ->values();

        $manualPanelMembers = collect($requestedPanelMembers)->values();

        try {
            DB::beginTransaction();
            $schedule->update([
                'group_id' => $validated['group_id'],
                'stage' => $validated['stage'],
                'academic_term_id' => $validated['academic_term_id'],
                'start_at' => $startAt,
                'end_at' => $endAt,
                'room' => $validated['room'],
                'milestone_gate_overridden' => $gateOverridden,
                'milestone_override_reason' => $gateOverridden ? $validated['milestone_override_reason'] : null,
            ]);
            DefensePanel::where('defense_schedule_id', $schedule->id)->delete();
            foreach ($manualPanelMembers as $index => $member) {
                $existingPanelForSlot = $existingInvitedPanelsOrdered->get($index);
                $preserveAccepted = $existingPanelForSlot
                    && (int) $existingPanelForSlot->faculty_id === (int) $member['faculty_id']
                    && $existingPanelForSlot->status === 'accepted'
                    && ($existingPanelForSlot->role === $member['role']);

                DefensePanel::create([
                    'defense_schedule_id' => $schedule->id,
                    'faculty_id' => $member['faculty_id'],
                    'role' => $member['role'],
                    'status' => $preserveAccepted ? 'accepted' : 'pending',
                    'responded_at' => $preserveAccepted ? ($existingPanelForSlot->responded_at ?? now()) : null,
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
            \Log::error('Failed to update defense schedule: '.$e->getMessage());

            return back()->withErrors(['error' => 'Failed to update defense schedule. Please try again or contact support if the problem persists.'])->withInput();
        }
    }

    public function destroy($id)
    {
        $defenseSchedule = DefenseSchedule::with('group')->findOrFail($id);
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        if (! in_array($defenseSchedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only delete defense schedules for groups in your offerings.');
        }

        $groupName = $defenseSchedule->group->name;

        try {
            DB::beginTransaction();

            if ($defenseSchedule->defense_request_id) {
                DefenseRequest::where('id', $defenseSchedule->defense_request_id)->delete();
            } else {

                DefenseRequest::where('group_id', $defenseSchedule->group_id)
                    ->where('status', 'approved')
                    ->whereDoesntHave('defenseSchedule')
                    ->orderByDesc('requested_at')
                    ->limit(1)
                    ->update([
                        'status' => 'pending',
                        'responded_at' => null,
                    ]);
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
            'end_time' => 'nullable|required_without:duration_hours|date_format:H:i',
            'duration_hours' => 'nullable|required_without:end_time|integer|min:1|max:12',
            'room' => 'required|string',
            'group_id' => 'required|exists:groups,id',
        ]);
        $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        $group = Group::with(['adviser', 'offering'])->find($request->group_id);
        if (! in_array($group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only access faculty for groups in your offerings.');
        }

        $tz = config('app.timezone');
        $startAt = Carbon::parse($request->date.' '.$request->start_time, $tz);
        if ($request->filled('duration_hours')) {
            $endAt = $startAt->copy()->addHours((int) $request->duration_hours);
        } else {
            $endAt = Carbon::parse($request->date.' '.$request->end_time, $tz);
        }

        if ($msg = $this->defenseEndMustBeAfterStart($startAt, $endAt)) {
            return response()->json([
                'message' => $msg,
                'availableFaculty' => [],
                'autoAssignedFacultyIds' => [],
                'conflict' => false,
                'invalid_time_window' => true,
            ], 422);
        }

        if ($msg = $this->defenseStartMustBeInFuture($startAt)) {
            return response()->json([
                'message' => $msg,
                'availableFaculty' => [],
                'autoAssignedFacultyIds' => [],
                'conflict' => false,
                'past_start' => true,
            ], 422);
        }
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

        $slotCount = $this->panelSlotCount();
        $autoAssignedFacultyIds = $availableFaculty
            ->take($slotCount)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values();

        return response()->json([
            'availableFaculty' => $availableFaculty,
            'autoAssignedFacultyIds' => $autoAssignedFacultyIds,
            'conflict' => $conflict,
            'message' => $conflict ? 'This room is already booked for the selected time slot.' : null,
        ]);
    }

    private function resolveAutoPanelMembers(
        Group $group,
        Carbon $startAt,
        Carbon $endAt,
        ?int $excludeScheduleId = null,
        array $excludedFacultyIds = []
    ): Collection {
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        $conflictingFacultyIds = $this->getConflictingFacultyIds($startAt, $endAt, $excludeScheduleId);

        $availableFaculty = $this->panelChairMemberCandidates($group)
            ->whereNotIn('id', $conflictingFacultyIds)
            ->whereNotIn('id', $excludedFacultyIds)
            ->values();

        $assignmentCounts = DefensePanel::select('faculty_id', DB::raw('COUNT(*) as assignment_count'))
            ->whereHas('defenseSchedule', function ($query) use ($activeTerm, $excludeScheduleId) {
                if ($activeTerm) {
                    $query->where('academic_term_id', $activeTerm->id);
                }
                if ($excludeScheduleId) {
                    $query->where('id', '!=', $excludeScheduleId);
                }
            })
            ->groupBy('faculty_id')
            ->pluck('assignment_count', 'faculty_id');

        $slotCount = $this->panelSlotCount();

        $selectedFaculty = $availableFaculty
            ->map(function ($facultyMember) use ($assignmentCounts) {
                $facultyMember->assignment_count = (int) ($assignmentCounts[$facultyMember->id] ?? 0);

                return $facultyMember;
            })
            ->sortBy([
                ['assignment_count', 'asc'],
                ['name', 'asc'],
            ])
            ->take($slotCount)
            ->values();

        if ($selectedFaculty->count() < $slotCount) {
            return collect();
        }

        $roles = $this->invitedRolesOrdered($slotCount);

        return collect(range(0, $slotCount - 1))->map(fn (int $i) => [
            'faculty_id' => $selectedFaculty[$i]->id,
            'role' => $roles[$i],
        ]);
    }

    private function getConflictingFacultyIds($startAt, $endAt, ?int $excludeScheduleId = null)
    {
        return DefensePanel::whereHas('defenseSchedule', function ($query) use ($startAt, $endAt, $excludeScheduleId) {
            $query->where(function ($overlapQuery) use ($startAt, $endAt) {
                $overlapQuery->whereBetween('start_at', [$startAt, $endAt])
                    ->orWhereBetween('end_at', [$startAt, $endAt])
                    ->orWhere(function ($containQuery) use ($startAt, $endAt) {
                        $containQuery->where('start_at', '<=', $startAt)
                            ->where('end_at', '>=', $endAt);
                    });
            });
            if ($excludeScheduleId) {
                $query->where('id', '!=', $excludeScheduleId);
            }
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
        $slotCount = $this->panelSlotCount();
        $n = count($panelMembers);
        if ($n < 2 || $n > $slotCount) {
            return "Panel must include Chair and Member, with at most {$slotCount} invited faculty (Chair + Member + optional panelists).";
        }

        $expected = $this->invitedRolesOrdered($n);
        foreach ($panelMembers as $index => $row) {
            if (($row['role'] ?? '') !== ($expected[$index] ?? null)) {
                return 'Panel roles must be Chair, then Member, then optional panelists in order.';
            }
        }

        $roles = collect($panelMembers)->pluck('role')->filter()->values();
        $chairCount = $roles->filter(fn ($role) => $role === 'chair')->count();
        $memberCount = $roles->filter(fn ($role) => $role === 'member')->count();
        if ($chairCount !== 1 || $memberCount !== 1) {
            return 'Panel must contain exactly one Chair and one Member.';
        }

        return null;
    }

    /**
     * @param  list<array{faculty_id: int, role: string}>  $requestedPanelMembers
     * @param  list<int>  $declinedFacultyIds
     */
    private function assertInvitedPanelValidForCreateOrUpdate(
        array $requestedPanelMembers,
        Group $group,
        Carbon $startAt,
        Carbon $endAt,
        ?int $excludeScheduleId,
        array $declinedFacultyIds
    ): ?string {
        $compositionError = $this->validatePanelComposition($requestedPanelMembers);
        if ($compositionError) {
            return $compositionError;
        }

        $pickedFacultyIds = collect($requestedPanelMembers)->pluck('faculty_id')->filter()->values()->all();
        if (count(array_unique($pickedFacultyIds)) !== count($pickedFacultyIds)) {
            return 'Each panel slot must be a different faculty member.';
        }

        $blockedSelectionError = $this->panelMembersMustNotIncludeAdviserOrCoordinator($group, $requestedPanelMembers);
        if ($blockedSelectionError) {
            return $blockedSelectionError;
        }

        $candidateUserIds = $this->panelChairMemberCandidates($group)->pluck('id')->map(fn ($id) => (int) $id)->all();
        foreach ($pickedFacultyIds as $fid) {
            if (! in_array((int) $fid, $candidateUserIds, true)) {
                return 'Each invited panel member must be eligible for the selected group (same faculty pool as create defense).';
            }
        }

        if ($this->checkPanelMemberConflicts($pickedFacultyIds, $startAt, $endAt, $excludeScheduleId)) {
            return 'One or more selected panel members are already assigned to another defense at this time.';
        }

        if ($declinedFacultyIds !== [] && count(array_intersect($pickedFacultyIds, $declinedFacultyIds)) > 0) {
            return 'Replacement required: previously declined panelist cannot be re-selected for this update.';
        }

        return null;
    }

    private function panelChairMemberCandidates(Group $group): Collection
    {
        $group->loadMissing('offering');
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        return User::query()
            ->whereIn('role', ['teacher', 'chairperson', 'panelist', 'adviser', 'coordinator'])
            ->when($activeTerm, fn ($q) => $q->where('academic_term_id', $activeTerm->id))
            ->when($group->faculty_id, fn ($q) => $q->where('faculty_id', '!=', $group->faculty_id))
            ->when($group->offering && $group->offering->faculty_id, fn ($q) => $q->where('faculty_id', '!=', $group->offering->faculty_id))
            ->orderBy('name')
            ->get()
            ->unique('faculty_id')
            ->values();
    }

    /**
     * Faculty eligible for invited panel slots per group (same rules everywhere).
     *
     * @return array<int, list<array{id: int, name: string, faculty_id: mixed}>>
     */
    private function panelFacultyJsonByGroupIds(Collection $groups): array
    {
        return $groups->mapWithKeys(function (Group $group) {
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
        })->all();
    }

    /**
     * Keep current panel members visible if they are no longer in the candidate pool (legacy rows).
     *
     * @param  list<array{id: int, name: string, faculty_id: mixed}>  $rows
     * @return list<array{id: int, name: string, faculty_id: mixed}>
     */
    private function mergeLegacyUsersIntoPanelFacultyJson(array $rows, Collection $extraUserIds): array
    {
        $byId = collect($rows)->keyBy('id');
        foreach ($extraUserIds as $uid) {
            $uid = (int) $uid;
            if ($uid && ! $byId->has($uid)) {
                $u = User::find($uid);
                if ($u) {
                    $byId->put($uid, [
                        'id' => $u->id,
                        'name' => $u->name,
                        'faculty_id' => $u->faculty_id,
                    ]);
                }
            }
        }

        return $byId->sortBy('name')->values()->all();
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
                return 'The group adviser and subject coordinator cannot fill invited panel slots — they are added to the panel automatically.';
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
                $isInvitedPanelRole = in_array($panel->role, DefensePanel::INVITED_ROLES, true);
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
        if (! $defenseRequest->isPending() && ! $defenseRequest->isApproved()) {
            return back()->with('error', 'This defense request cannot be scheduled.');
        }

        if ($this->hasActiveScheduleForGroup($defenseRequest->group_id)) {
            return back()->with('error', 'This group already has an active defense schedule.');
        }

        $defenseRequest->loadMissing(['group.offering', 'group.adviser', 'group.members']);

        if (! $defenseRequest->group) {
            return back()->with('error', 'This defense request has no group.');
        }

        $panelSlotCount = $this->panelSlotCount();
        $optionalPanelistCapacity = max(0, $panelSlotCount - 2);
        $panelFacultyByGroupId = $this->panelFacultyJsonByGroupIds(collect([$defenseRequest->group]));

        return view('coordinator.defense-requests.create-schedule', compact('defenseRequest', 'panelSlotCount', 'optionalPanelistCapacity', 'panelFacultyByGroupId'));
    }

    public function storeSchedule(Request $request, DefenseRequest $defenseRequest)
    {
        if (! $defenseRequest->isPending() && ! $defenseRequest->isApproved()) {
            abort(403, 'This defense request cannot be scheduled.');
        }

        if ($this->hasActiveScheduleForGroup($defenseRequest->group_id)) {
            return back()->withErrors(['error' => 'This group already has an active defense schedule.'])->withInput();
        }

        $activeTerm = AcademicTerm::where('is_active', true)->first();
        $coordinatorOfferings = auth()->user()->offerings()
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $defenseRequest->loadMissing('group');
        if (! $defenseRequest->group || ! in_array((int) $defenseRequest->group->offering_id, $coordinatorOfferings, true)) {
            abort(403, 'You can only schedule defense requests for groups in your coordinated offerings.');
        }

        if ($activeTerm && (int) $defenseRequest->group->academic_term_id !== (int) $activeTerm->id) {
            abort(403, 'You can only schedule defense requests for groups in the active academic term.');
        }

        if (is_string($request->scheduled_time) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $request->scheduled_time)) {
            $request->merge(['scheduled_time' => substr($request->scheduled_time, 0, 5)]);
        }

        $slotCount = $this->panelSlotCount();
        $request->validate([
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'room' => 'required|string|max:255',
            'coordinator_notes' => 'nullable|string|max:1000',
            'milestone_override_reason' => 'nullable|string|max:1000',
            'panel_members' => ['required', 'array', 'min:2', 'max:'.$slotCount],
            'panel_members.*.faculty_id' => 'required|exists:users,id',
            'panel_members.*.role' => 'required|in:chair,member,panelist',
        ]);

        $defenseRequest->loadMissing(['group.offering', 'group.groupMilestones.milestoneTemplate', 'group.adviser']);
        $group = $defenseRequest->group;
        if (! $group) {
            return back()->withErrors(['error' => 'This defense request has no group.'])->withInput();
        }

        $gate = $this->defenseMilestoneGateService->evaluate($group, $defenseRequest->defense_type);
        $gateOverridden = false;
        if (! $gate['eligible']) {
            if (blank($request->milestone_override_reason)) {
                return back()->withErrors([
                    'milestone_override_reason' => $gate['message'].' Add override reason to schedule anyway.',
                ])->withInput();
            }
            $gateOverridden = true;
        }

        $startAt = $this->parseDefenseDateAndTime($request->scheduled_date, $request->scheduled_time);
        $endAt = $startAt->copy()->addHours(2);

        if ($msg = $this->defenseStartMustBeInFuture($startAt)) {
            return back()->withErrors(['scheduled_time' => $msg])->withInput();
        }

        if ($this->hasGroupScheduleOnDate($defenseRequest->group_id, $request->scheduled_date)) {
            return back()->withErrors([
                'scheduled_date' => 'This group already has a defense schedule on the selected date.',
            ])->withInput();
        }

        if ($this->checkDoubleBooking($startAt, $endAt, $request->room)) {
            return back()->withErrors(['room' => 'This room is already booked for the selected time slot.'])->withInput();
        }

        $requestedPanelMembers = collect($request->input('panel_members', []))
            ->map(function ($row) {
                return [
                    'faculty_id' => (int) ($row['faculty_id'] ?? 0),
                    'role' => (string) ($row['role'] ?? ''),
                ];
            })
            ->values()
            ->all();

        $panelError = $this->assertInvitedPanelValidForCreateOrUpdate(
            $requestedPanelMembers,
            $group,
            $startAt,
            $endAt,
            null,
            []
        );
        if ($panelError) {
            return back()->withErrors(['panel_members' => $panelError])->withInput();
        }

        $stage = match ($defenseRequest->defense_type) {
            'proposal' => 'proposal',
            '60_percent' => '60',
            '100_percent' => '100',
            default => 'proposal',
        };

        try {
            DB::beginTransaction();
            $defenseSchedule = DefenseSchedule::create([
                'defense_request_id' => $defenseRequest->id,
                'group_id' => $defenseRequest->group_id,
                'stage' => $stage,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'room' => $request->room,
                'academic_term_id' => $group->academic_term_id,
                'milestone_gate_overridden' => $gateOverridden,
                'milestone_override_reason' => $gateOverridden ? $request->milestone_override_reason : null,
                'status' => 'scheduled',
            ]);

            foreach ($requestedPanelMembers as $member) {
                DefensePanel::create([
                    'defense_schedule_id' => $defenseSchedule->id,
                    'faculty_id' => $member['faculty_id'],
                    'role' => $member['role'],
                    'status' => 'pending',
                ]);
            }
            if ($group->faculty_id) {
                $adviserUser = User::where('faculty_id', $group->faculty_id)->first();
                if ($adviserUser) {
                    DefensePanel::create([
                        'defense_schedule_id' => $defenseSchedule->id,
                        'faculty_id' => $adviserUser->id,
                        'role' => 'adviser',
                        'status' => 'accepted',
                        'responded_at' => now(),
                    ]);
                }
            }
            if ($group->offering && $group->offering->faculty_id) {
                $coordinatorUser = User::where('faculty_id', $group->offering->faculty_id)->first();
                if ($coordinatorUser) {
                    DefensePanel::create([
                        'defense_schedule_id' => $defenseSchedule->id,
                        'faculty_id' => $coordinatorUser->id,
                        'role' => 'coordinator',
                        'status' => 'accepted',
                        'responded_at' => now(),
                    ]);
                }
            }

            $defenseRequest->update([
                'status' => 'scheduled',
                'responded_at' => now(),
                'coordinator_notes' => $request->coordinator_notes,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to store defense schedule from request: '.$e->getMessage());

            return back()->withErrors(['error' => 'Failed to create defense schedule. Please try again.'])->withInput();
        }

        $defenseSchedule->loadMissing(['group.adviser', 'group.members', 'defensePanels']);
        $this->sendDefenseScheduleNotifications($defenseSchedule);

        return redirect()->route('coordinator.defense.index')->with('success', 'Defense schedule created successfully!');
    }

    public function approve(DefenseRequest $defenseRequest)
    {
        if (! $defenseRequest->isPending()) {
            return back()->with('error', 'This defense request cannot be approved.');
        }

        $activeTerm = AcademicTerm::where('is_active', true)->first();
        $coordinatorOfferings = auth()->user()->offerings()
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $defenseRequest->loadMissing('group');
        if (! $defenseRequest->group || ! in_array((int) $defenseRequest->group->offering_id, $coordinatorOfferings, true)) {
            abort(403, 'You can only approve defense requests for groups in your coordinated offerings.');
        }

        if ($activeTerm && (int) $defenseRequest->group->academic_term_id !== (int) $activeTerm->id) {
            abort(403, 'You can only approve defense requests for groups in the active academic term.');
        }

        $defenseRequest->update([
            'status' => 'approved',
            'responded_at' => now(),
        ]);

        return redirect()
            ->route('coordinator.defense.create', ['from_request' => $defenseRequest->id])
            ->with('success', 'Defense request approved. Group and defense stage are pre-filled—set date, time, and room to complete the schedule.');
    }

    public function reject(Request $request, DefenseRequest $defenseRequest)
    {
        $request->validate([
            'coordinator_notes' => 'required|string|max:1000',
        ]);

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

        if (! in_array($defenseSchedule->group->offering_id, $coordinatorOfferings)) {
            abort(403, 'You can only update defense schedules for groups in your offerings.');
        }

        if (! in_array($defenseSchedule->status, ['scheduled', 'in_progress'])) {
            return back()->with('error', 'Only scheduled or in-progress defenses can be marked as completed.');
        }

        $defenseSchedule->loadMissing(['group.members', 'defensePanels', 'evaluationSummary']);
        $readiness = $this->defenseEvaluationService->readiness($defenseSchedule);
        if (! $readiness['is_ready']) {
            return back()->with('error', 'Cannot complete yet. Required panelists must submit all rating sheets first.');
        }

        if (! $defenseSchedule->evaluationSummary) {
            return back()->with('error', 'Cannot complete directly. Finalize the result from Rating Sheets first.');
        }

        $defenseSchedule->update([
            'status' => 'completed',
        ]);

        return back()->with('success', 'Defense marked as completed successfully.');
    }

    private function getDashboardRouteForRole(string $userRole): string
    {
        return match ($userRole) {
            'coordinator' => route('coordinator.dashboard'),
            'chairperson' => route('chairperson.dashboard'),
            default => route('adviser.dashboard'),
        };
    }

    private function hasActiveScheduleForGroup($groupId)
    {
        return DefenseSchedule::where('group_id', $groupId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->exists();
    }

    /**
     * Application timezone (see config/app.php, default Asia/Manila) defines "now".
     */
    private function defenseStartMustBeInFuture(Carbon $startAt): ?string
    {
        if (! $startAt->gt(now())) {
            return 'The defense must be scheduled for a future date and time (Philippines time).';
        }

        return null;
    }

    private function defenseEndMustBeAfterStart(Carbon $startAt, Carbon $endAt): ?string
    {
        if ($endAt->lte($startAt)) {
            return 'End time must be after start time.';
        }

        return null;
    }

    /**
     * Interpret date + clock times in the application timezone (same instant as server validation).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function parseDefenseWindow(string $date, string $startTime, string $endTime): array
    {
        $tz = config('app.timezone');

        return [
            Carbon::parse($date.' '.$startTime, $tz),
            Carbon::parse($date.' '.$endTime, $tz),
        ];
    }

    private function parseDefenseDateAndTime(string $date, string $time): Carbon
    {
        return Carbon::parse($date.' '.$time, config('app.timezone'));
    }
}
