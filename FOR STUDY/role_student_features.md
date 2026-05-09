# Student Features

Students manage group formations, submit deliverables, track capstone milestones, manage specific tasks, and request defense schedules.

## Artifact types (how this doc labels code)

Every snippet is tagged so you can tell **what kind of file** it comes from:

| Tag | Meaning |
|-----|---------|
| **`[Middleware]`** | `app/Http/Middleware/*.php` — runs before controllers; no UI. |
| **`[Controller]`** | `app/Http/Controllers/*.php` — HTTP actions, validation, redirects/JSON. |
| **`[Service]`** | `app/Services/*.php` — reusable domain logic injected into controllers. |
| **`[JS]`** | Inline or stacked scripts in `resources/views/**/*.blade.php` (browser). |

Student routes also use **`[Middleware]`** (`StudentAuthMiddleware`, `CheckStudentPasswordChange`); see §11.

Line-by-line tables below each fence refer to the **lines inside that same fence**, not necessarily original project line numbers.

## 🔄 User Journey Flow (Top to Bottom)
If panelists ask for the "System Workflow" or "Use Case" of a Student, explain this exact step-by-step flow:
1. **Secure Account:** The student logs in for the first time and the middleware blocks them until they change their default password.
2. **Form a Group:** They search for classmates by ID and invite them to form a Capstone Group.
3. **Find an Adviser:** They send an Adviser Invitation to a faculty member from the available list.
4. **Work on Milestones:** They access their Kanban Board, uploading documents to specific tasks and dragging the cards from 'Pending' to 'Done'.
5. **Revise Documents:** When the adviser leaves feedback, the student uploads a new document which automatically increments the `version` number.
6. **Request Defense:** Once their milestone progress hits an acceptable threshold, they submit a Defense Request to their Coordinator.


## 1. Password management (current code)

**Description:** First-time / forced password changes use the `must_change_password` flag on the **student account** (`student_accounts`), not a hard-coded `password123` check. **`CheckStudentPasswordChange`** redirects until the flag is cleared; **`StudentPasswordController::updatePassword`** validates, hashes, and clears the flag.

**`[Middleware]` — `app/Http/Middleware/CheckStudentPasswordChange.php` (excerpt):**
```php
public function handle(Request $request, Closure $next): Response
{
    if (Auth::guard('student')->check()) {
        $studentAccount = Auth::guard('student')->user();

        if ($studentAccount->must_change_password &&
            !$request->routeIs('student.change-password') &&
            !$request->routeIs('student.update-password')) {

            return redirect()->route('student.change-password')
                ->with('warning', 'You must change your password before continuing.');
        }
    }

    return $next($request);
}
```

| Line (in fence) | What it does |
|-----------------|--------------|
| 1 | Declares middleware `handle`; must return a `Response`. |
| 2–3 | If the **student** guard has a logged-in account, continue checks. |
| 4 | Reads `StudentAccount`; **`must_change_password`** is the business flag. |
| 6–8 | Only redirect if flag is true **and** the route is not the password form or POST update (those are exempt via `withoutMiddleware` on routes too). |
| 10–11 | Flash warning and send user to **`student.change-password`**. |
| 15 | Otherwise pass the request down the stack. |

**`[Controller]` — `app/Http/Controllers/StudentPasswordController.php` — `updatePassword()` (excerpt):**
```php
$student = Auth::guard('student')->user();
$isFirstTime = is_null($student->password);

$rules = [
    'new_password' => 'required|min:8|confirmed',
    'new_password_confirmation' => 'required',
];
if (!$isFirstTime) {
    $rules['current_password'] = 'required';
}
// ... Validator::make, optional Hash::check(current_password) ...

$student->update([
    'password' => Hash::make($request->new_password),
    'must_change_password' => false,
]);

return redirect()->route('student.dashboard')->with('success', $message);
```

| Line (in fence) | What it does |
|-----------------|--------------|
| 1–2 | Resolver for account under **`student`** guard; **`isFirstTime`** when password never set. |
| 4–8 | Rules: min length + confirmation; **current** password required only when one already exists. |
| (omitted) | Failed validation → back with errors; wrong current password → field error. |
| 12–15 | Persist bcrypt hash; clear **`must_change_password`** so middleware stops blocking. |
| 18 | Redirect to dashboard with success flash. |

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

## 3. Project submissions & versioning (student `ProjectSubmissionController::store`)

**Description:** **`[Controller]`** `ProjectSubmissionController::store` validates MIME/type rules, stores the file, computes the next **version** per student + type, creates a **`project_submissions`** row, and writes an **`ActivityLog`** entry.

**Core logic — `app/Http/Controllers/ProjectSubmissionController.php` (student path, condensed to match repo):**
```php
public function store(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:pdf,doc,docx,zip,pptx,ppt|max:10240',
        'type' => 'required|in:proposal,final,other',
        'description' => 'nullable|string|max:1000',
    ]);
    // Extension-vs-type cross-check ($allowedExtensionsByType) omitted here — see full file.

    $studentAccount = Auth::guard('student')->user();
    $student = $studentAccount->student;

    $path = $request->file('file')->store('submissions', 'public');
    $nextVersion = ProjectSubmission::getNextVersionFor($student->student_id, $request->type);

    $submission = ProjectSubmission::create([
        'student_id' => $student->student_id,
        'file_path' => $path,
        'type' => $request->type,
        'version' => $nextVersion,
        'status' => 'pending',
        'submitted_at' => now(),
        'title' => $this->getSubmissionTitle($request->type),
        'objectives' => $request->description,
    ]);

    ActivityLog::create([
        'student_id' => $student->student_id,
        'action' => 'submission_uploaded',
        'description' => 'Uploaded ' . ($submission->title ?? 'project submission') . ' (v' . ($submission->version ?? 1) . ')',
        'loggable_type' => ProjectSubmission::class,
        'loggable_id' => $submission->id,
    ]);

    return redirect()->route('student.project')->with('success', 'Submission uploaded successfully!');
}
```

| Line (in fence) | What it does |
|-----------------|--------------|
| 1–6 | **`[Controller]`** Validates file types, submission **type** enum, optional description. |
| 10–12 | Resolve authenticated **`StudentAccount`** → related **`Student`**. |
| 14 | Store under **`storage/app/public/submissions`**. |
| 15 | **`getNextVersionFor`** = MAX(version)+1 for that `student_id` + `type`. |
| 17–26 | **`INSERT`** new row—old versions remain for history/compare. |
| 28–35 | Audit trail for advisers/coordinators. |
| 37 | Redirect to student project index. |

### 🧠 Defense Tip: How does Document Versioning work?
If a panelist asks: *"How do you keep track of old files without overwriting them?"*
**Your Answer:** *"Instead of simply updating the existing database row when a student uploads a revision, the system runs a query to find the `MAX(version)` for that specific document type, adds `+ 1` to it, and creates a brand new row in the `project_submissions` table. Because we create a new row every time, we preserve the old file paths in the database. This is exactly what allows us to load two different versions side-by-side for comparison."*

## 4. Milestone Kanban & checklist

**Description:** Task status values in this codebase are **`pending`**, **`doing`**, **`done`** (not `todo` / `in_progress`). **`[Controller]`** `StudentMilestoneController::moveTask` authorizes the student’s group, validates status, delegates to the **`GroupMilestoneTask`** model, and returns JSON including **`milestone_progress`**.

**Core logic — `StudentMilestoneController::moveTask` (excerpt):**
```php
public function moveTask(Request $request, $taskId)
{
    $student = $this->getAuthenticatedStudent();
    if (!$student) {
        return response()->json(['success' => false, 'message' => 'Not authenticated']);
    }
    $task = GroupMilestoneTask::find($taskId);
    if (!$task) {
        return response()->json(['success' => false, 'message' => 'Task not found']);
    }
    $group = $student->groups()->first();
    if (!$group || $task->groupMilestone->group_id !== $group->id) {
        return response()->json(['success' => false, 'message' => 'Not authorized']);
    }
    $request->validate([
        'status' => 'required|in:pending,doing,done'
    ]);
    $task->updateStatus($request->status);

    return response()->json([
        'success' => true,
        'message' => 'Task moved successfully',
        'task' => $task->fresh(),
        'milestone_progress' => $task->groupMilestone->calculateProgressPercentage()
    ]);
}
```

| Line (in fence) | What it does |
|-----------------|--------------|
| 1–5 | **`[Controller]`** Require authenticated capstone **`Student`**. |
| 6–12 | Load task; ensure it belongs to the student’s **group**. |
| 13–15 | Whitelist **`status`** to Kanban columns used in UI/DB. |
| 16 | **`updateStatus`** on the model updates DB + triggers progress logic internally. |
| 18–24 | JSON for SortableJS frontend: success + fresh task + **`milestone_progress`**. |

**`[JS]`** — Kanban persistence lives in **`resources/views/student/milestones/show.blade.php`** (`PATCH` `/student/milestones/tasks/{id}/move`); see **§12** for line-by-line code and **how the percentage auto-updates in the browser**.

### 4.1 `StudentMilestoneController` — all public methods (`[Controller]`)

File: `app/Http/Controllers/StudentMilestoneController.php`. Routes are under prefix **`/student`**, names like **`student.milestones.*`** (see `routes/web.php`).

