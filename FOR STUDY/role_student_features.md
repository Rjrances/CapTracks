# Student Features

Students manage group formations, submit deliverables, track capstone milestones, manage specific tasks, and request defense schedules.

## 🔄 User Journey Flow (Top to Bottom)
If panelists ask for the "System Workflow" or "Use Case" of a Student, explain this exact step-by-step flow:
1. **Secure Account:** The student logs in for the first time and the middleware blocks them until they change their default password.
2. **Form a Group:** They search for classmates by ID and invite them to form a Capstone Group.
3. **Find an Adviser:** They send an Adviser Invitation to a faculty member from the available list.
4. **Work on Milestones:** They access their Kanban Board, uploading documents to specific tasks and dragging the cards from 'Pending' to 'Done'.
5. **Revise Documents:** When the adviser leaves feedback, the student uploads a new document which automatically increments the `version` number.
6. **Request Defense:** Once their milestone progress hits an acceptable threshold, they submit a Defense Request to their Coordinator.


## 1. Password Management & Bypass Middleware

**Description:** When a student logs in for the first time, they are forced to change their default password. A special middleware handles this bypass logic.

**Core Logic (`app/Http/Middleware/CheckStudentPasswordChange.php` & `StudentPasswordController.php`):**
```php
// Middleware Check
public function handle(Request $request, Closure $next) {
    
    // Get the logged-in user from the student table
    $student = Auth::guard('student')->user(); 
    
    // Check if their encrypted password matches the default 'password123'
    if ($student && Hash::check('password123', $student->password)) { 
        
        // If it does, stop the request and redirect them to the forced password change page
        return redirect()->route('student.change-password')
            ->with('warning', 'You must change your default password before proceeding.');
    }
    
    // If they already changed it, let the request proceed normally
    return $next($request); 
}

// Update Password
public function updatePassword(Request $request) {
    
    // Get the logged-in student
    $student = Auth::guard('student')->user(); 
    
    // Encrypt the newly typed password and save it to the database
    $student->update(['password' => Hash::make($request->new_password)]); 
    
    // Send them to the dashboard now that they are secure
    return redirect()->route('student.dashboard'); 
}
```

## 2. Group Formation & Management

**Description:** Students form groups and invite peers or advisers.

**Core Logic (`app/Http/Controllers/StudentGroupController.php`):**
```php
public function inviteAdviser(Request $request) {
    
    // Fetch the group that the logged-in student belongs to
    $group = Auth::guard('student')->user()->student->group; 
    
    // Create a new invitation record
    AdviserInvitation::create([ 
        'group_id' => $group->id, // Link it to the student's group
        'faculty_id' => $request->faculty_id, // Link it to the chosen faculty member
        'status' => 'pending' // Mark it as pending so the faculty sees it on their dashboard
    ]);
}
```

## 3. Project & Proposal Versioning

**Description:** Auto-incrementing version numbers for document uploads.

**Core Logic (`app/Http/Controllers/ProjectSubmissionController.php`):**
```php
public function store(Request $request) {
    
    // Get the logged-in student
    $student = Auth::guard('student')->user()->student; 
    
    // Automatically save the uploaded file into the public/submissions folder and get the file path
    $path = $request->file('file')->store('submissions', 'public'); 
    
    // Fetch the MAX version number for this specific document type and increment it by +1
    $nextVersion = ProjectSubmission::getNextVersionFor($student->student_id, $request->type);

    // Create a brand new record for this version
    ProjectSubmission::create([ 
        'student_id' => $student->student_id, // Link to the student
        'file_path' => $path, // Save the path to the physical file
        'type' => $request->type, // e.g., 'proposal' or 'final'
        'version' => $nextVersion, // Save the newly incremented version number
        'status' => 'pending', // Await adviser approval
    ]);
}
```

### 🧠 Defense Tip: How does Document Versioning work?
If a panelist asks: *"How do you keep track of old files without overwriting them?"*
**Your Answer:** *"Instead of simply updating the existing database row when a student uploads a revision, the system runs a query to find the `MAX(version)` for that specific document type, adds `+ 1` to it, and creates a brand new row in the `project_submissions` table. Because we create a new row every time, we preserve the old file paths in the database. This is exactly what allows us to load two different versions side-by-side for comparison."*

