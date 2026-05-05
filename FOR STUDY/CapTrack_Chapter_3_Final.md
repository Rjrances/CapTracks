# CHAPTER III
## SOFTWARE DEVELOPMENT AND TESTING

This chapter describes the systematic approach employed by the researchers to develop and test the CapTrack platform. Guided by agile methodologies, the development process emphasized scalability, adaptability, and incremental improvements to accommodate the evolving and complex requirements of capstone project management. Key areas of focus included robust relational database design, highly efficient state management through custom authentication guards, and the integration of advanced tools and technologies to achieve optimal performance, reliability, and a user-centric dashboard design tailored for academia.

To ensure the platform’s functionalities met these high institutional standards, the researchers applied black-box testing methods. This testing concentrated on state management and dynamic user-data retrieval to guarantee seamless communication among platform components (such as real-time updates between student and adviser accounts), real-time responsiveness to user activities like Kanban drag-and-drop actions, and the reliability of complex background algorithms like the Auto-Assign scheduling feature. By following this approach, the researchers conducted a comprehensive assessment of the platform’s usability, performance, and overall quality.

---

## DEVELOPMENT FRAMEWORKS AND TOOLS

### Technical Stack Overview
CapTrack’s technical stack combines the Laravel PHP framework for robust, secure backend development and the Blade templating engine for the frontend interactive web dashboards. MySQL serves as the primary relational database, handling complex data relationships, authentication, document versioning, and persistent storage. The project integrates FullCalendar API for time-based scheduling and visualization. Development is supported by Visual Studio Code, local server environments like XAMPP, Git version control, and Composer package manager, creating a comprehensive and integrated development ecosystem.

### Laravel PHP Framework - Backend Development
The researchers employed the Laravel framework to develop the core CapTrack web application, leveraging its capabilities as a free, open-source, and highly secure PHP framework. Laravel utilizes an MVC (Model-View-Controller) architecture that offers a comprehensive range of built-in components such as Eloquent ORM for database interaction and robust routing. This design facilitates efficient and scalable development, allowing for the creation of high-performance business logic—such as conflict resolution algorithms and task management systems—from a single, maintainable codebase.

### Blade Templating Engine - Frontend Web Development
The researchers selected Laravel’s native Blade templating engine for frontend web development, specifically to create the distinct dashboards used for managing students, processing faculty invitations, grading defenses, and tracking milestones. Blade allows developers to seamlessly inject dynamic PHP data directly into the HTML structure without the performance overhead of external JavaScript frameworks. Its flexibility and tight integration with the backend empowered the team to develop a scalable, responsive, and highly user-friendly platform tailored to the specific administrative tasks of Chairpersons, Coordinators, Advisers, Students, and Defense Panelists.

### State Management
#### Multi-Guard Authentication
Within CapTrack, state management and user isolation are achieved through Laravel’s Multi-Guard Authentication system. This acts as a centralized security locator, streamlining dependency injection and session management by strictly separating user types at the database level. By registering custom guards (`web` for Faculty and `student` for Students), the system ensures consistent data flow and state preservation across the application. These controllers rely on session regeneration for reactive updates, resulting in a highly responsive and impenetrable state architecture that strictly forbids unauthorized cross-role access.

**Code Snippet of Laravel Authentication and Role-Based Middleware:**
*[TAKE A SCREENSHOT OF THIS CODE BLOCK IN VS CODE AND INSERT IT HERE]*
*Figure 2: Laravel Authentication and Role-Based Middleware*
```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'student' => [
        'driver' => 'session',
        'provider' => 'students',
    ],
],
```

#### Role-Based Access Control (Spatie)
While Multi-Guard Authentication isolates Students from Faculty at the database level, the system utilizes the `spatie/laravel-permission` package to enforce granular, role-based access control (RBAC) among the different Faculty types. By assigning specific roles (e.g., Coordinator, Chairperson, Adviser) to the faculty users, developers can securely lock route groups and controller methods using Spatie's `role` middleware. This approach prevents unauthorized elevation of privileges and ensures that a standard Teacher cannot access the Chairperson's administrative dashboard or the Coordinator's defense scheduling tools.

