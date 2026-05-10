# Student area — guide to routes, controllers, services, and JavaScript

This document explains **what each major student-facing piece of code is for** in plain language, and gives a **step-by-step trace of the Kanban board** (drag-and-drop, status values, and the AJAX `fetch` call).

**Authoritative route list:** `routes/web.php` (group `Route::prefix('student')->name('student.')`).

**Auth:** Student pages use the **`student`** guard (`StudentAuthMiddleware`), plus **`CheckStudentPasswordChange`** so first-time users change their password before the rest of the app (except the password-change routes, which skip that middleware).

**First-time login:** Imported or newly provisioned students usually have **`StudentAccount.must_change_password`** set and obtain a **temporary plain password by email** from the public **“Email me a temporary password”** flow (`login.student-credentials`). That is **not** the same as faculty self-registration; see **section 2** below.

---

## 1. Route map (student prefix)

| Area | HTTP | Route name | Controller |
|------|------|------------|------------|
| Request temp password (guest) | GET | `login.student-credentials` | `StudentTemporaryPasswordController@create` |
| Submit student ID for email | POST | `login.student-credentials.store` | `StudentTemporaryPasswordController@store` |
| Dashboard | GET | `student.dashboard` | `StudentDashboardController@index` |
| Change password | GET | `student.change-password` | `StudentPasswordController@showChangePasswordForm` |
| Update password | POST | `student.update-password` | `StudentPasswordController@updatePassword` |
| Project submissions | GET/POST/… | `student.project`, `student.project.*` | `ProjectSubmissionController` — student-specific preview/file/compare routes are named `student.project.submission.*`, `student.project.submissions.compare` |
| Group (single group UX) | GET/POST/PUT/DELETE | `student.group`, `student.group.*` | `StudentGroupController` |
| Proposal versions | GET | `student.proposal.version.preview`, `student.proposal.versions.compare` | `StudentProposalController` |
| Proposal CRUD | * | `student.proposal`, `student.proposal.*` | `StudentProposalController` |
| Milestones list | GET | `student.milestones` | `StudentMilestoneController@index` |
| Milestone checklist | GET | `student.milestones.checklist` | `StudentMilestoneChecklistController@checklist` |
| Milestone Kanban | GET | `student.milestones.show` | `StudentMilestoneController@show` |
| Milestone edit/update/delete | GET/PUT/DELETE | `student.milestones.edit`, `.update`, `.destroy` | `StudentMilestoneController` |
| Move task (Kanban AJAX) | PATCH | `student.milestones.move-task` | `StudentMilestoneController@moveTask` |
| Bulk task PATCH | PATCH | `student.milestones.bulk-update` | `StudentMilestoneController@bulkUpdateTasks` |
| Recompute progress | POST | `student.milestones.recompute-progress` | `StudentMilestoneController@recomputeProgress` |
| Assign / unassign task | PATCH/DELETE | `student.milestones.assign-task`, `student.milestones.unassign-task` | `StudentMilestoneController` |
| Update task (toggle complete) | PATCH | `student.milestones.update-task` | `StudentMilestoneController@updateTask` |
| Task comments | POST | `student.milestones.task-comments.store` | `StudentMilestoneController@storeTaskComment` |
| Task submission (files) | GET/POST | `student.task-submission.*` | `TaskSubmissionController` |
| Defense requests | * | `student.defense-requests.*` | `StudentDefenseRequestController` |
| Notifications | GET/POST/DELETE | `student.notifications*` | `StudentController` |
| Calendar | GET | `student.calendar` | `CalendarController@studentCalendar` |

---

## 2. First-time login — temporary password via email (how the API works)

Students who have not finished first-time setup use **student ID + email on file**. Coordinators/chairperson imports **create or update** `Student` + **`StudentAccount`** rows but **do not send mail** at import time; the student triggers delivery from the login screen.

### 2.1 Public routes (no authentication)

| HTTP | Path | Name | Behavior |
|------|------|------|----------|
| GET | `/login/student-credentials` | `login.student-credentials` | Shows `resources/views/auth/request-student-temporary-password.blade.php` (form: student ID only). |
| POST | `/login/student-credentials` | `login.student-credentials.store` | Validates ID, syncs email from `Student` → `StudentAccount`, generates password, emails it. **Rate limit:** `throttle:6,1` (six attempts per minute per IP). |

Link from the main login: **`resources/views/auth/login.blade.php`** → route `login.student-credentials` (“Email me a temporary password”).

### 2.2 Validation (`RequestStudentTemporaryPasswordRequest` + `StudentTemporaryPasswordEligible`)

