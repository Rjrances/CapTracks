# Coordinator backlog — status vs codebase

This document captures a **snapshot analysis** of requested Coordinator (and related) improvements against the CapTracks codebase. It is **not** a roadmap commitment; re-verify behavior in the app when prioritizing work.

**Last reviewed:** 2026 (from repository inspection).

---

## Defense scheduling

| # | Request | Status |
|---|---------|--------|
| 1 | **Prefilled when group is approved / when creating** the schedule page | **Mostly implemented** for the “schedule from defense request” flow (`resources/views/coordinator/defense-requests/create-schedule.blade.php`): date/time can prefill from `preferred_date` / `preferred_time`; group and adviser context are shown. Whether the page appears only after “approval” depends on routing/workflow, not just this template. |
| 2 | **Auto-assign panel:** drop manual dropdown change; keep conflict logic | **Not implemented as stated.** Create defense (`resources/views/coordinator/defense/create.blade.php`) still uses **two dropdown rows** (Chair + Member) while JS auto-suggests faculty and conflict checks run. It is **auto-suggestion + dropdowns**, not “pure auto-assign with no dropdown editing.” |
| 3 | **More than two** panelists (beyond Chair + Member) | **Not implemented** for the main flows: auto-assign resolves two roles; UI is two panel slots. Broader multi-panelist support would need model/UI/controller changes. |
| 4 | **Modal** for time errors on defense schedule | **Not implemented as a dedicated UX.** Failures use **validation + redirect** with errors (e.g. `DefenseScheduleController::defenseStartMustBeInFuture`). The coordinator calendar modal is for **event details**, not validation messaging. |
| 5 | **Time** must not be in the past | **Implemented (server-side).** `defenseStartMustBeInFuture()` blocks past start times on relevant store/update paths. |
| 6 | **Date** checks | **Partially implemented.** HTML `min` attributes on date inputs (varies by screen: e.g. “today” vs “+1 day” for requests); additional rules include same-day duplicate schedule for group, room double-booking, etc. |

---

## Coordinator UI, milestones, calendar, grades

| # | Request | Status |
|---|---------|--------|
| 7 | **Rating sheet title** larger / more prominent | **No dedicated global change identified.** Coordinator aggregate view: `resources/views/coordinator/rating-sheets/show.blade.php`. Panel/coordinator rating entry reuses `resources/views/adviser/rating-sheets/form.blade.php`. Any “bigger title” would be a targeted CSS/layout tweak in those views. |
| 8 | **Tasks added do not appear on Kanban** (bug) | **Open / unverified fix** from static analysis—confirm with a manual retest after adding tasks. |
| 9 | **Drag tasks** to update milestone status | **Student Kanban:** implemented (`student/milestones/show.blade.php` — Sortable + PATCH move). **Coordinator** milestone management is not the same Kanban drag experience. |
| 10 | **New milestone tasks do not auto-reflect** (live update) | **Open / unverified**—treat as bug until reproduced and fixed. |
| 11 | **Dropdowns scoped to the group** (faculty/panel options) | **Largely implemented** for defense create: `panelFacultyByGroupId` drives options per group; adviser/coordinator excluded as documented. |
| 12 | **Timezone** aligned to Philippines | **App default:** `config/app.php` uses `env('APP_TIMEZONE', 'Asia/Manila')`. If times still look wrong, check `.env`, DB stored UTC vs display, and any JavaScript `Date` formatting in the browser. |
| 13 | **Final grades** table (sidebar): columns for proposal, 60%, 100%, etc. | **Not implemented** as a first-class feature in the reviewed routes/views. |
| 14 | **“In progress”** Kanban column not behaving | **Label exists** (`In Progress` maps to status `doing` in `student/milestones/show.blade.php`). If the issue is **counts/cards not updating**, that is separate from the label text—needs runtime verification. |
| 15 | **Student Kanban within group**; leader assigns tasks (*nice to have*) | **Partially supported** via student milestone/task routes (e.g. assign/move endpoints). Full “leader-only” UX polish is product-dependent. |

---

## Imports, students, submissions, filters

| # | Request | Status |
|---|---------|--------|
| 16 | **Student import:** accept existing rows; **first login** sends credentials; **school_year + semester** columns | **Implemented in recent work:** import **does not** email passwords on upload; students use **“Email me a temporary password”** on the login page; CSV expects **`school_year`** plus slot (`1st` / `2nd` / `summer`) and **rejects** a full combined Academic Term string in the `semester` column (`StudentsImport` + `StudentTemporaryPasswordController`). |
| 17 | Remove **type** column from student project submit/upload listing | **Not implemented.** `resources/views/student/project/index.blade.php` still displays submission **type** (e.g. proposal / task). |
| 18 | **Calendar** should show **panelists** | **Partially implemented.** `CalendarController::coordinatorCalendar` loads panel data, but `resources/views/calendar/coordinator.blade.php` modal content emphasizes group, adviser, coordinator, schedule—**panelist names are not listed** in the modal body. |
| 19 | **Filter defenses by adviser** | **Not implemented** on coordinator defense index filters: current filters are **defense type** + **group search**, not adviser. Adviser may appear as **read-only** info in tables. |

---

## Related files (quick reference)

| Area | Primary locations |
|------|-------------------|
| Defense create / panel JS | `resources/views/coordinator/defense/create.blade.php` |
| Defense controller | `app/Http/Controllers/Coordinator/DefenseScheduleController.php` |
| Schedule from request | `resources/views/coordinator/defense-requests/create-schedule.blade.php` |
| Coordinator calendar | `app/Http/Controllers/CalendarController.php`, `resources/views/calendar/coordinator.blade.php` |
| Student import | `app/Imports/StudentsImport.php`, `app/Support/ImportAcademicFieldsResolver.php` |
| Temp password flow | `app/Http/Controllers/StudentTemporaryPasswordController.php` |
| Student project list | `resources/views/student/project/index.blade.php` |
| Student Kanban | `resources/views/student/milestones/show.blade.php`, `app/Http/Controllers/StudentMilestoneController.php` |

---

## How to use this doc

1. **Prioritize** rows marked open/not implemented.
2. **Re-test** items marked “unverified” in the running app (especially Kanban and task sync).
3. **Update** this file when a row ships or when behavior changes.
