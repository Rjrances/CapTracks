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

**Core Logic (`app/Http/Controllers/CoordinatorController.php`):**
```php
public function facultyMatrix() {
    
    // Get the current ongoing semester
    $activeTerm = AcademicTerm::where('is_active', true)->first(); 
    
    // Fetch all active faculty members (Teachers, Coordinators, Advisers, Chairpersons)
    $facultyLoad = User::whereIn('role', ['adviser', 'coordinator', 'chairperson', 'teacher'])
        
        // Dynamically count their relationships without fetching every single row
        ->withCount([ 
            
            // Count how many groups they advise...
            'advisingGroups' => function($q) use ($activeTerm) { 
                // ...but only for the current semester
                $q->where('academic_term_id', $activeTerm->id); 
            },
            
            // Count how many defense panels they sit on...
            'defensePanels' => function($q) use ($activeTerm) { 
                $q->whereHas('defenseSchedule', function($sq) use ($activeTerm) {
                    // ...but only for the current semester
                    $sq->where('academic_term_id', $activeTerm->id); 
                });
            }
        ])->get(); // Execute the query

    // Send the data to the Blade template
    return view('coordinator.faculty-matrix', compact('facultyLoad', 'activeTerm')); 
}
```

## 2. Defense Scheduling & Automatic Panel Assignment

**Description:** Coordinators approve student defense readiness requests and schedule defenses. Adviser and Offering Coordinator are auto-included, while Chair and Member are now **automatically assigned by backend policy** (availability + workload balancing), not manual user choice.

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

### 🧠 Defense Tip: How Does the System Choose the Auto-Assign Panel?
If panelists ask, *"How does your system know who to assign for a defense panel?"*, explain that `resolveAutoPanelMembers()` applies a deterministic **5-step policy**:

1. **Candidate Pool:** Pull Chair/Member-eligible faculty from `panelChairMemberCandidates()`.
2. **Conflict of Interest Filters:** Exclude the group’s **Adviser** and **Offering Coordinator** from Chair/Member pool.
3. **Time Collision Check:** Exclude faculty already assigned to overlapping defense windows via `getConflictingFacultyIds()`.
4. **Workload Balancing:** Count current term assignments and sort ascending (`assignment_count`).
5. **Deterministic Pick:** Take top two candidates (`Chair` = first, `Member` = second).

> UI note: the create form now disables panel fields until Group + Date + Start + End + Room are complete, then auto-prefills from backend response.


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

## 4. Proposal Review & Bulk Updating

**Description:** Coordinators review project proposals and can bulk-approve or reject them.

**Core Logic (`app/Http/Controllers/CoordinatorProposalController.php`):**
```php
public function bulkUpdate(Request $request) {
    
    // Select all the database rows that match the checkbox IDs sent from the frontend
    ProjectSubmission::whereIn('id', $request->submission_ids) 
                     
                     // Change their status to 'approved' or 'rejected' all at once
                     ->update(['status' => $request->status]); 
                     
    // Refresh the page
    return back()->with('success', 'Selected proposals updated successfully.'); 
}
```

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
    // LINE 1: Query the 'ProjectSubmission' table and select all rows where the 'id' matches the array of IDs sent from the checkboxes.
    ProjectSubmission::whereIn('id', $request->submission_ids) 
                     // LINE 2: Execute an immediate UPDATE query on all matched rows, changing their 'status' to whatever the coordinator selected (e.g., 'approved').
                     ->update(['status' => $request->status]); 
                     
    // LINE 3: Redirect the coordinator back to the proposal list with a success message.
    return back()->with('success', 'Selected proposals updated successfully.'); 
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
- `facultyMatrix()`: Queries the database with `withCount()` to show exactly how many groups and panels each teacher is assigned to, preventing workload burnout.

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
## 8. 🎤 The "Cheat Sheet" Defense Script
If a panelist points at these functions and asks you to explain them line-by-line without reading the syntax, use these exact scripts:

### A. Auto-Assign Algorithm (`DefenseScheduleController@getAvailableFaculty`)
**The Code:**
```php
private function resolveAutoPanelMembers(Group $group, Carbon $startAt, Carbon $endAt, ?int $excludeScheduleId = null): Collection {
    $conflictingFacultyIds = $this->getConflictingFacultyIds($startAt, $endAt, $excludeScheduleId);

    $availableFaculty = $this->panelChairMemberCandidates($group)
        ->whereNotIn('id', $conflictingFacultyIds)
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

### B. Faculty Matrix & Load Monitoring (`CoordinatorController@facultyMatrix`)
**The Code:**
```php
public function facultyMatrix() {
    $activeTerm = AcademicTerm::where('is_active', true)->first(); 
    $facultyLoad = User::whereIn('role', ['adviser', 'coordinator', 'chairperson', 'teacher'])
        ->withCount([ 
            'advisingGroups' => function($q) use ($activeTerm) { 
                $q->where('academic_term_id', $activeTerm->id); 
            },
            'defensePanels' => function($q) use ($activeTerm) { 
                $q->whereHas('defenseSchedule', function($sq) use ($activeTerm) {
                    $sq->where('academic_term_id', $activeTerm->id); 
                });
            }
        ])->get();
    return view('coordinator.faculty-matrix', compact('facultyLoad', 'activeTerm')); 
}
```
**Panel Question:** *"How do you count the faculty workload without slowing down the page with hundreds of database queries?"*
* **The Goal:** To show how many groups and panels each teacher is handling this semester.
* **The Process:** Use Laravel's `withCount` to count relationships natively in SQL instead of looping through them in PHP.

> *"Sir, to prevent performance issues, we do not fetch every single group and panel record. Instead, we use Laravel's `withCount` method. This allows the database itself to count the 'advisingGroups' and 'defensePanels' relationships that belong only to the active semester. It returns a single clean number for each teacher. This avoids the 'N+1 Query Problem' and keeps the matrix dashboard extremely fast."*

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
    ProjectSubmission::whereIn('id', $request->submission_ids) 
                     ->update(['status' => $request->status]); 
    return back()->with('success', 'Selected proposals updated successfully.'); 
}
```
**Panel Question:** *"How does the system handle approving 50 proposals at the exact same time?"*
* **The Goal:** To mass-approve or mass-reject project proposals.
* **The Process:** Use SQL's `WHERE IN` clause to execute a single bulk update query instead of looping.

> *"Sir, instead of running a slow `foreach` loop that triggers 50 separate database queries, we use a single optimized SQL command. The system takes the array of IDs from the checkboxes, uses the `whereIn` clause to target all matching rows in the database simultaneously, and instantly updates their status to 'approved'. It takes only one query regardless of how many proposals are selected."*

---

## 9. Methods Used (Simple Terms)

Use this section when panelists ask what specific Laravel/PHP methods mean.

- `pluck('column')` - Gets only one column from query results (for example, just IDs), instead of loading full records.
- `whereIn('column', [...])` - Filters rows where the value matches any item in a list.
- `whereNotIn('column', [...])` - Filters rows by excluding values from a list.
- `withCount('relation')` - Adds a count of related records (for example, how many panels a faculty already has) without manually looping.
- `whereHas('relation', fn...)` - Filters a model based on conditions inside a related model.
- `first()` - Returns the first matching row, or `null` if none exists.
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
