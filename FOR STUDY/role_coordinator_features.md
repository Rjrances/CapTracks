# Coordinator Features

The Coordinator acts as the project manager, establishing milestone requirements, reviewing proposals, analyzing faculty loads, and coordinating defenses.

## 1. Class Lists & Faculty Matrix

**Description:** Coordinators can view lists of all students enrolled in capstone classes for the current term and view a matrix showing how many groups each faculty member is advising/paneling.

**Core Logic (`app/Http/Controllers/CoordinatorController.php`):**
```php
public function facultyMatrix() {
    $activeTerm = AcademicTerm::where('is_active', true)->first();
    
    // Calculate load by counting relationships
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
**Code Explanation:**
- `AcademicTerm::where('is_active', true)->first();`: Fetches the current ongoing semester. We only want to see faculty workload for right now, not historically.
- `withCount([...])`: This is a Laravel Eloquent feature that counts related records without having to load all the actual rows. It adds a dynamic `advising_groups_count` and `defense_panels_count` attribute to the fetched user.
- `function($q) use ($activeTerm)`: We use closures inside the `withCount` to filter the count. We only count groups and defense schedules if they belong to the `$activeTerm->id`.

## 2. Defense Scheduling & Automatic Panel Assignment

**Description:** Coordinators approve student defense requests and schedule defense panels. The system automatically filters and suggests panel members to prevent scheduling conflicts and balance workload.

**Core Logic (`app/Http/Controllers/Coordinator/DefenseScheduleController.php`):**
```php
public function storeSchedule(Request $request, DefenseRequest $defenseRequest) {
    $schedule = DefenseSchedule::create([
        'group_id' => $defenseRequest->group_id,
        'academic_term_id' => AcademicTerm::where('is_active', true)->first()->id,
        'schedule_date' => $request->schedule_date,
        'start_time' => $request->start_time,
        'end_time' => $request->end_time,
        'venue' => $request->venue,
        'status' => 'scheduled'
    ]);

    foreach ($request->panel_members as $role => $facultyId) {
        DefensePanel::create([
            'defense_schedule_id' => $schedule->id,
            'faculty_id' => $facultyId,
            'role' => $role,
            'status' => 'pending' 
        ]);
    }
    $defenseRequest->update(['status' => 'approved']);
}
```

### 🧠 Defense Tip: How Does the System Choose the Auto-Assign Panel?
If panelists ask, *"How does your system know who to suggest for a defense panel?"*, you can explain that the `getAvailableFaculty()` method runs a strict **5-step filtering rule**:

1. **Role Check:** It pulls all active users who are faculty members (Teachers, Advisers, Coordinators).
2. **Conflict of Interest 1:** It actively excludes the faculty member who is the **Adviser** for that specific group (an adviser cannot be a panelist grading their own students).
3. **Conflict of Interest 2:** It excludes the faculty member who is the **Subject Coordinator** for that specific class offering.
4. **Time Collision Check:** It checks the `defense_schedules` table. If a faculty member is already sitting on another defense panel at the exact same `start_time` and `schedule_date`, they are completely hidden from the selection list.
5. **Workload Balancing (Sorting):** Finally, it looks at how many panels the remaining eligible faculty members are currently assigned to this semester. It **sorts them in ascending order**, placing faculty with the fewest panel assignments at the very top of the auto-assign list to balance the workload across the department.

**Code Explanation:**
- `DefenseSchedule::create(...)`: Saves the core scheduling metadata (when and where the defense is happening) and links it to the group that requested it.
- `foreach ($request->panel_members as $role => $facultyId)`: The coordinator assigns multiple panel members from a form (e.g., a 'chair' and a 'member'). This loop iterates through those assignments and creates individual `DefensePanel` rows tying the faculty member to the schedule. They are marked as `pending` because the faculty members must log in and explicitly accept the invitation later.
- `$defenseRequest->update(['status' => 'approved']);`: Updates the student's original request to inform them that it has been successfully processed.

## 3. Milestone Templates

**Description:** Coordinators define the milestones and required tasks.

**Core Logic (`app/Http/Controllers/MilestoneTemplateController.php`):**
```php
public function store(Request $request) {
    $milestone = MilestoneTemplate::create([
        'name' => $request->name,
        'order' => $request->order,
        'is_active' => true
    ]);

    foreach ($request->tasks as $taskData) {
        $milestone->tasks()->create([
            'task_name' => $taskData['name'],
            'is_required' => $taskData['required'] ?? false
        ]);
    }
}
```
**Code Explanation:**
- `MilestoneTemplate::create(...)`: Creates the "blueprint" or "parent category" for the milestone (e.g., "Chapter 1 Requirements").
- `$milestone->tasks()->create(...)`: Because a Milestone Template `hasMany` Tasks, we can call `create()` directly on the relationship. This automatically inserts the task and links its `milestone_template_id` to the parent milestone we just created.

## 4. Proposal Review & Bulk Updating

**Description:** Coordinators review project proposals and can bulk-approve or reject them.

**Core Logic (`app/Http/Controllers/CoordinatorProposalController.php`):**
```php
public function bulkUpdate(Request $request) {
    ProjectSubmission::whereIn('id', $request->submission_ids)
                     ->update(['status' => $request->status]);
    return back()->with('success', 'Selected proposals updated successfully.');
}
```
**Code Explanation:**
- `whereIn('id', $request->submission_ids)`: The frontend sends an array of selected checkbox IDs (e.g., `[15, 18, 22]`). The `whereIn` clause constructs a SQL query: `UPDATE project_submissions SET status = ? WHERE id IN (15, 18, 22)`.
- `update(['status' => $request->status])`: Sets the status (either 'approved' or 'rejected') across all matched records in a single, highly efficient database call.
