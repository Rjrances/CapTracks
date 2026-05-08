# Coordinator Features

The Coordinator acts as the project manager, establishing milestone requirements, reviewing proposals, analyzing faculty loads, and coordinating defenses.

## 🔄 User Journey Flow (Top to Bottom)
If panelists ask for the "System Workflow" or "Use Case" of a Coordinator, explain this exact step-by-step flow:
1. **Setup Milestones:** The Coordinator creates Milestone Templates (e.g., Chapter 1-3) which act as blueprints for the students.
2. **Manage Classlists:** They view and manage the specific students enrolled in their assigned Offerings.
3. **Monitor Load:** They check the Faculty Matrix to ensure they don't over-assign tasks to busy teachers.
4. **Review Proposals:** They review project proposals and can bulk approve/reject them.
5. **Schedule Defenses:** When students request a defense, the Coordinator reviews the request, uses the Auto-Assign logic to find available panelists, and finalizes the Defense Schedule.
6. **Review Grades:** After the defense finishes, they view the aggregated Rating Sheets submitted by the panelists.

## 1. Class Lists & Faculty Matrix

**Description:** Coordinators can view lists of all students enrolled in capstone classes for the current term and view a matrix showing how many groups each faculty member is advising/paneling.

### 🔗 End-to-End Flow: Chairperson Import -> Coordinator Classlist (Line-by-Line)

Use this when panelists ask: **"If Chairperson imports students, how do they appear in Coordinator class list?"**

#### A) Chairperson triggers import

**File:** `app/Http/Controllers/ChairpersonStudentController.php`

```php
public function upload(Request $request)
{
    return app(StudentImportService::class)->importFromRequest($request, StudentImportService::MODE_CHAIRPERSON);
}
```

**Simple line-by-line explanation:**
1. `public function upload(Request $request)` - declares the method and receives the HTTP request object.
2. `{` - starts the method body.
3. `return app(StudentImportService::class)->importFromRequest(...)` - gets the import service from Laravel container and forwards the request.
4. `StudentImportService::MODE_CHAIRPERSON` - tells the service this request came from the chairperson flow.
5. `}` - ends the method.

#### B) Import service validates, imports, and enrolls

**File:** `app/Services/StudentImportService.php`

```php
public function importFromRequest(Request $request, string $mode = self::MODE_CHAIRPERSON): RedirectResponse
{
    $request->validate([
        'file' => 'required|file|mimes:csv|max:10240',
    ]);

    $offeringId = $request->get('offering_id');
    $import = new StudentsImport($offeringId);
    Excel::import($import, $file);

    if ($offeringId) {
        $offering = Offering::find($offeringId);
        $recentStudents = Student::where('created_at', '>=', now()->subMinutes(2))->get();
        foreach ($recentStudents as $student) {
            $student->enrollInOffering($offering);
        }
    }
}
```

**Simple line-by-line explanation:**
1. Function signature - accepts request + mode, returns a redirect response.
2. `$request->validate(...)` - blocks invalid upload before processing.
3. `'file' => 'required|file|mimes:csv|max:10240'` - requires CSV and limits file size.
4. `$offeringId = $request->get('offering_id')` - reads selected offering (if any).
5. `$import = new StudentsImport($offeringId)` - prepares import class with offering context.
6. `Excel::import($import, $file)` - runs row import using Laravel Excel.
7. `if ($offeringId) { ... }` - only runs enrollment logic when offering is provided.
8. `Offering::find($offeringId)` - fetches target offering.
9. `Student::where(...)->get()` - gets recently created student records.
10. `foreach (...) { $student->enrollInOffering($offering); }` - links each imported student to offering.

#### C) Student-offering relation used by classlist

**File:** `app/Models/Student.php`

```php
public function offerings()
{
    return $this->belongsToMany(Offering::class, 'offering_student', 'student_id', 'offering_id', 'student_id', 'id')
                ->withPivot('enrolled_at')
                ->withTimestamps();
}
```

**Simple line-by-line explanation:**
1. `public function offerings()` - defines Student model relationship method.
2. `belongsToMany(...)` - declares many-to-many link between students and offerings.
3. `'offering_student'` - uses pivot table where enrollment is stored.
4. `->withPivot('enrolled_at')` - includes enrollment timestamp from pivot.
5. `->withTimestamps()` - keeps pivot created/updated timestamps.

#### D) Coordinator classlist fetches only coordinator-owned offerings

**File:** `app/Http/Controllers/CoordinatorController.php` (classlist logic)

```php
$coordinatedOfferingIds = Offering::where('faculty_id', $user->faculty_id)
    ->when($activeTerm, function ($query) use ($activeTerm) {
        return $query->where('academic_term_id', $activeTerm->id);
    })
    ->pluck('id');

$studentsQuery = Student::with(['offerings' => function ($query) use ($coordinatedOfferingIds) {
    $query->whereIn('offerings.id', $coordinatedOfferingIds);
}])->where('semester', $activeTerm->semester)
  ->whereHas('offerings', function ($query) use ($coordinatedOfferingIds) {
      $query->whereIn('offerings.id', $coordinatedOfferingIds);
  });
```

**Simple line-by-line explanation:**
1. `Offering::where('faculty_id', $user->faculty_id)` - gets offerings owned by logged-in coordinator.
2. `->when($activeTerm, ...)` - narrows to active term when available.
3. `->pluck('id')` - extracts offering IDs only.
4. `Student::with(['offerings' => ...])` - eager loads matching offering details for each student.
5. `->where('semester', $activeTerm->semester)` - keeps semester aligned.
6. `->whereHas('offerings', ...)` - returns only students enrolled in coordinator offerings.

**Core Logic (`app/Http/Controllers/CoordinatorController.php`):**
```php
public function facultyMatrix() {
    $user = auth()->user();
    $activeTerm = AcademicTerm::where('is_active', true)->first();

    $coordinatedOfferings = Offering::with(['teacher', 'academicTerm'])
        ->where('faculty_id', $user->faculty_id)
        ->when($activeTerm, fn ($q) => $q->where('academic_term_id', $activeTerm->id))
        ->get();

    $groups = Group::with([
        'offering.teacher',
        'adviser',
        'defenseSchedules' => fn ($q) => $q->latest('start_at'),
        'defenseSchedules.defensePanels.faculty',
    ])->whereIn('offering_id', $coordinatedOfferings->pluck('id'))->get();

    // Build matrix rows from coordinator-owned groups and latest schedule.
    return view('coordinator.faculty-matrix', compact('groups'));
}
```

