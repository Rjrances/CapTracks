# Authentication & Shared Features

This document outlines the core logic and features that are shared across all roles, including system authentication, real-time notifications, calendar integration, and activity logging.

## 1. Multi-Guard Authentication & Health Check

**Description:** The application handles different user types (Students vs. Faculty/Staff) using distinct guards. It routes users to specific dashboards upon successful login. A health check route is also included for server monitoring (e.g., Railway).

**Core Logic (`app/Http/Controllers/AuthController.php` & `web.php`):**
```php
// Health Check (web.php)
// Expose a simple URL endpoint
Route::get('/health', function () { 
    
    // Return a JSON response
    return response()->json([ 
        'status' => 'ok', // Let external monitoring services know the server is alive
        'timestamp' => now(), // Provide the exact server time
        'app' => config('app.name') // Provide the name of the app
    ]);
});

// Authentication (AuthController.php)
public function login(Request $request) {
    
    // Block the request if the email or password are empty or invalid format
    $credentials = $request->validate([ 
        'email' => ['required', 'email'], 
        'password' => ['required'],
    ]);

    // Check Faculty/Staff Guard
    // Try to log in against the main `users` table
    if (Auth::guard('web')->attempt($credentials)) { 
        
        // Instantly create a new session ID to prevent Session Fixation hacking
        $request->session()->regenerate(); 
        
        // Check the role column of the faculty member
        $role = Auth::user()->role; 
        
        // Use PHP match to cleanly redirect them based on their exact role
        return match($role) { 
            'coordinator' => redirect()->route('coordinator.dashboard'),
            'chairperson' => redirect()->route('chairperson.dashboard'),
            'adviser' => redirect()->route('adviser.dashboard'),
            default => redirect('/'),
        };
    }
    
    // Check Student Guard
    // If faculty login failed, try the `students` table
    if (Auth::guard('student')->attempt($credentials)) { 
        
        // Generate a new session ID for the student
        $request->session()->regenerate(); 
        
        // Send them to the student dashboard
        return redirect()->route('student.dashboard'); 
    }

    // If both guards fail, kick them back to the login page with an error
    return back()->withErrors(['email' => 'The provided credentials do not match our records.']);
}
```

### 🧠 Defense Tip: How Does Student Authentication Work? (60% Defense Question)
If panelists ask: *"How did you separate Student logins from Faculty logins since they are in different database tables?"*

**Where to find the code:**
- **Controller:** `app/Http/Controllers/AuthController.php` (Where the login logic happens)
- **Configuration:** `config/auth.php` (Where the custom 'student' guard is registered)
- **Model:** `app/Models/Student.php` (The model the guard uses)

**Your Answer:** *"We used Laravel's **Multi-Guard Authentication** system. By default, Laravel only looks at the `users` table for logins. Because we designed our database to keep students in a completely separate `students` table for better normalization, we couldn't use the default login. To solve this, we went into `config/auth.php` and created a custom guard named `'student'` and pointed it to the Student table. 

In our `AuthController.php`, when someone submits the login form, the system runs an `if` statement. It first tries `Auth::guard('web')->attempt()` to see if the email belongs to a faculty member. If that fails, it immediately tries `Auth::guard('student')->attempt()`. If it finds a match there, it logs them in but restricts them strictly to the student guard context, ensuring they can never access faculty routes."*

### 🧠 Defense Tip: How did you handle Cyber Security during Login?
If a panelist asks: *"How do you prevent malicious actors from stealing active sessions or hacking the login?"*
**Your Answer:** *"We handled security on three levels during login. First, we use explicit `validate()` rules on the server side so malicious payloads can't be injected into our database checks. Second, the passwords in the database are encrypted using `Bcrypt/Hash`, so even if the DB leaks, the passwords are safe. Lastly, right after a successful login, we execute `$request->session()->regenerate()`. This instantly generates a brand new session token, which completely prevents a major cyber attack known as 'Session Fixation'."*


## 2. Real-Time Notifications

**Description:** Users receive in-app notifications for important events. The system tracks read/unread status globally.

**Core Logic (`app/Http/Controllers/AdviserController.php` - Similar in others):**
```php
// Marking multiple notifications as read
public function markMultipleAsRead(Request $request) {
    
    // Get the logged-in user
    $user = Auth::user(); 
    
    $request->validate([
        'notification_ids' => 'required|array', // Ensure the incoming data is specifically an array
        'notification_ids.*' => 'integer|exists:notifications,id', // Ensure every ID actually exists in the database to prevent tampering
    ]);

    // Find all notifications matching these IDs
    $updated = Notification::whereIn('id', $request->notification_ids) 
        
        // Ensure the user actually owns these notifications (Security Local Scope)
        ->visibleToWebUser($user) 
        
        // Run a single, fast SQL UPDATE to mark them all as read
        ->update(['is_read' => true]); 

    // Send a JSON response back to the Javascript frontend
    return response()->json(['success' => true, 'message' => $updated . ' notifications marked as read']);
}
```

## 3. Global Activity Logging

**Description:** Actions across the platform are logged to construct a timeline/activity feed.