POST accepts **`student_id`** (trimmed). The custom rule **`StudentTemporaryPasswordEligible`** rejects the request unless all of the following hold:

- The ID is **not** a faculty `faculty_id` (prevents staff mistaking this flow for faculty login).
- Both **`Student`** and **`StudentAccount`** exist for that **`student_id`**.
- **`Student.email`** is non-empty, passes **`filter_var(..., FILTER_VALIDATE_EMAIL)`**, and is **not** a placeholder ending in **`@student.placeholder.local`**.
- The account is still eligible: **`password` is unset in the DB** *or* **`must_change_password` is true**. If the student has already completed first-time login (`must_change_password` false and password set), the rule fails with a message to use normal login / coordinator reset.

Validation errors redirect back to **`login.student-credentials`** with errors (not JSON).

### 2.3 Controller and email sync (`StudentTemporaryPasswordController`)

**`store`** loads **`Student`** and **`StudentAccount`** by **`student_id`**, then **`syncAccountEmailFromStudent`**: if the **`Student`** row has a non-empty email that differs from **`StudentAccount.email`**, the account email is updated so mail goes to the current roster address.

### 2.4 Provisioning service (`StudentCredentialProvisioner`)

**`assignTemporaryPasswordAndNotify($student, $account, $sendEmail)`**:

1. Generates a **16-character** random password (`Str::password(16)`).
2. If **`$sendEmail`** is true (normal web flow):
   - Runs a **database transaction**: set **`StudentAccount.password`** (hashed automatically via the model’s **`'password' => 'hashed'`** cast), set **`must_change_password`** to **true**, **`save()`**, then **`Mail::to($account->email)->send(new StudentTemporaryPasswordMail(...))`**.
   - If **anything** in that transaction throws (including SMTP failure), the transaction **rolls back** — the password is **not** left changed without a successful send. The controller then redirects to **`login`** with a **`mail`** error and hints to configure **`.env`** / **`php artisan mail:test`**.
3. If email sends successfully, redirects to **`login`** with a success **`status`** flash.

The mailable is **`App\Mail\StudentTemporaryPasswordMail`**; view **`resources/views/emails/student-temporary-password.blade.php`**. Subject uses **`config('app.name')`** plus “Your login credentials”.

### 2.5 After the student receives the email — login and forced password change

1. On **`POST /login`**, **`AuthController::attemptStudentLogin`** verifies the password against **`StudentAccount`** (same field used for the temporary password).
2. If **`must_change_password`** is still **true**, redirect **`student.change-password`** with an info message — **not** straight to the dashboard.
3. **`CheckStudentPasswordChange`** middleware blocks all other **`student.*`** routes until **`student.change-password`** / **`student.update-password`** complete.
4. **`StudentPasswordController@updatePassword`** saves the new hash and sets **`must_change_password`** to **false**.

So the “API” for first-time access is: **GET/POST credential routes (guest) → SMTP delivers secret → standard login POST → password change routes → rest of `student.*`.**

---

## 3. Controllers — what each one does

### `StudentDashboardController`

- **`index`** — Main landing after login. Resolves the logged-in student (via `student` guard or fallback `Auth::user()->student`), loads their primary **group**, then computes dashboard widgets: overall milestone progress, task counts (`pending` / `doing` / `done`), submission counts, “current milestone” summary, recent tasks/activities, deadlines, adviser info, defense summary, notifications, latest proposal, and **offering** (subject / teacher / coordinator labels). All of that is passed to `dashboards.student`.

### `StudentController`

- **`index`** — Redirects to `student.dashboard` (shortcut).
- **`notifications`** and the **`mark*read` / `delete*`** actions — Student notification inbox; uses **`NotificationService`** for bulk/single updates. Queries respect **`Notification::visibleToStudent`** so students only see their own feed.

### `StudentPasswordController`

- **`showChangePasswordForm` / `updatePassword`** — First-login or voluntary password change; registered **without** `CheckStudentPasswordChange` so the form is reachable.

### `StudentGroupController`

Handles **creating/updating a group**, **member invitations** (accept/decline/cancel), **adviser invitations**, **removing members**, and related listing pages. Uses **`getAuthenticatedStudent()`** (student guard) throughout. Private helpers filter “students you can invite” by offering and term so invites stay within the right class roster.

### `StudentProposalController`

- Proposal **list, create, store, show, edit, update, rollback** for the student’s capstone proposal pipeline.
- **`previewVersion` / `compareVersions`** — Uses **`DocumentPreviewService`** where applicable so document previews stay consistent.