| Method | Route (typical) | What it does |
|--------|-----------------|--------------|
| **`index()`** | `GET /student/milestones` | Lists milestones for the student’s group; computes **`overallProgress`**; loads templates, assigned tasks, recent submissions; or empty state if no group. |
| **`show($milestoneId)`** | `GET /student/milestones/{milestone}` | **Kanban page:** loads one **`GroupMilestone`**, buckets tasks into **`pending` / `doing` / `done`**, computes display **`progress`**, sets **`isGroupLeader`**. |
| **`edit($milestoneId)`** | `GET /student/milestones/{milestone}/edit` | Leader-only form to edit milestone title/description/due date. |
| **`update(Request, $milestoneId)`** | `PUT /student/milestones/{milestone}` | Leader-only: validates and updates **`GroupMilestone`** fields. |
| **`destroy($milestoneId)`** | `DELETE /student/milestones/{milestone}` | Leader-only: deletes all **`groupTasks`** then the **`GroupMilestone`**. |
| **`moveTask(Request, $taskId)`** | `PATCH /student/milestones/tasks/{taskId}/move` | **JSON:** validates status, **`updateStatus`**, returns **`milestone_progress`** (used by Kanban JS). |
| **`bulkUpdateTasks(Request, $milestoneId)`** | `PATCH /student/milestones/{milestoneId}/bulk-update` | **JSON:** mass **`updateStatus`** for many tasks; recomputes milestone progress. |
| **`recomputeProgress(Request, $milestoneId)`** | `POST /student/milestones/{milestoneId}/recompute-progress` | **JSON or redirect:** calls **`calculateProgressPercentage()`** if totals drift (optional manual refresh). |
| **`updateTask(Request, $taskId)`** | `PATCH /student/task/{task}` | **JSON:** milestone index checkboxes — toggles **`is_completed`**, syncs **`status`** to **`done`** / **`pending`**. |
| **`storeTaskComment(Request, $groupMilestoneTask)`** | `POST .../tasks/{task}/comments` | Validates body/threaded **`parent_id`**, creates **`TaskComment`**, **`ActivityLogService::logTaskCommentAdded`**. |
| **`updateMultipleTasks(Request, $milestoneId)`** | `PATCH /student/milestones/{milestone}/update-tasks` | Form POST from bulk checklist: updates tasks **`done`** / **`pending`** from **`completed_tasks[]`**, then **`calculateProgressPercentage`**, redirects back to **`show`**. |
| **`assignTask(Request, $groupMilestoneTask)`** | `PATCH /student/task/{task}/assign` | Leader-only: assigns **`assigned_to`** to a group member **`student_id`**. |
| **`unassignTask($groupMilestoneTask)`** | `DELETE /student/task/{task}/unassign` | Leader-only: clears **`assigned_to`**. |

**Private helpers (same class):** `getAuthenticatedStudent`, `calculateGroupProgress`, `getStudentTasks`, `getRecentSubmissions`, `getMilestoneTasksByStatus`, `calculateMilestoneProgress` — support queries and dashboard math; **not** HTTP endpoints.

**Related `[Controller]`:** `StudentMilestoneChecklistController` — separate checklist view across templates (not listed above).

### 4.2 Backend chain: drag → stored percentage → JSON for JS (`[Model]`)

Kanban moves do **not** compute progress in JavaScript. The browser sends **`status`** only; Laravel persists and recomputes.

1. **`StudentMilestoneController::moveTask`** calls **`GroupMilestoneTask::updateStatus($status)`** (`app/Models/GroupMilestoneTask.php`).
2. **`updateStatus`** writes **`status`** and **`is_completed`** (`true` when **`done`**), then calls **`$this->groupMilestone->calculateProgressPercentage()`**.
3. **`GroupMilestone::calculateProgressPercentage()`** (`app/Models/GroupMilestone.php`): counts all **`groupTasks`**, counts those with **`status === 'done'`**, sets **`progress_percentage`** on **`group_milestones`**, returns the integer **percentage**.
4. The controller returns that number again as **`milestone_progress`** in the JSON response so the page can repaint the bar **without recalculating on the client**.

### 🧠 Defense Tip: How does milestone progress stay consistent?
If a panelist asks: *"Where is progress calculated?"*
**Your Answer:** *"On the server: **`GroupMilestone::calculateProgressPercentage`** runs whenever **`GroupMilestoneTask::updateStatus`** completes—it stores **`(done tasks / total tasks) × 100`** on **`group_milestones.progress_percentage`**. **`moveTask`** returns that value as **`milestone_progress`** in JSON. The Kanban script only **displays** that number (`updateProgressBarUI`); it does not derive percentages from the DOM."*


## 5. Task submissions (milestone task artifacts)

**Description:** **`[Controller]`** `TaskSubmissionController::store` validates **`submission_type`**, optional file, assignment rules, then creates **`task_submissions`** (and may mirror a **`ProjectSubmission`** for traceability). Paths use **`task-submissions`** on the `public` disk.

**Core logic — `TaskSubmissionController::store` (excerpt, see file for full branch):**
```php
public function store(Request $request, $taskId)
{
    $student = $this->getAuthenticatedStudent();
    $task = GroupMilestoneTask::with(['groupMilestone.group'])->findOrFail($taskId);
    $group = $student->groups()->first();
    if (!$group || $task->groupMilestone->group_id !== $group->id) { /* ... */ }
    if ($task->assigned_to && $task->assigned_to !== $student->student_id) { /* ... */ }

    $request->validate([
        'submission_type' => 'required|in:document,screenshots,progress_notes',
        'description' => 'required|string|min:10',
        'notes' => 'nullable|string|max:1000',
        'progress_percentage' => 'nullable|integer|min:0|max:100',
        'file' => 'required_if:submission_type,document,screenshots|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:10240',
    ]);
    $filePath = $request->hasFile('file')
        ? $request->file('file')->store('task-submissions', 'public')
        : null;

    TaskSubmission::create([
        'group_milestone_task_id' => $taskId,
        'student_id' => $student->student_id,
        'submission_type' => $request->submission_type,
        'file_path' => $filePath,
        'description' => $request->description,
        'notes' => $request->notes,
        'progress_percentage' => $request->progress_percentage ?? 0,
        'status' => 'pending',
    ]);
    // Additional ProjectSubmission + status updates follow in the full controller.
}
```

| Line (in fence) | What it does |
|-----------------|--------------|
| 1–7 | **`[Controller]`** Auth + group ownership + optional **assignee** check. |
| 9–15 | Validate submission shape; file required only for document/screenshots. |
| 16–18 | Store upload under **`public/task-submissions`**. |
| 20–29 | Persist **`TaskSubmission`** row linked to **`group_milestone_task_id`**. |

## 6. Defense requests

**Description:** **`[Controller]`** `StudentDefenseRequestController::store` checks authentication, group + adviser, duplicate **pending/approved** requests and active **DefenseSchedule** rows, validates **`defense_type`**, then runs **`[Service]`** `DefenseMilestoneGateService::evaluate`. On success it creates **`defense_requests`** with **`requested_at`**.

**Core logic — `StudentDefenseRequestController::store` (excerpt):**
```php
public function store(Request $request)
{
    $student = $this->getAuthenticatedStudent();
    $group = $student->groups()->first();

    $request->validate([
        'defense_type' => 'required|in:proposal,60_percent,100_percent',
    ]);
    if (!$this->canCreateDefenseRequest($group->id)) {
        return redirect()->route('student.defense-requests.index')
            ->withErrors(['pending' => 'You already have an active defense process...']);
    }
    $gate = $this->defenseMilestoneGateService->evaluate($group, $request->defense_type);
    if (!$gate['eligible']) {
        return redirect()->route('student.defense-requests.index')
            ->withErrors(['milestone' => $gate['message'] . ' Student requests are blocked until this milestone is complete.']);
    }

    $defenseRequest = DefenseRequest::create([
        'group_id' => $group->id,
        'defense_type' => $request->defense_type,
        'student_message' => null,
        'status' => 'pending',
        'requested_at' => now(),
    ]);
    ActivityLog::create([ /* defense_requested */ ]);
    return redirect()->route('student.defense-requests.index')->with('success', '...');
}
```

| Line (in fence) | What it does |
|-----------------|--------------|
| 1–3 | Resolve **`Student`** and **first** group membership. |
| 5–7 | Defense stage slug validation only (no preferred date in this `store`). |
| 8–11 | **`canCreateDefenseRequest`** blocks overlapping pipeline (see private method in controller). |
| 12–16 | **`[Service]`** Gate returns **`eligible` + message**; failures flash **`milestone`**. |
| 18–24 | Insert request; **`student_message`** currently null from this action. |
| 26–27 | Activity log + redirect (full code in repo). |

**`[Service]` — `DefenseMilestoneGateService::evaluate` (excerpt):**
```php
public function evaluate(Group $group, string $stage): array
{
    if ($this->isProposalStage($stage)) {
        return $this->evaluateProposalStage($group);
    }
    $requiredOrder = $this->stageToOrder($stage);
    $group->loadMissing('groupMilestones.milestoneTemplate');
    $milestone = $group->groupMilestones->first(function ($groupMilestone) use ($requiredOrder) {
        return (int) ($groupMilestone->milestoneTemplate->sequence_order ?? 0) === $requiredOrder;
    });
    $isComplete = ((int) $milestone->progress_percentage >= 100)
        || in_array((string) $milestone->status, ['completed', 'done'], true);
    return [
        'eligible' => $isComplete,
        'message' => $isComplete ? '... complete.' : '... not complete yet ...',
    ];
}
```

*(Proposal stage instead requires an **approved** `project_submissions` row with `type = proposal` for a member—see `evaluateProposalStage` in the same service file.)*

## 8. Critical Code Line-by-Line Breakdown (For 1000% Defense Readiness)

If your panelists want you to explain the code line-by-line, memorize these three most complex and critical Student functions.

### A. Document versioning (`[Controller]` `ProjectSubmissionController@store`)
Panel Question: *"Explain line-by-line how the system prevents students from overwriting old proposal versions when they upload a new one."*

