# Coordinator and adviser features — route map and code traces

This document maps coordinator- and adviser-facing routes to controllers and views. **Every fenced code block below is coordinator- or adviser-specific** (routing, controllers, or Blade those roles use). Each snippet uses **inline documentation comments** (`//` in PHP, `{{-- --}}` in Blade) on **every line** (including array keys, `compact()` entries, validation rules, and Blade tags). Those comments are **not** in the repository unless they already existed there.

**Sources of truth:** `routes/web.php`, `app/Http/Controllers/`, `resources/views/`.

Tables in §§2–5 are **indexes only** (no executable snippets). All **code** appears in §§6 onward.

---

## 1. How access control works

| Mechanism | Meaning |
|-----------|---------|
| `middleware(['auth', 'role:coordinator|adviser'])` | Authenticated user with Spatie role **`coordinator`** or **`adviser`**. |
| Nested `middleware(['role:coordinator'])` | Further restricts to **`coordinator`** only (e.g. defense rubrics CRUD, second coordinator route group). |
| `prefix('coordinator')->name('coordinator.')` | URLs `/coordinator/...`, names `coordinator.*`. |
| `prefix('adviser')->name('adviser.')` + `auth` | Authenticated users; **resource checks** often happen in controllers (`abort(403)`). |

---

## 2–5. Route and controller indexes

See the prior revision’s tables for full route names (dashboard, classlist, defense, groups, milestones, proposals, notifications, coordinator-only defense-requests and rating sheets, adviser projects, etc.). Run `php artisan route:list --path=coordinator` and `--path=adviser` for an up-to-date list.

---

## 6. Route registration — coordinator (shared + coordinator-only) and adviser

**File:** `routes/web.php`. Each line wires URL → controller for coordinator/adviser flows.

```php
// --- A. Coordinators AND advisers (shared /coordinator area) ---
Route::middleware(['auth', 'role:coordinator|adviser'])->prefix('coordinator')->name('coordinator.')->group(function () { // Spatie: must have coordinator OR adviser role.
    Route::get('/dashboard', [CoordinatorDashboardController::class, 'index'])->name('dashboard'); // coordinator.dashboard
    Route::get('/classlist', [CoordinatorController::class, 'classlist'])->name('classlist.index'); // Enrolled students list
    Route::resource('defense', DefenseScheduleController::class); // RESTful CRUD for defense schedules under /coordinator/defense
    Route::resource('milestones', MilestoneTemplateController::class); // Milestone template CRUD
    Route::get('/groups', [CoordinatorController::class, 'groups'])->name('groups.index'); // Capstone groups for coordinated offerings
    Route::get('/proposals', [CoordinatorProposalController::class, 'index'])->name('proposals.index'); // Proposal submissions review
    Route::get('/notifications', [CoordinatorController::class, 'notifications'])->name('notifications'); // In-app notifications inbox
    Route::get('/calendar', [CalendarController::class, 'coordinatorCalendar'])->name('calendar'); // FullCalendar defense events

    Route::middleware(['role:coordinator'])->group(function () { // Nested: advisers hit 403 here
        Route::resource('defense-rubrics', DefenseRubricController::class)->except(['show']); // Rubric templates; no show route
    });
});

// --- B. Adviser prefix (/adviser): auth only; controllers authorize ---
Route::middleware(['auth'])->prefix('adviser')->name('adviser.')->group(function () { // Must be logged in; role checked per action
    Route::get('/dashboard', [AdviserController::class, 'dashboard'])->name('dashboard'); // adviser.dashboard
    Route::get('/invitations', [AdviserController::class, 'invitations'])->name('invitations'); // Pending adviser invites from students
    Route::post('/invitations/{invitation}/respond', [AdviserController::class, 'respondToInvitation'])->name('invitations.respond'); // Accept/decline
    Route::get('/groups/{group}', [AdviserController::class, 'groupDetails'])->name('groups.details'); // Adviser or accepted panelist
    Route::get('/proposals', [AdviserProposalController::class, 'index'])->name('proposal.index'); // Proposals for advised groups
    Route::get('/calendar', [CalendarController::class, 'adviserCalendar'])->name('calendar'); // Defenses for advised groups only
    Route::get('/rating-sheets/{schedule}', [RatingSheetController::class, 'showAdviserForm'])->name('rating-sheets.show'); // Panel rating form (model binding: DefenseSchedule)
    Route::post('/rating-sheets/{schedule}', [RatingSheetController::class, 'submitAdviserRating'])->name('rating-sheets.submit'); // POST scores
});

// --- C. Coordinator-only second group (same name prefix coordinator.*) ---
Route::middleware(['auth', 'role:coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () { // Coordinator Spatie role required
    Route::get('/defense-requests', [DefenseScheduleController::class, 'defenseRequestsIndex'])->name('defense-requests.index'); // Student-requested defenses queue
    Route::post('/defense-requests/{defenseRequest}/store-schedule', [DefenseScheduleController::class, 'storeSchedule'])->name('defense-requests.store-schedule'); // Create schedule from request
    Route::get('/rating-sheets/{schedule}', [RatingSheetController::class, 'showCoordinatorRatings'])->name('rating-sheets.show'); // Aggregate ratings (not the adviser entry form)
    Route::get('/adviser-invitations', [AdviserController::class, 'invitations'])->name('adviser-invitations'); // Same controller as adviser URL alias
});
```

*(Group A in the real file contains more routes—profile, final-grades, milestone task routes, etc.—same pattern: `coordinator.*` + controller.)*

---

## 7. Trace A — Adviser dashboard (`AdviserController@dashboard`)

**Route:** `adviser.dashboard` → `app/Http/Controllers/AdviserController.php`.