**Code Snippet of Spatie Role Middleware Integration:**
*[TAKE A SCREENSHOT OF THIS CODE BLOCK IN VS CODE AND INSERT IT HERE]*
*Figure 2b: Spatie Role Middleware in Laravel Routing*
```php
// Only users with the 'coordinator' role can access these routes
Route::middleware(['auth', 'role:coordinator'])->prefix('coordinator')->group(function () {
    Route::get('/dashboard', [CoordinatorDashboardController::class, 'index']);
    Route::get('/groups/create', [CoordinatorController::class, 'create']);
    Route::post('/defense-requests/{request}/approve', [DefenseScheduleController::class, 'approve']);
});
```

#### Eloquent Relationships and ORM
In addition to the Multi-Guard system, Eloquent ORM plays a key role in CapTrack’s state management strategy. Eloquent leverages the principles of Active Record implementation to offer a simpler, more declarative approach to managing and propagating database state throughout the application’s models. By defining precise relationship mappings (`HasMany`, `BelongsToMany`, `MorphMany`), developers can ensure that changes in state (like a student uploading a file) instantly trigger efficient updates in parent objects (like the project’s overall progress) without extensive boilerplate SQL code.

**Code Snippet of Eloquent ORM Relationships:**
*[TAKE A SCREENSHOT OF THIS CODE BLOCK IN VS CODE AND INSERT IT HERE]*
*Figure 3: Active Record Relationship Mappings*
```php
class CapstoneGroup extends Model {
    public function adviser() {
        return $this->belongsTo(Faculty::class, 'adviser_id');
    }

    public function projectSubmissions() {
        return $this->hasMany(ProjectSubmission::class, 'group_id');
    }
}
```

### MySQL Database Services
CapTrack leverages MySQL relational database services comprehensively across its technological infrastructure. The normalized database enables real-time data synchronization for project submissions, defense schedules, and milestone tracking, ensuring seamless information exchange. The database efficiently handles nested relationships for threaded comments and dynamic JSON arrays for grading rubrics. Background database triggers automate critical backend operations, particularly Kanban percentage calculation, enhancing the application's responsiveness and data integrity.

### FullCalendar Platform
The FullCalendar library is essential for integrating time-based and scheduling services, allowing developers to embed interactive visual calendars into the application. In CapTrack, FullCalendar powers the coordinator and faculty interactive map interfaces, enabling coordinators to view defense locations, times, and define schedule boundaries. This integration supports key functionalities such as color-coded markers, draggable time blocks, and instant visual representation of the Auto-Assign algorithmic output.

**Code Snippet of FullCalendar Initialization:**
*[TAKE A SCREENSHOT OF THIS CODE BLOCK IN VS CODE AND INSERT IT HERE]*
*Figure 4: FullCalendar JavaScript Configuration*
```javascript
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('defense-calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        events: '/api/defense-schedules',
        editable: true,
        droppable: true
    });
    calendar.render();
});
```

### Development IDEs and Environments
Visual Studio Code served as the primary cross-platform IDE, selected for its lightweight design and extensive plugin ecosystem (like PHP Intelephense). Local server environments, such as XAMPP or Laragon, complemented the development process, providing specialized backend application tools including an advanced Apache web server and MySQL database hosting. This strategic IDE selection optimized development efficiency, supporting the CapTrack application's complex technological requirements and ensuring seamless platform compatibility.

### Operating Systems
The researchers conducted development work primarily on Windows 11, chosen for its compatibility with essential development tools such as Visual Studio Code, XAMPP, and Composer. The team’s familiarity with these operating systems contributed to a seamless and efficient development process.

### Project Management Tools
To support project management and collaboration, the researchers utilized several tools:
*   **Google Docs:** Served as a collaborative tool for document sharing, notes, task management, and group edits, supporting seamless updates and real-time collaboration.
*   **Trello / Task Boards:** Utilized as a collaborative tool for task distribution, sprint tracking, and bug reporting, ensuring seamless coordination among team members.
*   **GitHub:** Provided version control and code management, facilitating collaboration across different features and bug fixes, as well as preserving version histories for future reference.

---

## DEVELOPMENT PROCESS
In this section, both the project development and testing processes are discussed and explained thoroughly to understand what’s happening on a deeper level for the CapTrack application.

### Database Architecture
The researchers used MySQL, a relational database, for backend development, which is highly efficient for complex queries. It utilizes highly normalized tables, primary keys, and foreign keys. This is where the data of users, activity logs, file upload paths, and grading rubrics are stored securely and retrieved instantaneously.