### `ProjectSubmissionController` (student routes)

- **`index`, `create`, `store`, `show`, `destroy`** — General project document uploads tied to `student.project` routes.
- **`studentPreviewSubmission`, `studentSubmissionFile`, `studentCompareSubmissions`** — Safe views/downloads/compare for the student’s own files; still uses **`DocumentPreviewService`** on preview.

### `StudentMilestoneController`

| Method | Role |
|--------|------|
| `index` | Lists group milestones and templates; if the student has no group, shows an empty state message. |
| `show` | **Kanban page**: loads one `GroupMilestone`, splits **`GroupMilestoneTask`** rows into three collections by **`status`** (`pending`, `doing`, `done`), computes progress %, passes **`isGroupLeader`**. |
| `edit` / `update` / `destroy` | **Leader-only** — edit milestone metadata or delete (including deleting related group tasks). |
| **`moveTask`** | **Kanban AJAX** — validates `status` ∈ `pending,doing,done`, ensures the task belongs to the student’s group, calls **`GroupMilestoneTask::updateStatus`**, returns JSON with updated task + **`milestone_progress`**. |
| `bulkUpdateTasks` | JSON bulk status updates for many tasks on one milestone (same validation as move). |
| `recomputeProgress` | Recalculates milestone progress; supports JSON for the optional “recompute” button on the Kanban page. |
| `updateTask` | PATCH toggle completion (legacy/simple path: sets `done` vs `pending`). |
| `storeTaskComment` | Adds a threaded comment on a task; logs via **`ActivityLogService::logTaskCommentAdded`**. |
| `updateMultipleTasks` | Form POST path for updating several checkboxes at once (progress form). |
| `assignTask` / `unassignTask` | **Leader-only** — set or clear **`assigned_to`** for a group member. |

Private helpers: **`getAuthenticatedStudent`**, **`getMilestoneTasksByStatus`** (normalizes missing `status` from legacy `is_completed`), **`calculateMilestoneProgress`**, etc.

### `StudentMilestoneChecklistController`

- **`checklist`** — Alternate “checklist” view of milestone progress (lighter than full Kanban).

### `TaskSubmissionController`

- **`create` / `store`** — Upload evidence for a **`GroupMilestoneTask`** (documents/screenshots/notes). Enforces group membership and optional **assignee** (if the task is assigned to someone else, only that student can submit).
- **`store`** — After saving a **`TaskSubmission`**, if the task was **`pending`**, it calls **`$task->updateStatus('doing')`** so the first submission automatically moves the card toward **In Progress** on the next page load.
- **`show`** — Student sees their submission; advisers hit a different branch with role checks.

### `StudentDefenseRequestController`

- **`index` / `create` / `store` / `show` / `cancel`** — Group-scoped defense requests.
- Injects **`DefenseMilestoneGateService`**: before allowing a request, **`evaluate()`** checks proposal approval or milestone completion rules so students cannot skip required stages.

### `StudentTemporaryPasswordController`

- Guest-only **temporary password email** flow (**section 2**): GET form, POST **`store`** → **`StudentCredentialProvisioner::assignTemporaryPasswordAndNotify`**.

---

## 4. Services touched by student flows

| Service | Used where | Purpose |
|---------|------------|---------|
| **`NotificationService`** | `StudentController` | Mark read/delete notifications reliably. |
| **`DocumentPreviewService`** | `StudentProposalController`, `ProjectSubmissionController` | Normalize preview/compare for uploaded documents. |
| **`DefenseMilestoneGateService`** | `StudentDefenseRequestController` | Block/allow defense requests until milestones or proposal approval rules pass. |
| **`ActivityLogService`** | `StudentMilestoneController` (task comments) | Writes an audit log entry when a student comments on a task. |
| **`StudentCredentialProvisioner`** | `StudentTemporaryPasswordController` | Generate temporary password, set **`must_change_password`**, send **`StudentTemporaryPasswordMail`** inside a DB transaction when SMTP is used (**section 2**). |

Other services (**`StudentEnrollmentService`**, **`StudentImportService`**, etc.) are used by **imports and chairperson/coordinator** tooling, not by day-to-day student browser pages.

---

## 5. Front-end JavaScript for students

- **Bundled assets:** `resources/js/app.js` only imports **`bootstrap.js`** — there is **no** large student SPA bundle; most behavior lives in **Blade `@push('scripts')` sections**.
- **Kanban board:** Implemented in **`resources/views/student/milestones/show.blade.php`** (inline `<script>`). It loads **SortableJS** from a CDN for drag-and-drop.

---