```php
public function dashboard()
{
    $user = Auth::user(); // Logged-in faculty User model.
    $activeTerm = AcademicTerm::where('is_active', true)->first(); // Current term row, or null.

    $pendingInvitations = AdviserInvitation::with(['group', 'group.members']) // Eager-load invitation context.
        ->where('faculty_id', $user->id) // Invitations use users.id for invitee (column name "faculty_id" on invitation).
        ->pending() // Query scope: status pending.
        ->get(); // Collection of invitations for this account.

    $adviserGroups = Group::with([ // Groups where this user is assigned adviser.
        'members', // Group member students
        'adviserInvitations', // Other pending invites for same groups
        'groupMilestones.milestoneTemplate', // Assigned milestone instances + template names
        'groupMilestoneTasks.milestoneTask', // Task rows under milestones
        'academicTerm', // Term label for UI
    ])
        ->where('faculty_id', $user->faculty_id) // Institutional adviser id on groups table.
        ->get()
        ->map(function ($group) {
            $group->progress_percentage = $this->calculateGroupProgress($group); // Computed UI metric.
            $group->submissions_count = $this->getSubmissionsCount($group); // Count of project submissions by members
            $group->milestone_progress = $this->getMilestoneProgress($group); // Array of labels/percent per milestone
            $group->next_milestone = $this->getNextMilestone($group); // Next incomplete milestone meta
            return $group; // Pass enriched model to collection.
        });

    $panelGroups = Group::with(['academicTerm', 'defenseSchedules.defensePanels']) // Panelist perspective.
        ->whereHas('defenseSchedules.defensePanels', function ($query) use ($user) {
            $query->where('faculty_id', $user->id) // Panel row references users.id.
                ->whereIn('role', DefensePanel::INVITED_ROLES) // chair, member, panelist constants
                ->where('status', 'accepted'); // Must have accepted the invite
        })
        ->get();

    $summaryStats = [ // Card numbers on dashboards/adviser.
        'total_groups' => $adviserGroups->count(), // Adviser-owned groups
        'panel_groups' => $panelGroups->count(), // Groups where user is accepted panelist
        'groups_ready_for_defense' => $adviserGroups->filter(fn ($group) => $group->progress_percentage >= 60)->count(), // Threshold for “ready”
        'groups_needing_attention' => $adviserGroups->filter(fn ($group) => $group->progress_percentage < 40)->count(), // Low progress
        'overdue_tasks_total' => $adviserGroups->sum('overdue_tasks'), // Sum of per-group overdue counts
        'pending_invitations' => $pendingInvitations->count(), // Open adviser invitations
    ];

    return view('dashboards.adviser', compact( // Blade: resources/views/dashboards/adviser.blade.php
        'activeTerm', // Current term or null
        'pendingInvitations', // Collection for invitations widget
        'adviserGroups', // Main group cards
        'summaryStats' // KPI strip
    ));
}
```

---

## 8. Trace B — Respond to adviser invitation (`respondToInvitation`)

**Route:** `adviser.invitations.respond` (also aliased under `coordinator.adviser-invitations.respond` for coordinators viewing the same UI).

```php
public function respondToInvitation(Request $request, AdviserInvitation $invitation)
{
    $request->validate([
        'status' => 'required|in:accepted,declined', // Accept or decline only.
        'response_message' => 'nullable|string|max:500', // Optional note to students
    ]);

    $user = Auth::user();
    if ($invitation->faculty_id !== $user->id) { // Must be the invited faculty user.
        abort(403, 'Unauthorized');
    }
    if (! $invitation->isPending()) {
        return back()->with('error', 'This invitation has already been responded to.');
    }

    $invitation->update([
        'status' => $request->status, // accepted | declined
        'response_message' => $request->response_message,
        'responded_at' => now(), // Timestamp response
    ]);

    if ($request->status === 'accepted') {
        $invitation->group->update(['faculty_id' => Auth::user()->faculty_id]); // Assign adviser to group.
        AdviserInvitation::where('group_id', $invitation->group_id)
            ->where('id', '!=', $invitation->id)
            ->where('status', 'pending')
            ->delete(); // Clear competing pending invites for same group.
        $user = Auth::user();
        if (! $user->hasRole('adviser') && $user->hasRole('teacher')) {
            $user->assignRole('adviser'); // Spatie role promotion.
        }
        Notification::create([
            'title' => 'Adviser Invitation Accepted', // Shown to student role inbox
            'description' => 'Your adviser invitation has been accepted by '.Auth::user()->name,
            'role' => 'student', // Route notification toward student UI
        ]);
    } else {
        Notification::create([
            'title' => 'Adviser Invitation Declined',
            'description' => 'Your adviser invitation has been declined by '.Auth::user()->name,
            'role' => 'student',
        ]);
    }

    return back()->with('success', 'Invitation response submitted successfully.'); // Flash on invitations page
}
```

---

## 9. Trace C — Group detail — adviser vs panel (`groupDetails`)

**Route:** `adviser.groups.details`.