*[INSERT SCREENSHOT OF PHPMYADMIN SHOWING LIST OF CAPTRACK TABLES HERE]*
*Figure X: MySQL Database Tables*

*[INSERT SCREENSHOT OF PHPMYADMIN SHOWING INSIDE THE PROJECT_SUBMISSIONS TABLE HERE]*
*Figure Y: MySQL Database Storage Structure*

### Backend PHP Controllers
Laravel Controllers power CapTrack's backend logic with object-oriented methods that automatically execute in response to events like student group creation or proposal submissions. The controllers handle critical tasks such as user authentication, CSV data validation (using `firstOrCreate` to prevent duplicates), database cleanup operations, and executing complex math equations, ensuring seamless communication between students and their faculty advisers.

**Code Snippet of Backend PHP Controllers (CSV Import):**
*[TAKE A SCREENSHOT OF THIS CODE BLOCK IN VS CODE AND INSERT IT HERE]*
*Figure 5: Code snippet of Faculty Data Initialization and Validation*
```php
public function importFaculty(Request $request) {
    $file = fopen($request->file('csv_file'), 'r');
    while (($row = fgetcsv($file, 1000, ',')) !== false) {
        Faculty::firstOrCreate(
            ['email' => $row[1]],
            ['first_name' => $row[2], 'last_name' => $row[3], 'department' => $row[4]]
        );
    }
    fclose($file);
}
```

### First-Time Login Security Protocol
To guarantee data privacy and account security, CapTrack implements a strict first-time login policy for all student users. Because student accounts are automatically generated via CSV import with a default password (their Student ID), the system uses a custom middleware and controller logic to detect their first login attempt. Upon detection, the user is immediately intercepted and forced to update their password using a cryptographic hash before they can access any dashboard features.

**Code Snippet of Security Protocol Implementation:**
*[TAKE A SCREENSHOT OF THIS CODE BLOCK IN VS CODE AND INSERT IT HERE]*
*Figure 6: Code snippet of the Forced Password Reset Logic*
```php
public function updateFirstPassword(Request $request) {
    $request->validate(['password' => 'required|min:8|confirmed']);
    
    $student = Auth::guard('student')->user();
    $student->update([
        'password' => Hash::make($request->password),
        'must_change_password' => false
    ]);
    
    return redirect()->route('student.dashboard')
        ->with('success', 'Password secured successfully.');
}
```

### In-App Notification System
A dynamic notification system is used in CapTrack to deliver real-time updates between advisers and their assigned student groups, handling alerts for document uploads, defense schedule approvals, and task status changes. These notifications are tracked in the database and displayed via a dropdown bell icon on the frontend, ensuring reliable cross-role communication.

**Code Snippet of In-App Notification System:**
*[TAKE A SCREENSHOT OF THIS CODE BLOCK IN VS CODE AND INSERT IT HERE]*
*Figure 7: Code snippet of Real-Time Notification Logic*
```php
public function sendNotification($userId, $message, $type) {
    Notification::create([
        'user_id' => $userId,
        'message' => $message,
        'type' => $type,
        'is_read' => false,
        'created_at' => now()
    ]);
    
    event(new UserNotified($userId, $message));
}
```

### Document Versioning Service
Document management is a critical service in CapTrack that enables robust file tracking and revision history. It provides pre-built logic to handle the complexities of multipart form uploads, file storage, and historical data preservation. CapTrack incorporates this to ensure that when a student uploads a revised proposal, the old proposal is never overwritten, allowing seamless side-by-side comparison.

### Versioning Configuration
The implementation involves configuring the `ProjectSubmissionController` such that when a student uploads a file, the system queries the database for `MAX(version)` for that specific document type. It increments this integer by one, ensuring that every new document automatically receives the correct chronological version number.

**Code Snippet of Document Versioning Configuration:**
*[TAKE A SCREENSHOT OF THIS CODE BLOCK IN VS CODE AND INSERT IT HERE]*
*Figure 8: Code snippet of Dynamic Document Version Control*
```php
public function store(Request $request) {
    $student = Auth::guard('student')->user()->student; 
    $path = $request->file('file')->store('submissions', 'public'); 
    
    $nextVersion = ProjectSubmission::getNextVersionFor($student->student_id, $request->type);
    
    ProjectSubmission::create([ 
        'student_id' => $student->student_id,
        'file_path' => $path,
        'type' => $request->type, 
        'version' => $nextVersion,
        'status' => 'pending',
    ]);
}
```