```php
// Student branch after validation + extension checks — see §3 for full method.
$path = $request->file('file')->store('submissions', 'public');
$nextVersion = ProjectSubmission::getNextVersionFor($student->student_id, $request->type);
$submission = ProjectSubmission::create([
    'student_id' => $student->student_id,
    'file_path' => $path,
    'type' => $request->type,
    'version' => $nextVersion,
    'status' => 'pending',
    'submitted_at' => now(),
    'title' => $this->getSubmissionTitle($request->type),
    'objectives' => $request->description,
]);
```

| # | What it does |
|---|----------------|
| 1 | **`store()`** on `public` disk under `submissions/`. |
| 2 | **`[Model]`** `getNextVersionFor` returns next version number (new row each upload). |
| 3–12 | **`[Controller]`** `create`: new row = full history; also sets **`submitted_at`**, human **title**, **objectives** from description. |

### B. Kanban move (`[Controller]` `StudentMilestoneController@moveTask`)
Panel Question: *"What happens when a student drags a card between columns?"*

```php
$request->validate(['status' => 'required|in:pending,doing,done']);
$task->updateStatus($request->status);
return response()->json([
    'success' => true,
    'milestone_progress' => $task->groupMilestone->calculateProgressPercentage()
]);
```

| # | What it does |
|---|----------------|
| 1 | Whitelist **`pending` / `doing` / `done`** — matches Blade **`data-status`**. |
| 2 | **`[Model]`** `updateStatus` encapsulates DB updates + milestone progress refresh. |
| 3–6 | JSON response consumed by **`[JS]`** in `milestones/show.blade.php` to repaint the bar. |

### C. Defense pipeline (`[Controller]` + `[Service]`)
Panel Question: *"How does the system block defense requests until prerequisites are met?"*

```php
$request->validate(['defense_type' => 'required|in:proposal,60_percent,100_percent']);
if (!$this->canCreateDefenseRequest($group->id)) { /* redirect pending */ }
$gate = $this->defenseMilestoneGateService->evaluate($group, $request->defense_type);
if (!$gate['eligible']) {
    return redirect()->route('student.defense-requests.index')
        ->withErrors(['milestone' => $gate['message'] . ' Student requests are blocked until this milestone is complete.']);
}
DefenseRequest::create([
    'group_id' => $group->id,
    'defense_type' => $request->defense_type,
    'student_message' => null,
    'status' => 'pending',
    'requested_at' => now(),
]);
```