```php
public function groupDetails(Group $group)
{
    $user = Auth::user();
    $isAdviserOwner = $group->faculty_id === $user->faculty_id; // Assigned adviser for this group.

    $isAcceptedPanelist = $group->defenseSchedules() // Any schedule for this group
        ->whereHas('defensePanels', function ($query) use ($user) {
            $query->whereIn('role', DefensePanel::INVITED_ROLES) // chair, member, panelist per model
                ->where('status', 'accepted')
                ->whereHas('faculty', function ($facultyQuery) use ($user) {
                    $facultyQuery->where('faculty_id', $user->faculty_id); // Match faculty table code on User's faculty profile.
                });
        })
        ->exists(); // True if this user is an accepted invited panelist

    if (! $isAdviserOwner && ! $isAcceptedPanelist) {
        abort(403, 'Unauthorized'); // Neither adviser nor panel → deny
    }

    $viewerMode = $isAdviserOwner ? 'adviser' : 'panel'; // Blade reads this for layout/permissions.
    $canViewMilestoneDiscussions = $isAdviserOwner; // Panelists cannot open full discussion threads

    $group->load([
        'adviser', // Faculty record for assigned adviser
        'groupMilestones.milestoneTemplate', // Milestones assigned to group
        'groupMilestoneTasks' => function ($query) {
            $query->with(['milestoneTask', 'groupMilestone.milestoneTemplate']) // Task definition + parent milestone
                ->withCount('taskComments'); // Badge count for comments
        },
    ]);

    return view('adviser.group-details', compact('group', 'viewerMode', 'canViewMilestoneDiscussions')); // adviser/group-details.blade.php
}
```

---

## 10. Trace D — Coordinator dashboard (`CoordinatorDashboardController@index`)

**Route:** `coordinator.dashboard` → `app/Http/Controllers/CoordinatorDashboardController.php`.

```php
public function index(Request $request)
{
    $activeTerm = AcademicTerm::where('is_active', true)->first(); // One flagged active term.

    $coordinatorOfferings = auth()->user()->offerings()
        ->when($activeTerm, function ($query) use ($activeTerm) {
            return $query->where('academic_term_id', $activeTerm->id);
        })
        ->pluck('id')
        ->toArray(); // Offering IDs this user coordinates — scopes almost all counts below.

    $studentCount = $activeTerm ? Student::forAcademicTerm($activeTerm)
        ->whereHas('offerings', function ($query) use ($coordinatorOfferings) {
            $query->whereIn('offerings.id', $coordinatorOfferings);
        })->count() : 0; // Students enrolled in coordinated offerings this term

    $groupCount = $activeTerm ? Group::where('academic_term_id', $activeTerm->id)->whereIn('offering_id', $coordinatorOfferings)->count() : 0; // Capstone groups in scope

    $facultyCount = User::withAnyRole(['adviser', 'panelist', 'teacher', 'coordinator', 'chairperson'])
        ->when($activeTerm, function ($query) use ($activeTerm) {
            return $query->where('academic_term_id', $activeTerm->id);
        })->count(); // Faculty users tied to active term (global-ish KPI)

    $submissionCount = $activeTerm ? ProjectSubmission::whereHas('student', function ($query) use ($activeTerm, $coordinatorOfferings) {
        $query->forAcademicTerm($activeTerm)
            ->whereHas('offerings', function ($offeringQuery) use ($coordinatorOfferings) {
                $offeringQuery->whereIn('offerings.id', $coordinatorOfferings);
            });
    })->count() : 0; // All project submissions from scoped students

    $groupsWithAdviser = $activeTerm ? Group::where('academic_term_id', $activeTerm->id)->whereIn('offering_id', $coordinatorOfferings)->whereNotNull('faculty_id')->count() : 0; // Has groups.faculty_id
    $groupsWithoutAdviser = $groupCount - $groupsWithAdviser; // For System Status widget
    $totalGroupMembers = $activeTerm ? Group::where('academic_term_id', $activeTerm->id)->whereIn('offering_id', $coordinatorOfferings)->withCount('members')->get()->sum('members_count') : 0; // Headcount across groups

    $pendingSubmissions = $activeTerm ? ProjectSubmission::where('status', 'pending')
        ->whereHas('student', function ($query) use ($activeTerm, $coordinatorOfferings) {
            $query->forAcademicTerm($activeTerm)
                ->whereHas('offerings', function ($offeringQuery) use ($coordinatorOfferings) {
                    $offeringQuery->whereIn('offerings.id', $coordinatorOfferings);
                });
        })->count() : 0;

    $approvedSubmissions = $activeTerm ? ProjectSubmission::where('status', 'approved')
        ->whereHas('student', function ($query) use ($activeTerm, $coordinatorOfferings) {
            $query->forAcademicTerm($activeTerm)
                ->whereHas('offerings', function ($offeringQuery) use ($coordinatorOfferings) {
                    $offeringQuery->whereIn('offerings.id', $coordinatorOfferings);
                });
        })->count() : 0;

    $rejectedSubmissions = $activeTerm ? ProjectSubmission::where('status', 'rejected')
        ->whereHas('student', function ($query) use ($activeTerm, $coordinatorOfferings) {
            $query->forAcademicTerm($activeTerm)
                ->whereHas('offerings', function ($offeringQuery) use ($coordinatorOfferings) {
                    $offeringQuery->whereIn('offerings.id', $coordinatorOfferings);
                });
        })->count() : 0;

    $milestoneTemplates = MilestoneTemplate::count(); // Global template count (not scoped)
    $activeMilestones = MilestoneTemplate::where('status', 'active')->count();
    $totalTasks = MilestoneTask::count();
    $completedTasks = MilestoneTask::where('is_completed', true)->count();

    $recentStudents = $activeTerm ? Student::forAcademicTerm($activeTerm)
        ->whereHas('offerings', function ($query) use ($coordinatorOfferings) {
            $query->whereIn('offerings.id', $coordinatorOfferings);
        })->latest()->take(5)->get() : collect(); // Sidebar widget

    $recentGroups = $activeTerm ? Group::where('academic_term_id', $activeTerm->id)->whereIn('offering_id', $coordinatorOfferings)->with(['adviser', 'members'])->latest()->take(5)->get() : collect();

    $recentSubmissions = $activeTerm ? ProjectSubmission::whereHas('student', function ($query) use ($activeTerm, $coordinatorOfferings) {
        $query->forAcademicTerm($activeTerm)
            ->whereHas('offerings', function ($offeringQuery) use ($coordinatorOfferings) {
                $offeringQuery->whereIn('offerings.id', $coordinatorOfferings);
            });
    })->latest()->take(5)->get() : collect();

    $notifications = Notification::latest()->take(5)->get(); // Latest global notifications (role-filtered in view if needed)

    $pendingInvitations = $activeTerm ? AdviserInvitation::with(['faculty', 'group'])
        ->where('status', 'pending')
        ->whereHas('group', function ($query) use ($activeTerm, $coordinatorOfferings) {
            $query->where('academic_term_id', $activeTerm->id)
                  ->whereIn('offering_id', $coordinatorOfferings);
        })
        ->latest()
        ->take(5)
        ->get() : collect(); // Invitations for groups in coordinator scope

    $upcomingDeadlines = $this->getUpcomingDeadlines(); // Private helper — builds sample deadline list for widget.

    $user = auth()->user();
    $coordinatedOfferings = collect(); // Default empty
    $isTeacherCoordinator = false;
    if ($user && $user->hasRole('coordinator') && $user->offerings()->exists()) {
        $isTeacherCoordinator = true; // Dashboard shows offering-centric panels
        $coordinatedOfferings = $user->offerings()
            ->with(['academicTerm', 'groups.members'])
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->get();
    }
    $coordinatedOfferings = $coordinatedOfferings ?? collect();
    $isTeacherCoordinator = $isTeacherCoordinator ?? false;

    return view('dashboards.coordinator', compact(
        'activeTerm', // Term record or null
        'studentCount', // KPI
        'groupCount', // KPI
        'facultyCount', // KPI
        'submissionCount', // KPI
        'groupsWithAdviser', // System Status numerator
        'groupsWithoutAdviser', // System Status remainder
        'totalGroupMembers', // Membership KPI
        'pendingSubmissions', // Queue depth
        'approvedSubmissions',
        'rejectedSubmissions',
        'milestoneTemplates', // Template stats
        'activeMilestones',
        'totalTasks',
        'completedTasks',
        'recentStudents', // Recent lists
        'recentGroups',
        'recentSubmissions',
        'notifications',
        'pendingInvitations',
        'upcomingDeadlines',
        'coordinatedOfferings', // Full offering rows when teacher-coordinator
        'isTeacherCoordinator' // Toggle sections in Blade
    ));
}
```

