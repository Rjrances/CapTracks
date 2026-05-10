# Coordinator and adviser features — route map and code traces

This document lists **every coordinator- and adviser-facing route group** in CapTracks, maps each to **controller actions**, primary **views**, and supporting **models/services**. It also includes **line-by-line walks** of representative code paths so you can follow execution through the stack.

**Sources of truth:** `routes/web.php`, controllers under `app/Http/Controllers/`, and Spatie’s `role` middleware alias (`bootstrap/app.php`).

---

## 1. How access control works

| Mechanism | Meaning |
|-----------|---------|
| `middleware(['auth', 'role:coordinator|adviser'])` | User must be authenticated and have Spatie role **`coordinator`** or **`adviser`** (pipe means OR). |
| `middleware(['role:coordinator'])` nested inside that group | Extra gate: only **`coordinator`** (e.g. defense rubrics CRUD, coordinator-only coordinator routes). |
| `prefix('coordinator')->name('coordinator.')` | URLs like `/coordinator/...` and route names like `coordinator.dashboard`. |
| `prefix('adviser')->name('adviser.')` + `auth` | Authenticated users only at route level; **many actions enforce ownership** inside controllers (`abort(403)`). |

The **`role`** alias is Spatie `RoleMiddleware` (`bootstrap/app.php`).

---

## 2. Coordinator + adviser shared area (`role:coordinator|adviser`)

Prefix: **`/coordinator/`**, name prefix: **`coordinator.`**

These routes are available to **both** coordinators and advisers unless nested middleware restricts further.

| Feature area | HTTP | Route name | Controller@method |
|--------------|------|------------|---------------------|
| Dashboard | GET | `coordinator.dashboard` | `CoordinatorDashboardController@index` |
| Class list | GET | `coordinator.classlist.index` | `CoordinatorController@classlist` |
| Import students | GET | `coordinator.classlist.import` | `CoordinatorController@importStudentsForm` |
| Import students | POST | `coordinator.classlist.import.store` | `CoordinatorController@importStudents` |
| Faculty matrix | GET | `coordinator.faculty-matrix` | `CoordinatorController@facultyMatrix` |
| Defense schedules (resource) | * | `coordinator.defense.*` | `DefenseScheduleController` — Laravel resource (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) plus custom routes below |
| Available faculty (AJAX) | GET/POST | `coordinator.defense.available-faculty` | `DefenseScheduleController@getAvailableFaculty` |
| Mark defense completed | PATCH | `coordinator.defense.complete` | `DefenseScheduleController@markAsCompleted` |
| Defense rubrics | REST except `show` | `coordinator.defense-rubrics.*` | `DefenseRubricController` — **`role:coordinator`** only inside nested group |
| Groups list | GET | `coordinator.groups.index` | `CoordinatorController@groups` |
| Create group | GET | `coordinator.groups.create` | `CoordinatorController@create` |
| Store group | POST | `coordinator.groups.store` | `CoordinatorController@store` |
| Group show | GET | `coordinator.groups.show` | `CoordinatorController@show` |
| Edit group | GET | `coordinator.groups.edit` | `CoordinatorController@edit` |
| Update group | PUT | `coordinator.groups.update` | `CoordinatorController@update` |
| Delete group | DELETE | `coordinator.groups.destroy` | `CoordinatorController@destroy` |
| Assign adviser | GET | `coordinator.groups.assignAdviser` | `CoordinatorController@assignAdviser` |
| Group milestones | GET | `coordinator.groups.milestones` | `CoordinatorController@groupMilestones` |
| Notifications | GET/POST/DELETE | `coordinator.notifications*` | `CoordinatorController` — mark read, delete, bulk |
| Profile | GET | `coordinator.profile` | `CoordinatorController@profile` — **confirm method exists in repo** |
| Calendar | GET | `coordinator.calendar` | `CalendarController@coordinatorCalendar` |
| Final grades | GET | `coordinator.final-grades` | `CoordinatorController@finalGrades` |
| Activity log | GET | `coordinator.activity-log` | `CoordinatorController@activityLog` |
| Proposals | various | `coordinator.proposals.*` | `CoordinatorProposalController` |
| Milestone templates | resource + extras | `coordinator.milestones.*` | `MilestoneTemplateController` |

**Defense rubrics** (`coordinator.defense-rubrics`): nested middleware **`role:coordinator`** — advisers in the shared group **cannot** hit these routes.