| # | What it does |
|---|----------------|
| 1 | Only validates defense **type** (no calendar fields in current `store`). |
| 2 | **`canCreateDefenseRequest`**: no concurrent pending/approved request; no active scheduled defense. |
| 3–7 | **`[Service]`** `evaluate`: proposal ⇒ approved proposal submission; else ⇒ milestone **sequence_order** + completion. |
| 8–14 | Persist **`DefenseRequest`** + timestamps when allowed. |

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
- `index()`: Milestone list + overall group progress, templates, assigned tasks, recent submissions.
- `show($milestoneId)`: **Kanban** — tasks split into **`pending` / `doing` / `done`**; header **`progress`**; leader flag for assign UI.
- `edit($milestoneId)` / `update()` / `destroy($milestoneId)`: **Group leader only** — edit milestone metadata, or delete milestone (+ its tasks).
- `moveTask(Request, $taskId)`: **JSON** — PATCH drag-and-drop; statuses **`pending`/`doing`/`done`**; returns **`milestone_progress`** for JS bar update.
- `bulkUpdateTasks(Request, $milestoneId)`: **JSON** — batch status updates for many tasks in one milestone.
- `recomputeProgress(Request, $milestoneId)`: **JSON or redirect** — recomputes **`GroupMilestone::calculateProgressPercentage()`** (repair/sync).
- `updateTask(Request, $taskId)`: **JSON** — milestone **index** quick-complete checkboxes (`is_completed` ↔ **`done`**/**`pending`**).
- `updateMultipleTasks(Request, $milestoneId)`: Traditional form **`completed_tasks[]`** update (route **`student.milestones.update-tasks`**) — applies **`done`**/**`pending`** per checkbox, **`calculateProgressPercentage`**, redirect back to Kanban **`show`**.
- `assignTask()` / `unassignTask()`: **Leader only** — set/clear **`assigned_to`** on a **`GroupMilestoneTask`**.
- `storeTaskComment()`: Threaded comments + **`ActivityLogService`** entry.
- `checklist()` *(StudentMilestoneChecklistController)*: Cross-milestone checklist view (separate controller).
- `create()` / `store()` / `show()` / `review()` *(TaskSubmissionController)*: File (or notes) **task submission** pipeline for a **`GroupMilestoneTask`**.

**Proposals & Project Submissions (`StudentProposalController`, `ProjectSubmissionController`)**
- `store()` / `update()`: Handles uploading project documents, automatically calculating the next `version` number to preserve history.
- `rollback()`: Restores a previous document version if the adviser rejects the newest one.
- `previewVersion()` / `studentPreviewSubmission()`: Opens the uploaded PDF or document in an embedded browser viewer.
- `compareVersions()` / `studentCompareSubmissions()`: Loads two versions side-by-side to visually inspect what was changed.

**Defense Management (`StudentDefenseRequestController`)**
- `index()` / `create()` / `store()`: List and create defense requests; **`store`** validates **`defense_type`**, runs **`DefenseMilestoneGateService`**, sets **`requested_at`** (no preferred date fields in current `store`).
- `show()` / `cancel()`: View one request; **`cancel`** only while **pending**.

**Calendar & Scheduling (`CalendarController`)**
- `studentCalendar()`: Fetches the single, specific, approved defense schedule for the student's group and plots it on the calendar.

**Authentication (`AuthController`)**
- `login()` / `logout()`: Validates credentials against the encrypted `password` column and manages session tokens.
- `changePassword()`: Receives a new password, hashes it using `bcrypt()`, and updates the user's account row.

---

## 10. 🎤 The "Cheat Sheet" Defense Scripts
If a panelist points at these functions and asks you to explain them line-by-line without reading the syntax, use these exact scripts:

### A. Document versioning (`ProjectSubmissionController@store`)
**The Code:**
```php
$path = $request->file('file')->store('submissions', 'public');
$nextVersion = ProjectSubmission::getNextVersionFor($student->student_id, $request->type);
ProjectSubmission::create([
    'student_id' => $student->student_id,
    'file_path' => $path,
    'type' => $request->type,
    'version' => $nextVersion,
    'status' => 'pending',
    'submitted_at' => now(),
    'title' => $this->getSubmissionTitle($request->type),
    'objectives' => $request->description,
]);
```
**Panel Question:** *"How does the system prevent students from overwriting old proposal versions?"*
* **The Goal:** To securely upload a student's document while keeping a complete history.
* **The Process:** Find the highest version number the student currently has, and add +1 to it.

> *"Sir, the goal of this function is to securely upload a student's document without deleting revisions.*
> *First, the system saves the uploaded physical file into our server.*
> *Next, it queries the database to find the highest version number the student already has for this document type using `getNextVersionFor`. If they uploaded Version 1, the system assigns the new file as Version 2.*
> *Because we create a brand new database row instead of updating the old one, the old file path is never overwritten, allowing the adviser to compare Version 1 against Version 2 side-by-side."*

### B. Kanban move (`StudentMilestoneController@moveTask`)
**The Code:**
```php
$request->validate(['status' => 'required|in:pending,doing,done']);
$task->updateStatus($request->status);
return response()->json([
    'milestone_progress' => $task->groupMilestone->calculateProgressPercentage()
]);
```
**Panel Question:** *"How does the Kanban header percentage stay correct after a drag?"*
* **The Goal:** Single source of truth on the server; browser only **displays** the returned number.
* **The Process:** **`moveTask`** → **`GroupMilestoneTask::updateStatus`** → **`GroupMilestone::calculateProgressPercentage`** (stores **`progress_percentage`**) → JSON **`milestone_progress`** → **`updateProgressBarUI`** sets bar width and colors—**no percentage math in JS**.

> *"Sir, when the student drops a card, we PATCH the new status. Laravel updates the task row and recomputes **`(done tasks ÷ all tasks) × 100`** on the parent **`group_milestones`** row. That integer comes back as **`milestone_progress`**. Our JavaScript does not derive the percentage from counting DOM nodes for the header—it just applies **`data.milestone_progress`** to the Bootstrap progress bar and label."*

### C. Student password middleware (`[Middleware]` `CheckStudentPasswordChange@handle`)
**The Code:**
```php
if (Auth::guard('student')->check()) {
    $studentAccount = Auth::guard('student')->user();
    if ($studentAccount->must_change_password &&
        !$request->routeIs('student.change-password') &&
        !$request->routeIs('student.update-password')) {
        return redirect()->route('student.change-password')
            ->with('warning', 'You must change your password before continuing.');
    }
}
return $next($request);
```
**Panel Question:** *"How do you force newly imported students to change their password on first login?"*
* **The Goal:** Enforce account security when **`must_change_password`** is set on **`StudentAccount`**.
* **The Process:** Middleware runs on `/student/*`; exempt routes allow POSTing the new password.

> *"Sir, we store a boolean **`must_change_password`** on the student account. Middleware checks it on every student request; if true, we redirect to the change-password routes unless the request is already those routes. **`StudentPasswordController::updatePassword`** hashes the password and clears the flag."*

### D. Defense gate + request (`[Controller]` + `[Service]`)
**The Code:**
```php
$request->validate(['defense_type' => 'required|in:proposal,60_percent,100_percent']);
$gate = $this->defenseMilestoneGateService->evaluate($group, $request->defense_type);
if (!$gate['eligible']) {
    return redirect()->route('student.defense-requests.index')
        ->withErrors(['milestone' => $gate['message']]);
}
DefenseRequest::create([
    'group_id' => $group->id,
    'defense_type' => $request->defense_type,
    'student_message' => null,
    'status' => 'pending',
    'requested_at' => now(),
]);
```
**Panel Question:** *"How does the system physically stop a student from requesting a defense if they aren't done?"*
* **The Goal:** To validate milestone completion before allowing defense requests.
* **The Process:** Feed the group data into the `DefenseMilestoneGateService`. If ineligible, abort.

> *"Sir, we built a dedicated `DefenseMilestoneGateService`. Before the defense request is even saved to the database, the system sends the group's current progress data into this service. The service verifies if their global progress matches the strict requirements for the defense type (for example, 100% completion). If the service returns false, the controller aborts the creation and throws an error back to the student, physically preventing the request from proceeding."*

---

## 9. Methods Used (Simple Terms)

- `pluck('column')` - Gets only one column from query results (like IDs) instead of full rows.
- `whereIn('column', [...])` - Filters records that match any value in a list.
- `whereNotIn('column', [...])` - Excludes records that match values in a list.
- `withCount('relation')` - Adds relation counts without manual loops.
- `whereHas('relation', fn...)` - Filters by conditions inside related tables.
- `first()` - Gets the first matching row or `null`.
- `findOrFail(id)` - Finds by ID or throws a not found error.
- `create([...])` - Inserts a new database row.
- `update([...])` - Updates fields of existing rows.
- `delete()` - Removes a row.
- `exists()` - Returns true/false if any matching row exists.
- `collect([...])` - Creates a Laravel collection for chainable operations.
- `map(fn...)` - Transforms each item in a collection.
- `sortBy(...)` - Sorts collection items by one or more rules.
- `take(n)` - Gets only the first `n` items.
- `values()` - Reindexes collection keys to clean 0..n numbering.
- `unique('field')` - Removes duplicates by field.
- `toArray()` - Converts data to plain PHP array.
- `return back()->withErrors(...)->withInput()` - Returns user to form with errors and keeps previous input.
- `DB::beginTransaction()/commit()/rollback()` - All-or-nothing database save flow.
- `Carbon::parse(...)` - Converts date/time text into a date object.
- `response()->json([...])` - Returns JSON for frontend scripts.

### Symbols / Operators (Q&A quick guide)
- `?` (ternary) - Short if/else in one line.
- `??` (null coalescing) - Use fallback value when left side is `null`.
- `?:` (elvis shorthand) - Use left side if truthy, otherwise fallback.
- `?->` (null-safe operator) - Access property/method only if object is not `null`.
- `=>` - Key/value separator in arrays, and short function arrow syntax.
- `===` - Strict comparison (value and type must match).

## 10. Quick Oral Cheat Sheet (Top 10 Terms)

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

## 11. Codebase-aligned reference (defense study)

This section matches the **current CapTracks codebase** for the student role: **`routes/web.php`** (`student.*`), controllers under `app/Http/Controllers`, middleware **`StudentAuthMiddleware`** and **`CheckStudentPasswordChange`**, and Blade under `resources/views/student`. Use it when panelists ask for **exact** class names and services.

### Routing and security

- Student routes use prefix **`/student`**, names **`student.*`**, middleware **`StudentAuthMiddleware`** and **`CheckStudentPasswordChange`** (both **`[Type: Middleware]`**).
- **`CheckStudentPasswordChange`** is **skipped** on `student.change-password` and `student.update-password` so first-time password setup works.
- Authentication uses the **`student`** guard; the logged-in model links to **`Student`** records for capstone data.

### Controllers (what each does)

All entries below are **`[Type: Controller]`** — PHP classes under `app/Http/Controllers/`.

| Controller | Purpose |
|------------|---------|
| **`StudentDashboardController`** | Main hub: resolves student (**checks `student` guard first** to avoid conflicts if a faculty `web` session exists). Loads group (adviser, invitations, defenses, offering), computed progress, task stats, milestone snippets, deadlines, adviser/defense summaries, unread notifications (`visibleToStudent`), latest proposal submission, offering summary via **`getCurrentOffering()`**. Heavy logic lives in **private helpers**—good defense phrase: “aggregation layer for the dashboard view.” |
| **`StudentPasswordController`** | If **`must_change_password`** is false, redirects away from change form. Validates **current** password unless first-time (`password` null). Hashes new password, clears **`must_change_password`**. |
| **`StudentController`** | Redirect index to dashboard; **notifications** list with **`visibleToStudent`**; JSON handlers for mark read / delete (single, all, multiple) using **`NotificationService`** where applicable. |
| **`StudentGroupController`** | Create group (requires enrollment in an offering via **`getCurrentOffering()`**), invite members from same offering/semester excluding invalid peers, adviser invitations, accept/decline/cancel, remove member. Uses **`NotificationService`** for adviser-related notices. |
| **`StudentProposalController`** | Proposal workflow: versions, status vs defense requests, create/store with validation and file upload, edit/update, rollback, **preview** and **compare** via **`DocumentPreviewService::panelForSubmission`**. Routes: **`student.proposal`** (`/proposal`). |
| **`ProjectSubmissionController`** | General **project** submissions (not only proposal): index/create/store/show/destroy for students; **preview** and **compare** use **`DocumentPreviewService`**. Student views live under **`student/project/*`**; routes **`student.project`**. |
| **`StudentMilestoneController`** | Full list of public actions in **§4.1**. Kanban: **`show`** + **`moveTask`** (returns **`milestone_progress`** for JS). **`ActivityLogService::logTaskCommentAdded`** on comments. Leader-only edit/delete/assign where enforced. |
| **`StudentMilestoneChecklistController`** | Loads all **`MilestoneTemplate`** tasks and maps **`GroupMilestoneTask`** status for the student’s group—cross-milestone checklist view. |
| **`StudentDefenseRequestController`** | Lists requests for the group; **create/store** only if adviser exists, no conflicting pending **`DefenseRequest`** / active **`DefenseSchedule`**, and **`DefenseMilestoneGateService::evaluate`** passes. **Cancel** only while status is pending. Injects gate service via constructor. |

### Services tied to student flows

All entries below are **`[Type: Service]`** — PHP classes under `app/Services/`.

| Service | Where | Role |
|---------|--------|------|
| **`DefenseMilestoneGateService`** | `StudentDefenseRequestController` | Before creating a request: **proposal** stage checks approved proposal submission for group members; other stages check matching **group milestone** completion (progress/status vs template order). Returns `eligible` + message. |
| **`DocumentPreviewService`** | `StudentProposalController`, `ProjectSubmissionController` | Builds preview metadata: file path, embed kind (pdf/office/etc.), iframe URL, download URL for version preview and side-by-side compare. |
| **`NotificationService`** | `StudentController`, `StudentGroupController` | Mark read; create notifications (e.g. invitations). |
| **`ActivityLogService`** | `StudentMilestoneController` | Logs actions such as task comments for accountability. |

**Usually outside student controllers but related:** `DefenseRubricService`, `DefenseEvaluationService`, `MilestoneAssignmentService`—used in coordinator/adviser/template flows; mention if questions broaden to the whole capstone module.

### Blade views that include JavaScript (`resources/views/student/`)

All rows describe **`[Type: JS]`** (inline or `@push` scripts in Blade).

| View | Typical JS behavior |
|------|---------------------|
| **`notifications.blade.php`** | Fetch/AJAX against **`student.notifications`** routes; CSRF. |
| **`milestones/show.blade.php`** | **SortableJS** drag-and-drop between Kanban columns; on drop, **`PATCH`** `student/milestones/tasks/{id}/move` with JSON; updates progress UI from JSON response. |
| **`milestones/index.blade.php`**, **`submit-task.blade.php`**, **`partials/task-card.blade.php`** | Task interactions, modals, inline updates. |
| **`group/show.blade.php`**, **`create.blade.php`**, **`edit.blade.php`**, **`invitations.blade.php`** | Invitation UX, dynamic lists, validation feedback. |
| **`proposal/index.blade.php`**, **`project/index.blade.php`**, **`project/create.blade.php`** | File inputs, confirmations, version actions. |
| **`change-password.blade.php`** | Bootstrap bundle; toggles/strength UI if present. |
| **`proposal/compare.blade.php`**, **`project/compare.blade.php`** | Compare UI paired with controllers + **`DocumentPreviewService`**. |

**Student dashboard view:** `StudentDashboardController` returns **`dashboards.student`** (under `resources/views/dashboards/`), not only `student/`.

### Proposal vs project (avoid confusion)

- **`student.proposal`** → **`StudentProposalController`** → views in **`student/proposal/`** (capstone **proposal** pipeline).
- **`student.project`** → **`ProjectSubmissionController`** → views in **`student/project/`** (broader **project submissions**, previews, compare).

### Middleware accuracy note for defense

The **`CheckStudentPasswordChange`** middleware in your repo should align with how first login works (**`must_change_password`** and/or null password). When demonstrating, **read the actual middleware file**—do not rely on illustrative snippets elsewhere in this document if they mention hashing `password123` literally.

### One-line defense summary

**Students** use a **separate guard**, optional **forced password** flow, then **group → proposals/submissions → milestones (Kanban + checklist) → defense requests**, with **`DefenseMilestoneGateService`** enforcing prerequisites and **`DocumentPreviewService`** supporting document preview/compare.

---

## 12. JavaScript in student views (full code + explanations)

Everything in this section is **`[Type: JS]`** — browser scripts embedded in Blade under `resources/views/student/` (and related `@push('scripts')` stacks) as of this documentation pass.

**Line-by-line tables:** Numbered rows map **statement-by-statement** to the JavaScript in the fence directly above (grouped rows like “8–11” mean “lines 8 through 11 of that snippet”). Use them for defense Q&A without memorizing whole blocks verbatim.

### Conventions

- **`meta[name="csrf-token"]`** supplies the token for **`fetch`** (Laravel expects it on AJAX).
- Routes are under prefix **`/student`**; many handlers **`PATCH`/`POST`** JSON to match **`StudentMilestoneController`** and related endpoints.
- **`showAlert`** in milestone pages builds Bootstrap alerts dynamically—same UX pattern as chairperson role badges but for feedback toasts.

---

### `student/notifications.blade.php`

**Purpose:** Same interaction model as chairperson notifications, but routes are **`student.notifications.*`**.

```javascript
function updateSelectedCount() {
    const selectedCount = document.querySelectorAll('.notification-checkbox:checked').length;
    const selectedCountElement = document.getElementById('selectedCount');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    if (selectedCountElement) selectedCountElement.textContent = selectedCount;
    if (deleteSelectedBtn) deleteSelectedBtn.disabled = selectedCount === 0;
}
function markAllAsRead() {
    if (!confirm('Mark all notifications as read?')) return;
    fetch('{{ route("student.notifications.mark-all-read") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    }).then(response => response.json()).then(data => {
        if (data.success) location.reload();
        else alert('Error marking notifications as read: ' + (data.message || 'Unknown error'));
    }).catch(() => alert('Error marking notifications as read'));
}
function deleteNotification(notificationId) {
    if (!confirm('Are you sure you want to delete this notification?')) return;
    const urlTemplate = '{{ route("student.notifications.delete", ["notification" => "__ID__"]) }}';
    fetch(urlTemplate.replace('__ID__', notificationId), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    }).then(response => response.json()).then(data => {
        if (data.success) location.reload();
        else alert('Error deleting notification: ' + (data.message || 'Unknown error'));
    }).catch(() => alert('Error deleting notification'));
}
function deleteSelected() {
    const selectedIds = Array.from(document.querySelectorAll('.notification-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) return;
    if (!confirm(`Are you sure you want to delete ${selectedIds.length} selected notification(s)?`)) return;
    fetch('{{ route("student.notifications.delete-multiple") }}', {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
        body: JSON.stringify({ notification_ids: selectedIds })
    }).then(response => response.json()).then(data => {
        if (data.success) location.reload();
        else alert('Error deleting notifications: ' + (data.message || 'Unknown error'));
    }).catch(() => alert('Error deleting notifications'));
}
function refreshNotifications() { location.reload(); }
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.notification-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });
    }
    document.querySelectorAll('.notification-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    updateSelectedCount();
});
```

**Line-by-line walkthrough** (same structure as chairperson notifications; routes differ.)

| # | Code | What it does |
|---|------|----------------|
| 1–7 | `updateSelectedCount` | Counts `.notification-checkbox:checked`, updates `#selectedCount`, enables/disables `#deleteSelectedBtn`. |
| 8–17 | `markAllAsRead` | `confirm` → `fetch` POST to **`student.notifications.mark-all-read`** with CSRF → JSON parse → reload on success. |
| 18–27 | `deleteNotification` | Builds DELETE URL from Blade template + `notificationId`; CSRF; reload on success. |
| 28–37 | `deleteSelected` | Collects checked ids → confirms count → DELETE with body `{ notification_ids }` → **`StudentController::deleteMultiple`** validation on server. |
| 38 | `refreshNotifications` | Full page reload shortcut. |
| 39–52 | `DOMContentLoaded` | Wires `#selectAll` to mirror all checkboxes; each row checkbox calls `updateSelectedCount`; initial count. |

**Summary:** Server-side difference is **`visibleToStudent`** scope; client JS matches chairperson **`notifications.blade.php`** behavior.

---

### `student/milestones/show.blade.php` (Kanban + progress)

**Purpose:** **SortableJS** powers drag-and-drop between columns; **`moveTask`** persists new status via **`PATCH`**; **`updateProgressBarUI`** paints the header progress from the **`milestone_progress`** integer the API returns—**no client-side math**.

#### How the Kanban percentage auto-updates (end-to-end)

This is the flow panelists usually mean by “auto update” of the percentage bar after a drag:

| Step | Layer | What happens |
|------|--------|----------------|
| 1 | **`[JS]` Sortable** | User drops a card. **`onEnd`** reads **`data-task-id`** from the card and **`data-status`** from the destination **`.kanban-column`**, then calls **`moveTask(taskId, newStatus)`**. Sortable has already moved the DOM node, so the column visually updates immediately. |
| 2 | **`[JS]` `moveTask`** | Sends **`PATCH`** to **`/student/milestones/tasks/{taskId}/move`** with JSON **`{ status }`**, CSRF header, **`Accept: application/json`**. |
| 3 | **`[Controller]`** | **`StudentMilestoneController::moveTask`** validates membership, calls **`GroupMilestoneTask::updateStatus`**, which calls **`GroupMilestone::calculateProgressPercentage()`** and persists **`progress_percentage`** (see **§4.2**). Response JSON includes **`milestone_progress`** (0–100). |
| 4 | **`[JS]`** (`moveTask` `.then`) | On **`data.success`**, calls **`updateProgressBarUI(data.milestone_progress)`** — **this is the “auto update.”** It mutates the Bootstrap **`.progress-bar`** and the **`h4.mb-0`** label using the **server number**, not a client-side task count. |
| 5 | **`[JS]` `updateColumnCounts`** | Recounts **`.task-card`** elements per column and refreshes header **badges** and empty-state visibility—keeps counts aligned after Sortable’s DOM move. |
| 6 | **Failure** | On error, **`showAlert`** then **`location.reload()`** after 1s so DOM and DB stay in sync. |

**Important distinction:** The **percentage value** always comes from **`data.milestone_progress`** returned by Laravel. JavaScript only applies it to **`style.width`**, **`aria-valuenow`**, label text, and **Bootstrap color classes** (≥80% green, ≥50% yellow, else red). It does **not** count tasks in JS for that header percentage.

**Optional second path:** **`recomputeProgress()`** **`POST`s** **`/student/milestones/{id}/recompute-progress`** and passes **`data.progress`** into the same **`updateProgressBarUI`**—useful if you ever need to resync without dragging.

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const columns = document.querySelectorAll('.kanban-column-body');
    columns.forEach(column => {
        new Sortable(column, {
            group: 'tasks',
            animation: 150,
            ghostClass: 'dragging',
            onEnd: function(evt) {
                const taskId = evt.item.dataset.taskId;
                const newStatus = evt.to.closest('.kanban-column').dataset.status;
                moveTask(taskId, newStatus);
            }
        });
    });
    columns.forEach(column => {
        column.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.closest('.kanban-column').classList.add('drag-over');
        });
        column.addEventListener('dragleave', function(e) {
            this.closest('.kanban-column').classList.remove('drag-over');
        });
        column.addEventListener('drop', function(e) {
            this.closest('.kanban-column').classList.remove('drag-over');
        });
    });
});
function moveTask(taskId, newStatus) {
    fetch(`/student/milestones/tasks/${taskId}/move`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('Task moved successfully!', 'success');
            updateProgressBarUI(data.milestone_progress);
            updateColumnCounts();
        } else {
            showAlert('Failed to move task: ' + data.message, 'danger');
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(() => {
        showAlert('Error moving task. Please try again.', 'danger');
        setTimeout(() => location.reload(), 1000);
    });
}

function updateProgressBarUI(progress) {
    const progressBar = document.querySelector('.progress-bar');
    const progressText = document.querySelector('h4.mb-0');

    if (!progressBar || !progressText) {
        return;
    }

    progressBar.style.width = progress + '%';
    progressBar.setAttribute('aria-valuenow', progress);
    progressText.textContent = progress + '%';
    progressBar.className = 'progress-bar';

    if (progress >= 80) {
        progressBar.classList.add('bg-success');
        progressText.className = 'mb-0 text-success';
    } else if (progress >= 50) {
        progressBar.classList.add('bg-warning');
        progressText.className = 'mb-0 text-warning';
    } else {
        progressBar.classList.add('bg-danger');
        progressText.className = 'mb-0 text-danger';
    }
}

function updateColumnCounts() {
    document.querySelectorAll('.kanban-column').forEach(column => {
        const taskCards = column.querySelectorAll('.task-card');
        const count = taskCards.length;

        const badge = column.querySelector('.card-header .badge');
        if (badge) {
            badge.textContent = count;
        }

        const emptyState = column.querySelector('.kanban-empty-state');
        if (emptyState) {
            emptyState.style.display = count === 0 ? '' : 'none';
        }
    });
}

function recomputeProgress() {
    fetch(`/student/milestones/{{ $groupMilestone->id }}/recompute-progress`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            updateProgressBarUI(data.progress);
        } else {
            showAlert('Failed to recompute progress: ' + data.message, 'danger');
        }
    })
    .catch(() => {
        showAlert('Error recomputing progress. Please try again.', 'danger');
    });
}
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| A1 | `DOMContentLoaded` + `querySelectorAll('.kanban-column-body')` | Finds each column’s scroll/drop area after DOM ready. |
| A2 | `new Sortable(column, { group: 'tasks', ... })` | SortableJS: **`group: 'tasks'`** lets one drag ghost exist across columns so cards can move between lists. |
| A3 | `animation: 150` | Milliseconds for reorder animation. |
| A4 | `ghostClass: 'dragging'` | CSS class on placeholder while dragging (styling in Blade/CSS). |
| A5 | `onEnd: function(evt)` | Fires when drag completes (mouse released). |
| A6 | `evt.item.dataset.taskId` | Reads **`data-task-id`** on the card DOM node → **`group_milestone_tasks.id`** for API. |
| A7 | `evt.to.closest('.kanban-column').dataset.status` | Destination column has **`data-status`** → new workflow status string for backend. |
| A8 | `moveTask(taskId, newStatus)` | Persists move via HTTP (see below). |
| B1–B8 | Second `columns.forEach` dragover/dragleave/drop | Adds/removes **`drag-over`** class for visual highlight (`preventDefault` on dragover allows drop). |
| C1 | `function moveTask(taskId, newStatus)` | Sends PATCH so **`StudentMilestoneController::moveTask`** updates DB. |
| C2 | `` `/student/milestones/tasks/${taskId}/move` `` | Matches route name **`student.milestones.move-task`**. |
| C3 | `JSON.stringify({ status: newStatus })` | Request body keys match Laravel validation. |
| C4 | `Accept: application/json` | Expects JSON response (not redirect HTML). |
| C5 | `if (data.success)` | Server returns **`milestone_progress`** after move. |
| C6 | `updateProgressBarUI(data.milestone_progress)` | Updates bar without reload when successful. |
| C7 | `updateColumnCounts()` | Fixes badge counts / empty placeholders after DOM already moved by Sortable. |
| C8 | On failure → `showAlert` + `location.reload()` after 1s | Reload restores consistent UI if server rejected move. |
| D1–D12 | `updateProgressBarUI(progress)` | **`progress`** is the **integer from the JSON response** (`milestone_progress` or `progress`), not computed here. Sets **`style.width`**, **`aria-valuenow`**, **`progressText.textContent`**; resets **`progressBar.className`** then adds **`bg-success` / `bg-warning` / `bg-danger`** by thresholds **80** and **50**; mirrors text color on **`h4.mb-0`**. Source comment: use tag selector **`h4.mb-0`**, not **`.h4`**. |
| E1–E15 | `updateColumnCounts()` | Counts `.task-card` per column; updates header badge; toggles empty-state div. |
| F1 | `recomputeProgress()` | POST to **`recompute-progress`** — Blade injects **`{{ $groupMilestone->id }}`** into URL string at render time. |
| F2 | On success `updateProgressBarUI(data.progress)` | Same UI path as drag: server sends **`progress`** after **`recomputeProgress`** action—still **no client-side percentage formula**. |
| G1–G12 | `showAlert` | Creates Bootstrap dismissible alert; prepends to **`.container-fluid`**; removes after 5s. |

