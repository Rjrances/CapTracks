# CapTracks — Full codebase guide

This document explains **how the application is put together**: folders, **every controller and its public methods**, **models and relationships**, **services**, **middleware**, **routes conceptually**, main **database tables**, and **typical user flows**. Use it with **`routes/web.php`** and **`php artisan route:list`** for exact URLs.

---

## Part A — What CapTracks is (architecture)

CapTracks is a **Laravel 12** web app for **capstone / defense coordination**:

- **Students** (`student` guard): groups, proposals, milestone Kanban, defense requests, uploads.
- **Faculty** (`users` table + `auth` guard): **advisers**, **coordinators**, **chairperson**, teachers — dashboards, reviews, scheduling.
- **Data** lives in **MySQL** (typical); files on **local/public disk** (`storage/app/public`).
- **UI** is **Blade** + Bootstrap-style layouts under `resources/views/{student,adviser,coordinator,chairperson}`.

```text
HTTP Request → routes/web.php → Middleware → Controller → Models / Services → Blade View (or JSON)
```

---

## Part B — Folder map (where everything lives)

| Path | Contents |
|------|-----------|
| `routes/web.php` | **All** web routes (single file). |
| `app/Http/Controllers/` | Controllers (sometimes grouped, e.g. `Coordinator/`). |
| `app/Http/Middleware/` | `CheckRole`, `StudentAuthMiddleware`, `CheckStudentPasswordChange`, `CanRegisterMiddleware`. |
| `bootstrap/app.php` | Middleware **aliases** (`checkrole` → `CheckRole`). |
| `app/Models/` | Eloquent models. |
| `app/Services/` | Shared business helpers (notifications, logging, document preview, imports). |
| `app/Imports/` | Maatwebsite Excel imports (`StudentsImport`, `FacultyImport`). |
| `database/migrations/` | Schema history (many incremental `*_table.php` files). |
| `database/seeders/` | Demo data (`UserSeeder`, `StudentSeeder`, etc.). |
| `resources/views/` | Blade templates by role and feature. |
| `public/` | Static assets; `storage` symlink target for uploads. |
| `railway.json` / `railway.env` | Railway deploy hints (review before production). |

---

## Part C — Authentication and middleware

### Guards

| Guard | Who | Credentials |
|-------|-----|-------------|
| Default (`web`) | Faculty/staff | `users` + `user_accounts` password via `User::getAuthPassword()` |
| `student` | Students | `student_accounts` linked to `students` |

### Middleware classes (`app/Http/Middleware/`)

| Class | Behavior |
|-------|-----------|
| `CheckRole` | Registered as **`checkrole`**. Requires `Auth::check()` and `User::hasAnyRole(...)`. Params: comma-separated roles in route definition. |
| `StudentAuthMiddleware` | Ensures `Auth::guard('student')->check()` and student account type; else redirect `/login`. |
| `CheckStudentPasswordChange` | Forces password change flow when flagged on student account. |
| `CanRegisterMiddleware` | Used where registration is gated (see usages in codebase). |

### Laravel built-ins used on routes

- **`auth`** — must be logged in as faculty (`users`).
- Route groups in **`routes/web.php`** tie **prefix + middleware + controller**.

---

## Part D — Controllers: every file and public methods

**Table format (one row per method, all controllers):** **`CONTROLLERS_REFERENCE.md`**. **Coordinator-only, full explanations:** **`COORDINATOR_FUNCTIONS.md`**.

Below, **each line is one public action** (what gets called from a route). **Private** helpers are not listed but exist inside the same file (e.g. validation, notifications).

### `AuthController`

| Method | Purpose |
|--------|---------|
| `showLoginForm` | Faculty login form |
| `login` | Authenticate faculty |
| `logout` | Session logout |
| `showRegisterForm` | Registration form (if enabled) |
| `register` | Create account flow |
| `showChangePasswordForm` | Password change form |
| `changePassword` | Update password |

### `StudentDashboardController`

| Method | Purpose |
|--------|---------|
| `index` | Student dashboard |

### `StudentController`

| Method | Purpose |
|--------|---------|
| `index` | Redirect / student home routing |
| `notifications` | List notifications |
| `markNotificationAsRead` | Single read |
| `markAllNotificationsAsRead` | Mark all |
| `deleteNotification` | Delete one |
| `markMultipleAsRead` | Bulk read |
| `deleteMultiple` | Bulk delete |

### `StudentPasswordController`

