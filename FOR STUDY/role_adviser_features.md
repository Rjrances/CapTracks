# Adviser & Teacher Features (Faculty Member)

Faculty members (whether assigned as an Adviser, Subject Teacher, or Panelist) mentor student groups, provide threaded feedback, evaluate submissions, track group progress, and rate capstone defenses.

## 🔄 User Journey Flow (Top to Bottom)
If panelists ask for the "System Workflow" or "Use Case" of an Adviser or Panelist, explain this exact step-by-step flow:
1. **Receive Invitations:** The Faculty member logs in and sees pending dashboard notifications inviting them to either Advise a group or sit on a Defense Panel.
2. **Accept/Decline:** They interact with the invitation, accepting it to officially link themselves to the group.
3. **Monitor Progress:** As an Adviser, they use their Dashboard to see the real-time progress percentages of their assigned groups.
4. **Provide Feedback:** They review uploaded task submissions and leave threaded (nested) comments to guide the students.
5. **Grade Defenses:** As a Panelist, they attend the scheduled defense and input their final grades into the dynamic JSON Rating Sheet rubric.


## 1. Dashboard Statistics Calculation

**Description:** The dashboard dynamically calculates group progress and pending tasks to give the adviser a snapshot of their workload.

**Core Logic (`app/Http/Controllers/AdviserController.php`):**
```php
public function dashboard() {
    $user = Auth::user(); 
    
    // Fetch groups assigned to this adviser and dynamically calculate progress
    $adviserGroups = Group::with(['groupMilestones', 'groupMilestoneTasks']) 
        ->where('faculty_id', $user->faculty_id) 
        ->get() 
        ->map(function ($group) { 
            $group->progress_percentage = $this->calculateGroupProgress($group); 
            $group->overdue_tasks = $this->getOverdueTasks($group); 
            return $group; 
        });

    // Fetch groups assigned to this faculty as a panel member
    $panelGroups = Group::with(['academicTerm', 'defenseSchedules.defensePanels'])
        ->whereHas('defenseSchedules.defensePanels', function($query) use ($user) {
            $query->where('faculty_id', $user->id)
                  ->whereIn('role', ['chair', 'member'])
                  ->where('status', 'accepted');
        })
        ->get();

    // Build an array of quick summary statistics for the top widgets
    $summaryStats = [ 
        'total_groups' => $adviserGroups->count(),
        'panel_groups' => $panelGroups->count(), // Count panel assignments correctly
        'groups_ready_for_defense' => $adviserGroups->filter(fn($g) => $g->progress_percentage >= 60)->count(),
        'overdue_tasks_total' => $adviserGroups->sum('overdue_tasks'),
    ];

    // Send the data to the Blade template
    return view('dashboards.adviser', compact('adviserGroups', 'panelGroups', 'summaryStats')); 
}

private function calculateGroupProgress($group) {
    
    // Get all milestones for this group
    $groupMilestones = $group->groupMilestones; 
    
    // If they have no milestones, return 0%
    if ($groupMilestones->isEmpty()) return 0; 
    
    // Add up the individual percentages of all milestones
    $totalProgress = $groupMilestones->sum('progress_percentage'); 
    
    // Calculate the average percentage (Total / Count) and round it off
    return round($totalProgress / $groupMilestones->count()); 
}
```

## 2. Group Invitations & Panel Assignments

**Description:** Advisers accept or decline mentorship or panel invitations.

**Core Logic (`app/Http/Controllers/AdviserController.php`):**
```php
public function respondToPanelInvitation(Request $request, DefensePanel $panel) {
    
    // Check if the faculty clicked the "Accept" button
    if ($request->response === 'accept') { 
        
        // Call the custom model method to change status to 'accepted'
        $panel->accept(); 
        
    } else { // Otherwise, they clicked "Decline"
        
        // Call the custom model method to change status to 'declined'
        $panel->decline(); 
    }
    
    // Refresh the page
    return back()->with('success', 'Panel invitation response submitted.'); 
}
```


## 3. Threaded Milestone Feedback

**Description:** Advisers leave detailed, nested comments on student tasks.

**Core Logic (`app/Http/Controllers/AdviserController.php`):**
```php
public function storeMilestoneTaskComment(Request $request, Group $group, GroupMilestoneTask $groupMilestoneTask) {
    
    // Create a new comment record
    TaskComment::create([ 
        'group_milestone_task_id' => $groupMilestoneTask->id, // Link the comment specifically to this task
        'user_id' => Auth::id(), // Record the ID of the faculty member commenting
        'body' => $request->body, // Save the actual comment text
        'parent_id' => $request->parent_id, // If replying to someone, save their comment ID here (Adjacency List model)
    ]);
    
    // Trigger a real-time notification to the students in the group
    NotificationService::adviserCommentOnMilestoneTask(Auth::user(), $groupMilestoneTask);
}
```