**Core Logic (`app/Services/ActivityLogService.php`):**
```php
public static function logTaskCommentAdded(GroupMilestoneTask $task, $user, $student = null) {
    
    // Insert a new record into the activity_logs table
    ActivityLog::create([ 
        'student_id' => clone $student?->student_id, // Nullsafe operator: Only get ID if student exists
        'user_id'    => clone $user?->id, // Nullsafe operator: Only get ID if faculty exists         
        'action'     => 'task_commented', // Categorize the action
        'description'=> 'Added a comment to task: ' . $task->milestoneTask->name, // Human-readable log
        'loggable_type' => GroupMilestoneTask::class, // Polymorphic relation: Save the Model namespace
        'loggable_id' => $task->id, // Polymorphic relation: Save the exact Model ID
    ]);
}
```

## 4. Shared Calendar Integration

**Description:** Fetches relevant dates (milestone deadlines, defense schedules) and formats them for the FullCalendar frontend library depending on the user's role.

**Core Logic (`app/Http/Controllers/CalendarController.php`):**
```php
public function coordinatorCalendar() {
    
    // Get the current ongoing semester
    $activeTerm = AcademicTerm::where('is_active', true)->first(); 
    
    // Initialize an empty array to hold our calendar data
    $events = []; 

    if ($activeTerm) {
        
        // Fetch defense schedules with their related group data to prevent N+1 queries
        $defenses = DefenseSchedule::where('academic_term_id', $activeTerm->id)->with('group')->get();
        
        // Loop through the schedules
        foreach ($defenses as $defense) { 
            
            // Construct a JSON-friendly array for FullCalendar.js
            $events[] = [ 
                'title' => 'Defense: ' . $defense->group->name, // What to display on the calendar block
                'start' => $defense->schedule_date . 'T' . $defense->start_time, // ISO8601 formatting for Javascript (e.g. 2026-05-15T14:30)
                'end' => $defense->schedule_date . 'T' . $defense->end_time, // ISO8601 formatting
                'color' => '#ef4444', // Red background for defense events
                'url' => route('coordinator.defense.show', $defense->id) // Make the calendar block clickable
            ];
        }
        
        // Fetch milestone deadlines, but only for groups in the current active term
        // Ignore milestones without a strict deadline
        $milestones = GroupMilestone::whereHas('group', function($q) use ($activeTerm) {
            $q->where('academic_term_id', $activeTerm->id);
        })->whereNotNull('target_date')->get(); 
        
        // Loop through the deadlines
        foreach ($milestones as $milestone) { 
            
            // Add them to the FullCalendar array
            $events[] = [ 
                'title' => 'Deadline: ' . $milestone->milestoneTemplate->name,
                'start' => $milestone->target_date, // Simple date format
                'color' => '#3b82f6', // Blue background for milestone events
            ];
        }
    }

    // Inject the JSON array into the Blade template
    return view('coordinator.calendar', compact('events')); 
}
```

---

## 9. Methods Used (Simple Terms)

- `pluck('column')` - Gets only one column from query results (like IDs) instead of full rows.
- `whereIn('column', [...])` - Filters records that match any value in a list.
- `whereNotIn('column', [...])` - Excludes records that match values in a list.
- `withCount('relation')` - Adds relation counts without manual loops.
- `whereHas('relation', fn...)` - Filters by conditions inside related tables.
- `first()` - Gets the first matching row or `null`.
- `findOrFail(id)` - Finds by ID or throws a not found error.
- `create([...])` - Inserts a new database row.
- `update([...])` - Updates fields of existing rows.
- `delete()` - Removes a row.
- `exists()` - Returns true/false if any matching row exists.
- `collect([...])` - Creates a Laravel collection for chainable operations.
- `map(fn...)` - Transforms each item in a collection.
- `sortBy(...)` - Sorts collection items by one or more rules.
- `take(n)` - Gets only the first `n` items.
- `values()` - Reindexes collection keys to clean 0..n numbering.
- `unique('field')` - Removes duplicates by field.
- `toArray()` - Converts data to plain PHP array.
- `return back()->withErrors(...)->withInput()` - Returns user to form with errors and keeps previous input.
- `DB::beginTransaction()/commit()/rollback()` - All-or-nothing database save flow.
- `Carbon::parse(...)` - Converts date/time text into a date object.
- `response()->json([...])` - Returns JSON for frontend scripts.

### Symbols / Operators (Q&A quick guide)
- `?` (ternary) - Short if/else in one line.
- `??` (null coalescing) - Use fallback value when left side is `null`.
- `?:` (elvis shorthand) - Use left side if truthy, otherwise fallback.
- `?->` (null-safe operator) - Access property/method only if object is not `null`.
- `=>` - Key/value separator in arrays, and short function arrow syntax.
- `===` - Strict comparison (value and type must match).

## 10. Quick Oral Cheat Sheet (Top 10 Terms)

1. **`pluck`** - "Get only one column, like IDs, from many rows."
2. **`whereIn`** - "Filter rows that match any value in a list."
3. **`withCount`** - "Add relationship counts directly from DB, no manual loops."
4. **`whereHas`** - "Filter by a condition inside a related table."
5. **`create`** - "Insert a new database row quickly."
6. **`update`** - "Modify existing row values."
7. **`exists`** - "Fast yes/no check if a matching record exists."
8. **`sortBy`** - "Order results by a rule, like least workload first."
9. **`take(2)`** - "Get only the first two ranked candidates."
10. **`DB transaction`** - "All-or-nothing save: commit if all pass, rollback if any fail."