| Method | Purpose |
|--------|---------|
| `showChangePasswordForm` | Student password change UI |
| `updatePassword` | Save new password |

### `StudentGroupController`

| Method | Purpose |
|--------|---------|
| `show` | Current group overview |
| `create` | Create group form |
| `store` | Save new group + invitations |
| `edit` | Edit group form |
| `update` | Save group changes |
| `index` | Group list / routing |
| `inviteAdviser` | Send adviser invitation |
| `inviteMember` | Invite peer student |
| `acceptInvitation` | Accept group invite |
| `declineInvitation` | Decline invite |
| `invitations` | List pending invitations |
| `removeMember` | Remove member from group |
| `requestDefense` | Hook for defense-related request from group UI |
| `cancelInvitation` | Cancel outgoing invite |

### `StudentProposalController`

| Method | Purpose |
|--------|---------|
| `index` | Proposal status + **version history** |
| `create` | New proposal form |
| `store` | Upload first proposal |
| `show` | View single submission |
| `edit` | Edit proposal (pending/rejected) |
| `update` | Save text / new file version |
| `rollback` | Copy old file as new version |
| `previewVersion` | In-browser preview one version |
| `compareVersions` | Side-by-side two proposal versions |

### `ProjectSubmissionController`

| Method | Purpose |
|--------|---------|
| `index` | **Student:** uploads list; **Adviser:** reviews by groups |
| `create` | Student upload form |
| `store` | Save upload (`proposal`/`final`/`other`) |
| `show` | View submission (student vs adviser access checks) |
| `edit` | Adviser edit review |
| `update` | Adviser status/comment |
| `destroy` | Student deletes own upload |
| `studentPreviewSubmission` | Preview one file |
| `studentCompareSubmissions` | Compare two same-type uploads |

### `StudentMilestoneController`

| Method | Purpose |
|--------|---------|
| `index` | Milestones list + **group progress bars** |
| `create` | Create milestone from template |
| `store` | Persist `GroupMilestone` |
| `show` | **Kanban** board for one milestone |
| `edit` | Edit milestone meta |
| `update` | Save milestone |
| `destroy` | Delete milestone |
| `moveTask` | Drag task column/status |
| `bulkUpdateTasks` | Batch task updates |
| `recomputeProgress` | Recalculate % |
| `updateTask` | Edit single task fields |
| `storeTaskComment` | **Threaded task comment** |
| `updateMultipleTasks` | Bulk task patch |
| `assignTask` | Assign task to member |
| `unassignTask` | Clear assignee |

### `StudentMilestoneChecklistController`

| Method | Purpose |
|--------|---------|
| `checklist` | Checklist view for milestones |

### `TaskSubmissionController`

| Method | Purpose |
|--------|---------|
| `create` | Form to submit artifact for a task |
| `store` | Save `TaskSubmission` |
| `show` | View submission |
| `review` | Review action (if used in routes) |

### `StudentDefenseRequestController`

| Method | Purpose |
|--------|---------|
| `index` | List defense requests |
| `create` | New request form |
| `store` | Submit request |
| `show` | Detail |
| `cancel` | Cancel request |

### `CoordinatorDashboardController`

| Method | Purpose |
|--------|---------|
| `index` | Coordinator dashboard |

### `CoordinatorController`

| Method | Purpose |
|--------|---------|
| `index` | Legacy coordinator landing |
| `classlist` | Filterable student class list |
| `importStudentsForm` | CSV import form |
| `importStudents` | Run import via `StudentImportService` |
| `groups` | Coordinator-managed groups |
| `create` | Create empty group form |
| `store` | Save group |
| `show` | Group detail |
| `edit` | Edit group |
| `assignAdviser` | Pick adviser for group |
| `update` | Save group + adviser |
| `destroy` | Delete group |
| `groupMilestones` | View group’s milestones |
| `notifications` | Coordinator notifications |
| `markNotificationAsRead` | … |
| `markAllNotificationsAsRead` | … |
| `deleteNotification` | … |
| `markMultipleAsRead` | … |
| `deleteMultiple` | … |
| `activityLog` | **Filtered chronological activity log** |
| `facultyMatrix` | Faculty/defense matrix summary |

### `CoordinatorProposalController`

