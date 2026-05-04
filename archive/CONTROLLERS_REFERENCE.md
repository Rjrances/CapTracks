# CapTracks — Controller methods reference

**Purpose:** One-line explanations of **public** controller methods so you can study or defend the app. **Private** / **protected** helpers are listed only when they matter to the story. For exact routes, use `php artisan route:list` and `routes/web.php`.

**Note:** `Controller` (base) is Laravel’s empty base class — no app logic.

---

## `AuthController` — login, registration, password (faculty + student on same form)

| Method | What it does |
|--------|----------------|
| `showLoginForm` | Renders `auth.login` (single form: **school_id** = faculty `faculty_id` or **student** `student_id`). |
| `login` | Validates credentials: tries **`UserAccount`** by `faculty_id` → faculty **`Auth::login`** → redirects by role via **`redirectBasedOnRole`**; else **`StudentAccount`** by `student_id` → optional first-login password setup → **`student` guard** login → dashboard. |
| `logout` | Logs out **both** default guard and **`student`** guard; invalidates session. |
| `showRegisterForm` | Shows `auth.register`. |
| `register` | Creates either **`Student` + StudentAccount** or **`User` + UserAccount`** (faculty) depending on role rules (chairperson may pick faculty role). |
| `showChangePasswordForm` | Password change view if faculty **or** student is logged in. |
| `changePassword` | Updates password on **`StudentAccount`** or **`UserAccount`** and redirects appropriately. |

**Private:** `redirectBasedOnRole` — sends chairperson / coordinator / adviser+teacher+panelist / student to the right dashboard route.

---

## `ClassController` — simple “class” entity

| Method | What it does |
|--------|----------------|
| `create` | Form to create a **`ClassModel`** row. |
| `store` | Validates name, saves class, redirects to index. |
| `index` | Lists all `ClassModel`, view `classes.index`. |

---

## `RoleController` — chairperson faculty roles UI

| Method | What it does |
|--------|----------------|
| `index` | Builds role descriptions + counts; paginates **`User`** rows (filtered by active term semester) for `chairperson.roles.index`. |
| `update` | Syncs **pivot / many-to-many roles** for a `faculty_id` (validates role list). |

---

## `StudentDashboardController` — student home

| Method | What it does |
|--------|----------------|
| `index` | Resolves **`Student`** (from `student` guard or edge case with `User`); loads **group**, progress, task stats, submissions, milestone info, recent tasks/activities, deadlines, adviser info, defense info, notifications, proposal, offering; returns `dashboards.student`. |

**Private helpers:** `calculateOverallProgress`, `getTaskStatistics`, `getSubmissionsCount`, `getCurrentMilestoneInfo`, `getRecentTasks`, `getRecentActivities`, `getUpcomingDeadlines`, `getAdviserInfo`, `getDefenseInfo`, `getNotifications`, `getExistingProposal`, `getOfferingInfo` — aggregate data for widgets (some **placeholder** task rows if no group yet).

---

## `StudentController` — student notifications center

| Method | What it does |
|--------|----------------|
| `index` | Redirects to **`student.dashboard`**. |
| `notifications` | Lists notifications visible to this student account. |
| `markNotificationAsRead` | Marks one read (with ownership check). |
| `markAllNotificationsAsRead` | Marks all visible unread. |
| `deleteNotification` | Deletes one. |
| `markMultipleAsRead` | JSON/API-style bulk read. |
| `deleteMultiple` | Bulk delete. |

**Private:** `getAuthenticatedStudent` — `student` guard → `Student` model.

---

## `StudentPasswordController` — forced student password change

| Method | What it does |
|--------|----------------|
| `showChangePasswordForm` | First-login / policy password form. |
| `updatePassword` | Validates and saves new hash on **`StudentAccount`**, clears `must_change_password` if set. |

---

## `StudentGroupController` — group CRUD, invites, members

| Method | What it does |
|--------|----------------|
| `show` | Current student’s group overview. |
| `create` | Form to create a group (leader). |
| `store` | Creates **`Group`**, attaches leader, optional **member** `GroupInvitation` rows, **adviser** `AdviserInvitation`, notifications. |
| `edit` | Edit group form. |
| `update` | Saves name/description. |
| `index` | List / redirect for group listing. |
| `inviteAdviser` | Creates **adviser invitation** for selected faculty. |
| `inviteMember` | Invites another student to the group (same offering rules). |
| `acceptInvitation` / `declineInvitation` | Student accepts/declines **group** invite. |
| `invitations` | Page listing pending **group** invites for this user. |
| `removeMember` | Leader removes a member. |
| `requestDefense` | Bridge to defense request from group context (if wired in UI). |
| `cancelInvitation` | Leader cancels a **pending** group invite. |

---

## `StudentProposalController` — formal capstone proposal (typed `proposal` on `ProjectSubmission`)

| Method | What it does |
|--------|----------------|
| `index` | Latest proposal + **version list**, compare UI hooks, defense eligibility hints. |
| `create` | Upload form (first proposal). |
| `store` | Validates rich text + file, creates **`ProjectSubmission`** (`type=proposal`), activity log. |
| `show` | Single submission detail. |
| `edit` | Edit while not approved. |
| `update` | Updates fields; optional **new file** creates **new version** row. |
| `rollback` | Copies a past version’s file into a **new** pending version (same metadata snapshot). |
| `previewVersion` | **`DocumentPreviewService`** — in-browser preview for one version (student-owned). |
| `compareVersions` | Side-by-side preview for two proposal versions (same student). |

**Private:** `getAuthenticatedStudent`, `getProposalStatus` — eligibility for defense request messaging.

---

## `ProjectSubmissionController` — “quick uploads” + adviser review of submissions

| Method | What it does |
|--------|----------------|
| `index` | **Student:** own submissions; **Adviser:** builds group list + submissions by advisee groups (+ panel groups). |
| `create` | Student upload form (`proposal` / `final` / `other`). |
| `store` | Saves file + **`ProjectSubmission`** row + activity log. |
| `show` | **Student** OR **adviser** (must advise group) views submission. |
| `edit` | Adviser review form. |
| `update` | Adviser sets **status** + comment. |
| `destroy` | Student deletes own submission. |
| `studentPreviewSubmission` | Preview one file (student owner). |
| `studentCompareSubmissions` | Compare two submissions **same type**, same student. |

**Private:** `adviserIndex`, `studentIndex`, `studentIndexFromSession`, `getSubmissionTitle`.

---

## `StudentMilestoneController` — group milestones, Kanban, tasks, task comments

| Method | What it does |
|--------|----------------|
| `index` | Lists **`GroupMilestone`** rows with **progress bars** + overall group progress. |
| `create` | Pick template → create group milestone. |
| `store` | Instantiates **`GroupMilestone`** (+ tasks from template). |
| `show` | **Kanban** for one milestone: columns Pending / Doing / Done, task cards, comment modals. |
| `edit` | Edit milestone metadata. |
| `update` | Saves milestone fields. |
| `destroy` | Deletes milestone (with sanity checks). |
| `moveTask` | PATCH: drag-drop **status** column → updates task status (may log completion). |
| `bulkUpdateTasks` | Batch update tasks. |
| `recomputeProgress` | Recalculates **`GroupMilestone`** `%` from task states. |
| `updateTask` | Edit due date, assignee, notes, etc. |
| `storeTaskComment` | Creates **`TaskComment`** + **`ActivityLogService::logTaskCommentAdded`**. |
| `updateMultipleTasks` | Bulk patch from checklist-style UI. |
| `assignTask` | Sets **`assigned_to`** student id. |
| `unassignTask` | Clears assignee. |

**Private:** `getAuthenticatedStudent`, and other helpers as implemented in file.

---

## `StudentMilestoneChecklistController`

| Method | What it does |
|--------|----------------|
| `checklist` | Shows **must-have** template tasks vs **`GroupMilestoneTask`** completion map for the student’s group. |

---

## `TaskSubmissionController` — artifact upload per **GroupMilestoneTask**

| Method | What it does |
|--------|----------------|
| `create` | Form to attach file/notes to a task (must be group member; respects **assignee**). |
| `store` | Creates **`TaskSubmission`**; may mirror a **`ProjectSubmission`** row for adviser visibility; may bump task to **doing**. |
| `show` | **Student** sees own submission detail; **adviser** if group’s adviser. |
| `review` | **Adviser** approves/rejects submission; may call **`markAsCompleted`** on task when all approved. |

**Private:** `getAuthenticatedStudent`.

---

## `StudentDefenseRequestController`

| Method | What it does |
|--------|----------------|
| `index` | Lists **`DefenseRequest`** for student’s group; shows whether new request allowed. |
| `create` | Form (`defense_type`, preferred slot); requires adviser on group. |
| `store` | Creates pending **`DefenseRequest`** + **`ActivityLog`**. |
| `show` | Detail + linked schedule/panels if any. |
| `cancel` | Cancels if still pending (and allowed). |

**Private:** `getAuthenticatedStudent`, `canCreateDefenseRequest` — prevents duplicate active workflows.

---

## `CoordinatorDashboardController`

| Method | What it does |
|--------|----------------|
| `index` | Heavy stats for **active term** + coordinator’s **offerings**: students, groups, submissions, milestones, recent lists, notifications, deadlines; view `dashboards.coordinator`. |

**Private:** `getRecentActivities`, `getUpcomingDeadlines`, etc.

---

## `CoordinatorController` — coordinator workspace (not defense CRUD — see `DefenseScheduleController`)

| Method | What it does |
|--------|----------------|
| `index` | Alternate coordinator landing (stats / notifications) for legacy `coordinator-dashboard` route. |
| `classlist` | Filter/sort **students** in active term for coordinator’s world. |
| `importStudentsForm` | CSV import form (offerings list). |
| `importStudents` | Delegates to **`StudentImportService`**. |
| `groups` | Paginated **groups** in term. |
| `create` / `store` | Create coordinator-managed group shell. |
| `show` | Group detail. |
| `edit` / `update` | Edit group; may assign **adviser** (`faculty_id`) with conflict rules. |
| `assignAdviser` | Pick adviser UI. |
| `destroy` | Deletes group (detaches members, clears invites). |
| `groupMilestones` | Read-only style view of group milestones. |
| `notifications` | Coordinator notification inbox page. |
| `markNotificationAsRead` / `markAll…` / `deleteNotification` / `markMultipleAsRead` / `deleteMultiple` | Notification CRUD (JSON or redirect variants per route). |
| `activityLog` | Chronological **`ActivityLog`** for students in coordinated offerings + optional **student_id** filter. |
| `facultyMatrix` | Table of groups × adviser / panel / schedule summary. |

---

## `CoordinatorProposalController`

| Method | What it does |
|--------|----------------|
| `index` | Proposals grouped by **coordinated capstone offerings**. |
| `show` | Proposal detail, **version history**, threaded **`SubmissionComment`**. |
| `preview` | Document preview page (**`DocumentPreviewService`**). |
| `compareVersions` | Two-version compare (authorization: student’s group must be in coordinator offerings). |
| `update` | Optional coordinator fields / status if implemented. |
| `bulkUpdate` | Bulk status on proposals. |
| `getStats` | JSON counts for dashboard widgets. |
| `storeComment` | Threaded comment on proposal. |

---

## `Coordinator\DefenseScheduleController` — defenses, panels, JSON faculty pool

| Method | What it does |
|--------|----------------|
| `defenseRequestsIndex` | Coordinator queue of **`DefenseRequest`** (pending approval). |
| `index` | **Defense Management** main page: stats, filters, requests list, schedules. |
| `create` | Form to create **`DefenseSchedule`** (group, slot, room, panel selects). |
| `store` | Validates overlaps (room, group, panel), creates schedule + **`DefensePanel`** rows + notifications. |
| `show` | Schedule detail. |
| `edit` | Edit schedule + panel members. |
| `update` | Saves changes with same validation family as store. |
| `destroy` | Deletes schedule (transactional cleanup). |
| `getAvailableFaculty` | **JSON**: eligible faculty for date/time (excludes adviser + offering coordinator, excludes overlapping panels, **sorts by panel load**). Used by **Auto-Assign** in Blade JS. |
| `createSchedule` | Wizard from an approved **`DefenseRequest`**. |
| `storeSchedule` | Persists schedule linked to request. |
| `approve` | Approves **`DefenseRequest`**. |
| `reject` | Rejects with reason. |
| `markAsCompleted` | Marks defense **completed**. |

**Private:** `createDefensePanel`, `getConflictingFacultyIds`, `checkDoubleBooking`, `hasGroupScheduleOnDate`, `checkPanelMemberConflicts`, `validatePanelComposition`, `sendDefenseScheduleNotifications`, `sendPanelNotifications`, `hasActiveScheduleForGroup`, etc.

> **Routes note:** `routes/web.php` may reference **`editSchedule`** / **`updateSchedule`** — if those methods are missing from this class, hitting those URLs will error until implemented or routes removed.

---

## `MilestoneTemplateController` — coordinator template CRUD

| Method | What it does |
|--------|----------------|
| `index` | Lists templates + groups using them. |
| `create` / `store` | New **`MilestoneTemplate`**. |
| `edit` / `update` | Edit template fields. |
| `destroy` | Delete template. |
| `updateStatus` | AJAX toggle / status for template workflow (`todo` / `in_progress` / `done` style — see validation). |

---

## `CalendarController`

| Method | What it does |
|--------|----------------|
| `coordinatorCalendar` | All **scheduled** defenses → JSON-like **calendarEvents** for coordinator Blade. |
| `adviserCalendar` | Defenses where **`group.faculty_id`** = current adviser. |
| `studentCalendar` | Defenses for **student’s** group only. |
| `chairpersonCalendar` | Institution-wide scheduled defenses (similar to coordinator view). |

---

## `AdviserController` — adviser dashboard, groups, task threads, panel

| Method | What it does |
|--------|----------------|
| `dashboard` | Adviser home metrics + quick links. |
| `invitations` | Pending **adviser** group invitations. |
| `respondToInvitation` | Accept/decline **`AdviserInvitation`**. |
| `myGroups` / `allGroups` | Lists groups where user is adviser (aliases). |
| `groupDetails` | One group: members, milestones summary, link to **milestone task** discussion table. |
| `milestoneTaskComments` | Full-page **`TaskComment`** thread for one **`GroupMilestoneTask`**. |
| `storeMilestoneTaskComment` | Post/reply; **`ActivityLogService`**. |
| `panelSubmissions` | Submissions visible as panelist. |
| `panelInvitations` | **`DefensePanel`** invites for this faculty. |
| `respondToPanelInvitation` | Accept/decline panel slot. |
| `notifications` + mark read/delete helpers | Same pattern as other notification controllers. |
| `activityLog` | **`ActivityLog`** filtered to advisees’ student_ids. |

---

## `AdviserProposalController`

| Method | What it does |
|--------|----------------|
| `index` | Proposals for adviser’s groups (filter by group). |
| `show` | Review proposal + version history + **`SubmissionComment`** threads. |
| `preview` | Document preview. |
| `compareVersions` | Two-version compare. |
| `edit` | Approve/reject form. |
| `update` | Saves decision + **`NotificationService`** on approve/reject. |
| `getStats` | JSON stats. |
| `bulkUpdate` | Bulk approve/reject with shared comment. |
| `storeComment` | Threaded proposal comment + activity log. |

---

## `RatingSheetController`

| Method | What it does |
|--------|----------------|
| `showAdviserForm` | Panel member fills **criteria/scores** for a **`DefenseSchedule`**. |
| `submitAdviserRating` | Upserts **`RatingSheet`** for this faculty + schedule. |
| `showCoordinatorRatings` | Coordinator views all **`RatingSheet`** rows for a schedule (offering scope check). |

---

## `ChairpersonDashboardController`

| Method | What it does |
|--------|----------------|
| `index` | Chairperson stats: active groups with adviser, faculty count, defenses, offerings; notifications snippet. |

---

## `ChairpersonOfferingController`

| Method | What it does |
|--------|----------------|
| `index` | List offerings (filters). |
| `create` / `store` | New **`Offering`** tied to term. |
| `show` / `edit` / `update` / `destroy` | CRUD offering. |
| `removeStudent` | Remove student from offering enrollment. |
| `showUnenrolledStudents` | Students eligible to enroll. |
| `enrollStudent` / `enrollMultipleStudents` | Attach students to offering. |

---

## `ChairpersonFacultyController`

| Method | What it does |
|--------|----------------|
| `index` | Faculty list. |
| `create` / `upload` | CSV import upload. |
| `createManual` / `storeManual` | Manual faculty row. |
| `edit` / `update` / `destroy` | Edit/delete faculty user. |

---

## `ChairpersonStudentController`

| Method | What it does |
|--------|----------------|
| `index` | Student registry with filters. |
| `export` | Export CSV. |
| `edit` / `update` / `destroy` | Edit/delete student. |
| `bulkDelete` | Mass delete. |
| `upload` | CSV student import. |

---

## `AcademicTermController` — resource + toggles

| Method | What it does |
|--------|----------------|
| `index` … `destroy` | Standard Laravel resource for **`AcademicTerm`**. |
| `toggleActive` | Sets one term active (and usually deactivates others). |
| `toggleArchived` | Archive flag for historical terms. |

---

## `ChairPersonController` — chairperson notifications API-style

| Method | What it does |
|--------|----------------|
| `getActiveTerm` | Returns active **`AcademicTerm`** model (helper; may be called internally). |
| `notifications` | Full notification list page. |
| `markNotificationAsRead` / `markAllNotificationsAsRead` / `deleteNotification` / `markMultipleAsRead` / `deleteMultiple` | JSON responses using **`NotificationService`** and visibility scope **`visibleToWebUser`**. |

---

## Reading order for defense (suggested)

1. **`AuthController`** + **`routes/web.php`** middleware groups → how roles enter the app.  
2. **Student path:** `StudentGroupController` → `StudentProposalController` → `StudentMilestoneController` → `StudentDefenseRequestController`.  
3. **Adviser path:** `AdviserController` + `AdviserProposalController`.  
4. **Coordinator path:** `CoordinatorController` + `CoordinatorProposalController` + `DefenseScheduleController`.  
5. **Chairperson path:** `ChairpersonOfferingController` + `AcademicTermController` + `RoleController`.

---

*Methods and behavior reflect the codebase at documentation time. After refactors, re-verify against the PHP files.*