**Simple line-by-line explanation:**
1. Gets logged-in user and active term.
2. Queries offerings where `faculty_id` equals coordinator faculty ID.
3. Eager-loads groups under those offerings with adviser and latest defense panels.
4. Prepares coordinator-scoped data for matrix rendering.
5. Returns `coordinator.faculty-matrix` view with the prepared dataset.

## 2. Defense Scheduling & Automatic Panel Assignment

**Description:** Coordinators approve student defense readiness requests and schedule defenses. Adviser and Offering Coordinator are auto-included. For **create**, Chair and Member are auto-assigned by backend policy (availability + workload balancing). For **edit/update**, the coordinator now selects Chair and Member manually from eligible options, with backend conflict and role safeguards.

**Core Logic (`app/Http/Controllers/Coordinator/DefenseScheduleController.php`):**
```php
public function store(Request $request)
{
    // 1) Validate scheduling inputs (group, stage, room, date/time)
    // 2) Validate scope: coordinator can only schedule groups in their offerings/active term
    // 3) Guardrail checks: milestone gate + room/date conflicts

    $startAt = Carbon::parse($request->date . ' ' . $request->start_time);
    $endAt = Carbon::parse($request->date . ' ' . $request->end_time);

    // 4) Backend auto-assignment (source of truth)
    $autoPanelMembers = $this->resolveAutoPanelMembers($group, $startAt, $endAt);
    if ($autoPanelMembers->count() < 2) {
        return back()->withErrors([
            'panel_members' => 'Unable to auto-assign Chair and Member for this schedule. Please choose another date/time/room.',
        ]);
    }

    // 5) Persist chair/member from auto policy
    foreach ($autoPanelMembers as $member) {
        DefensePanel::create([
            'defense_schedule_id' => $schedule->id,
            'faculty_id' => $member['faculty_id'],
            'role' => $member['role'], // chair/member
            'status' => 'pending',
        ]);
    }

    // 6) Auto-include adviser + offering coordinator (accepted immediately)
    DefensePanel::create([... 'role' => 'adviser', 'status' => 'accepted']);
    DefensePanel::create([... 'role' => 'coordinator', 'status' => 'accepted']);
}
```

**Simple line-by-line explanation:**
1. Validates schedule input fields (group, stage, date/time, room).
2. Converts date/time strings into `Carbon` datetime objects.
3. Runs backend auto-assign to get best chair/member pair.
4. If no valid pair, returns with `panel_members` validation error.
5. Creates invited panel rows for chair/member with `pending` status.
6. Auto-adds adviser and offering coordinator with `accepted` status.

**Update/Edit Behavior (`app/Http/Controllers/Coordinator/DefenseScheduleController.php`):**
```php
public function update(Request $request, $id)
{
    $validated = $request->validate([
        // ...
        'panel_members' => 'required|array|size:2',
        'panel_members.*.faculty_id' => 'required|exists:users,id',
        'panel_members.*.role' => 'required|in:chair,member',
    ]);

    // Validate composition, adviser/coordinator exclusion, duplicate picks,
    // and time collision checks for selected faculty.
    // Then persist selected chair/member as panel rows.
    // If same accepted person remains in same role, keep accepted status.
}
```

**Simple line-by-line explanation:**
1. Validates update inputs including exactly two `panel_members`.
2. Checks coordinator authorization scope for selected group/offering.
3. Applies milestone gate rules with optional override reason.
4. Checks date conflict (same group same date) and room double-booking.
5. Normalizes selected panel payload and validates chair/member composition.
6. Blocks duplicate selected faculty and conflict-of-interest picks.
7. Blocks schedule collisions for selected panelists.
8. Prevents reselecting previously declined chair/member in replacement flow.
9. Updates schedule + rewrites defense panel rows inside DB transaction.
10. Preserves `accepted` status if same accepted person remains in same role.

### 🧠 Defense Tip: How Does the System Choose the Auto-Assign Panel?
If panelists ask, *"How does your system know who to assign for a defense panel?"*, explain that `resolveAutoPanelMembers()` applies a deterministic **5-step policy**:

1. **Candidate Pool:** Pull Chair/Member-eligible faculty from `panelChairMemberCandidates()`.
2. **Conflict of Interest Filters:** Exclude the group’s **Adviser** and **Offering Coordinator** from Chair/Member pool.
3. **Time Collision Check:** Exclude faculty already assigned to overlapping defense windows via `getConflictingFacultyIds()`.
4. **Workload Balancing:** Count current term assignments and sort ascending (`assignment_count`).
5. **Deterministic Pick:** Take top two candidates (`Chair` = first, `Member` = second).

> UI note: Create uses backend auto-assign for Chair/Member. Edit allows coordinator-selected Chair/Member, then backend validates conflicts and role rules before saving.


## 3. Milestone Templates

**Description:** Coordinators define the milestones and required tasks.

**Core Logic (`app/Http/Controllers/MilestoneTemplateController.php`):**
```php
public function assignToGroup(Request $request) {
    
    // Find the requested template and the specific group
    $template = MilestoneTemplate::with('tasks')->findOrFail($request->milestone_template_id);
    $group = Group::findOrFail($request->group_id);

    // Prevent duplicate assignments
    if (GroupMilestone::where('group_id', $group->id)->where('milestone_template_id', $template->id)->exists()) {
        return back()->withErrors(['assign' => 'Already assigned.']);
    }

    // Create the active milestone for the group based on the template
    $groupMilestone = GroupMilestone::create([
        'group_id' => $group->id,
        'milestone_template_id' => $template->id,
        'title' => $template->name,
        'status' => 'not_started',
    ]);

    // Copy all the template tasks into the active group milestone
    foreach ($template->tasks as $task) {
        GroupMilestoneTask::create([
            'group_milestone_id' => $groupMilestone->id,
            'milestone_task_id' => $task->id,
            'status' => 'pending',
        ]);
    }

    // Send a notification to the student group
    NotificationService::coordinatorAssignedMilestoneToGroup($group, $groupMilestone, $template);
}
```