| Method | Purpose |
|--------|---------|
| `index` | Proposals by offering |
| `show` | Proposal detail + history + comments |
| `preview` | Document preview |
| `compareVersions` | Two-version compare |
| `update` | Coordinator review fields |
| `bulkUpdate` | Bulk status |
| `getStats` | JSON stats |
| `storeComment` | Threaded proposal comment |

### `Coordinator\DefenseScheduleController`

| Method | Purpose |
|--------|---------|
| `defenseRequestsIndex` | Incoming requests queue |
| `index` | **Defense management** list + filters |
| `create` | Create schedule form |
| `store` | Persist `DefenseSchedule` + panels |
| `show` | Schedule detail |
| `edit` | Edit schedule |
| `update` | Save schedule |
| `destroy` | Delete schedule |
| `getAvailableFaculty` | **JSON** eligible faculty for panel + load balancing |
| `createSchedule` | Build schedule from approved `DefenseRequest` |
| `storeSchedule` | Save that schedule |
| `approve` | Approve defense request |
| `reject` | Reject with reason |
| `markAsCompleted` | Complete a defense |

> **Note:** `routes/web.php` references `editSchedule` and `updateSchedule` on this controller for defense-requests URLs. Those methods were **not found** in the controller file — if those routes are hit, Laravel will error until implemented or routes removed. Verify with `php artisan route:list` and test those links.

### `MilestoneTemplateController`

| Method | Purpose |
|--------|---------|
| `index` | List templates |
| `create` | New template |
| `store` | Save template + tasks |
| `edit` | Edit template |
| `update` | Save changes |
| `destroy` | Delete template |
| `updateStatus` | Activate/deactivate template |

### `CalendarController`

| Method | Purpose |
|--------|---------|
| `coordinatorCalendar` | Coordinator calendar view |
| `adviserCalendar` | Adviser calendar |
| `studentCalendar` | Student calendar |
| `chairpersonCalendar` | Chairperson calendar |

### `AdviserController`

| Method | Purpose |
|--------|---------|
| `dashboard` | Adviser home |
| `invitations` | Adviser invitations |
| `respondToInvitation` | Accept/decline group invite |
| `myGroups` | Groups where adviser |
| `groupDetails` | One group + milestone task table |
| `milestoneTaskComments` | Full-page **task comments** thread |
| `storeMilestoneTaskComment` | Post/reply on task |
| `allGroups` | Alias listing |
| `panelSubmissions` | Submissions where panelist |
| `panelInvitations` | Panel defense invites |
| `respondToPanelInvitation` | Accept/decline panel slot |
| `markAllNotificationsAsRead` | … |
| `markNotificationAsRead` | … |
| `notifications` | … |
| `markMultipleAsRead` | … |
| `deleteNotification` | … |
| `deleteMultiple` | … |
| `activityLog` | Adviser-scoped activity |

### `AdviserProposalController`

| Method | Purpose |
|--------|---------|
| `index` | Proposal inbox by group |
| `show` | Review proposal |
| `preview` | File preview |
| `compareVersions` | Compare versions |
| `edit` | Approve/reject form |
| `update` | Submit decision |
| `getStats` | Stats JSON |
| `bulkUpdate` | Bulk approve/reject |
| `storeComment` | Proposal discussion comment |

### `RatingSheetController`

| Method | Purpose |
|--------|---------|
| `showAdviserForm` | Adviser rating sheet for schedule |
| `submitAdviserRating` | Submit ratings |
| `showCoordinatorRatings` | Coordinator views collected ratings |

### `ChairpersonDashboardController` / `ChairpersonOfferingController` / `ChairpersonFacultyController` / `ChairpersonStudentController`

- **Dashboard:** chair home.
- **Offerings:** CRUD offerings, enroll/remove students, bulk enroll.
- **Faculty:** list, CSV upload, manual create/edit/delete.
- **Students:** list, export, edit, delete, bulk delete, **CSV upload** (`upload`).

### `AcademicTermController`

| Method | Purpose |
|--------|---------|
| Full resource + `toggleActive` + `toggleArchived` | Manage academic terms |

### `RoleController`

| Method | Purpose |
|--------|---------|
| `index` | Faculty roles UI |
| `update` | Assign/update role for `faculty_id` |

### `ClassController`

| Method | Purpose |
|--------|---------|
| `index`, `create`, `store` | Class model CRUD (legacy/simple) |

### `ChairPersonController`

| Method | Purpose |
|--------|---------|
| `getActiveTerm` | JSON/helper for active term |
| `notifications` + read/delete helpers | Chairperson notification inbox |

---