**Milestone routes** (same shared group): template CRUD, task CRUD, reorder, assign to group, remove from group — see `routes/web.php` lines for exact names (`coordinator.milestones.tasks.store`, etc.).

---

## 3. Coordinator-only area (`role:coordinator`)

Second `Route::middleware(...)->prefix('coordinator')` block — still **`coordinator.*`** names but **coordinator role only**.

| Feature | Route name (representative) | Controller@method |
|---------|---------------------------|-------------------|
| Defense requests inbox | `coordinator.defense-requests.index` | `DefenseScheduleController@defenseRequestsIndex` |
| Create schedule from request | `coordinator.defense-requests.create-schedule` | `DefenseScheduleController@createSchedule` |
| Store schedule from request | `coordinator.defense-requests.store-schedule` | `DefenseScheduleController@storeSchedule` |
| Edit / update schedule from request | `coordinator.defense-requests.edit-schedule`, `coordinator.defense-requests.update-schedule` | Registered as `DefenseScheduleController@editSchedule`, `@updateSchedule` — **verify implementations exist** if those URLs are used |
| Approve / reject request | `coordinator.defense-requests.approve`, `.reject` | `DefenseScheduleController@approve`, `@reject` |
| Panel rating (coordinator UI duplicate of adviser form) | `coordinator.rating-sheets.rate.show`, `.rate.submit` | `RatingSheetController@showAdviserForm`, `@submitAdviserRating` |
| Coordinator rating sheets | `coordinator.rating-sheets.show`, `.print`, `.finalize`, `.reopen` | `RatingSheetController` coordinator methods |
| Adviser invitations (coordinator URL aliases) | `coordinator.adviser-invitations`, `.respond` | `AdviserController@invitations`, `@respondToInvitation` |
| Panel invitations (coordinator URL aliases) | `coordinator.panel-invitations`, `.respond` | `AdviserController@panelInvitations`, `@respondToPanelInvitation` |

---

## 4. Adviser area (`auth`, prefix `adviser`)

Prefix: **`/adviser/`**, name: **`adviser.*`**. Routes require **`auth`** only; **authorization is enforced in controllers** (e.g. invitation `faculty_id`, group `faculty_id`, panel membership).

| Feature | Route name | Controller@method |
|---------|------------|-------------------|
| Dashboard | `adviser.dashboard` | `AdviserController@dashboard` |
| Adviser invitations | `adviser.invitations`, `adviser.invitations.respond` | `AdviserController@invitations`, `@respondToInvitation` |
| Panel invitations | `adviser.panel-invitations`, `adviser.panel-invitations.respond` | `AdviserController@panelInvitations`, `@respondToPanelInvitation` |
| My groups | `adviser.groups`, `adviser.all-groups` | `AdviserController@myGroups` (same handler for both route names) |
| Panel-only submissions view | `adviser.panel-groups`, `adviser.panel-submissions` | `AdviserController@panelSubmissions` |
| Group detail | `adviser.groups.details` | `AdviserController@groupDetails` |
| Milestone Kanban | `adviser.groups.milestone-kanban` | `AdviserController@showGroupMilestoneKanban` |
| Task comments | `adviser.groups.milestone-task-comments` (+ `.store`) | `AdviserController@milestoneTaskComments`, `@storeMilestoneTaskComment` |
| Student projects (review) | `adviser.project.*` | `ProjectSubmissionController` — `index`, `show`, `edit`, `update` |
| Proposals | `adviser.proposal.*` | `AdviserProposalController` |
| Notifications | `adviser.notifications*` | `AdviserController` |
| Calendar | `adviser.calendar` | `CalendarController@adviserCalendar` |
| Activity log | `adviser.activity-log` | `AdviserController@activityLog` |
| Rating sheets (panelist) | `adviser.rating-sheets.show`, `.submit` | `RatingSheetController@showAdviserForm`, `@submitAdviserRating` |

---

## 5. Controller method inventories

### 5.1 `CoordinatorController`

Public actions include: `index`, `classlist`, `importStudentsForm`, `importStudents`, `groups`, `create`, `store`, `show`, `edit`, `assignAdviser`, `update`, `destroy`, `groupMilestones`, notification helpers, `activityLog`, `facultyMatrix`, `finalGrades`.

### 5.2 `CoordinatorDashboardController`

- `index` — aggregates offerings-scoped students, groups, submissions, milestones, recent lists → coordinator dashboard view.