---

## 11. System Status card — Group assignment (coordinator Blade)

**Controller variables:** `$groupsWithAdviser`, `$groupsWithoutAdviser`, `$groupCount` from §10.

```php
$groupsWithAdviser = $activeTerm // When no active term, counts are zero.
    ? Group::where('academic_term_id', $activeTerm->id) // Same term filter as dashboard
        ->whereIn('offering_id', $coordinatorOfferings) // Only groups in coordinated offerings
        ->whereNotNull('faculty_id') // Adviser assigned on group row
        ->count()
    : 0;
$groupsWithoutAdviser = $groupCount - $groupsWithAdviser; // Derived: total groups minus those with adviser
```

```blade
<div class="card-header"> {{-- Bootstrap card header wrapper --}}
    <h5 class="mb-0"> {{-- Heading with no bottom margin --}}
        <i class="fas fa-chart-line me-2"></i>System Status {{-- Icon + title (operational KPIs, not server health) --}}
    </h5>
</div>
<div class="card-body"> {{-- Card content area --}}
    <div class="mb-3"> {{-- Margin-bottom spacing block --}}
        <h6 class="mb-1">Group Assignment Status</h6> {{-- Subheading for this KPI --}}
        <p class="text-muted mb-0">{{ $groupsWithAdviser ?? 0 }} groups have advisers, {{ $groupsWithoutAdviser ?? 0 }} need assignment</p> {{-- Echo counts; ?? 0 if unset --}}
        <div class="progress mt-2" style="height: 8px;"> {{-- Thin Bootstrap progress track --}}
            <div class="progress-bar bg-success" style="width: {{ ($groupCount ?? 0) > 0 ? (($groupsWithAdviser ?? 0) / ($groupCount ?? 1)) * 100 : 0 }}%"></div> {{-- Green fill width = percentage assigned --}}
        </div>
        <small class="text-muted">{{ ($groupCount ?? 0) > 0 ? round((($groupsWithAdviser ?? 0) / ($groupCount ?? 1)) * 100) : 0 }}% assigned</small> {{-- Text duplicate of bar percentage --}}
    </div>
</div>
```

---

## 12. Trace E — Store defense schedule from request (`storeSchedule`) — coordinator only

**Route:** `coordinator.defense-requests.store-schedule` — `Coordinator\DefenseScheduleController`.

**Part 1 — guards and validation** (after routing dispatches `storeSchedule`):

