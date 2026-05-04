# Adviser Features (Faculty)

The Adviser mentors student groups, provides threaded feedback, evaluates submissions, tracks group progress, and rates capstone defenses.

## 1. Dashboard Statistics Calculation

**Description:** The dashboard dynamically calculates group progress and pending tasks to give the adviser a snapshot of their workload.

**Core Logic (`app/Http/Controllers/AdviserController.php`):**
```php
public function dashboard() {
    
    // Get the currently logged-in faculty member
    $user = Auth::user(); 
    
    // Fetch groups and calculate progress on the fly using Eager Loading to prevent slow N+1 database queries
    $adviserGroups = Group::with(['groupMilestones', 'groupMilestoneTasks']) 
        
        // Only fetch groups assigned to this adviser
        ->where('faculty_id', $user->faculty_id) 
        
        // Execute the query
        ->get() 
        
        // Loop over each fetched group
        ->map(function ($group) { 
            
            // Dynamically calculate their overall percentage
            $group->progress_percentage = $this->calculateGroupProgress($group); 
            
            // Dynamically check for overdue tasks
            $group->overdue_tasks = $this->getOverdueTasks($group); 
            
            // Return the updated group object
            return $group; 
        });

    // Build an array of quick summary statistics for the top widgets
    $summaryStats = [ 
        'total_groups' => $adviserGroups->count(), // Total groups
        'groups_ready_for_defense' => $adviserGroups->filter(fn($g) => $g->progress_percentage >= 60)->count(), // Filter groups above 60% in memory
        'groups_needing_attention' => $adviserGroups->filter(fn($g) => $g->progress_percentage < 40)->count(), // Filter groups below 40% in memory
        'overdue_tasks_total' => $adviserGroups->sum('overdue_tasks'), // Add up all overdue tasks across all groups
    ];

    // Send the data to the Blade template
    return view('dashboards.adviser', compact('adviserGroups', 'summaryStats')); 
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
    
    // Find the specific panel assignment record for the currently logged-in faculty member. Throw a 404 error if they aren't on the panel.
    $panelMember = $schedule->defensePanels()->where('faculty_id', Auth::id())->firstOrFail();
    
    // Automatically add up all the individual scores (e.g., 20 + 30 = 50)
    $totalScore = array_sum($request->criteria_scores); 

    // Insert the final grades into the database
    RatingSheet::create([ 
        'defense_panel_id' => $panelMember->id, // Link the grades to this specific panelist
        'scores' => json_encode($request->criteria_scores), // Store the exact breakdown of scores as a JSON string for flexibility
        'total_score' => $totalScore, // Save the calculated total score
        'comments' => $request->overall_comments, // Save any final feedback from the panelist
    ]);
}
```