**Summary:** Sortable moves cards in the DOM immediately; **`moveTask`** persists **`status`** and returns **`milestone_progress`**; **`updateProgressBarUI`** only **renders** that number. Backend math lives in **`GroupMilestone::calculateProgressPercentage`** (**§4.2**). **`recomputeProgress`** uses the same painter with **`data.progress`**. Blade **`{{ $groupMilestone->id }}`** is fixed per page render.

---

### `student/milestones/index.blade.php`

**Purpose:** Quick-complete checkboxes on tasks send **`PATCH`** with **`is_completed`** boolean.

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const taskCheckboxes = document.querySelectorAll('.task-checkbox');
    taskCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const taskId = this.dataset.taskId;
            const isCompleted = this.checked;
            fetch(`/student/milestones/tasks/${taskId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ is_completed: isCompleted })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showAlert('Task updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('Failed to update task: ' + data.message, 'danger');
                    this.checked = !isCompleted;
                }
            })
            .catch(() => {
                showAlert('Error updating task. Please try again.', 'danger');
                this.checked = !isCompleted;
            });
        });
    });
});
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1 | `DOMContentLoaded` | Waits for milestone index DOM. |
| 2 | `querySelectorAll('.task-checkbox')` | Each row quick-complete control. |
| 3 | `this.dataset.taskId` | Checkbox carries Laravel-rendered **`data-task-id`**. |
| 4 | `const isCompleted = this.checked` | Saves intended state before async work. |
| 5 | `PATCH /student/milestones/tasks/${taskId}` | Updates task completion flag server-side. |
| 6 | `JSON.stringify({ is_completed: isCompleted })` | Body keys match controller expectation. |
| 7 | Success | Alert + delayed reload to refresh stats on page. |
| 8 | `this.checked = !isCompleted` | Reverts checkbox if server returned error or fetch threw. |
| 9 | `showAlert` function | Prepends dismissible alert to **`.container`**; removes after 5s. |

**Summary:** Checkbox state is rolled back on failure; reload on success keeps totals accurate.

---

### `student/milestones/partials/task-card.blade.php`

**Purpose:** Button shortcuts set task status via the **same** move endpoint as drag-and-drop.

```javascript
function changeTaskStatus(taskId, newStatus) {
    fetch(`{{ url('/student/milestones/tasks') }}/${taskId}/move`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('Task status updated successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('Failed to update task status: ' + (data.message || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating task status. Please try again.', 'danger');
    });
}
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1 | `function changeTaskStatus(taskId, newStatus)` | Global function from onclick buttons on card (`pending`/`doing`/`done`). |
| 2 | `` `{{ url('/student/milestones/tasks') }}/${taskId}/move` `` | Blade prints app URL prefix; concatenates id — same endpoint as Kanban **`moveTask`**. |
| 3–7 | headers + body `{ status: newStatus }` | Identical contract as drag-and-drop PATCH. |
| 8–11 | Success | Calls **`showAlert`** (defined on parent **`milestones/show`** page), then reload after 1s so column layout matches DB. |
| 12–15 | Failure / catch | Shows alert; does not reload—card may still be wrong until user refreshes (could be improved). |