**Simple line-by-line explanation:**
1. Loads selected milestone template with all its tasks.
2. Loads target group that will receive the assignment.
3. Checks if same template is already assigned to that group.
4. Creates one `GroupMilestone` record as the assignment header.
5. Loops template tasks and clones each into `GroupMilestoneTask`.
6. Sends notification so students see new assigned milestone.

## 4. Proposal Review & Bulk Updating

**Description:** Coordinators review project proposals and can bulk-approve or reject them.

**Core Logic (`app/Http/Controllers/CoordinatorProposalController.php`):**
```php
public function bulkUpdate(Request $request)
{
    $request->validate([
        'proposal_ids' => 'required|array',
        'proposal_ids.*' => 'integer|exists:project_submissions,id',
        'status' => 'required|in:approved,rejected',
        'teacher_comment' => 'required|string|min:10',
    ]);

    foreach ($request->proposal_ids as $proposalId) {
        // ownership checks per proposal (coordinator can only update
        // submissions under their coordinated offering)
        // then update status + teacher_comment and notify student.
    }
}
```

**Simple line-by-line explanation:**
1. Validates proposal IDs, decision status, and required comment.
2. Loops selected proposals one-by-one.
3. For each item, enforces coordinator ownership scope.
4. Saves status/comment and sends matching notification.
5. Returns back with count of successfully processed proposals.

## 6. Critical Code Line-by-Line Breakdown (For 1000% Defense Readiness)

If your panelists want you to explain the code line-by-line, memorize these three most complex and critical Coordinator functions.

### A. Auto-Assign Panel Engine (`DefenseScheduleController@resolveAutoPanelMembers`)
Panel Question: *"Explain line-by-line how the system automatically assigns Chair and Member without conflicts."*

```php
private function resolveAutoPanelMembers(Group $group, Carbon $startAt, Carbon $endAt, ?int $excludeScheduleId = null): Collection
{
    // LINE 1: Resolve active term context for fair per-term load balancing.
    $activeTerm = AcademicTerm::where('is_active', true)->first();

    // LINE 2: Get busy faculty IDs in overlapping defense windows.
    $conflictingFacultyIds = $this->getConflictingFacultyIds($startAt, $endAt, $excludeScheduleId);

    // LINE 3: Build eligible candidate pool (already excludes adviser/offering coordinator).
    $availableFaculty = $this->panelChairMemberCandidates($group)
        ->whereNotIn('id', $conflictingFacultyIds)
        ->values();

    // LINE 4: Count each candidate's panel assignments in active term.
    $assignmentCounts = DefensePanel::select('faculty_id', DB::raw('COUNT(*) as assignment_count'))
        ->whereHas('defenseSchedule', function ($query) use ($activeTerm, $excludeScheduleId) {
            if ($activeTerm) $query->where('academic_term_id', $activeTerm->id);
            if ($excludeScheduleId) $query->where('id', '!=', $excludeScheduleId);
        })
        ->groupBy('faculty_id')
        ->pluck('assignment_count', 'faculty_id');

    // LINE 5: Sort least-loaded first (then by name), and pick top 2.
    $selectedFaculty = $availableFaculty
        ->map(function ($facultyMember) use ($assignmentCounts) {
            $facultyMember->assignment_count = (int) ($assignmentCounts[$facultyMember->id] ?? 0);
            return $facultyMember;
        })
        ->sortBy([['assignment_count', 'asc'], ['name', 'asc']])
        ->take(2)
        ->values();

    // LINE 6: Convert deterministic top-2 into fixed roles.
    return collect([
        ['faculty_id' => $selectedFaculty[0]->id, 'role' => 'chair'],
        ['faculty_id' => $selectedFaculty[1]->id, 'role' => 'member'],
    ]);
}
```

### B. Milestone Template Cloning (`MilestoneTemplateController@assignToGroup`)
Panel Question: *"Explain line-by-line how a template becomes an active trackable milestone for a specific group."*

```php
public function assignToGroup(Request $request) {
    // LINE 1: Find the master Template using the ID from the request, and eagerly load all its attached 'tasks'.
    $template = MilestoneTemplate::with('tasks')->findOrFail($request->milestone_template_id);
    
    // LINE 2: Find the specific student Group that is receiving the assignment.
    $group = Group::findOrFail($request->group_id);

    // LINE 3: Prevent duplicate assignments. Check if this exact group already has this exact template assigned.
    if (GroupMilestone::where('group_id', $group->id)->where('milestone_template_id', $template->id)->exists()) {
        return back()->withErrors(['assign' => 'Already assigned.']); // Stop execution if true.
    }

    // LINE 4: Create the new 'GroupMilestone' record, essentially cloning the template's title and linking it to the group.
    $groupMilestone = GroupMilestone::create([
        'group_id' => $group->id,
        'milestone_template_id' => $template->id,
        'title' => $template->name,
        'status' => 'not_started',
    ]);

    // LINE 5: Loop through every single task defined in the original master template.
    foreach ($template->tasks as $task) {
        // LINE 6: For each task, create a brand new tracking row in 'GroupMilestoneTask' linked to the cloned group milestone.
        GroupMilestoneTask::create([
            'group_milestone_id' => $groupMilestone->id,
            'milestone_task_id' => $task->id,
            'status' => 'pending', // Default status for the Kanban board
        ]);
    }

    // LINE 7: Trigger the NotificationService to alert the students that a new milestone was assigned to their dashboard.
    NotificationService::coordinatorAssignedMilestoneToGroup($group, $groupMilestone, $template);
}
```

### C. Bulk Updating Proposals (`CoordinatorProposalController@bulkUpdate`)
Panel Question: *"Explain line-by-line how you process approving multiple submissions simultaneously."*