```php
public function storeSchedule(Request $request, DefenseRequest $defenseRequest)
{
    if (! $defenseRequest->isPending() && ! $defenseRequest->isApproved()) {
        abort(403, 'This defense request cannot be scheduled.'); // Must be open or pre-approved.
    }
    if ($this->hasActiveScheduleForGroup($defenseRequest->group_id)) {
        return back()->withErrors(['error' => 'This group already has an active defense schedule.'])->withInput();
    }

    $activeTerm = AcademicTerm::where('is_active', true)->first();
    $coordinatorOfferings = auth()->user()->offerings()
        ->when($activeTerm, fn ($q) => $q->where('academic_term_id', $activeTerm->id))
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all(); // int[] of offering ids this coordinator may manage.

    $defenseRequest->loadMissing('group');
    if (! $defenseRequest->group || ! in_array((int) $defenseRequest->group->offering_id, $coordinatorOfferings, true)) {
        abort(403, 'You can only schedule defense requests for groups in your coordinated offerings.');
    }

    if ($activeTerm && (int) $defenseRequest->group->academic_term_id !== (int) $activeTerm->id) {
        abort(403, 'You can only schedule defense requests for groups in the active academic term.');
    }

    if (is_string($request->scheduled_time) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $request->scheduled_time)) {
        $request->merge(['scheduled_time' => substr($request->scheduled_time, 0, 5)]); // Strip to H:i for validator.
    }

    $slotCount = $this->panelSlotCount(); // Max selectable invited panelists from rubric/config
    $request->validate([
        'scheduled_date' => 'required|date', // Calendar date for defense
        'scheduled_time' => 'required|date_format:H:i', // 24h local time
        'room' => 'required|string|max:255',
        'coordinator_notes' => 'nullable|string|max:1000', // Stored on defense_request
        'milestone_override_reason' => 'nullable|string|max:1000', // Required text if milestone gate fails
        'panel_members' => ['required', 'array', 'min:2', 'max:'.$slotCount], // At least chair+one; cap by slots
        'panel_members.*.faculty_id' => 'required|exists:users,id', // Each picker is a User id
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

    // (In app: build $startAt/$endAt, $requestedPanelMembers, assert panel + room rules, then Part 2.)
```

**Part 2 — persist schedule, panels, notify** (follows panel validation and `$stage` match in source):

```php
    $stage = match ($defenseRequest->defense_type) { // Normalizes request type → defense_schedules.stage column.
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
            'start_at' => $startAt, // From parseDefenseDateAndTime earlier in method.
            'end_at' => $endAt,     // Typically start + 2 hours.
            'room' => $request->room,
            'academic_term_id' => $group->academic_term_id,
            'milestone_gate_overridden' => $gateOverridden,
            'milestone_override_reason' => $gateOverridden ? $request->milestone_override_reason : null,
            'status' => 'scheduled',
        ]);

        foreach ($requestedPanelMembers as $member) {
            DefensePanel::create([
                'defense_schedule_id' => $defenseSchedule->id,
                'faculty_id' => $member['faculty_id'], // users.id
                'role' => $member['role'],             // chair | member | panelist
                'status' => 'pending',                 // Invited faculty must accept.
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
        DB::rollBack(); // Undo partial writes
        \Log::error('Failed to store defense schedule from request: '.$e->getMessage()); // Server log for admins
        return back()->withErrors(['error' => 'Failed to create defense schedule. Please try again.'])->withInput();
    }

    $defenseSchedule->loadMissing(['group.adviser', 'group.members', 'defensePanels']); // For notifier payloads
    $this->sendDefenseScheduleNotifications($defenseSchedule); // Email/in-app to adviser, panel, students, chair.

    return redirect()->route('coordinator.defense.index')->with('success', 'Defense schedule created successfully!');
}
```

*(Variables `$startAt`, `$endAt`, `$requestedPanelMembers` are computed in the same method after Part 1, before this transaction.)*

---

## 12a. Defense panel selection and validation (coordinator)

When a coordinator **creates** a defense (`DefenseScheduleController@store`), **updates** one (`update`), or **schedules from a defense request** (`storeSchedule`), panel picks go through **(1)** Laravel request rules, **(2)** `validatePanelComposition`, and **(3)** `assertInvitedPanelValidForCreateOrUpdate`. The UI dropdowns use the same **eligible faculty pool** as `panelChairMemberCandidates`.

### Config: how many invited slots

```14:14:config/defense.php
    'panel_slots' => max(2, min(10, (int) env('DEFENSE_PANEL_SLOTS', 4))),
```

```28:31:app/Http/Controllers/Coordinator/DefenseScheduleController.php
    private function panelSlotCount(): int
    {
        return max(2, min(10, (int) config('defense.panel_slots', 4)));
    }
```

- **Minimum** invited rows submitted: **2** (Chair + Member).  
- **Maximum** invited rows: **`panelSlotCount()`** (defaults from **`DEFENSE_PANEL_SLOTS`**, clamped **2–10**).  
- The group’s **adviser** and the offering’s **subject coordinator** are **not** chosen in those slots—they are **`DefensePanel`** rows added **automatically** after save.

### Layer 1 — HTTP validation (`panel_members`)

Typical rules (create defense):

```360:362:app/Http/Controllers/Coordinator/DefenseScheduleController.php
            'panel_members' => ['required', 'array', 'min:2', 'max:'.$slotCount],
            'panel_members.*.faculty_id' => 'required|exists:users,id',
            'panel_members.*.role' => 'required|in:chair,member,panelist',
```

- Each row is a **`users.id`** (`faculty_id` field name in the form means “faculty user id”).  
- **`role`** must be **`chair`**, **`member`**, or **`panelist`**.

### Layer 2 — Composition and role order (`validatePanelComposition`)

```972:994:app/Http/Controllers/Coordinator/DefenseScheduleController.php
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
```

- **Order is fixed:** index `0` → **`chair`**, index `1` → **`member`**, remaining indices → **`panelist`**.  
- Exactly **one** chair and **one** member across the array.

### Layer 3 — `assertInvitedPanelValidForCreateOrUpdate`

```1001:1039:app/Http/Controllers/Coordinator/DefenseScheduleController.php
    private function assertInvitedPanelValidForCreateOrUpdate(
        array $requestedPanelMembers,
        Group $group,
        Carbon $startAt,
        Carbon $endAt,
        ?int $excludeScheduleId,
        array $declinedFacultyIds
    ): ?string {
        $compositionError = $this->validatePanelComposition($requestedPanelMembers);
        // ...
        $pickedFacultyIds = collect($requestedPanelMembers)->pluck('faculty_id')->filter()->values()->all();
        if (count(array_unique($pickedFacultyIds)) !== count($pickedFacultyIds)) {
            return 'Each panel slot must be a different faculty member.';
        }

        $blockedSelectionError = $this->panelMembersMustNotIncludeAdviserOrCoordinator($group, $requestedPanelMembers);
        // ...

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
```

