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

**Account Management (`StudentDashboardController` & `StudentPasswordController`)**
- View Student Dashboard with real-time progress and upcoming deadlines (`index`).
- Change default password (forced on first login) (`updatePassword`).

**Group Management (`StudentGroupController`)**
- Create a Capstone Group (`store`).
- Invite classmates to join the group (`inviteMember`).
- Accept or decline group invitations from others (`acceptInvitation`, `declineInvitation`).
- Cancel a pending invite or remove a member (`cancelInvitation`, `removeMember`).
- Invite a faculty member to be the Adviser (`inviteAdviser`).

**Milestone & Task Management (`StudentMilestoneController` & `StudentMilestoneChecklistController` & `TaskSubmissionController`)**
- View the milestones and checklists assigned by the Coordinator.
- Move task cards across the Kanban board (`moveTask`).
- Bulk update task statuses (`bulkUpdateTasks`).
- Edit task details (due dates, notes) (`updateTask`).
- Assign a task to a specific groupmate (`assignTask`, `unassignTask`).
- Post or reply to comments on a task (`storeTaskComment`).
- Upload files directly to a specific task (`store` in TaskSubmissionController).

**Proposals & Project Submissions (`StudentProposalController` & `ProjectSubmissionController`)**
- Upload a formal Capstone Proposal (`store`).
- Upload new file versions for an existing proposal (`update`).
- Rollback to a previous proposal version (`rollback`).
- Preview their own documents or compare two versions side-by-side (`previewVersion`, `compareVersions`).
- Upload ad-hoc project submissions (e.g. final drafts) and delete them if unapproved (`store`, `destroy`).

**Defense Management (`StudentDefenseRequestController`)**
- Check eligibility for defense.
- Submit a formal defense request picking a preferred date/time (`store`).
- Cancel a pending defense request (`cancel`).