## 6. Kanban — how drag-and-drop maps to Pending / In Progress / Done

### 6.1 Database and model meaning

- Each card is a **`GroupMilestoneTask`** row. The canonical workflow field is **`status`**, with allowed values: **`pending`**, **`doing`**, **`done`**.
- The **UI labels** do not always match the raw string: **`doing`** is shown as **“In Progress”** on badges (see **`updateTaskCardStatusUI`** in the same Blade file).
- **`GroupMilestoneTask::updateStatus($status)`** (`app/Models/GroupMilestoneTask.php`):
  - Writes **`status`**.
  - Sets **`is_completed`** to **true** only when **`status === 'done'`**.
  - Calls **`$this->groupMilestone->calculateProgressPercentage()`** so the milestone (and dashboard stats) stay in sync.

So when you drag a card:

1. **SortableJS** has already moved the DOM node into the new column (instant visual feedback).
2. The script sends the **new column’s code** (`pending`, `doing`, or `done`) to the server.
3. The server persists that code on the row and recomputes progress.
4. On success, JavaScript updates the **badge text/colors**, **progress bar**, and **column counts** so the screen matches the database without a full reload.

### 6.2 HTML hooks

- Each column wrapper has **`data-status="pending|doing|done"`** on **`.kanban-column`**.
- Each task card should expose **`data-task-id="<id>"`** (used in **`evt.item.dataset.taskId`**).

### 6.3 SortableJS setup (browser)

From **`show.blade.php`** (simplified):

1. **`document.querySelectorAll('.kanban-column-body')`** — each column body is a Sortable “list”.
2. **`new Sortable(column, { group: 'tasks', animation: 150, onEnd: ... })`** — cards can move between columns because they share **`group: 'tasks'`**.
3. **`onEnd`** fires **after** the drag finishes:
   - **`evt.item.dataset.taskId`** → which **`GroupMilestoneTask`** moved.
   - **`evt.to.closest('.kanban-column').dataset.status`** → the **new** status string taken from the column’s **`data-status`**.

### 6.4 AJAX request (`fetch`)

Function **`moveTask(taskId, newStatus)`** sends:

- **URL:** `PATCH /student/milestones/tasks/{taskId}/move` (relative URL in code: `` `/student/milestones/tasks/${taskId}/move` ``).
- **Headers:**
  - **`Content-Type: application/json`** — Laravel reads JSON body.
  - **`X-CSRF-TOKEN`** — taken from **`<meta name="csrf-token">`** so Laravel accepts the state-changing request.
  - **`Accept: application/json`** — expect JSON errors/success, not an HTML error page.
- **Body:** **`JSON.stringify({ status: newStatus })`** where **`newStatus`** is exactly **`pending`**, **`doing`**, or **`done`**.

This is **not** jQuery — it is the browser **`fetch` API** (Promise-based).

### 6.5 Server handler

**`StudentMilestoneController::moveTask`**:

1. Ensures the student is logged in (JSON error if not).
2. Loads **`GroupMilestoneTask`** by id; **403-style JSON** if missing or not in the student’s group.
3. **`$request->validate(['status' => 'required|in:pending,doing,done'])`** — rejects invented statuses.
4. **`$task->updateStatus($request->status)`** — persists + recalculates milestone progress.
5. Returns JSON: **`success`**, **`task`** (fresh model), **`milestone_progress`** (number).

### 6.6 After the response (same page)

On **`data.success`**:

- **`updateTaskCardStatusUI(taskId, newStatus)`** — swaps badge classes and text (**Pending** / **In Progress** / **Done**) and toggles strikethrough on the title when **`done`**.
- **`updateProgressBarUI(data.milestone_progress)`** — updates the top progress bar + percentage text + color thresholds.
- **`updateColumnCounts()`** — recounts cards per column and shows/hides empty-state placeholders.

On failure or network error, the code shows an alert and **`location.reload()`** after a short delay so the UI re-syncs with the database.

### 6.7 Related: task file upload bumps status

When a student submits files via **`TaskSubmissionController@store`**, if the task was **`pending`**, the controller calls **`$task->updateStatus('doing')`**. That is **separate** from drag-and-drop but produces the same **`doing`** state you see in the **In Progress** column after you reload or revisit the Kanban.

---

## 7. File reference cheat sheet

### 7.1 First-time temporary password email — where it lives and what the code does

**URLs and routes (guest, no login):** Registered in **`routes/web.php`**: GET **`/login/student-credentials`** → `StudentTemporaryPasswordController@create` (named `login.student-credentials`); POST the same path → `StudentTemporaryPasswordController@store` (named `login.student-credentials.store`) with middleware **`throttle:6,1`** (six POSTs per minute per IP). The main login template **`resources/views/auth/login.blade.php`** links to this flow (“Email me a temporary password”).

