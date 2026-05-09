# Coordinator Features (Final + Line-by-Line)

Coordinator manages group operations, milestones, proposal review, and defense scheduling for offerings they coordinate.

## Main Route Space

- Prefix: `/coordinator`
- Middleware: `auth` + `role:coordinator|adviser`
- Coordinator-only submodule exists for defense rubrics.

## Sidebar Modules (Current UI)

- Dashboard
- Groups
- Class List
- Faculty Matrix
- Proposal Review
- Defense Management
- Milestone Templates
- Calendar
- Activity Log

---

## 1) Groups and Class List

- Groups CRUD: `/coordinator/groups/*`
- Class List: `/coordinator/classlist`
- Import students (coordinator scope): `/coordinator/classlist/import`

Scope rule:
- records are filtered to offerings tied to the logged-in coordinator and (when present) active term.

---

## 2) Milestone Assignment (Code + Line-by-Line)

Source: `app/Http/Controllers/MilestoneTemplateController.php`

```php
public function assignToGroup(Request $request)
{
    // Validate incoming payload: group and template must exist, due date is optional but future-only.
    $request->validate([
        'group_id'            => 'required|exists:groups,id',
        'milestone_template_id' => 'required|exists:milestone_templates,id',
        'due_date'            => 'nullable|date|after:today',
    ]);

    // Load selected template together with its tasks (for seeding group task rows).
    $template = MilestoneTemplate::with('tasks')->findOrFail($request->milestone_template_id);
    // Load target group record.
    $group    = Group::findOrFail($request->group_id);

    // Check duplicate: same milestone template already assigned to the same group.
    $alreadyAssigned = GroupMilestone::where('group_id', $group->id)
        ->where('milestone_template_id', $template->id)
        ->exists();

    // Stop early and return a user-facing validation error if duplicate assignment exists.
    if ($alreadyAssigned) {
        return redirect()->route('coordinator.milestones.index')
            ->withErrors(['assign' => "\"{$template->name}\" is already assigned to {$group->name}."]);
    }

    // Delegate business rules (sequence, prerequisite proposal approval, etc.) to service layer.
    $assignmentError = MilestoneAssignmentService::validateAssignment($group, $template);
    // If service returns a block reason, stop and return it to the UI.
    if ($assignmentError !== null) {
        return redirect()->route('coordinator.milestones.index')
            ->withErrors(['assign' => $assignmentError]);
    }

    // Create the group-level milestone record with initial progress/status.
    $groupMilestone = GroupMilestone::create([
        'group_id'              => $group->id,
        'milestone_template_id' => $template->id,
        'title'                 => $template->name,
        'description'           => $template->description,
        'target_date'           => $request->due_date,
        'due_date'              => $request->due_date,
        'progress_percentage'   => 0,
        'status'                => 'not_started',
    ]);

    // Seed one GroupMilestoneTask row per template task for Kanban tracking.
    foreach ($template->tasks as $task) {
        GroupMilestoneTask::create([
            'group_milestone_id' => $groupMilestone->id,
            'milestone_task_id'  => $task->id,
            'status'             => 'pending',
            'is_completed'       => false,
        ]);
    }
}
```

---

## 3) Milestone Rule Engine (Code + Line-by-Line)

Source: `app/Services/MilestoneAssignmentService.php`

```php
public static function validateAssignment(Group $group, MilestoneTemplate $template): ?string
{
    $meta = self::assignmentMeta($group);
    $hasApprovedProposal = self::groupHasApprovedProposal($group);

    if (! $meta['can_assign']) {
        return $meta['block_message'];
    }

    if ($meta['sequencing_enabled']) {
        if (! self::hasActiveSequenceStep(self::PROPOSAL_SEQUENCE_ORDER) && ! $hasApprovedProposal) {
            return 'Cannot assign 60%/100% milestones yet. Either approve at least one proposal for this group, or add an active Proposal milestone with step 1.';
        }

        if ($template->sequence_order === null) {
            return 'This template has no sequence order. Set step order (1 = Proposal, 2 = 60%, 3 = 100%) on the template edit page.';
        }

        if ((int) $template->sequence_order > self::PROPOSAL_SEQUENCE_ORDER && ! $hasApprovedProposal) {
            return 'Cannot assign 60%/100% milestones until the group has at least one approved proposal.';
        }
    }

    return null;
}
```

