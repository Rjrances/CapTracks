# Authentication & Shared Features

This document outlines the core logic and features that are shared across all roles, including system authentication, real-time notifications, calendar integration, and activity logging.

## 1. Multi-Guard Authentication & Health Check

**Description:** The application handles different user types (Students vs. Faculty/Staff) using distinct guards. It routes users to specific dashboards upon successful login. A health check route is also included for server monitoring (e.g., Railway).

**Core Logic (`app/Http/Controllers/AuthController.php` & `web.php`):**
```php
// Authentication (AuthController.php)
public function login(Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    // Check Faculty/Staff Guard
    if (Auth::guard('web')->attempt($credentials)) {
        $request->session()->regenerate();
        $role = Auth::user()->role;
        return match($role) {
            'coordinator' => redirect()->route('coordinator.dashboard'),
            'chairperson' => redirect()->route('chairperson.dashboard'),
            'adviser' => redirect()->route('adviser.dashboard'),
            default => redirect('/'),
        };
    }
    
    // Check Student Guard
    if (Auth::guard('student')->attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->route('student.dashboard');
    }

    return back()->withErrors(['email' => 'The provided credentials do not match our records.']);
}
```

### 🧠 Defense Tip: How did you handle Cyber Security during Login?
If a panelist asks: *"How do you prevent malicious actors from stealing active sessions or hacking the login?"*
**Your Answer:** *"We handled security on three levels during login. First, we use explicit `validate()` rules on the server side so malicious payloads can't be injected into our database checks. Second, the passwords in the database are encrypted using `Bcrypt/Hash`, so even if the DB leaks, the passwords are safe. Lastly, right after a successful login, we execute `$request->session()->regenerate()`. This instantly generates a brand new session token, which completely prevents a major cyber attack known as 'Session Fixation'."*

**Code Explanation:**
- `$request->validate(...)`: Validates that the user input actually contains valid email syntax and isn't empty, otherwise throwing an error before wasting database resources.
- `Auth::guard('web')->attempt(...)`: Laravel uses "guards" to manage different user tables. Here, it takes the email/password and checks it against the main `users` table (faculty/staff). If it matches, it logs them in.
- `$request->session()->regenerate()`: Crucial for security. Generates a brand new session ID upon login, completely preventing "Session Fixation" cyber attacks.
- `match($role)`: A modern, cleaner alternative to `switch` statements in PHP. It checks the user's role column and redirects them to the URL corresponding to their role's dashboard.
- `Auth::guard('student')->attempt(...)`: If they failed the faculty check, the system then checks if those credentials belong to a student in the `students` table.
- `back()->withErrors(...)`: If both guards fail, it redirects back to the login page and flashes an error message to the user.

## 2. Real-Time Notifications

**Description:** Users receive in-app notifications for important events. The system tracks read/unread status globally.

**Core Logic (`app/Http/Controllers/AdviserController.php` - Similar in others):**
```php
// Marking multiple notifications as read
public function markMultipleAsRead(Request $request) {
    $user = Auth::user();
    $request->validate([
        'notification_ids' => 'required|array',
        'notification_ids.*' => 'integer|exists:notifications,id',
    ]);

    $updated = Notification::whereIn('id', $request->notification_ids)
        ->visibleToWebUser($user)
        ->update(['is_read' => true]);

    return response()->json(['success' => true, 'message' => $updated . ' notifications marked as read']);
}
```
**Code Explanation:**
- `$request->validate(...)`: Ensures that the data sent by the frontend is specifically an array, and that every item inside the array is a valid ID that actually exists in the `notifications` table database. This prevents tampering.
- `whereIn(...)`: Performs a SQL `UPDATE` statement that updates multiple rows concurrently, rather than writing a slow loop that updates them one by one.
- `visibleToWebUser($user)`: This is a "Local Scope" defined on the Notification model. It ensures users can only mark notifications as read if they actually belong to them (preventing users from marking another person's notifications as read).

## 3. Global Activity Logging

**Description:** Actions across the platform are logged to construct a timeline/activity feed.

**Core Logic (`app/Services/ActivityLogService.php`):**
```php
public static function logTaskCommentAdded(GroupMilestoneTask $task, $user, $student = null) {
    ActivityLog::create([
        'student_id' => clone $student?->student_id, 
        'user_id'    => clone $user?->id,            
        'action'     => 'task_commented',
        'description'=> 'Added a comment to task: ' . $task->milestoneTask->task_name,
        'loggable_type' => GroupMilestoneTask::class,
        'loggable_id' => $task->id,
    ]);
}
```
**Code Explanation:**
- `public static function`: Declaring the method as static means we can call it from anywhere in the application (like `ActivityLogService::logTaskCommentAdded()`) without needing to instantiate the class with `new`.
- `clone $student?->student_id`: The `?->` (nullsafe operator) safely fetches the student's ID. If the `$student` object is null (for instance, if a faculty member made the comment, not a student), it safely returns null instead of throwing a "trying to access property on null" fatal error.
- `'loggable_type' => GroupMilestoneTask::class`: This relies on Laravel's Polymorphic Relationships. Because activities can belong to many different things (Tasks, Submissions, Group Edits), `loggable_type` stores the model's namespace (e.g. `App\Models\GroupMilestoneTask`) and `loggable_id` stores the specific ID, allowing us to fetch the related object flexibly.

## 4. Shared Calendar Integration

**Description:** Fetches relevant dates (milestone deadlines, defense schedules) and formats them for the FullCalendar frontend library depending on the user's role.

**Core Logic (`app/Http/Controllers/CalendarController.php`):**
```php
public function coordinatorCalendar() {
    $activeTerm = AcademicTerm::where('is_active', true)->first();
    $events = [];

    if ($activeTerm) {
        // Fetch defense schedules
        $defenses = DefenseSchedule::where('academic_term_id', $activeTerm->id)->with('group')->get();
        foreach ($defenses as $defense) {
            $events[] = [
                'title' => 'Defense: ' . $defense->group->name,
                'start' => $defense->schedule_date . 'T' . $defense->start_time,
                'end' => $defense->schedule_date . 'T' . $defense->end_time,
                'color' => '#ef4444', // Red for defense
                'url' => route('coordinator.defense.show', $defense->id)
            ];
        }
        
        // Fetch milestone deadlines
        $milestones = GroupMilestone::whereHas('group', function($q) use ($activeTerm) {
            $q->where('academic_term_id', $activeTerm->id);
        })->whereNotNull('target_date')->get();
        
        foreach ($milestones as $milestone) {
            $events[] = [
                'title' => 'Deadline: ' . $milestone->milestoneTemplate->name,
                'start' => $milestone->target_date,
                'color' => '#3b82f6', // Blue for milestone
            ];
        }
    }

    return view('coordinator.calendar', compact('events'));
}
```
**Code Explanation:**
- `$events = [];`: We initialize an empty array. The goal of this function is to construct a specific JSON-like array structure that the frontend javascript (FullCalendar.js) can read natively.
- `...->start_time`: FullCalendar expects date times in ISO8601 format. By concatenating the date and time strings with a `T` (`$defense->schedule_date . 'T' . $defense->start_time`), we create strings like `"2026-05-15T14:30:00"`, which Javascript automatically understands.
- `whereNotNull('target_date')`: Not all milestones have rigid deadlines. This ensures we only try to plot events on the calendar that actually have a physical target date saved in the database.
- `compact('events')`: A shorthand PHP function that passes the `$events` array down to the view template so it can be injected into the javascript setup block.