### 🧠 Defense Tip: How does Threaded Chat / Nested Comments work in the DB?
If a panelist asks: *"How did you design the database to allow infinite replying/nesting on comments?"*
**Your Answer:** *"We used an 'Adjacency List' database design. Instead of creating a separate table for 'Replies', our `task_comments` table has a `parent_id` column that points back to its own table (a self-referencing relationship). If a comment is a brand new thread, `parent_id` is null. If a user is replying to someone, `parent_id` simply holds the ID of the comment they are replying to. This handles infinite threading natively with only one table."*


## 4. Defense Rating Sheets

**Description:** Panel members use rating sheets to grade groups during defense.

**Core Logic (`app/Http/Controllers/RatingSheetController.php`):**
```php
public function submitAdviserRating(Request $request, DefenseSchedule $schedule) {
    $user = Auth::user();
    
    // Validate that the user is actually on the panel for this schedule
    $isAssignedPanel = $schedule->defensePanels()->where('faculty_id', $user->id)->exists();
    if (!$isAssignedPanel) abort(403, 'You are not assigned to this defense panel.');

    // Prepare JSON breakdown array from the request
    $criteria = collect($request->criteria_names)->values()->map(function ($name, $index) use ($request) {
        return ['name' => $name, 'score' => (float) ($request->criteria_scores[$index] ?? 0)];
    })->toArray();

    // Sum total score
    $totalScore = collect($criteria)->sum('score');

    // Create or update the rating sheet in the database
    RatingSheet::updateOrCreate(
        ['defense_schedule_id' => $schedule->id, 'faculty_id' => $user->id],
        [
            'group_id' => $schedule->group_id,
            'criteria' => $criteria, // Laravel handles JSON encoding automatically
            'total_score' => $totalScore,
            'remarks' => $request->remarks ?? null,
            'submitted_at' => now(),
        ]
    );
}
```

## 6. Critical Code Line-by-Line Breakdown (For 1000% Defense Readiness)

If your panelists want you to explain the code line-by-line, memorize these three most complex and critical Adviser functions.

### A. Submitting JSON Rating Sheets (`RatingSheetController@submitAdviserRating`)
Panel Question: *"Explain line-by-line how you save dynamic grading criteria without creating separate database tables for every single row on a rubric."*

```php
public function submitAdviserRating(Request $request, DefenseSchedule $schedule) {
    // LINE 1: Get the currently authenticated faculty member.
    $user = Auth::user();
    
    // LINE 2: Verify the user is actually assigned to this specific defense panel to prevent unauthorized grading.
    $isAssignedPanel = $schedule->defensePanels()->where('faculty_id', $user->id)->exists();
    if (!$isAssignedPanel) abort(403, 'You are not assigned to this defense panel.');

    // LINE 3: Combine the incoming arrays (criteria_names and criteria_scores) into a single collection.
    $criteria = collect($request->criteria_names)->values()->map(function ($name, $index) use ($request) {
        // LINE 4: Map each name to its corresponding score, casting the score to a float (decimal).
        return ['name' => $name, 'score' => (float) ($request->criteria_scores[$index] ?? 0)];
    })->toArray(); // LINE 5: Convert the final collection back into a raw PHP array.

    // LINE 6: Calculate the sum of all individual criteria scores to get the final grade.
    $totalScore = collect($criteria)->sum('score');

    // LINE 7: Use Eloquent's updateOrCreate to either insert a new grading sheet or overwrite an existing one for this faculty member.
    RatingSheet::updateOrCreate(
        ['defense_schedule_id' => $schedule->id, 'faculty_id' => $user->id], // LINE 8: The unique identifiers (Where to update).
        [
            'group_id' => $schedule->group_id, // LINE 9: The data to update.
            // LINE 10: Here is the magic. Laravel automatically encodes the PHP array into a raw JSON string because of the $casts array in the RatingSheet model.
            'criteria' => $criteria, 
            'total_score' => $totalScore,
            'remarks' => $request->remarks ?? null,
            'submitted_at' => now(), // LINE 11: Timestamp the submission.
        ]
    );
}
```

### B. Threaded Kanban Comments (`AdviserController@storeMilestoneTaskComment`)
Panel Question: *"Explain line-by-line how the database knows if a comment is a reply to another comment."*