```php
public function bulkUpdate(Request $request) {
    // LINE 1: Validate payload and require reviewer comment for auditability.
    $request->validate([
        'proposal_ids' => 'required|array',
        'proposal_ids.*' => 'integer|exists:project_submissions,id',
        'status' => 'required|in:approved,rejected',
        'teacher_comment' => 'required|string|min:10',
    ]);

    // LINE 2: Iterate selected proposals, enforce coordinator ownership, then update + notify.
    foreach ($request->proposal_ids as $proposalId) {
        // ownership check + update status/comment + notification
    }

    // LINE 3: Redirect back with result summary.
    return back()->with('success', 'Selected proposals processed successfully.');
}
```

## 7. Exhaustive Feature & Endpoint List (All Functions)
For complete system coverage, here is every single specific function the Coordinator can perform across the application:

**Dashboard & General Actions (`CoordinatorDashboardController` & `CoordinatorController`)**
- `index()`: Aggregates total students, active groups, faculty, and submissions specific to the coordinator's assigned sections.
- `classlist()`: Retrieves all enrolled students explicitly linked to the Coordinator's active offerings.
- `importStudents()` / `importStudentsForm()`: Allows localized uploading of student CSVs directly into the coordinator's assigned classes.
- `groups()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`: Standard group creation and management lifecycle.
- `assignAdviser()`: Overrides student choices to manually link a specific faculty member as an adviser to a group.
- `groupMilestones()`: A read-only view letting coordinators inspect how a specific group is progressing against assigned milestone templates.
- `notifications()`, `markNotificationAsRead()`, `deleteNotification()`, etc.: Fetches and manipulates alerts directed specifically to the coordinator.
- `activityLog()`: Queries the `Activity` model to show a real-time audit trail of actions taken by students under the coordinator's supervision.
- `facultyMatrix()`: Builds coordinator-scoped rows from owned offerings, advisers, and latest panel assignments for each group.

**Proposal Management (`CoordinatorProposalController`)**
- `index()` / `show()`: Lists and displays all capstone proposal documents awaiting the coordinator's global approval.
- `preview()`: Renders an embedded view of the uploaded proposal document.
- `compareVersions()`: Fetches two `ProjectSubmission` records (e.g., v1 and v2) and displays them side-by-side for delta review.
- `update()` / `bulkUpdate()`: Approves or rejects a single proposal or an array of proposals via checkboxes in one click.
- `getStats()`: Generates numerical counts (e.g., 5 Approved, 2 Pending) for the proposal dashboard cards.
- `storeComment()`: Injects a threaded comment record attached directly to the specific proposal submission.

**Defense Scheduling & Rubrics (`DefenseScheduleController` & `DefenseRubricController`)**
- `defenseRequestsIndex()`: Lists all groups that have hit 100% milestone completion and formally requested a defense.
- `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`: The core CRUD engine for `DefenseSchedule` records (time, room, date).
- `getAvailableFaculty()`: The auto-assign engine. It executes a complex query to filter out teachers with schedule conflicts, adviser conflicts, or heavy workloads, returning a safe list of available panelists.
- `createSchedule()` / `storeSchedule()`: Finalizes the schedule request and dispatches `DefensePanel` invitations to the selected faculty members.
- `approve()` / `reject()`: Processes the student's initial defense request before actual scheduling occurs.
- `markAsCompleted()`: Toggles the schedule status to 'done', locking further grading modifications.
- `index()` / `store()` / `update()` *(DefenseRubricController)*: Allows coordinators to define the dynamic JSON grading criteria (e.g., "Presentation 20%", "System Logic 40%") that panelists will use to grade defenses.

**Milestone Templates (`MilestoneTemplateController`)**
- `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`: Manages the overarching `MilestoneTemplate` (e.g., "Chapter 1-3 Requirements").
- `updateStatus()`: Toggles whether a template is 'active' and visible for group assignment.
- `storeTask()`, `updateTask()`, `destroyTask()`: Manages the individual checklist items (tasks) contained within a specific template.
- `assignToGroup()`: The replication logic. It copies a `MilestoneTemplate` and all its `MilestoneTask`s, generating live, trackable records (`GroupMilestone` and `GroupMilestoneTask`) for a specific student group.
- `removeAssignmentFromGroup()`: Detaches the cloned milestone structure from a group, effectively deleting their progress.

**Calendar & Scheduling (`CalendarController`)**
- `coordinatorCalendar()`: Fetches all defense schedules system-wide but dynamically injects a color-code (e.g., green vs gray) into the JSON payload for schedules that specifically belong to the coordinator's assigned groups.

**Authentication (`AuthController`)**
- `login()` / `logout()`: Validates credentials against the encrypted `password` column and manages session tokens.
- `changePassword()`: Receives a new password, hashes it using `bcrypt()`, and updates the user's account row.

---

## 8. 🎤 The "Cheat Sheet" Defense Script
If a panelist points at these functions and asks you to explain them line-by-line without reading the syntax, use these exact scripts:

### A. Auto-Assign Algorithm (`DefenseScheduleController@getAvailableFaculty`)
**The Code:**
```php
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
        ->whereHas('defenseSchedule', function ($q) use ($activeTerm) {
            $q->where('academic_term_id', $activeTerm->id);
        })
        ->groupBy('faculty_id')
        ->pluck('assignment_count', 'faculty_id');

    $selected = $availableFaculty
        ->map(fn ($f) => tap($f, fn () => $f->assignment_count = (int) ($assignmentCounts[$f->id] ?? 0)))
        ->sortBy([['assignment_count', 'asc'], ['name', 'asc']])
        ->take(2)
        ->values();

    return collect([
        ['faculty_id' => $selected[0]->id, 'role' => 'chair'],
        ['faculty_id' => $selected[1]->id, 'role' => 'member'],
    ]);
}
```
**Panel Question:** *"Explain how your Auto-Assign algorithm works without double-booking teachers."*
* **The Goal:** To automatically assign Chair and Member with fairness and no schedule overlap.
* **The Process:** Exclude conflicted faculty and conflict-of-interest roles, rank by least assignment load, and pick top two deterministically.