**Summary:** Buttons reuse **`move`** route; **`showAlert`** must be global on including page.

---

### `student/milestones/submit-task.blade.php`

**Purpose:** Show/hide **progress percentage** when submission type is screenshots; auto-grow textareas.

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const submissionType = document.getElementById('submission_type');
    const progressField = document.getElementById('progress_percentage_field');
    submissionType.addEventListener('change', function() {
        if (this.value === 'screenshots') {
            progressField.style.display = 'block';
            document.getElementById('progress_percentage').required = true;
        } else {
            progressField.style.display = 'none';
            document.getElementById('progress_percentage').required = false;
        }
    });
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
});
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1–3 | `submissionType`, `progressField`, `change` listener | When dropdown changes, decides if progress % is relevant (screenshots). |
| 4–6 | `screenshots` branch | Shows wrapper; sets **`required`** on **`#progress_percentage`** so HTML5 blocks submit if empty. |
| 7–9 | `else` branch | Hides field; **`required = false`** so hidden input does not fail validation. |
| 10–15 | `textareas` `input` listener | Sets height to **`auto`** then **`scrollHeight`** px — textarea grows with content. |

**Summary:** Keeps browser validation consistent with visible fields; auto-grow avoids scrollbar clutter.

---

### `student/change-password.blade.php`

**Purpose:** HTML5 **`setCustomValidity`** so “passwords must match” before submit.

```javascript
document.addEventListener('DOMContentLoaded', function () {
    const newPassword     = document.getElementById('new_password');
    const confirmPassword = document.getElementById('new_password_confirmation');

    function validateMatch() {
        if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }

    newPassword.addEventListener('input', validateMatch);
    confirmPassword.addEventListener('input', validateMatch);
});
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1 | `DOMContentLoaded` | Runs after password fields exist. |
| 2–3 | `newPassword`, `confirmPassword` | References `input` elements by id from Blade form. |
| 4 | `function validateMatch()` | Runs on every keystroke in either field. |
| 5–6 | `if (confirmPassword.value && newPassword.value !== confirmPassword.value)` | Only validates mismatch when confirm field non-empty (allows typing). |
| 7 | `setCustomValidity('Passwords do not match')` | Hooks into HTML5 constraint validation API—browser shows native tooltip on submit. |
| 8–9 | `else { setCustomValidity('') }` | Clears custom error when fields match or confirm empty. |
| 10–11 | `addEventListener('input', validateMatch)` | Re-runs check live as user types. |

**Summary:** Uses built-in **`ValidityState`** instead of custom modal; empty **`setCustomValidity`** restores default validity.

---

### `student/group/create.blade.php`

**Purpose:** Two optional member `<select>`s share one pool of classmates; **search** filters options; selections cannot duplicate across dropdowns.

```javascript
const memberSourceOptions = [];

function initializeMemberSourceOptions() {
    const member1Select = document.getElementById('member1');
    if (!member1Select) return;

    memberSourceOptions.length = 0;
    Array.from(member1Select.options).forEach((option) => {
        if (!option.value) return;
        memberSourceOptions.push({
            value: option.value,
            label: option.textContent,
            name: (option.getAttribute('data-name') || '').toLowerCase(),
        });
    });
}

function renderMemberSelects() {
    const member1Select = document.getElementById('member1');
    const member2Select = document.getElementById('member2');
    const searchInput = document.getElementById('student_search');
    const searchTerm = (searchInput?.value || '').toLowerCase();
    if (!member1Select || !member2Select) return;

    const selectedMember1 = member1Select.value;
    const selectedMember2 = member2Select.value;

    member1Select.innerHTML = '<option value="">Select a student...</option>';
    member2Select.innerHTML = '<option value="">Select a student...</option>';

    memberSourceOptions.forEach((student) => {
        const matchesSearch = student.name.includes(searchTerm);
        const hiddenInFirst = selectedMember2 && student.value === selectedMember2 && student.value !== selectedMember1;
        const hiddenInSecond = selectedMember1 && student.value === selectedMember1 && student.value !== selectedMember2;

        if (!hiddenInFirst && (matchesSearch || student.value === selectedMember1)) {
            const option = document.createElement('option');
            option.value = student.value;
            option.textContent = student.label;
            option.setAttribute('data-name', student.name);
            if (student.value === selectedMember1) {
                option.selected = true;
            }
            member1Select.appendChild(option);
        }

        if (hiddenInSecond) {
            return;
        }

        if (matchesSearch || student.value === selectedMember2) {
            const option = document.createElement('option');
            option.value = student.value;
            option.textContent = student.label;
            option.setAttribute('data-name', student.name);
            if (student.value === selectedMember2) {
                option.selected = true;
            }
            member2Select.appendChild(option);
        }
    });

    if (member1Select.value && member2Select.value && member1Select.value === member2Select.value) {
        member2Select.value = '';
    }

    updateSelectionCount();
}