Line-by-line explanation:
1-4. Builds metadata and proposal-approval state.
6-8. If UI/service says blocked, immediately return human-readable reason.
10. Applies sequencing checks only when sequencing is active.
11-13. If no step-1 template and no approved proposal, block later steps.
15-17. Blocks templates with missing `sequence_order`.
19-21. Hard business rule: step > 1 requires approved proposal.
24. Returns `null` when assignment is valid.

---

## 4) Faculty Matrix Logic (Code + Line-by-Line)

Source: `app/Http/Controllers/CoordinatorController.php`

```php
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
}
```

Line-by-line explanation:
1-3. Gets current user and active term.
5-11. Fetches only offerings coordinated by that faculty, term-scoped when active term exists.
13. Extracts offering IDs.
15-22. Loads groups with adviser, latest schedules, and panel faculty relations.
24-25. Restricts groups to coordinator-owned offerings only.

---

## 5) Defense Scheduling Gate + Override (Code + Line-by-Line)

Source: `app/Http/Controllers/Coordinator/DefenseScheduleController.php`

```php
$defenseRequest->loadMissing('group.groupMilestones.milestoneTemplate');
$gate = $this->defenseMilestoneGateService->evaluate($defenseRequest->group, $defenseRequest->defense_type);
$gateOverridden = false;

if (!$gate['eligible']) {
    if (blank($request->milestone_override_reason)) {
        return back()->withErrors([
            'milestone_override_reason' => $gate['message'] . ' Add override reason to schedule anyway.',
        ])->withInput();
    }
    $gateOverridden = true;
}

$defenseSchedule = DefenseSchedule::create([
    'milestone_gate_overridden' => $gateOverridden,
    'milestone_override_reason' => $gateOverridden ? $request->milestone_override_reason : null,
]);
```

Line-by-line explanation:
1. Ensures milestone/template relations are loaded before gate check.
2. Evaluates eligibility for requested defense stage.
3. Initializes override flag.
5-11. If not eligible, require override reason; otherwise block scheduling.
12. Marks request as overridden when reason is provided.
14-17. Persists override metadata into defense schedule for auditability.

---

## 6) Proposal Review

Routes:
- `/coordinator/proposals`
- Preview, compare, update status, comments, and bulk update actions.

Purpose:
- coordinator handles operational pipeline decisions separate from adviser mentoring flow.

---

## 7) Defense Rubrics and Rating Oversight

- Rubrics: `/coordinator/defense-rubrics` (coordinator-only middleware)
- Coordinator can view panel ratings, print, finalize, and reopen rating windows.

---

## 8) Notifications, Calendar, Activity Log

- Notifications: list/mark/delete operations under coordinator namespace
- Calendar: `/coordinator/calendar`
- Activity log: `/coordinator/activity-log`

---

## 9) Final Coordinator Workflow

1. Verify coordinated offerings and groups
2. Maintain milestone templates
3. Assign milestones with service-level prerequisites
4. Review proposals and comments
5. Process defense requests and schedules
6. Finalize/reopen rating sheets as needed

---

## 10) Connected Code and Functions (Coordinator)

This section lists the exact methods connected to coordinator logic so you can quickly trace behavior in code review or defense Q&A.

### A. Main coordinator controller

File: `app/Http/Controllers/CoordinatorController.php`

- `index()` - coordinator dashboard metrics.
- `classlist()` - class list view/filter scoped to coordinator offerings.
- `importStudentsForm()` / `importStudents()` - class list import flow.
- `groups()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()` - group CRUD.
- `assignAdviser()` - adviser selection entry for a group.
- `groupMilestones()` - per-group assignment/progress page.
- `notifications()`, `markNotificationAsRead()`, `markAllNotificationsAsRead()`, `markMultipleAsRead()`, `deleteNotification()`, `deleteMultiple()` - notification operations.
- `activityLog()` - coordinator activity timeline.
- `facultyMatrix()` - group-to-faculty matrix generation.

### B. Milestone templates and assignment

File: `app/Http/Controllers/MilestoneTemplateController.php`

- `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` - template lifecycle.
- `updateStatus()` - quick status update endpoint.
- `storeTask()`, `updateTask()`, `destroyTask()` - template task CRUD.
- `assignToGroup()` - creates group milestone + seeded tasks.
- `removeAssignmentFromGroup()` - unassigns template from group.

File: `app/Services/MilestoneAssignmentService.php`

