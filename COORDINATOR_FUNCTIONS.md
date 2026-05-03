# Coordinator side — simple guide (every function)

This guide uses **easy words**. It lists **every public and private function** in the PHP classes the coordinator uses.

**Public** = the website routes call these directly.  
**Private** = helper functions inside the same file; only other functions in that file call them.

---

## Files covered

| File | Public functions | Private helpers |
|------|------------------|-----------------|
| `CoordinatorDashboardController.php` | 1 | 2 |
| `CoordinatorController.php` | 22 | 0 |
| `CoordinatorProposalController.php` | 9 | 1 |
| `Coordinator/DefenseScheduleController.php` | 15 | 11 |
| `MilestoneTemplateController.php` | 7 | 0 |
| `CalendarController.php` (only the coordinator screen) | 1 | 0 |
| `RatingSheetController.php` (only coordinator rating screen + shared helper) | 1 (+ 2 adviser-only public noted) | 1 |

### Related services (no full method list here)

Some coordinator actions call **`app/Services/NotificationService.php`**. For defense prep, the coordinator-relevant one is:

| Method | Called from | Purpose |
|--------|-------------|---------|
| **`adviserAssignedByCoordinator`** | `CoordinatorController@update` | New/changed adviser gets an in-app notice (link to that group for the adviser). |

Other `NotificationService` methods are for students, advisers, chairperson, etc.—see the service file if you need them.

---

## 1. `CoordinatorDashboardController`

**Path:** `app/Http/Controllers/CoordinatorDashboardController.php`  
**What it’s for:** The main coordinator home page after login.

### Public

#### `index`

- **Simple:** Shows the coordinator dashboard with numbers, lists, and reminders.
- **Details:** Counts students, groups, faculty, submissions, milestones, tasks—**only for the school term that is active** and **only for classes (offerings) this coordinator handles**. Also loads recent students, groups, submissions, notifications, pending adviser invites, custom “recent activity” and “deadlines” from helper functions below.

### Private

#### `getRecentActivities`

- **Simple:** Builds a short mixed list of “what happened lately” for the dashboard.
- **Details:** Pulls a few new groups (if a term is picked), a few latest project uploads, and a few adviser invitations; mixes them and keeps about eight newest items.

#### `getUpcomingDeadlines`

- **Simple:** Builds a short list of “things coming up” to show on the dashboard.
- **Details:** Uses active milestone templates and adds some fixed reminder-style items (proposal deadline, defense reminder). Some dates are partly placeholder-style in code—not every date is read straight from the database.

---

## 2. `CoordinatorController`

**Path:** `app/Http/Controllers/CoordinatorController.php`  
**What it’s for:** Class lists, importing students, groups, notices, activity log, faculty matrix.  
**Private helpers:** This class has **no** private methods. Small bits of logic sit inside each public function only.

### Public

#### `index`

- **Simple:** Older dashboard-style page for a route named something like coordinator dashboard (legacy URL).
- **Details:** Loads broad counts (whole database style), recent groups and submissions, notifications filtered for the coordinator workspace, pending adviser invites. Same Blade dashboard skin as the other coordinator dashboard but **not** as tightly filtered as `CoordinatorDashboardController@index`.

#### `classlist`

- **Simple:** Searchable list of students for the active semester.
- **Details:** Sorting, filter by name/course, or general search (name, ID, email). Paginated.

#### `importStudentsForm`

- **Simple:** Shows the page to upload a file to import students.
- **Details:** Lists classes (offerings) tied to this teacher/coordinator so they pick where students go.

#### `importStudents`

- **Simple:** Runs the import after you submit the form.
- **Details:** Hands everything to `StudentImportService` in “coordinator mode.”

#### `groups`

- **Simple:** List of project groups for the active term; optional search by group name or description.

#### `create`

- **Simple:** Empty form to create a new group.

#### `store`

- **Simple:** Saves the new group (name and optional description).

#### `show`

- **Simple:** One group’s detail page (adviser + members).

#### `edit`

- **Simple:** Form to change that group’s info.

#### `assignAdviser`

