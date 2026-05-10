# Coordinator backlog — status vs codebase

This document captures a **snapshot analysis** of requested Coordinator (and related) improvements against the CapTracks codebase. It is **not** a roadmap commitment; re-verify behavior in the app when prioritizing work.

**Last reviewed:** 2026-05-10 (includes student Kanban drag UI fixes, project submissions page, and backlog row updates).

**Done column:** ✅ = done or meets the request in practice · 🔶 = partial / mixed · ⬜ = not done or still open

---

## Defense scheduling

| Done | # | Request | Status |
|:----:|---|---------|--------|
| 🔶 | 1 | **Prefilled when group is approved / when creating** the schedule page | **Mostly implemented** for the “schedule from defense request” flow (`resources/views/coordinator/defense-requests/create-schedule.blade.php`): date/time can prefill from `preferred_date` / `preferred_time`; group and adviser context are shown. Whether the page appears only after “approval” depends on routing/workflow, not just this template. |
| ⬜ | 2 | **Auto-assign panel:** drop manual dropdown change; keep conflict logic | **Not implemented as stated.** Create defense (`resources/views/coordinator/defense/create.blade.php`) still uses **two dropdown rows** (Chair + Member) while JS auto-suggests faculty and conflict checks run. It is **auto-suggestion + dropdowns**, not “pure auto-assign with no dropdown editing.” |
| ⬜ | 3 | **More than two** panelists (beyond Chair + Member) | **Not implemented** for the main flows: auto-assign resolves two roles; UI is two panel slots. Broader multi-panelist support would need model/UI/controller changes. |
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
| 🔶 | 11 | **Dropdowns scoped to the group** (faculty/panel options) | **Largely implemented** for defense create: `panelFacultyByGroupId` drives options per group; adviser/coordinator excluded as documented. |
| ✅ | 12 | **Timezone** aligned to Philippines | **App default:** `config/app.php` uses `env('APP_TIMEZONE', 'Asia/Manila')`. If times still look wrong, check `.env`, DB stored UTC vs display, and any JavaScript `Date` formatting in the browser. |
| ⬜ | 13 | **Final grades** table (sidebar): columns for proposal, 60%, 100%, etc. | **Not implemented** as a first-class feature in the reviewed routes/views. |
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