```php
public function storeMilestoneTaskComment(Request $request, Group $group, GroupMilestoneTask $groupMilestoneTask) {
    // LINE 1: Insert a new row into the 'task_comments' table using the Adjacency List model structure.
    TaskComment::create([ 
        // LINE 2: Link the comment explicitly to the Kanban task the adviser clicked on.
        'group_milestone_task_id' => $groupMilestoneTask->id, 
        
        // LINE 3: Record the adviser's faculty ID as the author of the comment.
        'user_id' => Auth::id(), 
        
        // LINE 4: Save the actual text content of the message.
        'body' => $request->body, 
        
        // LINE 5: If the user clicked 'Reply', $request->parent_id contains the ID of the comment they replied to. 
        // If it's a new standalone comment, parent_id is NULL. This single column allows infinite nesting threads.
        'parent_id' => $request->parent_id, 
    ]);
    
    // LINE 6: Dispatch an alert via the NotificationService to tell the students they received feedback.
    NotificationService::adviserCommentOnMilestoneTask(Auth::user(), $groupMilestoneTask);
}
```

### C. Calculating Dashboard Progress (`AdviserController@calculateGroupProgress`)
Panel Question: *"Explain line-by-line how the dashboard dynamically calculates the overall completion percentage of a group."*

```php
private function calculateGroupProgress($group) {
    // LINE 1: Retrieve all active milestone templates assigned to this specific group.
    $groupMilestones = $group->groupMilestones; 
    
    // LINE 2: Fail-safe check: If the group has no milestones assigned yet, their progress is mathematically 0%.
    if ($groupMilestones->isEmpty()) return 0; 
    
    // LINE 3: Use Laravel's collection sum() to add up the pre-calculated 'progress_percentage' from each individual milestone category.
    // E.g., Chapter 1 (100%) + Chapter 2 (50%) = Total 150
    $totalProgress = $groupMilestones->sum('progress_percentage'); 
    
    // LINE 4: Divide the sum by the total number of milestones (e.g., 150 / 2 = 75%).
    // LINE 5: Round the result to avoid decimal places on the dashboard UI.
    return round($totalProgress / $groupMilestones->count()); 
}
```

## 7. Exhaustive Feature & Endpoint List (All Functions)
For complete system coverage, here is every single specific function the Adviser/Teacher can perform across the application:

**Dashboard, General Mentoring & Invitations (`AdviserController`)**
- `dashboard()`: Computes real-time milestone progress metrics for all assigned student groups, rendering the overview UI.
- `invitations()`: Retrieves all pending group mentorship invites directed specifically to the logged-in faculty member.
- `respondToInvitation()`: Processes accept/decline inputs for mentorships, updating the `AdviserInvitation` table.
- `myGroups()` / `allGroups()`: Lists groups specifically mentored by the faculty vs. all groups where they are either a mentor or a panelist.
- `groupDetails()`: Fetches comprehensive profile data (members, timeline) for a single advisee group.
- `milestoneTaskComments()`: Loads the threaded discussion view for a specific task card on the Kanban board.
- `storeMilestoneTaskComment()`: Saves a new nested or parent comment onto a task, executing adjacency list logic.
- `panelInvitations()` / `respondToPanelInvitation()`: Dedicated methods for viewing and accepting/declining invites to sit on defense grading panels.
- `notifications()`, `markAllNotificationsAsRead()`, `deleteNotification()`: Standard endpoints to manage the adviser's personal alert feed.
- `activityLog()`: Shows a granular audit trail of file uploads and task movements made solely by the adviser's assigned groups.

**Proposals & Feedback (`AdviserProposalController`)**
- `index()` / `show()`: Lists capstone proposals submitted by the adviser's groups that await review.
- `preview()`: Displays the active proposal PDF/document in the browser.
- `compareVersions()`: Fetches two historical versions of the same document to help the adviser track student revisions.
- `edit()` / `update()` / `bulkUpdate()`: Functions allowing the adviser to stamp proposals as "Approved" or "Rejected", individually or en masse.
- `getStats()`: Fetches numerical summaries of proposal statuses for dashboard display.
- `storeComment()`: Attaches a feedback thread directly to a submitted project proposal.

**Defenses & Grading (`RatingSheetController`)**
- `showAdviserForm()`: Loads the dynamic, JSON-driven rubric form for panelists during a live defense.
- `submitAdviserRating()`: Captures panelist scores, encodes them into a JSON array, calculates the weighted total, and saves it to the `RatingSheet` model.
- `showCoordinatorRatings()` / `finalizeCoordinatorRatings()` / `reopenCoordinatorRatings()`: Evaluates the aggregated panel grades and provides options to lock or unlock the final group score.
- `printCoordinatorRatings()`: Generates a printer-friendly layout of the finalized defense grades.

**Calendar & View (`CalendarController`)**
- `adviserCalendar()`: Retrieves all scheduled defenses where the faculty member is assigned (either as an adviser or panelist) and maps them onto the interactive calendar UI.

**Authentication (`AuthController`)**
- `login()` / `logout()`: Validates credentials against the encrypted `password` column and manages session tokens.
- `changePassword()`: Receives a new password, hashes it using `bcrypt()`, and updates the user's account row.