| Check | Purpose |
|--------|---------|
| **Unique IDs** | No duplicate **`users.id`** across invited slots. |
| **`panelMembersMustNotIncludeAdviserOrCoordinator`** | Invited slots cannot be the **group adviser** or **subject coordinator** user (they are auto-added). |
| **`panelChairMemberCandidates`** | Every pick must belong to the **same eligible pool** used when building the create form. |
| **`checkPanelMemberConflicts`** | No invited faculty **double-booked** at overlapping defense times (same schedule overlap logic as room conflicts). |
| **Declined IDs** (updates) | Cannot re-select a faculty member who **declined** on this schedule. |

### Eligible faculty for dropdowns (`panelChairMemberCandidates`)

```1042:1055:app/Http/Controllers/Coordinator/DefenseScheduleController.php
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
```

- Pool = faculty-like Spatie roles, optionally scoped to **active term**, excluding users whose **`faculty_id`** equals the **group adviser** or **offering coordinator** (so they do not appear as invited chair/member/panelist picks).

### Explicit block if adviser/coordinator posted into invited slots

```1105:1128:app/Http/Controllers/Coordinator/DefenseScheduleController.php
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
```

**Implementation file:** `app/Http/Controllers/Coordinator/DefenseScheduleController.php` (`store`, `update`, `storeSchedule` all funnel invited picks through the same helpers).

---

## 13. Trace F — Rating form shared by adviser and coordinator (`showAdviserForm`)

**Routes:** `adviser.rating-sheets.show`, `coordinator.rating-sheets.rate.show` → same controller method.

**File:** `app/Http/Controllers/RatingSheetController.php`.

```php
public function showAdviserForm(DefenseSchedule $schedule)
{
    $user = Auth::user(); // Current faculty account (adviser, panelist, or subject coordinator on panel).
    if (! $this->isRatingStage($schedule)) { // Only 60% / 100% stages use numeric rubric ratings.
        return $this->redirectBlockedRatingAccess($schedule, 'Panel rating is available only for 60% and 100% defenses.');
    }
    if ($redirect = $this->redirectToPreferredScheduleIfNeeded($schedule, $user)) {
        return $redirect; // Canonical schedule row when duplicates exist.
    }
    $schedule->loadMissing('group.members'); // Need member list for per-student rubric rows.
    if (! $this->isRatingWindowOpen($schedule)) {
        return $this->redirectBlockedRatingAccess($schedule, $this->ratingWindowBlockReason($schedule)); // e.g. before/after defense window
    }

    $isAssignedPanel = $schedule->defensePanels() // defense_panels for this schedule
        ->whereIn('role', array_merge(['coordinator'], DefensePanel::INVITED_ROLES)) // Coordinator slot + invited roles
        ->where('status', 'accepted')
        ->whereHas('faculty', function ($query) use ($user) {
            $query->where('faculty_id', $user->faculty_id); // Match logged-in user’s faculty profile id
        })
        ->exists(); // Must be an accepted panel member (or coordinator panel row)

    if (! $isAssignedPanel) {
        abort(403, 'You are not assigned to this defense panel.');
    }

    $schedule->load('evaluationSummary'); // Coordinator finalization summary when present
    $isFinalized = $schedule->status === 'completed' && (bool) $schedule->evaluationSummary; // Lock form when outcome recorded

    $panelFacultyUserId = $this->resolvePanelFacultyUserId($schedule, $user); // users.id used as rating_sheets.faculty_id for this rater
    $existingRating = RatingSheet::where('defense_schedule_id', $schedule->id)
        ->where('faculty_id', $panelFacultyUserId)
        ->first(); // Draft or submitted sheet for this user + schedule

    if (
        $existingRating && // Row exists
        (float) $existingRating->total_score <= 0 && // No total recorded
        empty($existingRating->recommendation) && // No recommendation text
        empty($existingRating->remarks) // No remarks
    ) {
        $allZeroCriteria = collect($existingRating->criteria ?? [])->every(function ($criterion) {
            return ((float) ($criterion['score'] ?? 0)) <= 0; // Every rubric line still zero
        });

        if ($allZeroCriteria) {
            $existingRating = null; // Ignore empty placeholder so Blade shows blank form (not a real draft).
        }
    }

    $rubricTemplate = $this->defenseRubricService->getActiveTemplateForStage($schedule->stage); // Active DefenseRubricTemplate for proposal|60|100
    $defaultCriteria = $this->getDefaultCriteria($rubricTemplate?->criteria); // Normalized criteria array from JSON column
    $groupCriteria = collect($defaultCriteria)->filter(fn ($criterion) => ($criterion['scope'] ?? 'group') !== 'individual')->values()->all(); // Whole-group rows
    $individualCriterion = collect($defaultCriteria)->first(fn ($criterion) => ($criterion['scope'] ?? 'group') === 'individual')
        ?? ['name' => 'Individual Contribution', 'max_points' => 100, 'scope' => 'individual']; // Fallback single row
    $groupMembers = $schedule->group?->members ?? collect(); // Students for per-member scoring column

    return view('adviser.rating-sheets.form', compact(
        'schedule', // DefenseSchedule model (date, room, stage)
        'existingRating', // RatingSheet|null — prefill when draft exists
        'defaultCriteria', // Full merged criteria list for loop
        'rubricTemplate', // Template meta (title, version)
        'isFinalized', // bool — hide submit if finalized
        'groupCriteria', // Filtered group-scope criteria only
        'individualCriterion', // One row for per-student dimension
        'groupMembers' // Collection of Student/members for names
    ));
}
```

