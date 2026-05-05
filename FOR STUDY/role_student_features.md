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

## 7. Exhaustive Feature & Endpoint List (All Functions)
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