- **Simple:** Page to pick an adviser for the group.
- **Details:** Lists faculty who can advise—not someone who already **coordinates that same offering**. The form only sends **`faculty_id`** to **`groups.update`** and shows errors on the page.

#### `update`

- **Simple:** Saves edits to the group; can set or clear the adviser.
- **Details:** Blocks assigning the **subject coordinator** of that offering as adviser. If **`name`** is missing (assign page), it copies the group’s current **name** / **description** so validation passes. If **`faculty_id` changed** to a new person, **`NotificationService::adviserAssignedByCoordinator`** notifies them; same person again → no new notice.

#### `destroy`

- **Simple:** Deletes the group.
- **Details:** Removes members from the group, deletes adviser invitations, then deletes the group row.

#### `groupMilestones`

- **Simple:** Shows milestones for that group (overview style).
- **Details:** Quick Actions on the page: **View Group Details** and **Manage Adviser** only.

#### `notifications`

- **Simple:** Full list of in-app notifications for this coordinator’s workspace.

#### `markNotificationAsRead`

- **Simple:** Marks one notice as read (AJAX JSON).
- **Details:** Only if this coordinator is allowed to see that notice.

#### `markAllNotificationsAsRead`

- **Simple:** Marks every unread notice in their workspace as read (JSON).

#### `deleteNotification`

- **Simple:** Deletes one notice if allowed (JSON).

#### `markMultipleAsRead`

- **Simple:** Marks many notices read at once from a list of IDs (JSON).

#### `deleteMultiple`

- **Simple:** Deletes many notices at once from a list of IDs (JSON).

#### `activityLog`

- **Simple:** Timeline of student actions for students in **your** offerings.
- **Details:** Optional dropdown to filter to **one** student.

#### `facultyMatrix`

- **Simple:** Big table: each group, who teaches the subject, who advises, who chairs / sits as panel member on the **latest** defense, stage and status.
- **Details:** Also shows small summary numbers at the top (how many offerings, groups, groups with adviser, groups with a schedule).

---

## 3. `CoordinatorProposalController`

**Path:** `app/Http/Controllers/CoordinatorProposalController.php`  
**What it’s for:** Looking at student proposals (Capstone I/II style offerings), approving/rejecting, comments, stats.

### Public

#### `index`

- **Simple:** Lists proposals grouped by class offering you coordinate.
- **Details:** Only capstone offerings you coordinate (title/code rules in code). Each card is one offering: **offer code** badge, course title, group count.

#### `show`

- **Simple:** One proposal with history of versions and comment threads.
- **Details:** Checks the student’s group is in **your** offering (same `faculty_id` on the offering).

#### `preview`

- **Simple:** Opens a preview of the uploaded file (Word/PDF handling via `DocumentPreviewService`).

#### `compareVersions`

- **Simple:** Shows two versions side by side for the same student’s proposal.

#### `update`

- **Simple:** Approve or reject **one** proposal with a written comment.
- **Details:** Sends a notification to the student (approved or rejected message).

#### `bulkUpdate`

- **Simple:** Approve or reject **many** proposals with one shared comment.

#### `getStats`

- **Simple:** Returns JSON counts (totals, pending, approved, rejected, how many offerings/groups) for charts or widgets.

#### `storeComment`

- **Simple:** Adds a comment on the proposal; can be a reply if `parent_id` is set.
- **Details:** Writes an activity log entry when a comment is added.

### Private

#### `coordinatorCanReviewProposal`

- **Simple:** Returns true/false: “Is this coordinator allowed to touch this proposal?”
- **Details:** True when the student’s group belongs to an offering whose **`faculty_id`** matches the logged-in user’s **`faculty_id`**. Used by preview and compare to block outsiders.

---

## 4. `Coordinator\DefenseScheduleController`

**Path:** `app/Http/Controllers/Coordinator/DefenseScheduleController.php`  
**What it’s for:** Student defense requests, building schedules, panels, and conflict checks.

### Public

#### `defenseRequestsIndex`

- **Simple:** List of defense requests that are still **waiting** for coordinator action—only for groups in **your** offerings.

#### `index`