function filterStudents() {
    renderMemberSelects();
}

function updateMember2Options() {
    renderMemberSelects();
}

function updateSelectionCount() {
    const member1Select = document.getElementById('member1');
    const member2Select = document.getElementById('member2');
    const countSpan = document.getElementById('selection-count');

    let count = 0;
    if (member1Select?.value) count++;
    if (member2Select?.value) count++;

    if (countSpan) {
        countSpan.textContent = count;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initializeMemberSourceOptions();
    renderMemberSelects();

    document.getElementById('student_search')?.addEventListener('input', renderMemberSelects);
    document.getElementById('member1')?.addEventListener('change', renderMemberSelects);
    document.getElementById('member2')?.addEventListener('change', renderMemberSelects);
});
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1 | `const memberSourceOptions = [];` | Global cache of students parsed once from server HTML. |
| 2 | `initializeMemberSourceOptions()` | Reads **`#member1`** options on load into `{ value, label, name }`; **`name`** lowercased for search. |
| 3 | `memberSourceOptions.length = 0` then push | Clears then refills array if function called again. |
| 4 | `function renderMemberSelects()` | Master function rebuilding both dropdowns. |
| 5 | `searchTerm = (searchInput?.value \|\| '').toLowerCase()` | Optional chaining if search box missing. |
| 6 | Saves `selectedMember1/2` before clearing | Preserves user choice across rebuild. |
| 7 | `innerHTML = '<option value="">...'` | Wipes options—must rebuild entirely (filter trick). |
| 8 | `matchesSearch = student.name.includes(searchTerm)` | Substring match on normalized name. |
| 9 | `hiddenInFirst` / `hiddenInSecond` | If a student is selected in the *other* dropdown, hide that id here (unless it is the active selection for this dropdown). |
| 10 | First `if (!hiddenInFirst && (...)) { ... appendChild(option) }` | Builds **`member1`** options that pass search + dedupe rules; restores **`selected`**. |
| 11 | `if (hiddenInSecond) { return; }` | Skips building **`member2`** option for this loop iteration when that student should not appear in the second list. |
| 12 | Second `if (matchesSearch \|\| ...)` block | Builds **`member2`** options similarly. |
| 13 | After loop: `if (member1.value === member2.value) member2.value = ''` | Clears duplicate pick across slots. |
| 14 | `updateSelectionCount()` | Updates visible count of chosen invite slots. |
| 15 | `DOMContentLoaded` | Runs **`initializeMemberSourceOptions`**, first **`renderMemberSelects`**, then binds **`input`**/ **`change`** to re-render. |

**Summary:** Full rebuild pattern implements search + dedupe; **`hiddenInFirst`** encodes “don’t offer peer already picked in other slot.”

---

### `student/group/show.blade.php`

**Purpose:** **`requestDefense`** opens a modal and sets the **create defense request** link query string; second block duplicates invite-member search UX (`originalInviteOptions` / `renderInviteSelectOptions`) like group create.

```javascript
function requestDefense(defenseType) {
    const defenseTypeLabels = {
        'proposal': 'Proposal Defense',
        '60_percent': '60% Progress Defense',
        '100_percent': '100% Final Defense'
    };
    document.getElementById('defense_type_display').textContent = defenseTypeLabels[defenseType];

    const redirectLink = document.getElementById('defense_request_redirect');
    redirectLink.href = "{{ route('student.defense-requests.create') }}?defense_type=" + defenseType;

    const modal = new bootstrap.Modal(document.getElementById('defenseRequestModal'));
    modal.show();
}

const originalInviteOptions = [];

function initializeInviteOptions() {
    const first = document.getElementById('student_select_1');
    if (!first) return;
    originalInviteOptions.length = 0;

    const options = first.querySelectorAll('option');
    options.forEach((option) => {
        if (!option.value) return;
        originalInviteOptions.push({
            value: option.value,
            text: option.textContent,
            name: (option.getAttribute('data-name') || '').toLowerCase(),
        });
    });
}

function renderInviteSelectOptions() {
    const first = document.getElementById('student_select_1');
    const second = document.getElementById('student_select_2');
    const searchInput = document.getElementById('student_search');
    if (!first || !second) return;

    const searchTerm = (searchInput?.value || '').toLowerCase();
    const selectedFirst = first.value;
    const selectedSecond = second.value;

    first.innerHTML = '<option value="">Select student...</option>';
    second.innerHTML = '<option value="">Select student (optional)...</option>';

    originalInviteOptions.forEach((student) => {
        const matchesSearch = !searchTerm || student.name.includes(searchTerm);
        const hiddenInFirst = selectedSecond && student.value === selectedSecond && student.value !== selectedFirst;
        const hiddenInSecond = selectedFirst && student.value === selectedFirst && student.value !== selectedSecond;

        if (!hiddenInFirst && (matchesSearch || student.value === selectedFirst)) {
            const firstOption = document.createElement('option');
            firstOption.value = student.value;
            firstOption.textContent = student.text;
            firstOption.setAttribute('data-name', student.name);
            if (student.value === selectedFirst) {
                firstOption.selected = true;
            }
            first.appendChild(firstOption);
        }

        if (!hiddenInSecond && (matchesSearch || student.value === selectedSecond)) {
            const secondOption = document.createElement('option');
            secondOption.value = student.value;
            secondOption.textContent = student.text;
            secondOption.setAttribute('data-name', student.name);
            if (student.value === selectedSecond) {
                secondOption.selected = true;
            }
            second.appendChild(secondOption);
        }
    });

    if (selectedSecond && selectedSecond === selectedFirst) {
        second.value = '';
    }
}

function filterStudents() {
    renderInviteSelectOptions();
}

document.addEventListener('DOMContentLoaded', function () {
    const first = document.getElementById('student_select_1');
    const second = document.getElementById('student_select_2');
    const searchInput = document.getElementById('student_search');

    initializeInviteOptions();
    renderInviteSelectOptions();

    if (first) {
        first.addEventListener('change', renderInviteSelectOptions);
    }
    if (second) {
        second.addEventListener('change', renderInviteSelectOptions);
    }
    if (searchInput) {
        searchInput.addEventListener('input', renderInviteSelectOptions);
    }
});
```

**Line-by-line walkthrough**

**Part A — `requestDefense(defenseType)`**

| # | Code | What it does |
|---|------|----------------|
| A1 | `defenseTypeLabels` map | Human-readable title for modal body by slug (`proposal`, `60_percent`, `100_percent`). |
| A2 | `defense_type_display.textContent = ...` | Shows label inside Bootstrap modal. |
| A3 | `redirectLink.href = route + '?defense_type=' + defenseType` | Deep-links create page so controller + **`DefenseMilestoneGateService`** know which stage user requested. |
| A4 | `new bootstrap.Modal(...).show()` | Opens modal (`defenseRequestModal`) without navigation yet. |

**Part B — invite selects** (same algorithm as group create with variables renamed)

| # | Code | What it does |
|---|------|----------------|
| B1 | `originalInviteOptions` | Snapshot from **`student_select_1`** `<option>` list at load. |
| B2 | `matchesSearch = !searchTerm \|\| student.name.includes(searchTerm)` | Empty search shows all names; else filter. |
| B3 | `hiddenInFirst` / `hiddenInSecond` | Prevents duplicate selection across two invite slots. |
| B4 | `if (selectedSecond === selectedFirst) second.value = ''` | Clears conflict after rebuild. |
| B5 | `DOMContentLoaded` | Initializes options, **`renderInviteSelectOptions`** on change/input. |

**Summary:** Modal sets query param before user clicks through to defense create; invite UI mirrors **`memberSourceOptions`** dedupe logic.

---

### `student/group/edit.blade.php`

**Purpose:** Simpler member picker when editing group roster—same “two selects, no duplicate” rule without search.