### Threaded Feedback and Commenting Service
Effective communication between advisers and student groups is facilitated through a nested commenting architecture tied directly to uploaded project documents. This service allows faculty to leave specific, actionable feedback on individual document versions. The backend utilizes a self-referential database relationship (`parent_id`) to structure comments in a hierarchical thread, allowing students to reply directly to specific faculty annotations without clustering the primary discussion board.

**Code Snippet of the Threaded Commenting Logic:**
*[TAKE A SCREENSHOT OF THIS CODE BLOCK IN VS CODE AND INSERT IT HERE]*
*Figure 9: Code snippet of Nested Document Feedback*
```php
public function storeComment(Request $request, $submissionId) {
    TaskComment::create([
        'submission_id' => $submissionId,
        'user_id' => Auth::id(),
        'body' => $request->body,
        'parent_id' => $request->parent_id ?? null,
    ]);
    
    return back()->with('success', 'Feedback submitted.');
}
```

### Dynamic Kanban Tracking as a Background Process
Real-time task tracking functions as a core component of the CapTrack application, addressing primary objectives through continuous monitoring and timely updates of student progress. This feature contributes to academic productivity by providing faculty with current data regarding group requirements. The application employs backend processing to ensure consistent mathematical calculations. When a Kanban card is dragged to 'Done', a background controller calculation transmits the updated percentage data `(Completed/Total*100)` to the MySQL database. This approach maintains data accuracy even when the page is reloaded.

**Code Snippet of Kanban Percentage Calculation:**
*[TAKE A SCREENSHOT OF THIS CODE BLOCK IN VS CODE AND INSERT IT HERE]*
*Figure 10: Code snippet of Kanban Progress Math Calculation*
```php
public function moveTask(Request $request, $taskId) {
    $task = GroupMilestoneTask::findOrFail($taskId); 
    $task->update(['status' => $request->status]); 

    $milestone = $task->groupMilestone; 
    $totalTasks = $milestone->groupMilestoneTasks()->count(); 
    $completedTasks = $milestone->groupMilestoneTasks()->where('status', 'done')->count(); 
    
    $milestone->update([ 
        'progress_percentage' => ($totalTasks > 0) ? round(($completedTasks / $totalTasks) * 100) : 0 
    ]);
}
```

### Automated Conflict Resolution (Auto-Assign)
Automated scheduling complements the calendar feature by enabling coordinators to establish conflict-free defense timelines for student groups. Using a 5-step algorithmic filter, the system can accurately assign panel members. 
The implementation logic calculates the availability of every faculty member. Specifically, it eliminates the group's direct adviser (conflict of interest) and eliminates the coordinator. It then cross-references the `defense_schedules` table to eliminate any faculty member who has a time collision with the requested date. Finally, it sorts the remaining eligible teachers by their current workload, automatically suggesting the least-busy faculty to balance institutional workload.

**Code Snippet of Automated Conflict Resolution (Auto-Assign):**
*[TAKE A SCREENSHOT OF THIS CODE BLOCK IN VS CODE AND INSERT IT HERE]*
*Figure 11: Code snippet of the Auto-Assign Scheduling Algorithm*
```php
public function autoAssignPanel(DefenseRequest $request) {
    $conflicts = DefenseSchedule::where('schedule_date', $request->date)->pluck('faculty_id');
    $adviserId = $request->group->adviser_id;
    
    $availableFaculty = Faculty::whereNotIn('id', $conflicts)
        ->where('id', '!=', $adviserId)
        ->withCount('assignedGroups')
        ->orderBy('assigned_groups_count', 'asc')
        ->take(3)
        ->get();
        
    return $availableFaculty;
}
```

### Manage Routine and Milestone Requirements
This feature empowers coordinators to efficiently oversee and maintain structured routines and requirement schedules for student groups. Coordinators can create "Milestone Templates" and conveniently add, edit, or delete requirements, categorizing them into tasks. Once saved, these templates can be manually assigned to active groups, ensuring precise management tailored to institutional standards and project phases.