- **Simple:** Main defense screen: requests + defense schedules + stats.
- **Details:** Cleans up stuck request rows and hides requests that already have a live schedule. Filters are **`defense_type`** and **`search`** only (both narrow the requests list and the schedules table; defense type matches request type **or** manual **`stage`**). Optional **`offering`** query param if used. Eager-loads **`defenseRequest`**. Schedule rows show **`DefenseSchedule::status_label`** / **`status_badge_variant`**. Top stats for schedules use **all** your offerings’ schedules (not narrowed by filters): **active** count, **completed** subtext on the green card, **this week** = active defenses in the current week.

#### `create`

- **Simple:** Form to create a defense schedule **without** starting from a student request.
- **Details:** Only groups that do not already have a defense schedule attached. Passes **`panelFacultyByGroupId`**: for each group, the list of people allowed in the **Chair/Member** dropdowns (same rules as the server-side pool—see **`panelChairMemberCandidates`**). The page fills those selects from that JSON **before** date, time, and room are all set; once they are set, the browser calls **`getAvailableFaculty`** to refresh the list (busy people removed, lighter loads first). That way the adviser and subject coordinator never appear as Chair/Member even when the slot is incomplete.

#### `store`

- **Simple:** Saves a new defense: date, time, room, stage, and **two** hand-picked panel slots (one chair, one member).
- **Details:** Checks active term, your offerings, no double booking for room, no second defense same day for that group, panelists not busy elsewhere; rejects Chair/Member picks that are the **group adviser** or **subject coordinator** (**`panelMembersMustNotIncludeAdviserOrCoordinator`**); adds adviser and subject coordinator onto the panel automatically when possible; sends notifications.

#### `show`

- **Simple:** Detail page for one scheduled defense (403 if not your offering).

#### `edit`

- **Simple:** Form to change an existing schedule (same offering check).

#### `update`

- **Simple:** Saves changes; rebuilds panel rows the same way as `store` (two picks + auto adviser + auto coordinator).
- **Details:** Same rule as **`store`**: Chair/Member cannot be the group adviser or subject coordinator (**`panelMembersMustNotIncludeAdviserOrCoordinator`**).

#### `destroy`

- **Simple:** Deletes a defense schedule and related panel rows; may delete linked request; notifies students to submit a new request.

#### `getAvailableFaculty`

- **Simple:** Returns JSON list of teachers who **can** be picked for a time slot (for auto-fill in the browser).
- **Details:** Starts from **`panelChairMemberCandidates`** for that group, then drops anyone already tied up in another overlapping defense; marks if the **room** is double-booked; sorts people who have **fewer** panel duties in the active term first (then name).

#### `createSchedule`

- **Simple:** Wizard page to schedule **after** a student submitted a defense **request** (pending or approved).

#### `storeSchedule`

- **Simple:** Saves that schedule from the request flow: date, time, room, adviser, coordinator, two panelists; marks request as scheduled; notifies chair/member panelists.

#### `approve`

- **Simple:** Marks a pending defense request as **approved**.

#### `reject`

- **Simple:** Marks a request **rejected** and saves coordinator notes (required).

#### `markAsCompleted`

- **Simple:** Marks a defense as **finished** when it was scheduled or in progress.

### Private

#### `getConflictingFacultyIds`

- **Simple:** Finds user IDs of faculty who are **already on another defense** that overlaps this time window.

#### `checkDoubleBooking`

- **Simple:** True if the **same room** already has another defense that overlaps this start/end time (can ignore one schedule ID when editing).

#### `hasGroupScheduleOnDate`

- **Simple:** True if this **group** already has **any** defense on that **calendar day** (can ignore current schedule when editing).

#### `checkPanelMemberConflicts`

- **Simple:** True if **any** of the picked panelists already sits on another overlapping defense.

#### `validatePanelComposition`

- **Simple:** Checks the two manual picks are exactly **one chair** and **one member**; returns an error text if not.

#### `panelChairMemberCandidates`

- **Simple:** Builds the **Chair/Member-only** pool for one group (used by **`create`** JSON, **`getAvailableFaculty`**, and server checks).
- **Details:** Teachers/coordinators/chairperson/panelist/adviser roles, filtered to the active semester when a term exists; **excludes** anyone whose **`faculty_id`** matches the group’s **adviser** or the offering’s **subject coordinator**; **`unique('faculty_id')`** so duplicates don’t appear.