> *"Sir, the goal of this function is to automatically find available panelists for a defense.*
> *First, the system checks overlap conflicts for the selected date/time window and removes busy faculty.*
> *Next, the eligible pool already excludes the group adviser and offering coordinator for Chair/Member slots.*
> *Then, it computes each candidate’s current assignment count for the active term and sorts ascending.*
> *Finally, it picks the top 2 and maps them as Chair and Member. Adviser and coordinator are auto-included separately."*

### B. Faculty Matrix & Group Coverage (`CoordinatorController@facultyMatrix`)
**The Code:**
```php
public function facultyMatrix() {
    $user = auth()->user();
    $activeTerm = AcademicTerm::where('is_active', true)->first();

    $coordinatedOfferings = Offering::with(['teacher', 'academicTerm'])
        ->where('faculty_id', $user->faculty_id)
        ->when($activeTerm, fn ($q) => $q->where('academic_term_id', $activeTerm->id))
        ->get();

    $groups = Group::with([
        'offering.teacher',
        'adviser',
        'defenseSchedules' => fn ($q) => $q->latest('start_at'),
        'defenseSchedules.defensePanels.faculty',
    ])->whereIn('offering_id', $coordinatedOfferings->pluck('id'))->get();

    // map each group to matrix rows (adviser, chair, member, stage, status)
    return view('coordinator.faculty-matrix', compact('groups'));
}
```
**Panel Question:** *"How does your Faculty Matrix stay aligned to the coordinator's scope?"*
* **The Goal:** To show only coordinator-owned offerings and their groups, including adviser and latest panel composition.
* **The Process:** Resolve coordinator-owned offerings first, then eager-load group/adviser/schedule/panel relations and map them into matrix rows.

> *"Sir, this matrix is scoped to the logged-in coordinator's offerings. We first fetch offerings where `faculty_id` matches the coordinator, then load only groups under those offerings with adviser and latest defense panel relations. This keeps the data accurate to coordinator scope and avoids N+1 by eager loading related models."*

### C. Milestone Template Cloning (`MilestoneTemplateController@assignToGroup`)
**The Code:**
```php
public function assignToGroup(Request $request) {
    $template = MilestoneTemplate::with('tasks')->findOrFail($request->milestone_template_id);
    $group = Group::findOrFail($request->group_id);

    $groupMilestone = GroupMilestone::create([
        'group_id' => $group->id,
        'milestone_template_id' => $template->id,
        'title' => $template->name,
        'status' => 'not_started',
    ]);

    foreach ($template->tasks as $task) {
        GroupMilestoneTask::create([
            'group_milestone_id' => $groupMilestone->id,
            'milestone_task_id' => $task->id,
            'status' => 'pending',
        ]);
    }
}
```
**Panel Question:** *"Explain how a blueprint template is turned into trackable tasks for a specific student group."*
* **The Goal:** To assign a milestone blueprint to a group.
* **The Process:** Create a new `GroupMilestone`, then loop through the template's tasks and duplicate them as `GroupMilestoneTask`s.

> *"Sir, the goal of this function is to clone a master template so students can start tracking their progress.*
> *First, it fetches the requested template and the target student group. It creates a brand new `GroupMilestone` record linked specifically to that group.*
> *Then, it loops through every single task contained in the master template. Inside the loop, it creates a new `GroupMilestoneTask` for each one, setting its status to 'pending'. This essentially duplicates the blueprint and turns it into an active Kanban board for the students."*

### D. Bulk Updating Proposals (`CoordinatorProposalController@bulkUpdate`)
**The Code:**
```php
public function bulkUpdate(Request $request) {
    $request->validate([
        'proposal_ids' => 'required|array',
        'proposal_ids.*' => 'integer|exists:project_submissions,id',
        'status' => 'required|in:approved,rejected',
        'teacher_comment' => 'required|string|min:10',
    ]);

    foreach ($request->proposal_ids as $proposalId) {
        // ownership check + update + notification
    }
}
```
**Panel Question:** *"How does the system handle approving 50 proposals at the exact same time?"*
* **The Goal:** To mass-approve or mass-reject project proposals.
* **The Process:** Validate proposal IDs, enforce coordinator ownership per proposal, then update status/comments and trigger notifications.

> *"Sir, we support multi-select processing, but still enforce security per record. The coordinator sends an array of proposal IDs, the backend validates them, checks each belongs to the coordinator's offering scope, updates status plus teacher comment, and dispatches notifications. So it is bulk in UX, but still safe in authorization and audit behavior."*

---

## 9. Methods Used (Simple Terms)

Use this section when panelists ask what specific Laravel/PHP methods mean.

- `first()` - Returns only the first matching row (single model), or `null` if none found.  
  Example in coordinator code:  
  `$activeTerm = AcademicTerm::where('is_active', true)->first();`  
  Meaning: get one active term record (not a collection/list).
- `pluck('column')` - Gets only one column from query results (for example, just IDs), instead of loading full records.
- `whereIn('column', [...])` - Filters rows where the value matches any item in a list.
- `whereNotIn('column', [...])` - Filters rows by excluding values from a list.
- `withCount('relation')` - Adds a count of related records (for example, how many panels a faculty already has) without manually looping.
- `whereHas('relation', fn...)` - Filters a model based on conditions inside a related model.
- `findOrFail(id)` - Finds one record by ID; throws an error automatically if not found.
- `create([...])` - Inserts a new record in the database in one call.
- `update([...])` - Updates existing record fields in one call.
- `delete()` - Removes a record.
- `exists()` - Fast true/false check if at least one row matches.
- `collect([...])` - Creates a Laravel Collection object so we can chain helpers (sort, map, filter, etc.).
- `map(fn...)` - Transforms each item in a collection into a new shape/value.
- `sortBy([...])` - Sorts collection items (for auto-panel ranking by load, then name).
- `take(2)` - Gets only the first two items (used for Chair and Member picks).
- `values()` - Reindexes collection keys to clean 0..n ordering.
- `unique('field')` - Removes duplicates by a specific field.
- `toArray()` - Converts a collection/object into a plain PHP array.
- `return back()->withErrors([...])->withInput()` - Sends user back to form with validation errors and keeps typed input.
- `DB::beginTransaction()` / `DB::commit()` / `DB::rollback()` - Groups multiple DB writes into one safe unit: all succeed together, or all are undone on failure.
- `Carbon::parse(...)` - Converts date/time text into a date object for comparisons and scheduling.
- `response()->json([...])` - Returns structured JSON data to frontend JavaScript.