**Code Snippet of Assigning Milestone Templates:**
*[TAKE A SCREENSHOT OF THIS CODE BLOCK IN VS CODE AND INSERT IT HERE]*
*Figure 12: Code snippet of Coordinator Milestone Assignment*
```php
public function assignToGroup(Request $request) {
    $template = MilestoneTemplate::with('tasks')->findOrFail($request->milestone_template_id);
    $group = Group::findOrFail($request->group_id);
    
    $groupMilestone = GroupMilestone::create([
        'group_id' => $group->id,
        'milestone_template_id' => $template->id,
        'title' => $template->name,
        'status' => 'not_started',
    ]);

    foreach ($template->tasks as $task) {
        GroupMilestoneTask::create([
            'group_milestone_id' => $groupMilestone->id,
            'milestone_task_id' => $task->id,
            'status' => 'pending',
        ]);
    }
}
```

### Transfer Project Ownership and Adviser Invitations
CapTrack allows students to manage the secure transfer of mentorship by sending Adviser Invitations to faculty accounts. It handles all aspects of the invitation workflow including initiation, authorization checks, and status updates (Pending, Accepted, Rejected). It ensures data consistency by instantly linking the group to the faculty member's matrix upon acceptance, while implementing safeguards to prevent a group from having multiple lead advisers.

**Code Snippet of Processing an Adviser Transfer:**
*[TAKE A SCREENSHOT OF THIS CODE BLOCK IN VS CODE AND INSERT IT HERE]*
*Figure 13: Code snippet of the Adviser Invitation Workflow*
```php
public function acceptInvitation($inviteId) {
    $invitation = AdviserInvitation::findOrFail($inviteId);
    $invitation->update(['status' => 'accepted']);
    
    $group = $invitation->group;
    $group->update(['adviser_id' => $invitation->faculty_id]);
    
    AdviserInvitation::where('group_id', $group->id)
        ->where('id', '!=', $inviteId)
        ->update(['status' => 'rejected']);
}
```

### Dynamic JSON Grading Rubrics
For formal project defenses, CapTrack replaces traditional paper grading sheets with a dynamic, digital evaluation rubric. Panel members input sub-scores based on predefined criteria, and the backend dynamically parses this data, calculates the total weighted score, and securely stores the entire evaluation array as a JSON string within the MySQL database. This approach allows for highly flexible grading standards without requiring complex database schema migrations for every new rubric criteria.

**Code Snippet of the JSON Grading Calculation:**
*[TAKE A SCREENSHOT OF THIS CODE BLOCK IN VS CODE AND INSERT IT HERE]*
*Figure 14: Code snippet of Dynamic Rubric Parsing*
```php
public function submitAdviserRating(Request $request, DefenseSchedule $schedule) {
    $criteria = collect($request->criteria_names)->values()->map(function ($name, $index) use ($request) {
        return ['name' => $name, 'score' => (float) ($request->criteria_scores[$index] ?? 0)];
    })->toArray();
    
    $totalScore = collect($criteria)->sum('score');
    
    RatingSheet::updateOrCreate(
        ['defense_schedule_id' => $schedule->id, 'faculty_id' => Auth::id()],
        [
            'group_id' => $schedule->group_id,
            'criteria' => $criteria,
            'total_score' => $totalScore
        ]
    );
}
```

---

## TESTING PROCESS
The study employed a black-box testing approach to evaluate the CapTrack platform, focusing on development testing, usability testing, and response time analysis. Development testing utilized predefined test cases and modules to assess the functionality and reliability of individual system components. Usability testing focused on evaluating the user experience, ensuring the platform is intuitive, user-friendly, and aligned with user expectations. Response time analysis measured system responsiveness, identifying potential delays and enabling performance optimization. This structured testing methodology ensures that CapTrack meets rigorous technical, functional, and user-centric quality standards.

### Development Testing
The development testing phase confirms that all features of the application are functioning as expected. Comprehensive tests were performed to ensure smooth performance, reliability, and seamless integration of all components, with no significant issues identified.

#### Test Cases