- `assignmentMeta()` - computes UI-state (can assign, reason, next allowed template).
- `validateAssignment()` - final server-side rule enforcement before assignment.
- `resolveNextSequencedTemplate()` - computes next step in sequencing.
- `groupHasApprovedProposal()` - checks approved proposal prerequisite.
- `hasActiveSequenceStep()` - verifies required sequence step exists.

### C. Defense scheduling and requests

File: `app/Http/Controllers/Coordinator/DefenseScheduleController.php`

- `defenseRequestsIndex()` - pending request queue.
- `index()` - defense management list.
- `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()` - manual defense schedule lifecycle.
- `getAvailableFaculty()` - panel candidate filtering.
- `createSchedule()`, `storeSchedule()` - request-to-schedule conversion flow.
- `approve()`, `reject()` - request decision endpoints.
- `markAsCompleted()` - closes schedule and marks complete.

File: `app/Services/DefenseMilestoneGateService.php`

- `evaluate()` - stage eligibility decision.
- `evaluateProposalStage()` - proposal-stage specific check.
- `isProposalStage()`, `stageToOrder()`, `stageLabel()` - stage normalization helpers.

### D. Proposal and rating side modules used by coordinator routes

File: `app/Http/Controllers/CoordinatorProposalController.php`

- `index()`, `show()`, `preview()`, `compareVersions()`, `update()`, `storeComment()`, `bulkUpdate()`, `getStats()`.

File: `app/Http/Controllers/RatingSheetController.php`

- `showCoordinatorRatings()`, `printCoordinatorRatings()`, `finalizeCoordinatorRatings()`, `reopenCoordinatorRatings()`.

---

## 11) Core Connected Functions (Snippet + Line-by-Line)

### A) `MilestoneTemplateController::assignToGroup`

```php
$template = MilestoneTemplate::with('tasks')->findOrFail($request->milestone_template_id);
$group    = Group::findOrFail($request->group_id);
$assignmentError = MilestoneAssignmentService::validateAssignment($group, $template);
if ($assignmentError !== null) {
    return redirect()->route('coordinator.milestones.index')
        ->withErrors(['assign' => $assignmentError]);
}
```

Line-by-line:
1. Loads selected milestone template plus its tasks.
2. Loads target group.
3. Calls service-layer gate for assignment rules.
4. Checks if service returned an error string.
5-6. Redirects back and shows reason when blocked.

### B) `MilestoneAssignmentService::validateAssignment`

```php
$meta = self::assignmentMeta($group);
$hasApprovedProposal = self::groupHasApprovedProposal($group);
if (! $meta['can_assign']) {
    return $meta['block_message'];
}
if ((int) $template->sequence_order > self::PROPOSAL_SEQUENCE_ORDER && ! $hasApprovedProposal) {
    return 'Cannot assign 60%/100% milestones until the group has at least one approved proposal.';
}
```

Line-by-line:
1. Reads computed assignment metadata for current group.
2. Checks proposal approval prerequisite.
3-4. Immediately blocks if metadata says assignment is not allowed.
5-7. Applies hard rule for later stages (step > 1).

### C) `CoordinatorController::facultyMatrix`

```php
$coordinatedOfferings = Offering::with(['teacher', 'academicTerm'])
    ->where('faculty_id', $user->faculty_id)
    ->when($activeTerm, fn ($query) => $query->where('academic_term_id', $activeTerm->id))
    ->get();
$groups = Group::with(['offering.teacher', 'adviser', 'defenseSchedules.defensePanels.faculty'])
    ->whereIn('offering_id', $coordinatedOfferings->pluck('id'))
    ->get();
```

Line-by-line:
1-4. Fetches offerings owned by coordinator (active-term scoped when available).
5-7. Loads groups tied to those offerings with adviser and panel relations.

### D) `DefenseScheduleController::storeSchedule`

```php
$gate = $this->defenseMilestoneGateService->evaluate($defenseRequest->group, $defenseRequest->defense_type);
if (!$gate['eligible'] && blank($request->milestone_override_reason)) {
    return back()->withErrors([
        'milestone_override_reason' => $gate['message'] . ' Add override reason to schedule anyway.',
    ])->withInput();
}
```

Line-by-line:
1. Evaluates defense-stage readiness from milestone gate service.
2. Checks if stage is ineligible and no override reason was provided.
3-5. Blocks request and returns a form-level error with preserved input.
