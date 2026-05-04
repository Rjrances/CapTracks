# CapTracks Application Features and Core Logic

This document provides a comprehensive overview of the CapTracks application features, categorized by user roles and modules, along with references and key code snippets demonstrating the underlying logic.

---

## 1. Authentication & User Management

### Feature: Multi-Guard Login & Profile Management
The system handles login across multiple roles (Student, Adviser, Coordinator, Chairperson) using separate guards or role columns.

**Key Routes:**
- `GET/POST /login` (AuthController)
- `POST /change-password` (AuthController / StudentPasswordController)

**Code Reference (`AuthController.php`):**
```php
// Authentication logic routing to different dashboards based on role
public function login(Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::guard('web')->attempt($credentials)) {
        $request->session()->regenerate();
        $role = Auth::user()->role;
        return match($role) {
            'coordinator' => redirect()->route('coordinator.dashboard'),
            'chairperson' => redirect()->route('chairperson.dashboard'),
            'adviser' => redirect()->route('adviser.dashboard'),
            default => redirect('/'),
        };
    }
    // Handle student guard similarly...
}
```

---

## 2. Chairperson Features (Admin)

### Feature: Faculty and Student Management
Chairpersons manage the registration of students and faculty, handle academic terms, and assign roles.

**Key Routes:**
- `Resource /chairperson/teachers` (ChairpersonFacultyController)
- `Resource /chairperson/students` (ChairpersonStudentController)
- `Resource /chairperson/academic-terms` (AcademicTermController)
- `Resource /chairperson/roles` (RoleController)

**Code Reference (`ChairpersonFacultyController.php` / `RoleController.php`):**
```php
// Updating a faculty member's role
public function update(Request $request, $faculty_id) {
    $faculty = User::findOrFail($faculty_id);
    $faculty->update(['role' => $request->role]);
    return back()->with('success', 'Role updated successfully.');
}
```

---

## 3. Coordinator Features

### Feature: Defense Scheduling & Management
Coordinators receive defense requests, check faculty availability, and schedule defense panels.

**Key Routes:**
- `Resource /coordinator/defense` (DefenseScheduleController)
- `POST /coordinator/defense/{defenseSchedule}/complete`

**Code Reference (`DefenseScheduleController.php`):**
```php
// Creating a schedule and assigning panel members
public function storeSchedule(Request $request, DefenseRequest $defenseRequest) {
    $schedule = DefenseSchedule::create([
        'group_id' => $defenseRequest->group_id,
        'academic_term_id' => $activeTerm->id,
        'schedule_date' => $request->schedule_date,
        'start_time' => $request->start_time,
        'end_time' => $request->end_time,
        'venue' => $request->venue,
    ]);

    // Assigning panel members
    foreach ($request->panel_members as $role => $facultyId) {
        DefensePanel::create([
            'defense_schedule_id' => $schedule->id,
            'faculty_id' => $facultyId,
            'role' => $role,
            'status' => 'pending' // Panel members must accept invitations
        ]);
    }
}
```

---

## 4. Adviser / Faculty Features

### Feature: Advising Groups & Panel Invitations
Advisers can manage their advisory groups, view group progress, and accept/decline panel invitations from the coordinator.

**Key Routes:**
- `GET /adviser/groups` (AdviserController)
- `GET /adviser/panel-invitations` (AdviserController)
- `POST /adviser/panel-invitations/{panel}/respond`

**Code Reference (`AdviserController.php`):**
```php
// Responding to a panel invitation
public function respondToPanelInvitation(Request $request, DefensePanel $panel) {
    $user = Auth::user();
    if ($panel->faculty_id !== $user->id || !$panel->isPending()) {
        return back()->with('error', 'Unauthorized or already responded.');
    }

    if ($request->response === 'accept') {
        $panel->accept(); // Custom model method to update status to 'accepted'
    } else {
        $panel->decline();
    }
    return back()->with('success', 'Panel invitation updated.');
}
```