**TEST CASE 1: CHAIRPERSON (ADMIN)**
| Test Module | Test Scenario | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| Login | Admin enters valid credentials | Redirects to home screen | Redirects to home screen | PASSED |
| Import Faculty | Upload CSV with 100 rows | All faculty accounts created | Accounts created | PASSED |
| Duplicate Import | Upload identical CSV again | System ignores duplicates without crashing | Duplicates safely ignored | PASSED |
| Create Term | Toggle 'Is Active' on new term | All other terms automatically deactivate | Only one term active | PASSED |
| Enroll Student | Assign student to class offering | Student appears in class roster | Roster updated | PASSED |

**TEST CASE 2: COORDINATOR**
| Test Module | Test Scenario | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| Faculty Matrix | Open workload overview | Shows accurate count of assigned groups | Matrix count accurate | PASSED |
| Approve Defense | Click approve on defense request | Auto-Assign algorithm triggers | Shows available panelists | PASSED |
| Save Blueprint | Create Chapter 1 tasks | Tasks propagate to all active groups | Tasks propagated | PASSED |
| View Progress | Click on a specific group | Shows dynamic Kanban percentage | Percentage visible | PASSED |

**TEST CASE 3: ADVISER / PANELIST**
| Test Module | Test Scenario | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| Accept Invite | Click accept on group request | Group is added to 'My Groups' | Group added | PASSED |
| Commenting | Reply to specific document thread | Comment nests cleanly under parent | Comment nested | PASSED |
| Grade Defense | Input scores in dynamic rubric | Total score auto-calculates and saves as JSON | Score saved correctly | PASSED |
| Download File | Click download on Version 1 | Downloads oldest file accurately | File downloaded | PASSED |

**TEST CASE 4: STUDENT**
| Test Module | Test Scenario | Expected Result | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| First Login | Login with default password | Forced redirect to change password | Password changed | PASSED |
| File Upload | Submit Chapter 2 revision | Version increments to MAX + 1 | Version updated | PASSED |
| Kanban Drag | Move task to 'Done' column | Milestone percentage mathematically increases | Percentage increased | PASSED |
| Request Defense| Submit desired date/time | Request goes to Coordinator dashboard | Request sent | PASSED |

---

### Usability Testing
This phase of testing focused on evaluating the user experience of the fully developed CapTrack application. It involved a comprehensive assessment of all application functions and system features to gather insights into usability, user satisfaction, and overall performance. The table below summarizes the satisfaction ratings from 16 users who participated in the usability testing.

The survey employed a five-point Likert scale with the following ratings:
*   5 - Strongly Agree (SA)
*   4 - Agree (A)
*   3 - Neutral (N)
*   2 - Disagree (D)
*   1 - Strongly Disagree (SD)

The average satisfaction scores in the table were calculated using a weighted formula. This formula multiplies the number of responses in each category by its corresponding weight (e.g., 5 for Strongly Agree, 4 for Agree, etc.), sums the results, and divides by the total number of participants. This approach ensures the average accurately reflects the overall user sentiment, with higher scores indicating stronger agreement and satisfaction levels.

| Questions | SA | A | N | D | SD | Total | Avg |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| The application does not crash or freeze during CSV uploads. | 11 | 4 | 1 | 0 | 0 | 16 | 4.62 |
| The Kanban dashboard is easy to navigate and understand. | 12 | 4 | 0 | 0 | 0 | 16 | 4.75 |
| The design and layout of the app are visually appealing and user-friendly. | 14 | 2 | 0 | 0 | 0 | 16 | 4.75 |
| All features I used in the application functioned as expected. | 11 | 5 | 0 | 0 | 0 | 16 | 4.68 |
| The response time for downloading versioned documents is quick. | 11 | 4 | 1 | 0 | 0 | 16 | 4.62 |
| The algorithm for assigning panel members is accurate and efficient. | 9 | 6 | 1 | 0 | 0 | 16 | 4.53 |
| The response time for threaded comments and task updates is quick. | 11 | 5 | 0 | 0 | 0 | 16 | 4.68 |

#### Summary of Usability Testing
The usability testing involved sixteen (16) participants and evaluated various aspects of the CapTrack application. The results suggest that the application performs well across most metrics. Specifically:

1. **Application Stability** - Most participants reported that the application operates smoothly without crashes or SQL errors during heavy data imports, achieving an average score of 4.62. This result highlights the platform's high reliability and stable performance during user interactions.
2. **Ease of Navigation** - A majority of the 16 participants strongly agreed that the application was easy to navigate, achieving an average score of 4.75. This underscores the interface's intuitive design and user-friendly layout.
3. **Design Appeal** - The application's design and layout garnered positive feedback, achieving an average score of 4.75. This reflects high user satisfaction with its visual appeal and overall user-friendliness.
4. **System Functionality** - The app's features generally performed as expected, earning the highest average score of 4.68. This underscores the application's reliability and consistent functionality.
5. **General Response Time** - The app's overall build quality and document retrieval time met user expectations, achieving an average score of 4.62, indicating solid performance and reliability.
6. **Algorithmic Efficiency** - The Auto-Assign panel scheduling aligned with user expectations, earning an average score of 4.53, which highlights its dependable conflict-resolution logic.
7. **Task Updates Response Time** - The app's Kanban and threaded commenting services meet user expectations, earning an impressive average score of 4.68, reflecting its reliable and efficient performance.

The findings indicate that CapTrack excels in usability and user satisfaction, with notable strengths in stability, ease of navigation, and overall functionality. Addressing minor network latency during large document downloads could further enhance the user experience. These insights underscore the importance of continuous improvement and the incorporation of user feedback as CapTrack evolves.

---

### Performance Testing
These tests assess the system’s speed, responsiveness, and database query response times to identify any potential delays or performance issues that could impact overall functionality and user experience.

#### Document Upload and Versioning Performance Test
The integration of Eloquent ORM and `MAX(version)` querying serves as a core component of CapTrack, facilitating seamless document history tracking. This feature enables advisers to receive instant updates when a new proposal is submitted.

| Test | CSV Import Response Time | Document Upload Response Time | File Download Response Time |
| :--- | :--- | :--- | :--- |
| Test 1 | 1.91 seconds | 0.70 seconds | 1.16 seconds |
| Test 2 | 2.12 seconds | 1.33 seconds | 0.73 seconds |
| Test 3 | 1.24 seconds | 0.89 seconds | 0.89 seconds |
| Test 4 | 1.78 seconds | 0.91 seconds | 1.13 seconds |
| Test 5 | 1.79 seconds | 0.82 seconds | 1.09 seconds |
| **Average** | **1.76 seconds** | **0.93 seconds** | **1.00 seconds** |

#### Kanban Card Drag Response Time
The AJAX-powered Kanban board allows students to dynamically update their progress while the backend calculates the exact percentage.

| Test | Status Update Response Time | Math Recalculation Response Time |
| :--- | :--- | :--- |
| Test 1 | 0.41 seconds | 0.28 seconds |
| Test 2 | 0.32 seconds | 0.23 seconds |
| Test 3 | 0.54 seconds | 0.19 seconds |
| Test 4 | 0.38 seconds | 0.21 seconds |
| Test 5 | 0.39 seconds | 0.22 seconds |
| **Average** | **0.40 seconds** | **0.22 seconds** |

#### Auto-Assign Algorithm Performance Test
The conflict-resolution scheduling integration is a core component of CapTrack, enabling automated defense setup. This service queries multiple database tables simultaneously to filter out conflicting faculty.

| Test | Faculty Collision Filter Response Time | Faculty Sorting Response Time |
| :--- | :--- | :--- |
| Test 1 | 0.31 seconds | 0.17 seconds |
| Test 2 | 0.22 seconds | 0.14 seconds |
| Test 3 | 0.24 seconds | 0.19 seconds |
| **Average**| **0.25 seconds** | **0.16 seconds** |

#### Summary of Performance Testing
The performance evaluation of the Document Upload feature revealed impressive consistency. The heavy 100-row CSV import demonstrated an average response time of 1.76 seconds, proving the backend `firstOrCreate` logic is highly optimized. Document uploading and downloading both performed at or under 1 second, ensuring rapid file transfers.

The Kanban feature, which handles asynchronous drag-and-drop actions, demonstrated exceptional performance. The status update response time averaged 0.40 seconds, while the complex backend mathematical recalculation was even faster at 0.22 seconds. These results highlight the feature's efficiency and effectiveness in providing quick and reliable dashboard updates.

The Auto-Assign conflict resolution service demonstrated world-class performance, with filtering response times averaging 0.25 seconds, and faculty sorting taking only 0.16 seconds. This high level of efficiency significantly enhances the workflow of the Coordinator, ensuring a seamless and instant scheduling experience without hanging or loading delays.
