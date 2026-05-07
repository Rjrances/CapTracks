# CapTrack — MUST HAVE Implementation Audit

**Purpose:** Compare the capstone MUST HAVE specification (for 100% defense) against the current CapTracks codebase.  
**Scope:** Laravel application logic, routes, models, and representative views — not thesis chapter wording.

---

## Before panels: clean up the specification

The MUST HAVE document **repeats** several features under different owner lines (e.g. Defense Schedule Manager, Project Timeline, Activity Logs, Submission Version History). For defense paperwork, **deduplicate**: one bullet per capability with consistent ownership notes, so panels know which document is authoritative.

---

## Legend

| Symbol | Meaning |
|--------|---------|
| **Green** | Implemented in code with clear backing (routes/models/views). |
| **Yellow** | Partially aligned or wording differs from what the app actually does. |
| **Red** | Missing or materially different from the written requirement. |

---

## Feature alignment summary

### Capstone Milestone Tracker

| Requirement | Status | Notes |
|-------------|--------|-------|
| Visual progress by stage (Proposal → Capstone milestones → defenses) | **Yellow** | Progress is milestone-template + Kanban/progress bars; ties to proposal/60%/100% stages depend on template naming/order and defense module, not a single fixed pipeline widget. |
| Gantt **or** progress bar | **Yellow** | **Progress bars** and Kanban are present; **no dedicated Gantt** chart surfaced in codebase search. Satisfies “or” if rubric accepts progress bars + Kanban. |
| Milestones from Capstone Must Haves | **Green** | `MilestoneTemplate` / tasks; student checklist titled “Must Have Checklist” (`student/milestones/checklist.blade.php`). |
| Tasks per member, % from completion | **Green** | `GroupMilestoneTask`, assignment, `calculateProgressPercentage()`. |
| Comments on tasks | **Green** | `TaskComment` with `parent_id` (threaded). |
| Status: Pending, Doing, Done | **Green** | `GroupMilestoneTask` statuses and UI. |

### Task Scheduler

| Requirement | Status | Notes |
|-------------|--------|-------|
| Deadlines, assign to members | **Green** | `deadline`, `assigned_to` on `GroupMilestoneTask`. |
| Complete → milestone % | **Green** | Completion updates group milestone progress. |
| Comments on tasks | **Green** | Same as milestone task comments. |

### Team Management

| Requirement | Status | Notes |
|-------------|--------|-------|
| Responsibilities per member | **Green** | Group membership, leader role, task assignment. |
| Contribution / transparency logs | **Yellow** | `ActivityLog` + `ActivityLogService` audit key actions; not framed as “grading ledger” explicitly. |
| Faculty role mapping | **Green** | Roles (chairperson, coordinator, adviser, panelist, teacher, student flows). |

### Document Uploads and Feedback

| Requirement | Status | Notes |
|-------------|--------|-------|
| Upload drafts (Word/PDF as supported) | **Green** | `ProjectSubmission` and proposal flows. |
| Coordinator/Faculty threaded comments | **Green** | `SubmissionComment` with `parent_id`. |
| Version history: preview, compare, rollback/download | **Green** | `version` on submissions; preview/compare/rollback routes in `routes/web.php`. |

### Notification System

| Requirement | Status | Notes |
|-------------|--------|-------|
| In-app alerts for deadlines, approvals, feedback, schedules | **Green** | `NotificationService` wired from defenses, proposals, groups, advising, etc. |

### Defense Schedule Manager

| Requirement | Status | Notes |
|-------------|--------|-------|
| Students request defense | **Green** | `StudentDefenseRequestController`, defense types proposal / 60% / 100%. |
| “Available slots” from students | **Yellow** | UI describes a **readiness request** — students do **not** pick concrete room/time slots; coordinator schedules. Clarify this in SRS/defense slides. |
| Coordinator approve/reject, finalize schedules | **Green** | `DefenseScheduleController` + defense request approve/reject. |
| Panel accept/decline | **Green** | `DefensePanel`, adviser panel invitation flows. |
| Automatic panel assignments | **Yellow** | **Assisted**: adviser + subject coordinator auto-attached to panel; chair/member from candidates with workload hints (`getAvailableFaculty`). Coordinator still assigns chair + member explicitly. |
| List or calendar view | **Green** | Defense index/list + role calendars (`CalendarController`). |

### Project Timeline View

| Requirement | Status | Notes |
|-------------|--------|-------|
| Calendar or Gantt of deliverables | **Yellow** | Calendars emphasize **scheduled defenses**. Milestone/task deadlines live in milestone UI/dashboards — **not merged** into defense calendar JSON in `CalendarController`. |

### Activity Logs per Member

| Requirement | Status | Notes |
|-------------|--------|-------|
| Uploads, comments, task completions | **Green** | `ActivityLog` / `ActivityLogService` patterns across submissions, milestones, defenses. Coordinator/adviser activity log routes exist where implemented. |

### Submission Version History

| Requirement | Status | Notes |
|-------------|--------|-------|
| Track changes; preview/compare/rollback | **Green** | Version field + compare/preview/rollback routes for proposals/projects. |

### Calendar View for All Roles