## 4. Milestone Kanban & Checklist View

**Description:** Students manage their capstone requirements via a Kanban board and a Checklist.

**Core Logic (`app/Http/Controllers/StudentMilestoneChecklistController.php` & `StudentMilestoneController.php`):**
```php
// Kanban Task Movement
public function moveTask(Request $request, $taskId) {
    
    // Find the task that was dragged on the board
    $task = GroupMilestoneTask::findOrFail($taskId); 
    
    // Update its status column (todo, in_progress, done)
    $task->update(['status' => $request->status]); 

    // Recalculate group milestone overall progress percentage to keep dashboard fast
    $milestone = $task->groupMilestone; // Get the parent milestone category
    
    // Count how many total tasks exist in this milestone
    $totalTasks = $milestone->groupMilestoneTasks()->count(); 
    
    // Count how many are marked as 'done'
    $completedTasks = $milestone->groupMilestoneTasks()->where('status', 'done')->count(); 
    
    // Save the calculated percentage directly onto the parent milestone row
    $milestone->update([ 
        // (Completed / Total) * 100
        'progress_percentage' => ($totalTasks > 0) ? round(($completedTasks / $totalTasks) * 100) : 0 
    ]);
}
```

### 🧠 Defense Tip: How does the system calculate Progress Percentages so fast?
If a panelist asks: *"How do your dashboards load the progress percentage without slowing down?"*
**Your Answer:** *"We don't calculate the percentages on the fly when the dashboard loads. Instead, every time a student drags a Kanban task into the 'Done' column, a backend trigger fires. It calculates `(Completed Tasks / Total Tasks) * 100` and saves that static percentage directly into the parent `group_milestones` table. Because of this, the dashboard just reads a single number instead of recalculating hundreds of tasks every page refresh. It makes the system highly scalable."*


## 5. Task Submissions

**Description:** Submitting specific files or links directly attached to a milestone task.

**Core Logic (`app/Http/Controllers/TaskSubmissionController.php`):**
```php
public function store(Request $request, GroupMilestoneTask $task) {
    
    // Save the physical file to the public/task_submissions folder
    $path = $request->file('submission_file')->store('task_submissions', 'public');
    
    // Create the submission record
    TaskSubmission::create([ 
        'group_milestone_task_id' => $task->id, // Link it precisely to the Kanban task
        'student_id' => Auth::guard('student')->user()->student->student_id, // Link the student
        'file_path' => $path, // Save the file path
        'notes' => $request->notes // Save any optional notes typed by the student
    ]);

    // Automatically push the Kanban card forward to 'in_progress' so the student doesn't have to drag it manually
    $task->update(['status' => 'in_progress']);
}
```

## 6. Defense Requests

**Description:** Requesting a defense schedule when ready.

**Core Logic (`app/Http/Controllers/StudentDefenseRequestController.php`):**
```php
public function store(Request $request) {
    
    // Create a new record in the defense_requests table
    DefenseRequest::create([ 
        'group_id' => Auth::guard('student')->user()->student->group->id, // Automatically attach the logged-in student's group
        'preferred_date' => $request->preferred_date, // Save the date they selected from the calendar
        'status' => 'pending',  // Mark as pending for the Coordinator to review
    ]);
}
```

## 8. Critical Code Line-by-Line Breakdown (For 1000% Defense Readiness)

If your panelists want you to explain the code line-by-line, memorize these three most complex and critical Student functions.

### A. Document Versioning Auto-Increment (`ProjectSubmissionController@store`)
Panel Question: *"Explain line-by-line how the system prevents students from overwriting old proposal versions when they upload a new one."*