**Request form:** **`resources/views/auth/request-student-temporary-password.blade.php`** renders the page where the student enters **`student_id`** only; submitting POSTs to the route above.

**Validation:** **`app/Http/Requests/RequestStudentTemporaryPasswordRequest.php`** requires a non-empty **`student_id`** and applies the custom rule **`StudentTemporaryPasswordEligible`** (**`app/Rules/StudentTemporaryPasswordEligible.php`**). That rule ensures the ID is not a faculty **`faculty_id`**, that both **`Student`** and **`StudentAccount`** rows exist, that **`Student.email`** is a real address (not empty, not ending in **`@student.placeholder.local`**, passes **`filter_var`**), and that the account still qualifies for this flow (password never set in the DB *or* **`must_change_password`** is still true). Otherwise it fails with messages that steer the user to normal login or coordinator help.

**HTTP controller:** **`app/Http/Controllers/StudentTemporaryPasswordController.php`** — **`create`** returns the request form view; **`store`** reads the validated ID, loads **`Student`** and **`StudentAccount`**, calls **`syncAccountEmailFromStudent`** so **`StudentAccount.email`** matches the roster **`Student.email`** when they differ, refreshes the account, then delegates to **`StudentCredentialProvisioner::assignTemporaryPasswordAndNotify(..., true)`**. On success it redirects to **`login`** with a success flash; on mail failure it redirects with a **`mail`** error that mentions **`.env`** and **`php artisan mail:test`**.

**Provisioning and email send:** **`app/Services/StudentCredentialProvisioner.php`** — **`assignTemporaryPasswordAndNotify`** builds a **16-character** plain password with **`Str::password(16)`**. When **`$sendEmail`** is true, it wraps **hash + save + send** in **`DB::transaction`**: it assigns the plain string to **`$account->password`** (the **`StudentAccount`** model hashes it via its casts), sets **`must_change_password`** to **true**, saves, then **`Mail::to($account->email)->send(new StudentTemporaryPasswordMail(...))`**. Any throwable (including SMTP errors) rolls back the transaction so the password is not persisted without a successful send; the method then returns **`email_sent: false`** and the controller shows the mail error. On success it returns **`email_sent: true`**.

**The actual email:** **`app/Mail/StudentTemporaryPasswordMail.php`** is a Laravel **`Mailable`** whose subject is **`config('app.name').': Your login credentials'`** and whose body is the Blade view **`emails.student-temporary-password`** → **`resources/views/emails/student-temporary-password.blade.php`**, which outputs the plain temporary password and reminders about changing it after first login.

**After delivery (not part of sending, but same “first-time” story):** **`AuthController`** handles **`POST /login`** for students; **`CheckStudentPasswordChange`** and **`StudentPasswordController`** enforce changing the password until **`must_change_password`** is cleared — see **§2.5**.

| Students see… | Primary files |
|----------------|---------------|
| First-time temp password email | **§7.1** — `routes/web.php` (`login.student-credentials*`), `app/Http/Controllers/StudentTemporaryPasswordController.php`, `app/Http/Requests/RequestStudentTemporaryPasswordRequest.php`, `app/Rules/StudentTemporaryPasswordEligible.php`, `app/Services/StudentCredentialProvisioner.php`, `app/Mail/StudentTemporaryPasswordMail.php`, `resources/views/auth/request-student-temporary-password.blade.php`, `resources/views/auth/login.blade.php` (link), `resources/views/emails/student-temporary-password.blade.php`; post-login: `AuthController.php`, `CheckStudentPasswordChange`, `StudentPasswordController.php` |
| Dashboard | `resources/views/dashboards/student.blade.php`, `StudentDashboardController.php` |
| Kanban | `resources/views/student/milestones/show.blade.php`, `resources/views/student/milestones/partials/task-card.blade.php`, `StudentMilestoneController.php`, `GroupMilestoneTask.php` |
| Groups | `StudentGroupController.php`, views under `resources/views/student/group*` |
| Proposals / uploads | `StudentProposalController.php`, `ProjectSubmissionController.php`, related views |
| Defense requests | `StudentDefenseRequestController.php`, `DefenseMilestoneGateService.php`, views under `resources/views/student/defense-requests/` |

For a **coordinator/adviser-oriented** trace of shared concepts (milestones, templates), see **`docs/COORDINATOR_AND_ADVISER_CODE_TRACE.md`**.
