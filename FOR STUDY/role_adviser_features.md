# Adviser (Teacher) Features (Final)

Adviser role covers mentoring, invitation handling, proposal/project review, panel participation, and rating sheet submission.

## Main Route Space

- Prefix: `/adviser`
- Middleware: `auth`
- Access is scoped by role-linked data in queries (adviser groups, panel assignments, invitations).

## Sidebar Modules (current UI)

- Dashboard
- Adviser Groups
- Panel Groups
- Adviser Invitations
- Panel Invitations
- Calendar
- (Switch to Coordinator View appears when user also has coordinator context)

## 1) Dashboard and Invitations

Dashboard route:
- `/adviser/dashboard`

Adviser invitations:
- list and respond under `/adviser/invitations`

Panel invitations:
- list and respond under `/adviser/panel-invitations`

## 2) Group Mentoring Views

Routes:
- `/adviser/groups`
- `/adviser/groups/{group}`
- milestone kanban read/review routes for adviser perspective
- milestone task comments read/write for adviser

Purpose:
- monitor assigned groups, review progress, and provide guidance.

## 3) Proposal Review

Routes:
- `/adviser/proposals`
- preview, compare versions, show/edit/update
- adviser comments and bulk update endpoints

Behavior:
- scoped to adviser-accessible groups/submissions.

## 4) Project Submission Review

Routes:
- `/adviser/projects`
- `/adviser/projects/{id}` and edit/update

Recent UI cleanup:
- redundant "Review Actions" card removed from adviser project detail page.

## 5) Panel Role and Rating Sheets

Rating routes:
- `/adviser/rating-sheets/{schedule}` (show)
- POST same path (submit/update adviser rating)

Panel group views:
- panel submission and panel group routes are available in adviser space.

## 6) Notifications, Calendar, Activity Log

- adviser notifications endpoints support mark/delete operations
- calendar route: `/adviser/calendar`
- activity log route: `/adviser/activity-log`

## 7) Coordinator Dual-Role Bridge

When a faculty user also acts as coordinator:
- adviser sidebar can show `Switch to Coordinator View`
- coordinator sidebar can show `Switch to Adviser View`

This is a navigation bridge, not a separate auth session.

## 8) Final Adviser Workflow

1. Respond to adviser/panel invitations
2. Monitor assigned groups and submissions
3. Review proposal/project versions and provide feedback
4. Submit rating sheets for panel assignments

---

## 9) Connected Code and Functions (Adviser)

This section maps adviser features to concrete controller functions.

### A. Adviser workspace controller

File: `app/Http/Controllers/AdviserController.php`

- `dashboard()` - adviser dashboard data and summary cards.
- `invitations()` / `respondToInvitation()` - adviser invitation inbox and actions.
- `myGroups()` / `groupDetails()` - assigned groups and per-group view.
- `showGroupMilestoneKanban()` - adviser view of group milestone board.
- `milestoneTaskComments()` / `storeMilestoneTaskComment()` - threaded milestone discussion.
- `allGroups()` / `panelSubmissions()` - combined adviser/panel submissions context.
- `panelInvitations()` / `respondToPanelInvitation()` - panel assignment acceptance/rejection.
- `notifications()`, `markAllNotificationsAsRead()`, `markNotificationAsRead()`, `markMultipleAsRead()`, `deleteNotification()`, `deleteMultiple()` - adviser notifications.
- `activityLog()` - adviser activity history.

### B. Adviser proposal review controller

File: `app/Http/Controllers/AdviserProposalController.php`

- `index()` - adviser proposal list with filters.
- `show()` - proposal detail.
- `preview()` - file preview endpoint.
- `compareVersions()` - side-by-side version compare.
- `edit()` / `update()` - adviser decision and feedback update.
- `getStats()` - proposal metrics.
- `bulkUpdate()` - batch decision updates.
- `storeComment()` - proposal comment threading.

### C. Project submission review used by adviser routes

File: `app/Http/Controllers/ProjectSubmissionController.php`

- `index()` - project submissions list for adviser/panel contexts.
- `show()` - submission detail view.
- `edit()` / `update()` - review update actions.

### D. Panel grading and coordinator-facing aggregation link

File: `app/Http/Controllers/RatingSheetController.php`

- `showAdviserForm()` - adviser/panelist rating form view.
- `submitAdviserRating()` - submit/update adviser or panel rating.
- `showCoordinatorRatings()`, `printCoordinatorRatings()`, `finalizeCoordinatorRatings()`, `reopenCoordinatorRatings()` - downstream coordinator consolidation lifecycle.

---

## 10) Example Code Path (Line-by-Line): Panel Invitation Response

Connected function: `AdviserController::respondToPanelInvitation()`

```php
public function respondToPanelInvitation(Request $request, DefensePanel $panel)
{
    $request->validate([
        'status' => 'required|in:accepted,rejected',
    ]);

    $user = Auth::user();

    if ($panel->faculty_id != $user->id) {
        return back()->withErrors(['error' => 'Unauthorized action.']);
    }

    $panel->update([
        'status' => $request->status,
        'responded_at' => now(),
    ]);
}
```

Line-by-line:
1. Receives request + bound panel record.
2-4. Validates status payload.
6. Gets current authenticated faculty user.
8-10. Authorization check ensures only assigned faculty can respond.
12-15. Persists decision and response timestamp.

---

## 11) Core Connected Functions (Snippet + Line-by-Line)

### A) `AdviserController::respondToInvitation`

```php
$request->validate([
    'status' => 'required|in:accepted,declined',
]);
if ($invitation->faculty_id !== Auth::id()) {
    return back()->withErrors(['error' => 'Unauthorized action.']);
}
$invitation->update([
    'status' => $request->status,
    'responded_at' => now(),
]);
```

Line-by-line:
1-3. Validates adviser decision payload.
4-6. Enforces ownership: invitation must belong to current faculty.
7-10. Saves decision and response timestamp.

### B) `AdviserProposalController::update`

```php
$request->validate([
    'status' => 'required|in:pending,approved,rejected',
    'comment' => 'nullable|string',
]);
$proposal->update([
    'status' => $request->status,
    'teacher_comment' => $request->comment,
]);
```

Line-by-line:
1-4. Validates proposal decision and optional feedback.
5-8. Persists decision and comment on proposal record.

### C) `ProjectSubmissionController::show` (adviser-access context)

```php
$submission = ProjectSubmission::findOrFail($id);
$hasAdviserAccess = Group::where('faculty_id', $user->faculty_id)
    ->whereHas('members', fn($query) => $query->where('students.student_id', $submission->student_id))
    ->exists();
if (!$hasAdviserAccess) {
    abort(403, 'Unauthorized access.');
}
```

Line-by-line:
1. Loads submission by ID or fails.
2-4. Checks whether adviser owns a group containing the submission’s student.
5-7. Blocks access when adviser is not authorized.

### D) `RatingSheetController::submitAdviserRating`

```php
$validated = $request->validate([
    'scores' => 'required|array',
    'comments' => 'nullable|string',
]);
$rating = RatingSheet::updateOrCreate(
    ['defense_schedule_id' => $schedule->id, 'faculty_id' => auth()->id()],
    ['scores' => $validated['scores'], 'comments' => $validated['comments'] ?? null]
);
```

Line-by-line:
1-4. Validates rubric payload.
5-8. Upserts rating entry for the same schedule+faculty pair.
9. Stores normalized score/comments payload.
