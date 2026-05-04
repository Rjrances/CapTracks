# Student Features

Students manage group formations, submit deliverables, track capstone milestones, manage specific tasks, and request defense schedules.

## 1. Password Management & Bypass Middleware

**Description:** When a student logs in for the first time, they are forced to change their default password. A special middleware handles this bypass logic.

**Core Logic (`app/Http/Middleware/CheckStudentPasswordChange.php` & `StudentPasswordController.php`):**
```php
// Middleware Check
public function handle(Request $request, Closure $next) {
    $student = Auth::guard('student')->user();
    if ($student && Hash::check('password123', $student->password)) { // Default password
        return redirect()->route('student.change-password')
            ->with('warning', 'You must change your default password before proceeding.');
    }
    return $next($request);
}

// Update Password
public function updatePassword(Request $request) {
    $student = Auth::guard('student')->user();
    $student->update(['password' => Hash::make($request->new_password)]);
    return redirect()->route('student.dashboard');
}
```
**Code Explanation:**
- `Auth::guard('student')->user()`: Explicitly pulls the user from the 'student' guard (because faculties use a different table/guard).
- `Hash::check('password123', $student->password)`: Laravel's Hash facade safely checks if their current encrypted password in the database resolves to the literal string `'password123'` (the default). 
- `return redirect()->route(...)`: If they still have the default password, it interrupts their request and redirects them to the change password page.
- `return $next($request);`: If they already changed it, the middleware lets the request proceed normally.
- `Hash::make(...)`: Encrypts the newly submitted password before saving it to the database.

## 2. Group Formation & Management

**Description:** Students form groups and invite peers or advisers.

**Core Logic (`app/Http/Controllers/StudentGroupController.php`):**
```php
public function inviteAdviser(Request $request) {
    $group = Auth::guard('student')->user()->student->group;
    AdviserInvitation::create([
        'group_id' => $group->id,
        'faculty_id' => $request->faculty_id,
        'status' => 'pending'
    ]);
}
```
**Code Explanation:**
- `$group = ...->student->group`: Fetches the student's relationship records. It gets the logged-in student account, then the related student profile, and then the group that student belongs to.
- `AdviserInvitation::create(...)`: Inserts a new row linking the group to the requested faculty member with a `pending` status so the faculty member can see it in their dashboard and accept/decline.

## 3. Project & Proposal Versioning

**Description:** Auto-incrementing version numbers for document uploads.

**Core Logic (`app/Http/Controllers/ProjectSubmissionController.php`):**
```php
public function store(Request $request) {
    $student = Auth::guard('student')->user()->student;
    $path = $request->file('file')->store('submissions', 'public');
    
    // Fetches the max version number and increments it
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

### 🧠 Defense Tip: How does Document Versioning work?
If a panelist asks: *"How do you keep track of old files without overwriting them?"*
**Your Answer:** *"Instead of simply updating the existing database row when a student uploads a revision, the system runs a query to find the `MAX(version)` for that specific document type, adds `+ 1` to it, and creates a brand new row in the `project_submissions` table. Because we create a new row every time, we preserve the old file paths in the database. This is exactly what allows us to load two different versions side-by-side for comparison."*

**Code Explanation:**
- `$request->file('file')->store('submissions', 'public')`: Laravel automatically takes the uploaded file, generates a unique hash filename, saves it into the `storage/app/public/submissions` folder, and returns the generated file path.
- `getNextVersionFor(...)`: A custom method on the Model that looks at past submissions for this specific student and document type. E.g., if they have versions 1 and 2 of a "proposal", it calculates the new number as 3.
- `ProjectSubmission::create(...)`: Saves the record to the database along with the file path and calculated version number.

## 4. Milestone Kanban & Checklist View

**Description:** Students manage their capstone requirements via a Kanban board and a Checklist.

**Core Logic (`app/Http/Controllers/StudentMilestoneChecklistController.php` & `StudentMilestoneController.php`):**
```php
// Kanban Task Movement
public function moveTask(Request $request, $taskId) {
    $task = GroupMilestoneTask::findOrFail($taskId);
    $task->update(['status' => $request->status]);

    // Recalculate group milestone overall progress percentage
    $milestone = $task->groupMilestone;
    $totalTasks = $milestone->groupMilestoneTasks()->count();
    $completedTasks = $milestone->groupMilestoneTasks()->where('status', 'done')->count();
    
    $milestone->update([
        'progress_percentage' => ($totalTasks > 0) ? round(($completedTasks / $totalTasks) * 100) : 0
    ]);
}
```

### 🧠 Defense Tip: How does the system calculate Progress Percentages so fast?
If a panelist asks: *"How do your dashboards load the progress percentage without slowing down?"*
**Your Answer:** *"We don't calculate the percentages on the fly when the dashboard loads. Instead, every time a student drags a Kanban task into the 'Done' column, a backend trigger fires. It calculates `(Completed Tasks / Total Tasks) * 100` and saves that static percentage directly into the parent `group_milestones` table. Because of this, the dashboard just reads a single number instead of recalculating hundreds of tasks every page refresh. It makes the system highly scalable."*

**Code Explanation:**
- `$task->update(['status' => $request->status])`: When dragging a task on a Kanban board, the frontend sends the new column name (`todo`, `in_progress`, or `done`). This updates it in the DB.
- **Recalculation block**: After a task moves, we need to update the progress bar. It counts the total tasks in that milestone, counts how many are specifically marked `'done'`, and applies standard math: `(completed / total) * 100`. We round it off and save the percentage.

## 5. Task Submissions

**Description:** Submitting specific files or links directly attached to a milestone task.

**Core Logic (`app/Http/Controllers/TaskSubmissionController.php`):**
```php
public function store(Request $request, GroupMilestoneTask $task) {
    $path = $request->file('submission_file')->store('task_submissions', 'public');
    
    TaskSubmission::create([
        'group_milestone_task_id' => $task->id,
        'student_id' => Auth::guard('student')->user()->student->student_id,
        'file_path' => $path,
        'notes' => $request->notes
    ]);

    // Automatically move task to 'in_progress' or 'done' based on submission
    $task->update(['status' => 'in_progress']);
}
```
**Code Explanation:**
- `$request->file('submission_file')->store(...)`: Uploads the physical file.
- `TaskSubmission::create(...)`: Ties the uploaded file specifically to an exact milestone task (like "Chapter 1 Draft") rather than a general project upload, making it easier for the adviser to review specific objectives.
- `$task->update(['status' => 'in_progress']);`: Automatically pushes the Kanban card forward so the student doesn't have to drag it manually after uploading.

## 6. Defense Requests

**Description:** Requesting a defense schedule when ready.

**Core Logic (`app/Http/Controllers/StudentDefenseRequestController.php`):**
```php
public function store(Request $request) {
    DefenseRequest::create([
        'group_id' => Auth::guard('student')->user()->student->group->id,
        'preferred_date' => $request->preferred_date,
        'status' => 'pending', 
    ]);
}
```
**Code Explanation:**
- `DefenseRequest::create(...)`: Takes the date the student picked from a calendar picker, associates it with their group, and creates a pending request. The coordinator will see this pending row in their dashboard queue to approve and finalize the schedule.