### Symbols / Operators (Q&A quick guide)
- `?` (ternary) - Short if/else in one line.
- `??` (null coalescing) - Use fallback value when left side is `null`.
- `?:` (elvis shorthand) - Use left side if truthy, otherwise fallback.
- `?->` (null-safe operator) - Access property/method only if object is not `null`.
- `=>` - Key/value separator in arrays, and short function arrow syntax.
- `===` - Strict comparison (value and type must match).

## 10. Quick Oral Cheat Sheet (Top 10 Terms)

Use these one-liners when panelists ask suddenly during Q&A.

1. **`pluck`** - "Get only one column, like IDs, from many rows."
2. **`whereIn`** - "Filter rows that match any value in a list."
3. **`withCount`** - "Add relationship counts directly from DB, no manual loops."
4. **`whereHas`** - "Filter by a condition inside a related table."
5. **`create`** - "Insert a new database row quickly."
6. **`update`** - "Modify existing row values."
7. **`exists`** - "Fast yes/no check if a matching record exists."
8. **`sortBy`** - "Order results by a rule, like least workload first."
9. **`take(2)`** - "Get only the first two ranked candidates."
10. **`DB transaction`** - "All-or-nothing save: commit if all pass, rollback if any fail."

---

## 11. Updated Coordinator-Side Functions (With Line-by-Line Explanation)

Use this section as your **latest source-of-truth** for defense. These snippets reflect current coordinator behavior in CapTrack.

### A. Defense Schedule Update (Manual Chair/Member + Safety Rules)
**File:** `app/Http/Controllers/Coordinator/DefenseScheduleController.php`  
**Function:** `update(Request $request, $id)`

```php
public function update(Request $request, $id)
{
    // LINE 1: Validate all form inputs including the two invited panel slots.
    $validated = $request->validate([
        'group_id' => 'required|exists:groups,id',
        'stage' => 'required|in:proposal,60,100',
        'academic_term_id' => 'required|exists:academic_terms,id',
        'room' => 'required|string|max:255',
        'date' => 'required|date',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i|after:start_time',
        'milestone_override_reason' => 'nullable|string|max:1000',
        'panel_members' => 'required|array|size:2',
        'panel_members.*.faculty_id' => 'required|exists:users,id',
        'panel_members.*.role' => 'required|in:chair,member',
    ]);

    // LINE 2: Load schedule and enforce coordinator scope (only own offerings).
    $schedule = DefenseSchedule::findOrFail($id);
    $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
    $group = Group::with('offering')->findOrFail($validated['group_id']);
    if (!in_array($group->offering_id, $coordinatorOfferings)) {
        abort(403, 'You can only edit defense schedules for groups in your offerings.');
    }

    // LINE 3: Enforce milestone gate, allow override only with reason.
    $gate = $this->defenseMilestoneGateService->evaluate($group, $validated['stage']);
    $gateOverridden = false;
    if (!$gate['eligible']) {
        if (blank($validated['milestone_override_reason'] ?? null)) {
            return back()->withErrors([
                'milestone_override_reason' => $gate['message'] . ' Add override reason to schedule anyway.',
            ])->withInput();
        }
        $gateOverridden = true;
    }

    // LINE 4: Build datetime values and block duplicate date/room conflicts.
    $startAt = Carbon::parse($request->date . ' ' . $request->start_time);
    $endAt = Carbon::parse($request->date . ' ' . $request->end_time);
    if ($this->hasGroupScheduleOnDate($validated['group_id'], $request->date, $id)) {
        return back()->withErrors(['date' => 'This group already has a defense schedule on the selected date.'])->withInput();
    }
    if ($this->checkDoubleBooking($startAt, $endAt, $request->room, $id)) {
        return back()->withErrors(['room' => 'This room is already booked for the selected time slot.'])->withInput();
    }

    // LINE 5: Normalize selected panel payload (chair/member picks).
    $requestedPanelMembers = collect($validated['panel_members'] ?? [])
        ->map(fn ($row) => [
            'faculty_id' => (int) ($row['faculty_id'] ?? 0),
            'role' => (string) ($row['role'] ?? ''),
        ])->values()->all();

    // LINE 6: Enforce composition rules and no duplicate selected faculty.
    $compositionError = $this->validatePanelComposition($requestedPanelMembers);
    if ($compositionError) {
        return back()->withErrors(['panel_members' => $compositionError])->withInput();
    }
    $pickedFacultyIds = collect($requestedPanelMembers)->pluck('faculty_id')->filter()->values()->all();
    if (count(array_unique($pickedFacultyIds)) !== 2) {
        return back()->withErrors(['panel_members' => 'Chair and Member must be different faculty members.'])->withInput();
    }

    // LINE 7: Enforce COI + time collision checks for selected panelists.
    $blockedSelectionError = $this->panelMembersMustNotIncludeAdviserOrCoordinator($group, $requestedPanelMembers);
    if ($blockedSelectionError) {
        return back()->withErrors(['panel_members' => $blockedSelectionError])->withInput();
    }
    if ($this->checkPanelMemberConflicts($pickedFacultyIds, $startAt, $endAt, $id)) {
        return back()->withErrors([
            'panel_members' => 'One or more selected panel members are already assigned to another defense at this time.',
        ])->withInput();
    }

    // LINE 8: Prevent re-selecting previously declined invited panelists.
    $schedule->loadMissing('defensePanels');
    $declinedPanelistIds = $schedule->defensePanels
        ->whereIn('role', ['chair', 'member'])
        ->where('status', 'declined')
        ->pluck('faculty_id')->unique()->values()->all();
    if (!empty($declinedPanelistIds) && count(array_intersect($pickedFacultyIds, $declinedPanelistIds)) > 0) {
        return back()->withErrors([
            'panel_members' => 'Replacement required: previously declined panelist cannot be re-selected for this update.',
        ])->withInput();
    }

    // LINE 9: Save schedule + rebuild panel rows in one transaction.
    DB::beginTransaction();
    try {
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

        $existingInvitedPanelsByRole = $schedule->defensePanels
            ->whereIn('role', ['chair', 'member'])
            ->keyBy('role');
        DefensePanel::where('defense_schedule_id', $schedule->id)->delete();

        // LINE 10: Keep accepted status if same person remains in same role.
        foreach (collect($requestedPanelMembers)->sortBy(fn ($row) => $row['role'] === 'chair' ? 0 : 1) as $member) {
            $existingPanelForRole = $existingInvitedPanelsByRole->get($member['role']);
            $preserveAccepted = $existingPanelForRole
                && (int) $existingPanelForRole->faculty_id === (int) $member['faculty_id']
                && $existingPanelForRole->status === 'accepted';

            DefensePanel::create([
                'defense_schedule_id' => $schedule->id,
                'faculty_id' => $member['faculty_id'],
                'role' => $member['role'],
                'status' => $preserveAccepted ? 'accepted' : 'pending',
                'responded_at' => $preserveAccepted ? ($existingPanelForRole->responded_at ?? now()) : null,
            ]);
        }

        // LINE 11: Auto-include adviser + offering coordinator as accepted roles.
        // (same logic continues in controller)

        DB::commit();
        return redirect()->route('coordinator.defense.index')->with('success', 'Defense schedule updated successfully.');
    } catch (\Exception $e) {
        DB::rollback();
        return back()->withErrors(['error' => 'Failed to update defense schedule. Please try again or contact support if the problem persists.'])->withInput();
    }
}
```