#### `panelMembersMustNotIncludeAdviserOrCoordinator`

- **Simple:** After **`store`** / **`update`** validation, blocks Chair/Member **`users.id`** values that belong to the adviser or subject coordinator (defense in depth if someone tampers with the form).

#### `sendDefenseScheduleNotifications`

- **Simple:** After creating a schedule from the **main create/store** flow, sends simple in-app notices to adviser, each panel person, students, and chairperson.

#### `createDefensePanel`

- **Simple:** Used only in the **request → storeSchedule** path: creates four panel rows—adviser, coordinator, chair, member—from form IDs.

#### `sendPanelNotifications`

- **Simple:** After **storeSchedule**, notifies the chair and member panelists.

#### `hasActiveScheduleForGroup`

- **Simple:** True if this group already has a defense that is **scheduled** or **in progress** (blocks duplicate scheduling).

---

### Route mismatch (important)

The route file may name **`editSchedule`** and **`updateSchedule`** for editing a schedule that came from a request. Those **function names are not in this class right now**. If you open those URLs they may error until someone adds the methods or fixes the routes.

---

## 5. `MilestoneTemplateController`

**Path:** `app/Http/Controllers/MilestoneTemplateController.php`  
**What it’s for:** Coordinator manages milestone **templates** (blueprints), not each student’s tasks one by one.  
**Private helpers:** **None.**

### Public

#### `index`

- **Simple:** Lists all templates and also loads groups (current term) so you see who uses what.

#### `create`

- **Simple:** Form for a new template.

#### `store`

- **Simple:** Saves name, description, status (`active` / `inactive` / `draft`).

#### `edit`

- **Simple:** Form to change one template.

#### `update`

- **Simple:** Saves changes to that template.

#### `destroy`

- **Simple:** Deletes that template.

#### `updateStatus`

- **Simple:** Small AJAX toggle that sets a **workflow** status: `todo`, `in_progress`, or `done` (different from the active/inactive/draft fields above—watch which field your database uses).

---

## 6. `CalendarController` (coordinator part only)

**Path:** `app/Http/Controllers/CalendarController.php`

### Public

#### `coordinatorCalendar`

- **Simple:** Calendar page showing **scheduled** defenses as colored blocks.
- **Details:** Loads defenses with group and people; builds a JavaScript-friendly list of events (title, start, end, room, student names, etc.). No private method—only normal loop code inside this function.

**Note:** The same file has `adviserCalendar`, `studentCalendar`, and `chairpersonCalendar` for **other roles**, not the coordinator role.

---

## 7. `RatingSheetController` (coordinator + shared)

**Path:** `app/Http/Controllers/RatingSheetController.php`

### Public (coordinator)

#### `showCoordinatorRatings`

- **Simple:** Lets the coordinator **see all rating sheets** turned in for **one** defense (scores + average).
- **Details:** Only if that defense’s group is in **your** offerings.

### Public (adviser / panel — not coordinator duty, but same file)

#### `showAdviserForm`

- **Simple:** Form for a panel member to enter scores (only if they are on that panel).

#### `submitAdviserRating`

- **Simple:** Saves or updates that panel member’s scores for the defense.

### Private

#### `getDefaultCriteria`

- **Simple:** Returns the default list of scoring categories (problem, methods, tech, documentation) with zero scores.
- **Details:** Used when opening the **adviser** rating form, not the coordinator view—but it lives in this controller.

---

## Quick count

- **CoordinatorDashboardController:** 1 public, 2 private  
- **CoordinatorController:** 22 public, 0 private  
- **CoordinatorProposalController:** 9 public, 1 private  
- **DefenseScheduleController:** 15 public, 11 private  
- **MilestoneTemplateController:** 7 public, 0 private  
- **CalendarController:** 1 public for coordinator (`coordinatorCalendar`), 0 private  
- **RatingSheetController:** 1 public for coordinator; 2 other public for advisers; 1 private helper  

---

*If the PHP code changes, update this file so it stays true.*