| Requirement | Status | Notes |
|-------------|--------|-------|
| Role-based calendar | **Green** | `CalendarController`: coordinator, adviser, student, chairperson — events from `DefenseSchedule`. Implementation is **month-grid** style with event payload (not necessarily FullCalendar.js unless added in assets). |

### Role-Based Access Control

| Requirement | Status | Notes |
|-------------|--------|-------|
| Chairperson offerings, terms, students, notifications, calendar | **Green** | `routes/web.php` chairperson prefix. |
| Coordinator classlist, defense, groups, milestones resource, proposals, faculty matrix, rating sheets | **Green** / **Yellow** on roster | Coordinator **sees** roster + **CSV import** (`classlist`, `classlist/import`). **Not** individual add/edit/delete on Class List — align SRS (see bullet below). Rest of coordinator features as implemented. |
| Faculty advising/panel/notifications/projects/proposals/rating/calendar | **Green** | Adviser routes. |
| Students: login, password change, group, milestones, uploads, checklist, defenses | **Green** | Student prefix + middleware. |

#### Spec inconsistencies to fix in writing or code

- **Coordinator “upload and manage classlist (add or delete student records)”** is **stronger than the app**: coordinators get **view/filter** + **Import students** (CSV) only — `CoordinatorController::classlist`, `importStudentsForm` / `importStudents` in `routes/web.php`. There is **no** coordinator route to **delete** a student or **manually add one row** from the Class List UI. **Chairperson** student management includes delete (`chairperson.students.delete`). **Suggested SRS wording:** *“Coordinator: view and filter the class list for coordinated offerings; bulk-import students via CSV to an offering.”* Add per-student remove only if you implement it.
- **Coordinator “cannot add milestones”** vs routes: coordinators (with adviser in same middleware) have access to `milestones` resource. Verify `MilestoneTemplateController` authorization; align document with actual policy.
- **Checklist “approved must haves”** vs implementation: checklist loads templates via `MilestoneTemplate::...->get()` without an explicit **approved-only** filter. If templates use `status`, filter checklist to approved rows for strict alignment.
- **SuperAdmin naming:** App uses **`chairperson`** role — map SuperAdmin ↔ Chairperson in documentation.
- **Chairperson “move tasks Pending → Done”:** No dedicated chairperson task board found in routing survey; verify or remove from official MUST HAVE list.

---

### Group / project messaging (“chat” via Kanban)

| Requirement | Status | Notes |
|-------------|--------|-------|
| **Group / project collaboration** across students + faculty | **Green** | Implemented as **per-task threaded discussion** on the milestone Kanban, not a separate global chat room — consistent with SRS-style “group messaging / collaboration” and with Nice-to-have guidance that **real-time chat is optional**. |

**Where it lives in code**

- **Student Kanban** (`resources/views/student/milestones/show.blade.php`): each task opens a modal titled **Discussion** (`#taskCommentsModal…`) embedding `partials/task-comments-thread`; cards show a discussion control + badge count (`resources/views/student/milestones/partials/task-card.blade.php`).
- **Thread UI** (`resources/views/partials/task-comments-thread.blade.php`): top-level posts and **nested replies** (`parent_id`) — chat-style threading in context of each task.
- **Student routes** (`routes/web.php`): `student.milestones.task-comments.store` → `StudentMilestoneController::storeTaskComment`.
- **Adviser / faculty** (`routes/web.php`): `adviser.groups.milestone-task-comments` (view thread) + `adviser.groups.milestone-task-comments.store`; read-only Kanban links **View discussion →** (`resources/views/adviser/milestones/partials/task-card-readonly.blade.php`).
- **Persistence**: `TaskComment` on `GroupMilestoneTask`; activity logging via `ActivityLogService::logTaskCommentAdded` where wired.

**How to phrase this in SRS / defense:** *Async group coordination is implemented as threaded task discussions on each group’s milestone Kanban, so adviser and students message in context of work items without a standalone realtime chat module.*

---

## Nice to HAVES (not audited in depth here)

Per project docs: SMS, in-app PDF viewer, external calendar sync, analytics dashboard — typically **nice to have**; confirm status separately if demanded.

---

## “To accomplish ASAP” vs codebase (snapshot)

| Item | Typical status in codebase |
|------|----------------------------|
| Laravel login & RBAC | Present |
| Milestone tracker + task scheduler | Present |
| Document upload + threaded feedback | Present |
| Defense scheduling module | Present (with student wording caveat) |
| Activity and version logs | Present |
| Faculty role matrix & group assignment | Present (matrix, panel groups, adviser flows) |

---

## How to use this during 100% defense

1. **Demo path:** student checklist → milestones/Kanban + progress → task comments → proposal upload/version compare → defense request → coordinator schedule + panel → calendar by role → notifications.
2. **Honest deltas:** readiness request ≠ slot picker; calendars = defenses more than full deliverable fusion; optional Gantt; group chat = **Kanban task discussions** (threaded, not WebSocket realtime unless you add it later).
3. **Update SRS** once: dedupe MUST HAVES, fix coordinator milestone wording, add “approval filter” for checklist if required.

---

*Generated as a codebase alignment reference for thesis / defense preparation. Update after major releases.*