## Part E — Models (entities and relationships)

**Core identity**

- **`User`** — Faculty; `faculty_id`, `role`, `semester`; `account()` → `UserAccount` password; `offerings()`, `defensePanels()`, `hasRole` / `hasAnyRole`.
- **`Student`** — PK `student_id`; `groups()`, `submissions`, `offerings` pivot.
- **`StudentAccount`** — Login for students.

**Academic structure**

- **`AcademicTerm`**, **`Offering`**, **`Enrollment` / offering–student pivots** — terms, sections, who is enrolled.

**Groups and invitations**

- **`Group`** — `adviser`, `members`, `offering`, `groupMilestones`, `defenseSchedules`, `defenseRequests`.
- **`GroupInvitation`**, **`AdviserInvitation`** — invite workflows.

**Milestones**

- **`MilestoneTemplate`**, **`MilestoneTask`** — reusable definitions.
- **`GroupMilestone`** — instance per group; **`GroupMilestoneTask`** — concrete tasks (Kanban); links to **`TaskSubmission`**.
- **`TaskComment`** — threaded comments on `GroupMilestoneTask`.

**Documents**

- **`ProjectSubmission`** — proposals/files (`type`, `version`, `status`).
- **`SubmissionComment`** — threaded comments on proposals.

**Defense**

- **`DefenseRequest`**, **`DefenseSchedule`**, **`DefensePanel`** — requests, scheduled slot, who sits on panel (`role`: chair/member/adviser/coordinator).
- **`RatingSheet`** — ratings tied to defense/group.

**Audit / comms**

- **`ActivityLog`** — student-centric log rows (`action`, `description`, polymorphic `loggable`).
- **`Notification`** — in-app notifications.

---

## Part F — Services and imports

| File | Role |
|------|------|
| `DocumentPreviewService` | `publicUrl`, `embedKind`, `iframeSrc`, **`panelForSubmission()`** for previews |
| `ActivityLogService` | `logTaskCompleted`, `logSubmissionCommentAdded`, **`logTaskCommentAdded`** |
| `NotificationService` | Create notifications for proposals, defenses, invites, etc. |
| `StudentImportService` | Coordinator/chair import pipelines (`MODE_COORDINATOR`, etc.) |
| `StudentEnrollmentService` | Enrollment helpers |
| `App\Imports\StudentsImport` | Excel row mapping for students |
| `App\Imports\FacultyImport` | Excel row mapping for faculty |

---

## Part G — Database (main tables)

Derived from migrations naming — **not** every column:

`users`, `user_accounts`, `user_roles`, `roles`, `students`, `student_accounts`, `academic_terms`, `offerings`, `enrollments` / `offering_student`, `groups`, `group_members`, `group_invitations`, `adviser_invitations`, `milestone_templates`, `milestone_tasks`, `group_milestones`, `group_milestone_tasks`, `task_submissions`, `task_comments`, `project_submissions`, `submission_comments`, `defense_requests`, `defense_schedules`, `defense_panels`, `panel_assignments` (if used), `rating_sheets`, `activity_logs`, `notifications`, `sessions`, `cache`, `jobs` …

Use **`database/migrations/`** for ground truth.

---

## Part H — Views (`resources/views`)

| Folder | Audience |
|--------|-----------|
| `student/` | Student UI (dashboard, group, proposal, milestones, project, defense-requests, …) |
| `adviser/` | Adviser UI |
| `coordinator/` | Coordinator UI (**defense/create.blade.php** has auto-assign JS) |
| `chairperson/` | Chairperson UI |
| `layouts/` | Shared shells (`student`, `adviser`, `coordinator`, …) |
| `partials/` | Shared fragments (e.g. **document-embed-panel**) |

---

## Part I — End-to-end flows (for defense narrative)

1. **Chairperson** creates **terms** + **offerings**, imports **students/faculty** → students enrolled in capstone offerings.
2. **Student** joins/creates **group**, invites **adviser** → **StudentGroupController**.
3. **Student** submits **proposal** → **StudentProposalController**; **adviser** reviews → **AdviserProposalController**; optional **coordinator** oversight → **CoordinatorProposalController**.
4. **Coordinator** creates **groups** / assigns **adviser**; **milestone templates** applied → **GroupMilestone** / tasks.
5. **Students** move tasks on **Kanban**, comment → **StudentMilestoneController** + **TaskComment**; **ActivityLogService** records key actions.
6. **Defense request** → **StudentDefenseRequestController** → **Coordinator** approves and **DefenseScheduleController** schedules slot + **DefensePanel** (manual picks or **auto-assign** using `getAvailableFaculty`).
7. **Calendar** → **CalendarController** per role.