### 5.3 `CoordinatorProposalController`

- `index`, `show`, `preview`, `compareVersions`, `update`, `bulkUpdate`, `getStats`, `storeComment`.

### 5.4 `Coordinator\DefenseScheduleController`

Public actions include: `defenseRequestsIndex`, `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `getAvailableFaculty`, `createSchedule`, `storeSchedule`, `approve`, `reject`, `markAsCompleted`, plus notification helpers and private validation helpers.

### 5.5 `Coordinator\DefenseRubricController`

- `index`, `create`, `store`, `edit`, `update`, `destroy`.

### 5.6 `MilestoneTemplateController`

- Template CRUD, `updateStatus`, task CRUD/reorder, `assignToGroup`, `removeAssignmentFromGroup`.

### 5.7 `AdviserController`

- `dashboard`, `invitations`, `respondToInvitation`, `myGroups`, `groupDetails`, `milestoneTaskComments`, `storeMilestoneTaskComment`, `allGroups`, `panelSubmissions`, `panelInvitations`, `respondToPanelInvitation`, notification helpers, `activityLog`, `showGroupMilestoneKanban`.

### 5.8 `AdviserProposalController`

- `index`, `show`, `preview`, `compareVersions`, `edit`, `update`, `getStats`, `bulkUpdate`, `storeComment`.

### 5.9 Shared by roles

| Controller | Methods used by coordinator/adviser |
|------------|-------------------------------------|
| `CalendarController` | `coordinatorCalendar`, `adviserCalendar` |
| `RatingSheetController` | `showAdviserForm`, `submitAdviserRating`; coordinator-only: `showCoordinatorRatings`, `printCoordinatorRatings`, `finalizeCoordinatorRatings`, `reopenCoordinatorRatings` |
| `ProjectSubmissionController` | Adviser project review routes |

---

## 6. Trace A — Adviser dashboard (`AdviserController@dashboard`)

**Route:** `GET adviser/dashboard` → `adviser.dashboard`

What this method does, **block by block**:

```22:74:app/Http/Controllers/AdviserController.php
    public function dashboard()
    {
        $user = Auth::user();
        $activeTerm = AcademicTerm::where('is_active', true)->first();
        $pendingInvitations = AdviserInvitation::with(['group', 'group.members'])
            ->where('faculty_id', $user->id)
            ->pending()
            ->get();
        $adviserGroups = Group::with([
            'members',
            'adviserInvitations',
            'groupMilestones.milestoneTemplate',
            'groupMilestoneTasks.milestoneTask',
            'academicTerm',
        ])
            ->where('faculty_id', $user->faculty_id)
            ->get()
            ->map(function ($group) {
                $group->progress_percentage = $this->calculateGroupProgress($group);
                $group->submissions_count = $this->getSubmissionsCount($group);
                $group->milestone_progress = $this->getMilestoneProgress($group);
                $group->next_milestone = $this->getNextMilestone($group);

                return $group;
            });
        $panelGroups = Group::with(['academicTerm', 'defenseSchedules.defensePanels'])
            ->whereHas('defenseSchedules.defensePanels', function ($query) use ($user) {

                $query->where('faculty_id', $user->id)
                    ->whereIn('role', DefensePanel::INVITED_ROLES)
                    ->where('status', 'accepted');
            })
            ->get();
        $summaryStats = [
            'total_groups' => $adviserGroups->count(),
            'panel_groups' => $panelGroups->count(),
            'groups_ready_for_defense' => $adviserGroups->filter(function ($group) {
                return $group->progress_percentage >= 60;
            })->count(),
            'groups_needing_attention' => $adviserGroups->filter(function ($group) {
                return $group->progress_percentage < 40;
            })->count(),
            'overdue_tasks_total' => $adviserGroups->sum('overdue_tasks'),
            'pending_invitations' => $pendingInvitations->count(),
        ];

        return view('dashboards.adviser', compact(
            'activeTerm',
            'pendingInvitations',
            'adviserGroups',
            'summaryStats'
        ));
    }
