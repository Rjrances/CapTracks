# Adviser Features (Faculty)

The Adviser mentors student groups, provides threaded feedback, evaluates submissions, tracks group progress, and rates capstone defenses.

## 1. Dashboard Statistics Calculation

**Description:** The dashboard dynamically calculates group progress and pending tasks to give the adviser a snapshot of their workload.

**Core Logic (`app/Http/Controllers/AdviserController.php`):**
```php
public function dashboard() {
    $user = Auth::user();
    
    // Fetch groups and calculate progress on the fly
    $adviserGroups = Group::with(['groupMilestones', 'groupMilestoneTasks'])
        ->where('faculty_id', $user->faculty_id)
        ->get()
        ->map(function ($group) {
            $group->progress_percentage = $this->calculateGroupProgress($group);
            $group->overdue_tasks = $this->getOverdueTasks($group);
            return $group;
        });

    $summaryStats = [
        'total_groups' => $adviserGroups->count(),
        'groups_ready_for_defense' => $adviserGroups->filter(fn($g) => $g->progress_percentage >= 60)->count(),
        'groups_needing_attention' => $adviserGroups->filter(fn($g) => $g->progress_percentage < 40)->count(),
        'overdue_tasks_total' => $adviserGroups->sum('overdue_tasks'),
    ];

    return view('dashboards.adviser', compact('adviserGroups', 'summaryStats'));
}

private function calculateGroupProgress($group) {
    $groupMilestones = $group->groupMilestones;
    if ($groupMilestones->isEmpty()) return 0;
    
    $totalProgress = $groupMilestones->sum('progress_percentage');
    return round($totalProgress / $groupMilestones->count());
}
```
**Code Explanation:**
- `Group::with(['groupMilestones', 'groupMilestoneTasks'])`: Uses Eager Loading to fetch the groups alongside their milestones and tasks in a few queries rather than hundreds, preventing the "N+1 query problem" and ensuring the dashboard loads fast.
- `map(function ($group))`: Loops over the fetched groups and dynamically attaches temporary properties (`progress_percentage` and `overdue_tasks`) to the objects before sending them to the view.
- `$adviserGroups->filter(...)`: Uses Laravel Collections filtering. Instead of querying the database multiple times, it filters the already-fetched groups in memory. E.g., it looks at the newly calculated percentage and counts how many groups are above 60% completion.
- `calculateGroupProgress`: A private helper that takes all milestones for a group, sums up their individual percentages, and averages them out (`sum / count`).

## 2. Group Invitations & Panel Assignments

**Description:** Advisers accept or decline mentorship or panel invitations.

**Core Logic (`app/Http/Controllers/AdviserController.php`):**
```php
public function respondToPanelInvitation(Request $request, DefensePanel $panel) {
    if ($request->response === 'accept') {
        $panel->accept(); // Sets status to 'accepted'
    } else {
        $panel->decline(); // Sets status to 'declined'
    }
    return back()->with('success', 'Panel invitation response submitted.');
}
```
**Code Explanation:**
- `DefensePanel $panel`: Utilizes "Route Model Binding". By type-hinting the Model, Laravel automatically fetches the DefensePanel row from the database using the ID passed in the URL.
- `$panel->accept();`: Calls a custom method defined inside the `DefensePanel` Model class, which internally updates the `status` column to `'accepted'` and saves it.

## 3. Threaded Milestone Feedback

**Description:** Advisers leave detailed, nested comments on student tasks.

**Core Logic (`app/Http/Controllers/AdviserController.php`):**
```php
public function storeMilestoneTaskComment(Request $request, Group $group, GroupMilestoneTask $groupMilestoneTask) {
    TaskComment::create([
        'group_milestone_task_id' => $groupMilestoneTask->id,
        'user_id' => Auth::id(), 
        'body' => $request->body,
        'parent_id' => $request->parent_id, // Links to parent comment for threading
    ]);
    NotificationService::adviserCommentOnMilestoneTask(Auth::user(), $groupMilestoneTask);
}
```

### 🧠 Defense Tip: How does Threaded Chat / Nested Comments work in the DB?
If a panelist asks: *"How did you design the database to allow infinite replying/nesting on comments?"*
**Your Answer:** *"We used an 'Adjacency List' database design. Instead of creating a separate table for 'Replies', our `task_comments` table has a `parent_id` column that points back to its own table (a self-referencing relationship). If a comment is a brand new thread, `parent_id` is null. If a user is replying to someone, `parent_id` simply holds the ID of the comment they are replying to. This handles infinite threading natively with only one table."*

**Code Explanation:**
- `TaskComment::create(...)`: Inserts the comment text into the database. It associates it with the task ID and the currently logged-in faculty user ID.
- `'parent_id' => $request->parent_id`: This is the key to threaded (nested) comments. If someone clicks "Reply" to an existing comment, the original comment's ID is passed as the `parent_id`. If they are making a brand new comment, it is passed as `null`.
- `NotificationService::...`: A dedicated service class handles pushing real-time alerts or database notices to the students in the group so they know their adviser left a comment.

## 4. Defense Rating Sheets

**Description:** Panel members use rating sheets to grade groups during defense.

**Core Logic (`app/Http/Controllers/RatingSheetController.php`):**
```php
public function submitAdviserRating(Request $request, DefenseSchedule $schedule) {
    $panelMember = $schedule->defensePanels()->where('faculty_id', Auth::id())->firstOrFail();
    $totalScore = array_sum($request->criteria_scores);

    RatingSheet::create([
        'defense_panel_id' => $panelMember->id,
        'scores' => json_encode($request->criteria_scores),
        'total_score' => $totalScore,
        'comments' => $request->overall_comments,
    ]);
}
```
**Code Explanation:**
- `$schedule->defensePanels()->where('faculty_id', Auth::id())->firstOrFail();`: Since multiple faculty members rate the same schedule, we need to find the specific `DefensePanel` assignment record that belongs to the *currently logged-in faculty member*. If they aren't on the panel, it throws a 404 error (`firstOrFail`).
- `array_sum(...)`: Adds up all the individual criteria scores submitted via the form (e.g., Presentation: 20, Content: 30 = 50 total).
- `json_encode($request->criteria_scores)`: Since the rubric criteria could change in the future, we store the individual breakdown of scores as a JSON string inside a single column, making the database structure highly flexible.
