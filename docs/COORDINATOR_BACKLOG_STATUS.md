# Coordinator backlog — status vs codebase

This document captures a **snapshot analysis** of requested Coordinator (and related) improvements against the CapTracks codebase. It is **not** a roadmap commitment; re-verify behavior in the app when prioritizing work.

**Last reviewed:** 2026-05-10 (group-scoped panel dropdowns #11; final grades page #13).

**Done column:** ✅ = done or meets the request in practice · 🔶 = partial / mixed · ⬜ = not done or still open

---

## Defense scheduling

| Done | # | Request | Status |
|:----:|---|---------|--------|
| ✅ | 1 | **Prefilled when group is approved / when creating** the schedule page | **Done (2026-05-10).** After **Approve Request**, coordinator is redirected to **`coordinator.defense.create?from_request={id}`** with **group** and **defense stage** (from `defense_requests.defense_type`) pre-selected when valid (`DefenseScheduleController::approve`, `create`). Scoped to coordinator offerings + active term; warns if the group is no longer available for a new schedule. Schedule-from-request (`create-schedule`) still prefills date/time from preferences. |
| ✅ | 2 | **Auto-assign panel:** drop manual dropdown change; keep conflict logic | **Done (2026-05-10).** Create defense shows **read-only** Chair/Member labels fed by the same `available-faculty` JSON (`autoAssignedFacultyIds` + names). **No dropdowns**; `DefenseScheduleController::store` assigns via `resolveAutoPanelMembers()`. **Schedule from request** (`defense-requests/create-schedule`) matches the same behavior; preview uses **`duration_hours: 2`** so the window aligns with `storeSchedule` (fixed two-hour block). Double-booking, past start, time order (create-defense only), and insufficient-faculty preview unchanged in intent. |
| ✅ | 3 | **More than two** panelists (beyond Chair + Member) | **Done (2026-05-10).** Configurable **`DEFENSE_PANEL_SLOTS`** / `config/defense.php` (default **4**: Chair + Member + 2 Panelists). DB: `panelist` on `defense_panels.role` (MySQL migration). Auto-assign, create-defense preview, edit-defense slots, schedule-from-request, notifications, calendar payload, rating readiness, and adviser/adviser-sidebar queries use **`DefensePanel::INVITED_ROLES`**. |
| ✅ | 4 | **Modal** for time errors on defense schedule | **Met in practice (not a literal modal).** Create/edit defense use **inline danger alerts** (`pastStartWarning`, `timeOrderWarning` in `create.blade.php` / `edit.blade.php`), `is-invalid` on fields, submit-time messages + scroll, `available-faculty` **422** handling for past start and **invalid time window** (end ≤ start), and server **`withErrors`** from `DefenseScheduleController`. That covers the same intent as a time-error modal; a separate Bootstrap `modal` component was never added. |
| ✅ | 5 | **Time** must not be in the past | **Implemented.** Server: `defenseStartMustBeInFuture()` plus explicit app-timezone parsing (`parseDefenseWindow` / `parseDefenseDateAndTime`) in `DefenseScheduleController`. Create-defense UI: red banner, invalid state on start time, submit `alert`, and `available-faculty` 422 handling (`resources/views/coordinator/defense/create.blade.php`). |
| ✅ | 6 | **Date** checks | **Implemented on the server** in `DefenseScheduleController`: `required|date`, window parsing in app timezone, **future start** (`defenseStartMustBeInFuture`), **end after start**, **one defense per group per calendar day** (`hasGroupScheduleOnDate`), and **room double-booking** (`checkDoubleBooking`). Client-side **`min`** still **varies by screen by design**: create-defense uses **today** (`create.blade.php`), schedule-from-request uses **tomorrow** (`defense-requests/create-schedule.blade.php`), edit-defense has **no `min`** (editing existing dates); invalid submissions still fail with validation errors. |

---

## Coordinator UI, milestones, calendar, grades

| Done | # | Request | Status |
|:----:|---|---------|--------|
| ✅ | 7 | **Rating sheet title** larger / more prominent | **Done (2026-05-10).** Coordinator aggregate page reworked for clearer hierarchy: top card with group name + compact stage badge (`resources/views/components/rating-sheet/stage-badge.blade.php`), navbar title **Rating Sheets** only, finalize section aligned with other cards, metrics/sheets layout polished (`resources/views/coordinator/rating-sheets/show.blade.php`). Panel entry form unchanged aside from shared stage badge sizing. |
| ✅ | 8 | **Tasks added do not appear on Kanban** (bug) | **Fixed (2026-05-10).** Adding a task on the milestone **template** only created `milestone_tasks`; existing **group milestones** already assigned that template never received matching `group_milestone_tasks`, which the student Kanban reads. `MilestoneTemplateController::storeTask` now **`firstOrCreate`s** a `GroupMilestoneTask` per assigned group milestone and recomputes progress. **Repair existing data:** `php artisan milestones:sync-group-tasks`. |
| ✅ | 9 | **Drag tasks** to update milestone status | **Done for students (2026-05-10):** Sortable + `PATCH` `student.milestones.move-task`; after move, **`updateTaskCardStatusUI`** refreshes the status badge and title styling (no stale “Pending” in **In Progress**). Labels use **In Progress** (not “Doing”). Redundant per-card status icon row removed—columns + drag are enough. **Coordinator** workflow still uses template/group milestone admin UIs, not this Kanban. |
| ✅ | 10 | **New milestone tasks do not auto-reflect** (live update) | **Same underlying issue as #8 (fixed 2026-05-10):** template tasks now sync to `group_milestone_tasks`, so a **refresh** or revisiting the Kanban shows new tasks. **True live update** (new cards appearing in an **already-open** tab without reload) is **not** implemented—no Echo/Pusher/polling on `student/milestones/show.blade.php`. If that polish is required, treat as a separate enhancement. |
| ✅ | 11 | **Dropdowns scoped to the group** (faculty/panel options) | **Done (2026-05-10).** Shared helper **`panelFacultyJsonByGroupIds`** / **`panelChairMemberCandidates`** builds the same pool everywhere. **Edit defense:** JSON map per group + **change Group** refills panel selects; **server validation** rejects picks outside the pool. **Schedule from request** uses the same JSON map for preview only; **store-schedule** assigns via **`resolveAutoPanelMembers()`** (no manual picks). |
| ✅ | 12 | **Timezone** aligned to Philippines | **App default:** `config/app.php` uses `env('APP_TIMEZONE', 'Asia/Manila')`. If times still look wrong, check `.env`, DB stored UTC vs display, and any JavaScript `Date` formatting in the browser. |
| ✅ | 13 | **Final grades** table (sidebar): columns for proposal, 60%, 100%, etc. | **Done (2026-05-10).** Sidebar **Final grades** → `GET /coordinator/final-grades` (`CoordinatorController::finalGrades`, view `coordinator/final-grades/index.blade.php`). Table lists scoped groups with **Proposal / 60% / 100%** columns: latest **completed** defense per stage shows **average score**, **recommendation** badge, link to **rating sheets** when a **`defense_evaluation_summaries`** row exists. |
| ✅ | 14 | **“In progress”** Kanban column not behaving | **Fixed (2026-05-10):** drag-and-drop updated the server but the card badge stayed wrong—**`updateTaskCardStatusUI`** now runs after a successful move (`student/milestones/show.blade.php`). Column counts/progress were already updating; the status badge and title styling now match **`doing`** / **In Progress**. |
| 🔶 | 15 | **Student Kanban within group**; leader assigns tasks (*nice to have*) | **Partially supported** via student milestone/task routes (e.g. assign/move endpoints). Full “leader-only” UX polish is product-dependent. |

---

## Imports, students, submissions, filters

| Done | # | Request | Status |
|:----:|---|---------|--------|
| ✅ | 16 | **Student import:** accept existing rows; **first login** sends credentials; **school_year + semester** columns | **Implemented:** import **does not** email passwords on upload; students use **“Email me a temporary password”** on the login page; CSV expects **`school_year`** plus slot (`1st` / `2nd` / `summer`) and **rejects** a full combined Academic Term string in the `semester` column (`StudentsImport` + `StudentTemporaryPasswordController`). **Follow-up (2026-05):** mail uses Laravel SMTP (`config/mail.php`); temp password send is transactional; login shows clear success vs SMTP failure vs “already completed first login” / bad email (`StudentTemporaryPasswordController`, `StudentCredentialProvisioner`). |
| ✅ | 17 | Remove **type** column from student project submit/upload listing | **Implemented (2026-05-10).** No **Type** column on the submissions table. **Follow-up:** the **“Compare two versions (same type)”** card was **removed** from the same view—listing-only; compare route may remain for bookmarks. |
| ✅ | 18 | **Calendar** should show **panelists** | **Implemented (2026-05-10).** `CalendarController::coordinatorCalendar` passes `extendedProps.panelists` (chair/member names + invite status) with `panelists.faculty` eager-loaded; `resources/views/calendar/coordinator.blade.php` modal includes a **Panel** section. Coordinator calendar also scopes defenses to the coordinator’s offerings and uses timezone-safe `isSameDay` grid matching (same changes applied to student/adviser/chairperson calendar blades for day placement). |
| ✅ | 19 | **Filter defenses by adviser** | **Implemented.** Coordinator defense index (`DefenseScheduleController::index`) adds GET **`adviser_faculty_id`** (matches `groups.faculty_id`). Dropdown lists advisers drawn from groups in the coordinator’s offerings (scoped to the active term when set). Applies to **pending requests** and **scheduled defenses** tables; invalid IDs are ignored. |

---

## Related files (quick reference)

| Area | Primary locations |
|------|-------------------|
| Defense create / panel JS | `resources/views/coordinator/defense/create.blade.php` |
| Defense controller | `app/Http/Controllers/Coordinator/DefenseScheduleController.php` |
| Schedule from request | `resources/views/coordinator/defense-requests/create-schedule.blade.php` |
| Coordinator calendar | `app/Http/Controllers/CalendarController.php`, `resources/views/calendar/coordinator.blade.php` |
| Coordinator rating sheets (aggregate) | `resources/views/coordinator/rating-sheets/show.blade.php`, `resources/views/components/rating-sheet/stage-badge.blade.php` |
| Coordinator final grades (sidebar) | `CoordinatorController::finalGrades`, `resources/views/coordinator/final-grades/index.blade.php` |
| Student import | `app/Imports/StudentsImport.php`, `app/Support/ImportAcademicFieldsResolver.php` |
| Temp password flow | `app/Http/Controllers/StudentTemporaryPasswordController.php` |
| Student project list | `resources/views/student/project/index.blade.php` |
| Student Kanban | `resources/views/student/milestones/show.blade.php`, `resources/views/student/milestones/partials/task-card.blade.php`, `app/Http/Controllers/StudentMilestoneController.php` |
| Template task → group Kanban rows | `app/Http/Controllers/MilestoneTemplateController.php` (`storeTask`), `php artisan milestones:sync-group-tasks` |

---

## How to use this doc

1. **Scan the Done column** — ✅ is closure-worthy; 🔶 needs follow-up or scope clarity; ⬜ is still outstanding.
2. **Prioritize** rows marked ⬜ or “open / unverified.”
3. **Re-test** items marked “unverified” in the running app (especially Kanban and task sync).
4. **Update** this file when a row ships or when behavior changes.