---

## 14. Calendars — coordinator vs adviser

### 14a. `CalendarController@coordinatorCalendar` — `coordinator.calendar`

```php
public function coordinatorCalendar()
{
    $user = Auth::user(); // Logged-in coordinator/adviser faculty user
    $activeTerm = AcademicTerm::where('is_active', true)->first(); // Single active term flag

    $coordinatorOfferings = $user->offerings()
        ->when($activeTerm, function ($query) use ($activeTerm) {
            return $query->where('academic_term_id', $activeTerm->id);
        })
        ->pluck('id')
        ->toArray(); // Same scoping idea as coordinator dashboard: only “your” offerings.

    $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'group.offering.teacher', 'panelists.faculty'])
        ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
        ->whereHas('group', function ($query) use ($coordinatorOfferings) {
            $query->whereIn('offering_id', $coordinatorOfferings);
        })
        ->when($activeTerm, function ($query) use ($activeTerm) {
            return $query->where('academic_term_id', $activeTerm->id);
        })
        ->orderBy('start_at')
        ->get();

    $myGroupIds = Group::whereHas('offering', function ($query) use ($user) {
        $query->where('faculty_id', $user->faculty_id); // Offerings where you are the assigned teacher/coordinator.
    })->pluck('id')->toArray(); // Used to highlight “your” groups’ defenses in green.

    $calendarEvents = $defenses->map(function ($defense) use ($myGroupIds) {
        $startDate = \Carbon\Carbon::parse($defense->start_at); // Start instant for calendar widget.
        $endDate = \Carbon\Carbon::parse($defense->end_at);       // End instant (typically +2h in DB).
        $invitedPanels = $defense->panelists->whereIn('role', DefensePanel::INVITED_ROLES); // Chair/member/panelist only.
        $hasDeclinedInvite = $invitedPanels->where('status', 'declined')->isNotEmpty();       // Any decline triggers replacement flow.
        $allInvitedAccepted = $invitedPanels->isNotEmpty()
            && $invitedPanels->every(fn (DefensePanel $p) => $p->status === 'accepted');      // All invites OK.

        $panelState = $hasDeclinedInvite
            ? 'replacement_needed'
            : ($allInvitedAccepted ? 'confirmed' : 'awaiting_confirmation');                    // High-level panel readiness.

        $displayStatus = match ($defense->status) {                                             // Label for UI/modal.
            'completed' => 'Completed', // Defense finished
            'in_progress' => 'In progress', // Happening now
            default => match ($panelState) {
                'replacement_needed' => 'Replacement needed', // Panelist declined
                'awaiting_confirmation' => 'Awaiting panel confirmation', // Invites outstanding
                default => 'Scheduled',
            },
        };

        $displayStatusVariant = match ($defense->status) {                                       // Bootstrap-ish badge class.
            'completed' => 'success',
            'in_progress' => 'warning',
            default => match ($panelState) {
                'replacement_needed' => 'danger',
                'awaiting_confirmation' => 'warning text-dark',
                default => 'primary',
            },
        };

        $eventClass = match ($defense->status) {                                                 // CSS hook on calendar event.
            'completed' => 'approved',
            'in_progress' => 'scheduled',
            default => match ($panelState) {
                'replacement_needed' => 'declined',
                'awaiting_confirmation' => 'pending',
                default => 'scheduled',
            },
        };

        return [
            'id' => $defense->id, // Event id for FullCalendar / modal
            'title' => $defense->group->name ?? 'Defense', // Calendar cell text
            'start' => $startDate->toISOString(), // ISO8601 start
            'end' => $endDate->toISOString(), // ISO8601 end
            'className' => $eventClass, // CSS class from match above
            'backgroundColor' => in_array($defense->group_id, $myGroupIds) ? '#28a745' : '#6c757d', // Green if offering is “yours”.
            'borderColor' => in_array($defense->group_id, $myGroupIds) ? '#28a745' : '#6c757d', // Match fill color
            'textColor' => '#ffffff', // Legibility on colored bars
            'extendedProps' => [
                'group' => $defense->group->name ?? 'N/A',
                'defenseType' => $defense->stage_label ?? 'Defense',
                'adviser' => $defense->group->adviser->name ?? 'N/A',
                'coordinator' => $defense->group->offering->teacher->name ?? 'N/A', // Subject coordinator / teacher name
                'status' => $defense->status, // scheduled | in_progress | completed
                'panel_state' => $panelState, // Machine-readable panel readiness
                'display_status' => $displayStatus, // Human label for modal
                'display_status_variant' => $displayStatusVariant, // Badge color hint
                'local_date' => $startDate->format('m/d/Y'),
                'room' => $defense->room ?? 'TBD',
                'time' => $startDate->format('g:i A'),
                'students' => $defense->group->members->pluck('name')->join(', '), // Roster string
                'is_mine' => in_array($defense->group_id, $myGroupIds), // Boolean for UI emphasis
                'panelists' => self::panelistsPayloadForCalendar($defense->panelists), // Ordered chair/member list for modal
            ],
        ];
    })->toArray();
    if (empty($calendarEvents)) {
        $calendarEvents = []; // Frontend expects [] not null.
    }

    return view('calendar.coordinator', compact('defenses', 'calendarEvents', 'myGroupIds'));
}
```

### 14b. `CalendarController@adviserCalendar` — `adviser.calendar`