---

## Part J — Deployment (Railway)

- **`railway.json`**: Nixpacks; **review** `startCommand` (avoid `migrate:fresh` on every boot for real data; use **`PORT`**).
- **`railway.env`**: Template — copy keys into Railway project variables.
- **`Procfile`**: `php artisan serve` with `$PORT`.

---

## Part K — How to find anything quickly

1. **URL → Controller:** `php artisan route:list` or search path string in `routes/web.php`.
2. **Controller → Views:** search `return view(` inside that controller.
3. **Model fields / relations:** open `app/Models/<Model>.php`.
4. **“Where is X stored?”:** grep model name in `database/migrations`.

---

## Part L — Team ownership (working agreement — **code**)

| Person | Primary areas (typical) |
|--------|-------------------------|
| **RANCES** | Student group/milestone/adviser-facing flows (`StudentGroupController`, `StudentMilestoneController`, `AdviserController` patterns). |
| **ORTIZ** | Coordinator/chairperson/defense scheduling (`CoordinatorController`, `DefenseScheduleController`, chairperson controllers). |

Coordinate before editing the other person’s primary controller; extract shared logic into **`app/Services`** when both need it.

**Note:** *Code* ownership (above) may differ from **who speaks at oral defense** for each requirement — see **Part M**.

---

## Part M — Defense presentation: who explains what (proponents reference)

**Proponents:** Rances, Rainer Josh · Ortiz, Sean Michael  

Use during rehearsal so **each demo block has a primary speaker** and a backup. Tables follow **your MUST HAVES / roles document**; where the **same feature is listed twice** with **different proponents**, align **before** defense (see **M.3**).

**Status legend (your slides):** (Green) Complete · (Yellow) In progress · (Red) Not yet started — track in the deck, not here.

### M.1 MUST HAVES — by feature (as labeled in your document)

| Feature (short) | Your doc says | Suggested who leads the demo + Q&A | Notes |
|-----------------|---------------|-------------------------------------|--------|
| **Architecture** (Web, DB, System) | — | **Both** (one diagram, one data model/stack) | 1–2 min total; don’t repeat the same slide. |
| **Capstone Milestone Tracker** (stages, Gantt *or* progress bar, must-haves, tasks → %, **Pending/Doing/Done**, task comments) | **ORTIZ** | **Ortiz** leads: **Milestones** + **Kanban** `show`, progress bars, task comments. **Rances** one line on **adviser** same group if asked. | `StudentMilestoneController`, `GroupMilestone` / `GroupMilestoneTask`, `TaskComment`. |
| **Task Scheduler** (deadlines, assign, complete → %, comments) | **ORTIZ** | **Ortiz** — same path as tracker (board + deadlines). | One demo path. |
| **Team Management** (assign responsibilities, contribution / transparency, faculty role mapping) | **RANCES** | **Rances** leads: group **members**, **roles**, activity for “contribution.” **Ortiz** adds coordinator **classlist / activity log** if asked. | Overlap with activity logs — see **M.3**. |
| **Document uploads & feedback** (Word/PDF, threaded comments, version history, preview, compare, download/rollback) | **ORTIZ** | **Ortiz**: coordinator proposal UI + comments + preview/compare. **Rances**: student upload + versions + **adviser** review. | Split **by role** in demo, not by who coded. |
| **Notification system** (in-app alerts) | **RANCES** | **Rances**: student + adviser examples. **Ortiz**: coordinator if time. | `NotificationService`, `Notification`, dashboards. |
| **Defense Schedule Manager** (1st) — request slots, approve/reject, panel accept/decline, **auto panel**, list/calendar | **RANCES** | **Rances**: student **defense request**, **panel** invite response, **auto-assign** rules. **Ortiz**: coordinator defense index, approve/reject, schedule, calendar. | Also listed under **ORTIZ** — see **M.3**. |
| **Defense Schedule Manager** (2nd block, same title) | **ORTIZ** | **Ortiz** primary: **coordinator** defense management UI. | Align with row above. |
| **Project Timeline View** (1st) — calendar *or* Gantt, deliverables | **ORTIZ** | **Ortiz**: deadlines in UI (milestones + defense). | Doc also says **RANCES** for timeline — see **M.3**. |
| **Activity logs per member** | **ORTIZ** (later lines) | **Ortiz**: coordinator **activity log** + member filter. **Rances**: **adviser** log + `ActivityLog` on uploads/tasks. | One story: accountability. |
| **Submission version history** | **RANCES & ORTIZ** | **Both**: Rances = student rollback + adviser compare; Ortiz = coordinator compare. | `ProjectSubmission`, `DocumentPreviewService`, proposal controllers. |
| **Calendar view for all roles** (role-based filtering) | **RANCES** | **Rances** leads: student vs adviser vs coordinator calendar. **Ortiz** backup. | `CalendarController` per role; custom grid is defensible vs FullCalendar. |
| **Role-based access control** | **Both** | **Rances**: `student` guard + **adviser** routes. **Ortiz**: **coordinator** + **chairperson** + `checkrole`. | `routes/web.php`, `CheckRole`, `StudentAuthMiddleware`. |