### B. Auto-Assign Candidate API (Used by Coordinator Defense UI)
**File:** `app/Http/Controllers/Coordinator/DefenseScheduleController.php`  
**Function:** `getAvailableFaculty(Request $request)`

```php
public function getAvailableFaculty(Request $request)
{
    // LINE 1: Validate required date/time/room/group inputs.
    $request->validate([
        'date' => 'required|date',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i',
        'room' => 'required|string',
        'group_id' => 'required|exists:groups,id'
    ]);

    // LINE 2: Enforce coordinator scope by offering ownership.
    $coordinatorOfferings = auth()->user()->offerings()->pluck('id')->toArray();
    $group = Group::with(['adviser', 'offering'])->find($request->group_id);
    if (!in_array($group->offering_id, $coordinatorOfferings)) {
        abort(403, 'You can only access faculty for groups in your offerings.');
    }

    // LINE 3: Compute schedule overlap and room collision.
    $startAt = Carbon::parse($request->date . ' ' . $request->start_time);
    $endAt = Carbon::parse($request->date . ' ' . $request->end_time);
    $conflict = $this->checkDoubleBooking($startAt, $endAt, $request->room);
    $conflictingFacultyIds = $this->getConflictingFacultyIds($startAt, $endAt);

    // LINE 4: Build eligible pool and rank by least workload.
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
        ->sortBy([['assignment_count', 'asc'], ['name', 'asc']])
        ->values();

    // LINE 5: Return full list plus suggested top-2 IDs for auto-prefill.
    $autoAssignedFacultyIds = $availableFaculty
        ->take(2)
        ->pluck('id')
        ->map(fn ($id) => (string) $id)
        ->values();

    return response()->json([
        'availableFaculty' => $availableFaculty,
        'autoAssignedFacultyIds' => $autoAssignedFacultyIds,
        'conflict' => $conflict,
        'message' => $conflict ? 'This room is already booked for the selected time slot.' : null
    ]);
}
```

### C. Coordinator Calendar Status Derivation (Aligned with Panel Confirmation)
**File:** `app/Http/Controllers/CalendarController.php`  
**Function:** `coordinatorCalendar()`

```php
public function coordinatorCalendar()
{
    // LINE 1: Load active term + all defense schedules in display scope.
    $user = Auth::user();
    $activeTerm = AcademicTerm::where('is_active', true)->first();
    $defenses = DefenseSchedule::with(['group', 'group.members', 'group.adviser', 'group.offering.teacher', 'panelists'])
        ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
        ->when($activeTerm, fn ($query) => $query->where('academic_term_id', $activeTerm->id))
        ->orderBy('start_at')
        ->get();

    // LINE 2: Compute which groups belong to this coordinator.
    $myGroupIds = Group::whereHas('offering', function ($query) use ($user) {
        $query->where('faculty_id', $user->faculty_id);
    })->pluck('id')->toArray();

    // LINE 3: Derive panel state and display status for each event.
    $calendarEvents = $defenses->map(function ($defense) use ($myGroupIds) {
        $startDate = \Carbon\Carbon::parse($defense->start_at);
        $endDate   = \Carbon\Carbon::parse($defense->end_at);
        $invitedPanels = $defense->panelists->whereIn('role', ['chair', 'member']);
        $hasConfirmedChair = $invitedPanels->where('role', 'chair')->where('status', 'accepted')->isNotEmpty();
        $hasConfirmedMember = $invitedPanels->where('role', 'member')->where('status', 'accepted')->isNotEmpty();
        $hasDeclinedInvite = $invitedPanels->where('status', 'declined')->isNotEmpty();

        $panelState = $hasDeclinedInvite
            ? 'replacement_needed'
            : (($hasConfirmedChair && $hasConfirmedMember) ? 'confirmed' : 'awaiting_confirmation');

        // LINE 4: Build modal-friendly status labels and badge variants.
        $displayStatus = match ($defense->status) {
            'completed' => 'Completed',
            'in_progress' => 'In progress',
            default => match ($panelState) {
                'replacement_needed' => 'Replacement needed',
                'awaiting_confirmation' => 'Awaiting panel confirmation',
                default => 'Scheduled',
            },
        };

        return [
            'id' => $defense->id,
            'title' => $defense->group->name ?? 'Defense',
            'start' => $startDate->toISOString(),
            'end' => $endDate->toISOString(),
            'extendedProps' => [
                'status' => $defense->status,
                'panel_state' => $panelState,
                'display_status' => $displayStatus,
                'local_date' => $startDate->format('m/d/Y'),
                'is_mine' => in_array($defense->group_id, $myGroupIds),
            ],
        ];
    })->toArray();

    // LINE 5: Return coordinator calendar with enriched event payload.
    return view('calendar.coordinator', compact('defenses', 'calendarEvents', 'myGroupIds'));
}
```