### Feature: Threaded Feedback & Task Comments
Faculty can leave detailed, nested (threaded) comments on student milestone tasks.

**Code Reference (`AdviserController.php`):**
```php
public function storeMilestoneTaskComment(Request $request, Group $group, GroupMilestoneTask $groupMilestoneTask) {
    TaskComment::create([
        'group_milestone_task_id' => $groupMilestoneTask->id,
        'user_id' => Auth::id(),
        'body' => $request->body,
        'parent_id' => $request->parent_id, // For threaded replies
    ]);
    ActivityLogService::logTaskCommentAdded($groupMilestoneTask, Auth::user(), null);
    NotificationService::adviserCommentOnMilestoneTask(Auth::user(), $groupMilestoneTask);
}
```

---

## 5. Student Features

### Feature: Group Formation & Invitations
Students form groups, invite peers, and invite a faculty member to be their adviser.

**Key Routes:**
- `GET/POST /student/group` (StudentGroupController)
- `POST /student/group/invite-member`
- `POST /student/group/invite-adviser`

**Code Reference (`StudentGroupController.php`):**
```php
public function inviteAdviser(Request $request) {
    $group = Auth::guard('student')->user()->student->group;
    AdviserInvitation::create([
        'group_id' => $group->id,
        'faculty_id' => $request->faculty_id,
        'status' => 'pending'
    ]);
    // Notify the faculty member
}
```

### Feature: Project Submissions & Versioning
Students submit project proposals and final reports. The system handles versioning and allows side-by-side comparison of different versions.

**Key Routes:**
- `Resource /student/project` (ProjectSubmissionController)
- `GET /student/project/submissions/{left}/compare/{right}`

**Code Reference (`ProjectSubmissionController.php`):**
```php
// Handling a new submission version
public function store(Request $request) {
    $student = Auth::guard('student')->user()->student;
    $path = $request->file('file')->store('submissions', 'public');
    
    // Auto-increment logic for submission versioning
    $nextVersion = ProjectSubmission::getNextVersionFor($student->student_id, $request->type);

    ProjectSubmission::create([
        'student_id' => $student->student_id,
        'file_path' => $path,
        'type' => $request->type, // e.g., 'proposal'
        'version' => $nextVersion,
        'status' => 'pending',
    ]);
}
```

### Feature: Milestone Tracking
Students track their progress through checklists and milestone tasks.

**Key Routes:**
- `Resource /student/milestones` (StudentMilestoneController)
- `PATCH /student/milestones/tasks/{taskId}/move` (Kanban-style movement)

---

## 6. Shared & Cross-Role Features

### Feature: Activity Logging
The system tracks important actions (submissions, milestone completions, comments) and displays an activity log for advisers and coordinators.

**Code Reference (`AdviserController@getRecentActivities`):**
```php
// Extracting recent activities for an adviser's dashboard
$submissionActivities = ProjectSubmission::with('student')
    ->whereIn('student_id', $studentIds)
    ->latest('submitted_at')
    ->take(10)
    ->get()
    ->map(function ($submission) {
        return (object) [
            'title' => 'Group submitted ' . ucfirst($submission->type),
            'description' => 'Uploaded by ' . $submission->student->name,
            'icon' => 'file-alt',
            'created_at' => $submission->submitted_at,
        ];
    });
```

### Feature: System Notifications
Real-time database notifications across all roles for invitations, task assignments, and submission updates.

**Code Reference (Notification mark as read):**
```php
public function markNotificationAsRead(Notification $notification) {
    $notification->update(['is_read' => true]);
    return response()->json(['success' => true]);
}
```

---

### Summary
The `CapTracks` application is a robust Laravel monolithic application heavily utilizing Eloquent ORM, customized Middlewares for access control (e.g., `checkrole`), grouped Routing, and Blade templating to provide distinct portals for Students, Advisers, Coordinators, and Chairpersons.