```php
public function adviserCalendar()
{
    $user = Auth::user(); // Faculty user.
    $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'panelists'])
        ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
        ->whereHas('group', function ($query) use ($user) {
            $query->where('faculty_id', $user->faculty_id); // Only defenses for groups where user is assigned adviser.
        })
        ->orderBy('start_at')
        ->get();

    $calendarEvents = $defenses->map(function ($defense) {
        $startDate = \Carbon\Carbon::parse($defense->start_at); // Calendar start instant.
        $endDate = \Carbon\Carbon::parse($defense->end_at);       // Calendar end instant.

        return [
            'id' => $defense->id,
            'title' => $defense->group->name ?? 'Defense',
            'start' => $startDate->toISOString(),
            'end' => $endDate->toISOString(),
            'backgroundColor' => $defense->status === 'scheduled' ? '#ffc107' : '#28a745', // Yellow until done-ish.
            'borderColor' => $defense->status === 'scheduled' ? '#ffc107' : '#28a745',
            'textColor' => $defense->status === 'scheduled' ? '#000000' : '#ffffff', // Dark text on yellow bar
            'extendedProps' => [
                'group' => $defense->group->name ?? 'N/A',
                'defenseType' => $defense->stage_label ?? 'Defense',
                'groupId' => $defense->group_id, // Deep link / context
                'status' => $defense->status,
                'room' => $defense->room ?? 'TBD',
                'time' => $startDate->format('g:i A'),
                'students' => $defense->group->members->pluck('name')->join(', '),
            ],
        ];
    })->toArray();

    if (empty($calendarEvents)) {
        $calendarEvents = []; // Ensure JS receives array not null.
    }

    return view('calendar.adviser', compact('defenses', 'calendarEvents'));
}
```

---

## 15. Proposal index — coordinator vs adviser

### 15a. `CoordinatorProposalController@index` — `coordinator.proposals.index`

```php
public function index()
{
    $user = Auth::user(); // Coordinator faculty user
    $activeTerm = AcademicTerm::where('is_active', true)->first(); // Active term filter for listings

    $coordinatedOfferings = Offering::where('faculty_id', $user->faculty_id) // Offerings this coordinator owns as teacher row.
        ->when($activeTerm, function ($query) use ($activeTerm) {
            return $query->where('academic_term_id', $activeTerm->id);
        })
        ->with(['academicTerm', 'groups.members'])
        ->get();

    $proposalsByOffering = []; // Keyed by offering id for the Blade index.

    foreach ($coordinatedOfferings as $offering) {
        $groups = $offering->groups; // Capstone groups under this offering
        $allProposals = collect(); // Fresh accumulator per offering
        foreach ($groups as $group) {
            $groupProposals = ProjectSubmission::whereIn('student_id', $group->members->pluck('student_id'))
                ->where('type', 'proposal')
                ->get(); // Proposal-type submissions only
            $allProposals = $allProposals->merge($groupProposals); // Accumulate all proposal-type submissions in offering.
        }

        if ($allProposals->isNotEmpty()) {
            $proposalsByOffering[$offering->id] = [
                'offering' => $offering, // Offering model + nested relations
                'proposals' => $allProposals->sortByDesc('submitted_at'), // Newest first
                'pending_count' => $allProposals->where('status', 'pending')->count(),
                'approved_count' => $allProposals->where('status', 'approved')->count(),
                'rejected_count' => $allProposals->where('status', 'rejected')->count(),
                'total_groups' => $groups->count(), // How many groups in offering (even if some have no proposal yet)
            ];
        }
    }

    return view('coordinator.proposals.index', compact('proposalsByOffering')); // coordinator/proposals/index.blade.php
}
```

### 15b. `AdviserProposalController@index` — `adviser.proposal.index`

```php
public function index(Request $request)
{
    $user = Auth::user(); // Adviser faculty user
    $selectedGroupId = $request->query('group'); // Optional filter from query string ?group=id.
    $groups = Group::where('faculty_id', $user->faculty_id) // Adviser’s groups only.
        ->with(['members', 'members.submissions' => function ($query) {
            $query->where('type', 'proposal')->latest();
        }]);

    if ($selectedGroupId) {
        $groups->where('id', (int) $selectedGroupId);
    }

    $groups = $groups->get();
    $proposalsByGroup = [];
    $allProposals = collect();

    foreach ($groups as $group) {
        $proposals = $group->members->flatMap->submissions // All submissions for all members
            ->where('type', 'proposal')
            ->sortByDesc('submitted_at');

        $allProposals = $allProposals->merge($proposals); // Dashboard-wide totals

        if ($proposals->isNotEmpty()) {
            $proposalsByGroup[$group->id] = [
                'group' => $group,
                'proposals' => $proposals,
                'pending_count' => $proposals->where('status', 'pending')->count(),
                'approved_count' => $proposals->where('status', 'approved')->count(),
                'rejected_count' => $proposals->where('status', 'rejected')->count(),
            ];
        }
    }

    $stats = [
        'total_proposals' => $allProposals->count(), // Across all advised groups
        'pending_review' => $allProposals->where('status', 'pending')->count(),
        'approved' => $allProposals->where('status', 'approved')->count(),
        'rejected' => $allProposals->where('status', 'rejected')->count(),
    ];

    return view('adviser.proposal.index', compact('proposalsByGroup', 'stats', 'selectedGroupId')); // adviser/proposal/index.blade.php
}
```

---

## 16. Primary Blade entry points

| Role | Views |
|------|--------|
| Coordinator | `resources/views/dashboards/coordinator.blade.php`, `coordinator/**/*.blade.php`, `calendar/coordinator.blade.php` |
| Adviser | `resources/views/dashboards/adviser.blade.php`, `adviser/**/*.blade.php`, `calendar/adviser.blade.php`, `adviser/rating-sheets/form.blade.php` |

---

## 17. Keeping this document accurate

Re-sync snippets after refactors: line numbers and logic change. Use `php artisan route:list` for coordinator/adviser paths.

---

*Documentation overlays (`//`, `{{-- --}}`) inside snippets are for readers only unless the source file already contains them.*