### D. Proposal Bulk Update with Ownership Guard
**File:** `app/Http/Controllers/CoordinatorProposalController.php`  
**Function:** `bulkUpdate(Request $request)`

```php
public function bulkUpdate(Request $request)
{
    // LINE 1: Validate IDs, final decision, and required coordinator comment.
    $request->validate([
        'proposal_ids' => 'required|array',
        'proposal_ids.*' => 'integer|exists:project_submissions,id',
        'status' => 'required|in:approved,rejected',
        'teacher_comment' => 'required|string|min:10',
    ]);

    $user = Auth::user();
    $updatedCount = 0;

    // LINE 2: Iterate selected proposals and enforce coordinator scope per item.
    foreach ($request->proposal_ids as $proposalId) {
        $proposal = ProjectSubmission::find($proposalId);
        $student = $proposal->getStudentData();
        if (!$student) continue;
        $studentGroup = $student->groups()->first();
        if (!$studentGroup) continue;
        $offering = $studentGroup->offering;
        if (!$offering || $offering->faculty_id !== $user->faculty_id) continue;

        // LINE 3: Save decision + comment.
        $proposal->update([
            'status' => $request->status,
            'teacher_comment' => $request->teacher_comment,
        ]);

        // LINE 4: Notify student side based on decision type.
        if ($request->status === 'approved') {
            NotificationService::proposalApproved($student, $studentGroup->name, $proposal->title ?? 'Project Proposal');
        } else {
            NotificationService::proposalRejected($student, $studentGroup->name, $proposal->title ?? 'Project Proposal', $request->teacher_comment);
        }
        $updatedCount++;
    }

    // LINE 5: Return summary feedback to coordinator UI.
    $statusMessage = $request->status === 'approved' ? 'approved' : 'rejected';
    return redirect()->route('coordinator.proposals.index')->with('success', "{$updatedCount} proposals {$statusMessage} successfully.");
}
```

### E. Faculty Matrix Aggregation (Coordinator Scope)
**File:** `app/Http/Controllers/CoordinatorController.php`  
**Function:** `facultyMatrix()`

```php
public function facultyMatrix()
{
    // LINE 1: Determine coordinator identity + active academic term.
    $user = auth()->user();
    $activeTerm = AcademicTerm::where('is_active', true)->first();

    // LINE 2: Get offerings owned by this coordinator.
    $coordinatedOfferings = Offering::with(['teacher', 'academicTerm'])
        ->where('faculty_id', $user->faculty_id)
        ->when($activeTerm, function ($query) use ($activeTerm) {
            return $query->where('academic_term_id', $activeTerm->id);
        })
        ->get();

    // LINE 3: Pull groups + adviser + latest defense panels for matrix rows.
    $groups = Group::with([
        'offering.teacher',
        'adviser',
        'defenseSchedules' => function ($query) {
            $query->latest('start_at');
        },
        'defenseSchedules.defensePanels.faculty',
    ])->whereIn('offering_id', $coordinatedOfferings->pluck('id'))->get();

    // LINE 4: Transform into UI rows (group, adviser, chair/member, schedule stage/status).
    $matrixRows = $groups->map(function ($group) {
        $latestSchedule = $group->defenseSchedules->first();
        // mapping continues in actual file...
    });

    // LINE 5: Build summary cards and return view.
    $summary = [
        'total_offerings' => $coordinatedOfferings->count(),
        'total_groups' => $groups->count(),
    ];
    return view('coordinator.faculty-matrix', compact('matrixRows', 'summary'));
}
```

### F. Milestone Template Assignment to Group
**File:** `app/Http/Controllers/MilestoneTemplateController.php`  
**Function:** `assignToGroup(Request $request)`

```php
public function assignToGroup(Request $request)
{
    // LINE 1: Validate group/template IDs and optional due date rule.
    $request->validate([
        'group_id' => 'required|exists:groups,id',
        'milestone_template_id' => 'required|exists:milestone_templates,id',
        'due_date' => 'nullable|date|after:today',
    ]);

    // LINE 2: Load template (with tasks) and target group.
    $template = MilestoneTemplate::with('tasks')->findOrFail($request->milestone_template_id);
    $group = Group::findOrFail($request->group_id);

    // LINE 3: Prevent duplicate assignment of same template to same group.
    $alreadyAssigned = GroupMilestone::where('group_id', $group->id)
        ->where('milestone_template_id', $template->id)
        ->exists();
    if ($alreadyAssigned) {
        return redirect()->route('coordinator.milestones.index')
            ->withErrors(['assign' => "\"{$template->name}\" is already assigned to {$group->name}."]);
    }

    // LINE 4: Apply central assignment validation policy.
    $assignmentError = MilestoneAssignmentService::validateAssignment($group, $template);
    if ($assignmentError !== null) {
        return redirect()->route('coordinator.milestones.index')
            ->withErrors(['assign' => $assignmentError]);
    }

    // LINE 5: Create group milestone header + clone all template tasks.
    $groupMilestone = GroupMilestone::create([
        'group_id' => $group->id,
        'milestone_template_id' => $template->id,
        'title' => $template->name,
        'description' => $template->description,
        'target_date' => $request->due_date,
        'due_date' => $request->due_date,
        'progress_percentage' => 0,
        'status' => 'not_started',
    ]);
    foreach ($template->tasks as $task) {
        GroupMilestoneTask::create([
            'group_milestone_id' => $groupMilestone->id,
            'milestone_task_id' => $task->id,
            'status' => 'pending',
            'is_completed' => false,
        ]);
    }

    // LINE 6: Notify group members and redirect with success message.
    $group->load(['members.account']);
    NotificationService::coordinatorAssignedMilestoneToGroup($group, $groupMilestone, $template);
    return redirect()->route('coordinator.milestones.index')
        ->with('success', "\"{$template->name}\" assigned to {$group->name} with {$template->tasks->count()} tasks.");
}
```
