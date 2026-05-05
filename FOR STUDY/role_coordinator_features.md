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

**Description:** Coordinators approve student defense requests and schedule defense panels. The system automatically filters and suggests panel members to prevent scheduling conflicts and balance workload.

**Core Logic (`app/Http/Controllers/Coordinator/DefenseScheduleController.php`):**
```php
public function storeSchedule(Request $request, DefenseRequest $defenseRequest) {
    
    // Create a new record in the defense_schedules table
    $schedule = DefenseSchedule::create([ 
        'group_id' => $defenseRequest->group_id, // Link it to the group that requested it
        'academic_term_id' => AcademicTerm::where('is_active', true)->first()->id, // Link to the current semester
        'schedule_date' => $request->schedule_date, // Set the date
        'start_time' => $request->start_time, // Set the start time
        'end_time' => $request->end_time, // Set the end time
        'venue' => $request->venue, // Set the physical room
        'status' => 'scheduled' // Mark the schedule as officially active
    ]);

    // Loop through the selected panel members (Chair, Member)
    foreach ($request->panel_members as $role => $facultyId) { 
        
        // Create a new record in the defense_panels table
        DefensePanel::create([ 
            'defense_schedule_id' => $schedule->id, // Link this panelist to the schedule we just created
            'faculty_id' => $facultyId, // Assign the specific faculty member's ID
            'role' => $role, // Set their role (e.g., 'chair' or 'member')
            'status' => 'pending'  // Keep it pending until the faculty member logs in and accepts the invite
        ]);
    }
    
    // Update the student's original request so they know it was approved
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

## 5. Exhaustive Feature & Endpoint List (All Functions)
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