```

| Lines | Trace |
|-------|--------|
| 23–24 | Current user and single **active** academic term (if any). |
| 26–29 | **Pending adviser invitations** keyed by `AdviserInvitation.faculty_id` → **`users.id`** (not `faculty_id` code) — matches invitations addressed to this login. |
| 30–46 | **Groups where this user is the assigned adviser** (`groups.faculty_id` = user’s **`faculty_id`**); enrich each row with progress/submissions/milestone summaries via private helpers on the same class. |
| 47–54 | **Panel perspective:** groups where user appears on a **`defense_panels`** row as an invited role and **`accepted`**. |
| 55–66 | Aggregate counts for the Blade dashboard cards. |
| 68–73 | Render **`resources/views/dashboards/adviser.blade.php`** with compact variables. |

**Downstream helpers on `AdviserController`:** `calculateGroupProgress`, `getSubmissionsCount`, `getMilestoneProgress`, `getNextMilestone`, etc., use **`Group`**, **`GroupMilestone`**, **`ProjectSubmission`**.

---

## 7. Trace B — Accept or decline an adviser invitation (`respondToInvitation`)

**Route:** `POST adviser/invitations/{invitation}/respond` → `adviser.invitations.respond`

```186:228:app/Http/Controllers/AdviserController.php
    public function respondToInvitation(Request $request, AdviserInvitation $invitation)
    {
        $request->validate([
            'status' => 'required|in:accepted,declined',
            'response_message' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        if ($invitation->faculty_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
        if (! $invitation->isPending()) {
            return back()->with('error', 'This invitation has already been responded to.');
        }
        $invitation->update([
            'status' => $request->status,
            'response_message' => $request->response_message,
            'responded_at' => now(),
        ]);
        if ($request->status === 'accepted') {
            $invitation->group->update(['faculty_id' => Auth::user()->faculty_id]);
            AdviserInvitation::where('group_id', $invitation->group_id)
                ->where('id', '!=', $invitation->id)
                ->where('status', 'pending')
                ->delete();
            $user = Auth::user();
            if (! $user->hasRole('adviser') && $user->hasRole('teacher')) {
                $user->assignRole('adviser');
            }
            Notification::create([
                'title' => 'Adviser Invitation Accepted',
                ...
```

| Step | Meaning |
|------|---------|
| Validation | Only `accepted` / `declined` plus optional message. |
| 193–196 | Invitation row targets **`users.id`** (`faculty_id` column on invitation model stores **user id** for that faculty member). |
| 205–207 | On **accept**, set group’s **`faculty_id`** to the adviser’s institutional faculty code; delete competing **pending** invitations for the same group. |
| 212–214 | Promote **`teacher`** → **`adviser`** role via Spatie when accepting. |
| 215+ | Create **`notifications`** row for students (decline path similar below). |

**Related:** students invite via `StudentGroupController@inviteAdviser` (student routes), which creates `AdviserInvitation` rows consumed here.

---

## 8. Trace C — Group detail and adviser vs panel viewer (`groupDetails`)

**Route:** `GET adviser/groups/{group}` → `adviser.groups.details`

```280:310:app/Http/Controllers/AdviserController.php
    public function groupDetails(Group $group)
    {
        $user = Auth::user();
        $isAdviserOwner = $group->faculty_id === $user->faculty_id;
        $isAcceptedPanelist = $group->defenseSchedules()
            ->whereHas('defensePanels', function ($query) use ($user) {
                $query->whereIn('role', DefensePanel::INVITED_ROLES)
                    ->where('status', 'accepted')
                    ->whereHas('faculty', function ($facultyQuery) use ($user) {
                        $facultyQuery->where('faculty_id', $user->faculty_id);
                    });
            })
            ->exists();

        if (! $isAdviserOwner && ! $isAcceptedPanelist) {
            abort(403, 'Unauthorized');
        }

        $viewerMode = $isAdviserOwner ? 'adviser' : 'panel';
        $canViewMilestoneDiscussions = $isAdviserOwner;

        $group->load([...]);

        return view('adviser.group-details', compact('group', 'viewerMode', 'canViewMilestoneDiscussions'));
    }
```

| Concept | Trace |
|---------|--------|
| Adviser access | `groups.faculty_id` equals current user’s **`faculty_id`**. |
| Panel access | Accepted **`DefensePanel`** with invited role for user linked by **`users.faculty_id`**. |
| View toggles | **`viewerMode`** switches UI behavior; only adviser sees milestone discussions. |

---

## 9. Trace D — Coordinator dashboard (`CoordinatorDashboardController@index`)

**Route:** `GET coordinator/dashboard` → `coordinator.dashboard`

```16:25:app/Http/Controllers/CoordinatorDashboardController.php
    public function index(Request $request)
    {
        $activeTerm = AcademicTerm::where('is_active', true)->first();

        $coordinatorOfferings = auth()->user()->offerings()
            ->when($activeTerm, function($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->pluck('id')->toArray();
```

| Lines | Meaning |
|-------|---------|
| 18–19 | Resolve active term. |
| 21–25 | **Scoped offerings:** only **`offerings`** tied to this coordinator (`auth()->user()->offerings()` relationship), optionally filtered to active term — all downstream counts are restricted to those **`offering_id`** values. |

The remainder of `index` aggregates students, groups, submissions, milestones, and notifications for that scope (see file for full queries).

---

## 10. Trace E — Create defense schedule from approved/pending request (`storeSchedule`)

**Route:** `POST coordinator/defense-requests/{defenseRequest}/store-schedule` → `coordinator.defense-requests.store-schedule`

High-level flow inside `DefenseScheduleController@storeSchedule`:

1. **Gate:** request must be pending or approved; group must not already have an active schedule; group offering must belong to coordinator’s offerings and active term (`abort(403)` otherwise).
2. **Validate:** date, time, room, optional notes, **`panel_members`** array with `faculty_id` + `role` (`chair|member|panelist`).
3. **Milestone gate:** `DefenseMilestoneGateService->evaluate` — may require **`milestone_override_reason`** if not eligible.
4. **Scheduling constraints:** future start, no duplicate date per group, no double-booked room/time, panel composition validated via `assertInvitedPanelValidForCreateOrUpdate`.
5. **Transaction:** create **`DefenseSchedule`**, create **`DefensePanel`** rows (pending for invitees; adviser/coordinator auto-accepted), update **`DefenseRequest`** to `scheduled`.
6. **`sendDefenseScheduleNotifications`** → `NotificationService` for adviser, panelists, students, chairperson.

See `app/Services` / `DefenseScheduleController` for full branching.

---

## 11. Trace F — Rating sheets (adviser vs coordinator)

| Role | Route name | Controller flow |
|------|------------|-----------------|
| Adviser / panelist | `adviser.rating-sheets.show`, `.submit` | `RatingSheetController@showAdviserForm` loads schedule + rubric; `@submitAdviserRating` persists scores. |
| Coordinator (duplicate entry URL) | `coordinator.rating-sheets.rate.show`, `.rate.submit` | Same **`showAdviserForm`** / **`submitAdviserRating`** — lets coordinators fill panel-style ratings when needed. |
| Coordinator oversight | `coordinator.rating-sheets.show`, `.print`, `.finalize`, `.reopen` | Aggregates or finalizes panel ratings per **`DefenseSchedule`**. |

Inspect `app/Http/Controllers/RatingSheetController.php` for validation keys and model names (`DefenseSchedule`, rating sheet records).

---

## 12. Proposal flows (coordinator vs adviser)

| Role | Controller | Typical responsibilities |
|------|------------|---------------------------|
| Coordinator | `CoordinatorProposalController` | List/review proposals for coordinated offerings, bulk status updates, comments. |
| Adviser | `AdviserProposalController` | Same pattern scoped to advised groups; includes **`edit`**/`update` where policy allows. |

Both share patterns: **`preview`**, **`compareVersions`**, **`storeComment`**, **`bulkUpdate`**. Compare `index` queries in each controller for scoping rules.

---

## 13. Views index (primary Blade entry points)

Use these as the UI counterpart when tracing from route → controller:

| Area | View path (representative) |
|------|----------------------------|
| Adviser dashboard | `resources/views/dashboards/adviser.blade.php` |
| Adviser groups | `resources/views/adviser/groups.blade.php`, `adviser/group-details.blade.php` |
| Adviser panel submissions | `resources/views/adviser/project/panel-submissions.blade.php` |
| Coordinator dashboard | `resources/views/dashboards/coordinator.blade.php` |
| Coordinator defense | `resources/views/coordinator/defense/*.blade.php` |
| Coordinator defense requests | `resources/views/coordinator/defense-requests/*.blade.php` |

---

## 14. Keeping this document accurate

- **Run** `php artisan route:list` and filter by `coordinator` / `adviser` when routes change.
- After controller refactors, **update section 5** method lists and **re-check** code citations (line numbers shift).
- If a route points to a missing controller method, Symfony/Laravel will error at dispatch — fix the route or implement the method.

---

*Last aligned with `routes/web.php` and controllers in-repo at documentation time.*