```php
public function store(Request $request) {
    // LINE 1: Retrieve the currently logged-in student record.
    $student = Auth::guard('student')->user()->student; 
    
    // LINE 2: Automatically save the uploaded physical file into the 'public/submissions' folder and store the string path.
    $path = $request->file('file')->store('submissions', 'public'); 
    
    // LINE 3: Call a custom method 'getNextVersionFor()'. This executes a SQL query: 
    // SELECT MAX(version) FROM project_submissions WHERE student_id = ? AND type = ?
    // It then adds +1 to the result. If no previous version exists, it defaults to 1.
    $nextVersion = ProjectSubmission::getNextVersionFor($student->student_id, $request->type);

    // LINE 4: Instead of running an UPDATE query on an existing row, we execute an INSERT query.
    // By creating a brand new row with the incremented version, the old row and its file path remain untouched.
    ProjectSubmission::create([ 
        'student_id' => $student->student_id, 
        'file_path' => $path, // LINE 5: Save the new file path.
        'type' => $request->type, // e.g., 'proposal'
        'version' => $nextVersion, // LINE 6: Save the newly incremented integer (e.g., v2).
        'status' => 'pending', 
    ]);
}
```

### B. Kanban Drag-and-Drop Logic (`StudentMilestoneController@moveTask`)
Panel Question: *"Explain line-by-line what happens in the backend when a student drags a card from 'Todo' to 'Done'."*

```php
public function moveTask(Request $request, $taskId) {
    // LINE 1: Find the specific Kanban task in the database using the ID passed from the AJAX request.
    $task = GroupMilestoneTask::findOrFail($taskId); 
    
    // LINE 2: Execute an UPDATE query to change the 'status' column to the new column the card was dropped in (e.g., 'done').
    $task->update(['status' => $request->status]); 

    // LINE 3: Retrieve the parent Milestone category that this task belongs to (e.g., "Chapter 1").
    $milestone = $task->groupMilestone; 
    
    // LINE 4: Count the absolute total number of tasks inside this parent milestone.
    $totalTasks = $milestone->groupMilestoneTasks()->count(); 
    
    // LINE 5: Count how many of those tasks currently have the 'done' status.
    $completedTasks = $milestone->groupMilestoneTasks()->where('status', 'done')->count(); 
    
    // LINE 6: Execute an UPDATE query directly on the parent Milestone row to save the new overall completion percentage.
    // Math: (Completed Tasks / Total Tasks) * 100. If Total is 0, default to 0 to prevent division by zero errors.
    $milestone->update([ 
        'progress_percentage' => ($totalTasks > 0) ? round(($completedTasks / $totalTasks) * 100) : 0 
    ]);
}
```

### C. The Defense Gate Service (`StudentDefenseRequestController@store`)
Panel Question: *"Explain line-by-line how the system stops students from requesting a defense if they haven't finished their milestones."*

```php
public function store(Request $request) {
    // LINE 1: Retrieve the logged-in student and their associated group.
    $student = $this->getAuthenticatedStudent();
    $group = $student->groups()->first();
    
    // LINE 2: Validate the incoming request to ensure they provided a preferred date/time and selected a valid defense type.
    $request->validate([
        'defense_type' => 'required|in:proposal,60_percent,100_percent',
        'preferred_date' => 'required|date|after:today',
        'preferred_time' => 'required|date_format:H:i',
    ]);
    
    // LINE 3: Call the dedicated 'DefenseMilestoneGateService'. This service checks if the group's overall progress matches the requirement for the selected defense type.
    // For example, if they selected '100_percent', the service verifies the group's global progress is actually 100%.
    $gate = $this->defenseMilestoneGateService->evaluate($group, $request->defense_type);
    
    // LINE 4: If the service returns 'eligible' as false...
    if (!$gate['eligible']) {
        // LINE 5: Abort the submission and redirect the student back with the specific error message generated by the Gate service.
        return redirect()->route('student.defense-requests.index')
            ->withErrors(['milestone' => $gate['message'] . ' Student requests are blocked until this milestone is complete.']);
    }
    
    // LINE 6: If they pass the gate, insert the new 'DefenseRequest' into the database.
    DefenseRequest::create([
        'group_id' => $group->id,
        'defense_type' => $request->defense_type,
        'student_message' => $request->student_message,
        'status' => 'pending',
    ]);
}
```

## 9. Exhaustive Feature & Endpoint List (All Functions)
For complete system coverage, here is every single specific function the Student can perform across the application:

**Account & Dashboard Management (`StudentDashboardController`, `StudentPasswordController`, `StudentController`)**
- `index()` *(StudentDashboardController)*: Calculates the student's active group progress and retrieves upcoming milestone deadlines to render the dashboard.
- `updatePassword()` *(StudentPasswordController)*: Hashes and updates the student's default password, lifting the initial login restriction middleware.
- `notifications()`, `markNotificationAsRead()`, `deleteNotification()` *(StudentController)*: Standard methods for managing the student's personal alert feed.

**Group Management (`StudentGroupController`)**
- `show()`, `create()`, `store()`, `edit()`, `update()`: Manages the lifecycle of creating a Capstone Group, assigning the current student as the initial leader.
- `inviteMember()`: Creates a pending `GroupInvitation` for a specific classmate via their Student ID.
- `acceptInvitation()` / `declineInvitation()`: Processes peer invites, either attaching the student to the group or deleting the invite.
- `removeMember()` / `cancelInvitation()`: Allows group leaders to kick members or retract pending invitations.
- `inviteAdviser()`: Dispatches an invitation specifically directed to a faculty member to become the group's mentor.

**Milestone & Task Management (`StudentMilestoneController`, `StudentMilestoneChecklistController`, `TaskSubmissionController`)**
- `index()` / `show()`: Renders the active milestones and Kanban board assigned to the group.
- `moveTask()`: Handles the drag-and-drop AJAX request to change a task's status (e.g., from 'todo' to 'done').
- `bulkUpdateTasks()` / `updateMultipleTasks()`: Allows updating multiple tasks at once via checkboxes.
- `recomputeProgress()`: A background utility that recalculates the overall milestone percentage based on completed vs pending tasks.
- `assignTask()` / `unassignTask()`: Attaches or detaches a specific group member to a Kanban card for accountability.
- `storeTaskComment()`: Saves threaded discussion replies directly onto a task card.
- `checklist()` *(StudentMilestoneChecklistController)*: Returns a streamlined checklist view of pending milestone items.
- `store()` / `review()` *(TaskSubmissionController)*: Handles the actual file upload attached to a specific task and renders it for review.

**Proposals & Project Submissions (`StudentProposalController`, `ProjectSubmissionController`)**
- `store()` / `update()`: Handles uploading project documents, automatically calculating the next `version` number to preserve history.
- `rollback()`: Restores a previous document version if the adviser rejects the newest one.
- `previewVersion()` / `studentPreviewSubmission()`: Opens the uploaded PDF or document in an embedded browser viewer.
- `compareVersions()` / `studentCompareSubmissions()`: Loads two versions side-by-side to visually inspect what was changed.

**Defense Management (`StudentDefenseRequestController`)**
- `store()`: Submits a formal request to the coordinator indicating the group is ready to defend, saving the preferred date.
- `cancel()`: Withdraws the request before the coordinator approves it.

**Calendar & Scheduling (`CalendarController`)**
- `studentCalendar()`: Fetches the single, specific, approved defense schedule for the student's group and plots it on the calendar.

**Authentication (`AuthController`)**
- `login()` / `logout()`: Validates credentials against the encrypted `password` column and manages session tokens.
- `changePassword()`: Receives a new password, hashes it using `bcrypt()`, and updates the user's account row.

---

## 10. 🎤 The "Cheat Sheet" Defense Scripts
If a panelist points at these functions and asks you to explain them line-by-line without reading the syntax, use these exact scripts:

## 10. 🎤 The "Cheat Sheet" Defense Scripts
If a panelist points at these functions and asks you to explain them line-by-line without reading the syntax, use these exact scripts:

### A. Document Versioning (`ProjectSubmissionController@store`)
**The Code:**
```php
public function store(Request $request) {
    $student = Auth::guard('student')->user()->student; 
    $path = $request->file('file')->store('submissions', 'public'); 
    
    $nextVersion = ProjectSubmission::getNextVersionFor($student->student_id, $request->type);

    ProjectSubmission::create([ 
        'student_id' => $student->student_id, 
        'file_path' => $path, 
        'type' => $request->type, 
        'version' => $nextVersion, 
        'status' => 'pending', 
    ]);
}
```
**Panel Question:** *"How does the system prevent students from overwriting old proposal versions?"*
* **The Goal:** To securely upload a student's document while keeping a complete history.
* **The Process:** Find the highest version number the student currently has, and add +1 to it.