### M.2 MUST HAVES — by **role** (who presents which hat)

| Role | Your doc | Primary explainer (suggested) | What to show / say (~1 min) |
|------|----------|------------------------------|-----------------------------|
| **SuperAdmin / Chairperson** | **ORTIZ** | **Ortiz** | Offerings, terms, import, archive, schedules. `Chairperson*`, `AcademicTermController`. |
| **Coordinator** | **RANCES** | **Rances** *(per your PDF)* | Classlist, import, defense scheduling, **faculty matrix**, **rating sheets**, milestone read-only, student status, proposals. **Clarify with team:** `task.md` may assign `CoordinatorController` to **Ortiz** for code — for the **panel**, follow **submitted document** or one agreed sentence on who demos coordinator. |
| **Faculty** (adviser / panel / teacher) | **RANCES & ORTIZ** | **Split by screen** | Rances: **adviser** groups, milestones, proposals, task comments, panel invites. Ortiz: coordinator-adjacent faculty views if needed. |
| **Students** | **RANCES & ORTIZ** | **Split by flow** | Rances: **group**, **milestones**, **defense request**. Ortiz: enrollment / how records enter system. **Messaging:** no separate team chat — comments + invites + defense comms (per team scope). |

### M.3 Contradictions in the proponents list (resolve before defense)

| Topic | Conflict | What to do |
|-------|------------|------------|
| **Defense Schedule Manager** | First **RANCES**, later **ORTIZ** | **Split**: Rances = student + panelist; Ortiz = coordinator — or one primary + one supports. |
| **Project Timeline View** | First **ORTIZ**, later **RANCES** | One owner for “timeline,” or **joint** (Ortiz = dates in UI; Rances = role **Calendar**). |
| **Activity logs** | ORTIZ vs shared wording | Ortiz = coordinator log; Rances = adviser log + student-triggered `ActivityLog`. |
| **Coordinator speaker** | PDF **RANCES** vs internal **Ortiz** code habit | Panel follows **PDF** or explicit team agreement. |

### M.4 NICE TO HAVES (short answers)

| Item | Who answers “future / out of scope” |
|------|-------------------------------------|
| SMS, Google/iCal, analytics, real-time chat | **Either** — one sentence. |
| PDF in-platform | **Either** — preview via browser/Office already covers “basic.” |

### M.5 “To accomplish ASAP” — suggested lead

| Item | Suggested lead |
|------|----------------|
| Laravel login & role-based auth | **Both** (Rances: student + summary; Ortiz: chair + coordinator) |
| Milestone tracker + task scheduler | **Ortiz** (per doc) |
| Document upload + threaded feedback | **Ortiz** primary; **Rances** student/adviser |
| Defense scheduling | **Rances** + **Ortiz** (student vs coordinator) |
| Activity + version logs | **Ortiz** coordinator activity; **both** version history |
| Faculty matrix + group assignment | **Rances** (doc) coordinator matrix; **Ortiz** chair offerings if separate |

### M.6 Panel / advisors (slide credits — optional)

Engr. Vicente Patalita III — Project Advisor · Mr. Temothy Cole Homecillo — Project Coordinator · Mr. Roderick Bandalan — Panel Chairman · Engr. Violdan Bayocot — Panel Member  

---

*This guide describes the repository structure as of the last update. Regenerate route list before defense: `php artisan route:list`. Update **Part M** when you **lock** who presents **Coordinator** and **Defense** so you do not contradict each other live.*