```javascript
document.addEventListener('DOMContentLoaded', function () {
    const firstSelect = document.getElementById('edit_member1');
    const secondSelect = document.getElementById('edit_member2');
    if (!firstSelect || !secondSelect) return;

    const sourceOptions = Array.from(firstSelect.options)
        .filter((option) => option.value)
        .map((option) => ({ value: option.value, label: option.textContent }));

    function renderEditMemberOptions() {
        const selectedFirst = firstSelect.value;
        const selectedSecond = secondSelect.value;

        firstSelect.innerHTML = '<option value="">Select a student...</option>';
        secondSelect.innerHTML = '<option value="">Select a student...</option>';

        sourceOptions.forEach((student) => {
            const hiddenInFirst = selectedSecond && student.value === selectedSecond && student.value !== selectedFirst;
            const hiddenInSecond = selectedFirst && student.value === selectedFirst && student.value !== selectedSecond;

            if (!hiddenInFirst) {
            const firstOption = document.createElement('option');
            firstOption.value = student.value;
            firstOption.textContent = student.label;
            if (student.value === selectedFirst) {
                firstOption.selected = true;
            }
            firstSelect.appendChild(firstOption);
            }

            if (!hiddenInSecond) {
                const secondOption = document.createElement('option');
                secondOption.value = student.value;
                secondOption.textContent = student.label;
                if (student.value === selectedSecond) {
                    secondOption.selected = true;
                }
                secondSelect.appendChild(secondOption);
            }
        });

        if (firstSelect.value && secondSelect.value && firstSelect.value === secondSelect.value) {
            secondSelect.value = '';
        }
    }

    firstSelect.addEventListener('change', renderEditMemberOptions);
    secondSelect.addEventListener('change', renderEditMemberOptions);
    renderEditMemberOptions();
});
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1 | Early `return` if either select missing | Safe no-op if Blade didn’t render invites (e.g. permissions). |
| 2 | `sourceOptions = Array.from(firstSelect.options).filter(...).map(...)` | Builds plain JS array `{ value, label }` from initial DOM—single source of truth. |
| 3 | `renderEditMemberOptions()` | Clears both selects to placeholder option only. |
| 4 | `hiddenInFirst` / `hiddenInSecond` | Same duplicate-suppression as create/show (peer hidden in opposite select). |
| 5 | Creates `<option>` nodes | **`selected`** reapplied when **`student.value`** matches saved selection. |
| 6 | Clears second if both match same id | Cannot assign same member to slot 1 and 2. |
| 7 | `change` listeners + initial **`renderEditMemberOptions()`** | Any change rebuilds lists—keeps mutual exclusion consistent. |

**Summary:** No search box here—only mutual exclusion via **`sourceOptions`** rebuild.

---

### `student/group/invitations.blade.php`

**Purpose:** One reusable modal for **accept / decline / cancel** invitation actions; **`data-*`** on the trigger sets URL and copy.

```javascript
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('invitationActionModal');
    const actionForm = document.getElementById('invitationActionForm');
    const actionBody = document.getElementById('invitationActionModalBody');
    const actionConfirmBtn = document.getElementById('invitationActionConfirmBtn');

    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        const actionUrl = trigger.getAttribute('data-action-url');
        const actionLabel = trigger.getAttribute('data-action-label') || 'Confirm';
        const actionMessage = trigger.getAttribute('data-action-message') || 'Are you sure?';
        const actionBtnClass = trigger.getAttribute('data-action-btn-class') || 'btn-primary';

        actionForm.setAttribute('action', actionUrl);
        actionBody.textContent = actionMessage;
        actionConfirmBtn.textContent = actionLabel;
        actionConfirmBtn.className = 'btn ' + actionBtnClass;
    });
});
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1–4 | Cache modal, form, body text node, confirm button | Avoid repeated `getElementById` inside handler. |
| 5 | `show.bs.modal` listener | Bootstrap lifecycle event—runs each time modal opens. |
| 6 | `event.relatedTarget` | Element user clicked to open modal (`data-*` lives there). |
| 7 | `data-action-url` | POST destination for this invitation action (accept vs decline route). |
| 8 | `data-action-label`, `data-action-message`, `data-action-btn-class` | Customize button text, body copy, and Bootstrap button style per action. |
| 9 | `actionForm.setAttribute('action', actionUrl)` | Same `<form>` reused; only **`action`** changes. |
| 10 | `actionBody.textContent = actionMessage` | Plain text body—no HTML injection from attributes (safe default). |
| 11 | `actionConfirmBtn.textContent` / `className` | Visual distinction (e.g. danger vs primary). |

**Summary:** One modal, many triggers—each trigger carries REST URL + copy via **`data-*`**.

---

### `student/project/index.blade.php` (compare helper)

**Purpose:** Build compare URL from two `<select>` IDs per submission type using a Blade-injected template string.

```javascript
(function () {
    var tpl = @json($projectCompareTemplate);
    document.querySelectorAll('.project-compare-go').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var t = btn.getAttribute('data-t');
            var l = document.getElementById('proj-cmp-' + t + '-a').value;
            var r = document.getElementById('proj-cmp-' + t + '-b').value;
            if (!l || !r || l === r) {
                alert('Choose two different submissions.');
                return;
            }
            window.location.href = tpl.replace('__L__', l).replace('__R__', r);
        });
    });
})();
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1 | IIFE `(function () { ... })();` | Avoids leaking `tpl` into global scope. |
| 2 | `var tpl = @json($projectCompareTemplate)` | Blade emits quoted URL template string with **`__L__`** / **`__R__`** placeholders. |
| 3 | `.project-compare-go` forEach | One “Compare” button per submission **type** row (`data-t`). |
| 4 | `data-t` → builds ids `proj-cmp-{t}-a` and `-b` | Finds the two `<select>` elements for left/right submission ids for that type. |
| 5 | `if (!l \|\| !r \|\| l === r)` | Validates two distinct ids chosen. |
| 6 | `tpl.replace('__L__', l).replace('__R__', r)` | Substitutes numeric/string ids into compare route. |
| 7 | `window.location.href = ...` | Full navigation to compare page (GET). |

**Summary:** Template URL from server avoids hardcoding paths in JS; **`data-t`** namespaces widgets when multiple types on one page.

---

### `student/project/create.blade.php`

**Purpose:** Change **`accept`** attribute and helper text when submission **type** changes (`final` vs `other` vs default).

```javascript
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('type');
    const fileInput = document.getElementById('file');
    const typeRuleText = document.getElementById('type-rule-text');
    const fileRuleText = document.getElementById('file-rule-text');

    function applyTypeRules() {
        const selectedType = typeSelect.value;
        if (selectedType === 'final') {
            fileInput.setAttribute('accept', '.pdf,.doc,.docx');
            typeRuleText.textContent = 'Final Report accepts PDF, DOC, or DOCX only.';
            fileRuleText.textContent = 'Allowed for Final Report: PDF, DOC, DOCX (Max: 10MB)';
        } else if (selectedType === 'other') {
            fileInput.setAttribute('accept', '.pdf,.doc,.docx,.zip,.ppt,.pptx');
            typeRuleText.textContent = 'Additional Files accepts supplementary formats.';
            fileRuleText.textContent = 'Allowed for Additional Files: PDF, DOC, DOCX, ZIP, PPT, PPTX (Max: 10MB)';
        } else {
            fileInput.setAttribute('accept', '.pdf,.doc,.docx,.zip,.ppt,.pptx');
            typeRuleText.textContent = 'Choose the type of document you are submitting.';
            fileRuleText.textContent = 'Supported formats: PDF, DOC, DOCX, ZIP, PPT, PPTX (Max: 10MB)';
        }
    }

    typeSelect.addEventListener('change', applyTypeRules);
    applyTypeRules();
});
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1–4 | Cache `#type`, `#file`, helper text spans | DOM refs for upload rules UI. |
| 5 | `function applyTypeRules()` | Single place updating **`accept`** + copy. |
| 6 | `selectedType === 'final'` | Stricter extensions (PDF/DOC/DOCX only). |
| 7 | `selectedType === 'other'` | Wider list including ZIP/PPT. |
| 8 | `else` | Default capstone submission messaging. |
| 9 | `fileInput.setAttribute('accept', ...)` | Hints OS file picker—does **not** enforce security alone. |
| 10 | `typeRuleText` / `fileRuleText` | User-facing explanation beside input. |
| 11 | `typeSelect.addEventListener('change', applyTypeRules)` | Updates rules when user switches type mid-form. |
| 12 | `applyTypeRules()` immediate call | Correct initial state when page loads with old input. |

**Summary:** UX alignment with backend MIME rules; server validation still required for security.

---

### `student/proposal/index.blade.php` (compare helper)

**Purpose:** Same pattern as project compare for **proposal** version IDs.

```javascript
(function () {
    var tpl = @json($proposalCompareTemplate);
    document.getElementById('proposal-compare-go').addEventListener('click', function () {
        var l = document.getElementById('proposal-compare-left').value;
        var r = document.getElementById('proposal-compare-right').value;
        if (!l || !r || l === r) {
            alert('Choose two different versions.');
            return;
        }
        window.location.href = tpl.replace('__L__', l).replace('__R__', r);
    });
})();
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1 | IIFE wrapper | Keeps `tpl` local. |
| 2 | `var tpl = @json($proposalCompareTemplate)` | Blade outputs compare URL pattern for proposal version ids (same **`__L__`/`__R__`** idea as project). |
| 3 | Single **`proposal-compare-go`** button | Proposal page has one compare control (not per-type like project). |
| 4 | `proposal-compare-left` / `proposal-compare-right` | Fixed ids for the two version dropdowns. |
| 5 | Validation `!l \|\| !r \|\| l === r` | Ensures two distinct version ids before navigation. |
| 6 | `window.location.href = tpl.replace(...)` | GET navigates to **`StudentProposalController::compareVersions`**. |

**Summary:** Injected only when **`$proposalVersions->count() >= 2`** in Blade—otherwise no compare UI.

---

### Inline script elsewhere

**Delete submission (`student/project/index.blade.php`)**

| Step | What happens |
|------|----------------|
| 1 | User clicks delete `<button type="submit">` inside `<form method="POST">` with **`@method('DELETE')`**. |
| 2 | **`onclick="return confirm('Delete this submission?')"`** runs **before** submit event propagates. |
| 3 | If user clicks **Cancel** on confirm, handler returns **`false`** → browser **does not** submit the form. |
| 4 | If user clicks **OK**, returns **`true`** → normal POST to **`student.project.destroy`**. |

**Extra script tag (`student/change-password.blade.php`):** Loads **`bootstrap.bundle.min.js`** from CDN when the standalone HTML page might not inherit layout scripts—watch for loading Bootstrap twice if the layout already includes it (can cause subtle modal/tab bugs).


---

### Defense sound bite

**“Our student UI mixes Blade-rendered data with small vanilla JS modules: Sortable for Kanban, fetch + CSRF for REST-like PATCH endpoints, and dynamic selects so group invites cannot pick the same classmate twice. Compare flows build URLs from server-provided templates so we never hardcode IDs in JavaScript.”**