> *"Sir, the goal of this function is to securely upload a student's document without deleting revisions.*
> *First, the system saves the uploaded physical file into our server.*
> *Next, it queries the database to find the highest version number the student already has for this document type using `getNextVersionFor`. If they uploaded Version 1, the system assigns the new file as Version 2.*
> *Because we create a brand new database row instead of updating the old one, the old file path is never overwritten, allowing the adviser to compare Version 1 against Version 2 side-by-side."*

### B. Kanban Background Math (`StudentMilestoneController@moveTask`)
**The Code:**
```php
public function moveTask(Request $request, $taskId) {
    $task = GroupMilestoneTask::findOrFail($taskId); 
    $task->update(['status' => $request->status]); 

    $milestone = $task->groupMilestone; 
    $totalTasks = $milestone->groupMilestoneTasks()->count(); 
    $completedTasks = $milestone->groupMilestoneTasks()->where('status', 'done')->count(); 
    
    $milestone->update([ 
        'progress_percentage' => ($totalTasks > 0) ? round(($completedTasks / $totalTasks) * 100) : 0 
    ]);
}
```
**Panel Question:** *"How do your dashboards load the progress percentage without slowing down?"*
* **The Goal:** To natively recalculate the group's progress when a task changes.
* **The Process:** Count total tasks, count 'done' tasks, calculate percentage, and save to DB.

> *"Sir, we don't calculate percentages on the fly when the dashboard loads. Instead, every time a student drags a Kanban task into the 'Done' column, an asynchronous trigger fires.*
> *The system quickly counts the total tasks, counts the completed tasks, and calculates the math. It then saves that static percentage directly into the `group_milestones` table. Because of this, the dashboard simply reads a single number instead of recalculating hundreds of tasks every refresh, making the system highly scalable."*

### C. Default Password Middleware (`CheckStudentPasswordChange@handle`)
**The Code:**
```php
public function handle(Request $request, Closure $next) {
    $student = Auth::guard('student')->user(); 
    if ($student && Hash::check('password123', $student->password)) { 
        return redirect()->route('student.change-password')
            ->with('warning', 'You must change your default password.');
    }
    return $next($request); 
}
```
**Panel Question:** *"How do you force newly imported students to change their password on first login?"*
* **The Goal:** To enforce account security immediately after CSV mass import.
* **The Process:** Intercept the web request, check if their hashed password matches 'password123', and redirect them.

> *"Sir, we use Laravel Middleware. Whenever a student tries to access any page on the system, the middleware intercepts the request. It takes their current encrypted password from the database and runs a Hash Check against the default string 'password123'. If there is a match, the middleware blocks the page load and redirects them to the change password form. Once they change it, the Hash Check fails, and the middleware allows them to access the system normally."*

### D. The Defense Gate Service (`StudentDefenseRequestController@store`)
**The Code:**
```php
public function store(Request $request) {
    $student = $this->getAuthenticatedStudent();
    $group = $student->groups()->first();
    
    $gate = $this->defenseMilestoneGateService->evaluate($group, $request->defense_type);
    
    if (!$gate['eligible']) {
        return redirect()->route('student.defense-requests.index')
            ->withErrors(['milestone' => $gate['message']]);
    }
    
    DefenseRequest::create([
        'group_id' => $group->id,
        'defense_type' => $request->defense_type,
        'status' => 'pending',
    ]);
}
```
**Panel Question:** *"How does the system physically stop a student from requesting a defense if they aren't done?"*
* **The Goal:** To validate milestone completion before allowing defense requests.
* **The Process:** Feed the group data into the `DefenseMilestoneGateService`. If ineligible, abort.

> *"Sir, we built a dedicated `DefenseMilestoneGateService`. Before the defense request is even saved to the database, the system sends the group's current progress data into this service. The service verifies if their global progress matches the strict requirements for the defense type (for example, 100% completion). If the service returns false, the controller aborts the creation and throws an error back to the student, physically preventing the request from proceeding."*
