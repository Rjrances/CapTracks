# CapTracks Complete Codebase Map & Overview

Because CapTracks is built on the robust Laravel framework, it contains hundreds of files. Explaining every single line in the entire project would be tens of thousands of pages long! However, panelists will not ask you to explain framework boilerplate. They will ask you to explain **the code you wrote**.

This document is a comprehensive map of all the custom classes, models, and controllers in CapTracks and what they do.

---

## 1. 📂 Models (`app/Models/`)
*The database blueprints. These define relationships (e.g., "A student belongs to a group").*

* **`User.php`**: The faculty/staff model (Advisers, Coordinators, Chairpersons, Panelists). Uses Spatie's `HasRoles` trait.
* **`Student.php`**: The separate student model used for the custom `student` authentication guard.
* **`AcademicTerm.php`**: Tracks semesters. Only one can be `is_active` at a time.
* **`Group.php`**: Connects students together under an `offering_id` and an adviser (`faculty_id`).
* **`Offering.php`**: A class section. Belongs to an `AcademicTerm` and has many `Student`s (via pivot table `offering_student`).
* **`ProjectSubmission.php`**: Handles uploaded documents like Proposals. Tracks `version` numbers.
* **`SubmissionComment.php`**: Holds threaded/nested comments (using `parent_id`) for project proposals.
* **`TaskComment.php`**: Holds threaded/nested comments for specific Kanban tasks.
* **`MilestoneTemplate.php` & `MilestoneTask.php`**: The coordinator's "blueprints" for capstone phases (e.g., Chapter 1).
* **`GroupMilestone.php` & `GroupMilestoneTask.php`**: The actual "instances" of the milestones assigned to specific groups.
* **`TaskSubmission.php`**: Files uploaded by students for specific Kanban tasks.
* **`DefenseRequest.php`**: A student's request for a defense date.
* **`DefenseSchedule.php`**: The actual finalized schedule (date, time, venue) set by the Coordinator.
* **`DefensePanel.php`**: Connects a `User` (faculty) to a `DefenseSchedule` with a role of either `chair` or `member`.
* **`RatingSheet.php`**: Stores the final grading rubric for a defense. The actual scores are stored dynamically inside a JSON column.
* **`Notification.php`**: Stores in-app alerts. Uses polymorphic relationships or generic `role`/`user_id` targeting.
* **`ActivityLog.php`**: Stores user actions (e.g., "Student uploaded a file").

---

## 2. 📂 Controllers (`app/Http/Controllers/`)
*The "brains" of the application. These receive web requests, talk to models, and return views.*

### 🔐 Authentication & Shared
* **`AuthController.php`**: Uses Multi-Guard authentication to log in either a `User` (Faculty) or a `Student`.
* **`StudentPasswordController.php`**: Forces students to change their default `password123` upon first login.
* **`RoleController.php`**: Allows the Chairperson to assign Spatie roles (Coordinator, Adviser) to faculty.
* **`CalendarController.php`**: Formats database dates into a JSON array for the `FullCalendar` Javascript library.
* **`RatingSheetController.php`**: Handles processing and calculating the dynamic JSON grading rubrics during defense.

### 👑 Chairperson (Admin)
* **`ChairpersonDashboardController.php`**: Shows global system statistics.
* **`ChairpersonOfferingController.php`**: Manages class sections and manual student enrollment.
* **`ChairpersonStudentController.php` & `ChairpersonFacultyController.php`**: Handles manual creation and **Mass CSV Imports** of users.
* **`AcademicTermController.php`**: Toggles the active semester on and off.

### 🛠️ Coordinator (Manager)
* **`CoordinatorController.php`**: Manages groups, assigns advisers, and views the `facultyMatrix` to monitor teacher workload.
* **`CoordinatorProposalController.php`**: Allows bulk-approving of student proposals.
* **`DefenseScheduleController.php`**: Processes defense requests, auto-assigns available panelists, and finalizes schedules.
* **`MilestoneTemplateController.php`**: Creates milestone blueprints and manually assigns them to groups via `assignToGroup()`.
* **`DefenseRubricController.php`**: Manages the dynamic criteria that appear on the rating sheets.

### 👨‍🏫 Adviser (Mentor & Panelist)
* **`AdviserController.php`**: Shows dashboard stats (progress percentages), accepts/declines mentorship and panel invitations, and handles threaded commenting on tasks.
* **`AdviserProposalController.php`**: Reviews project documents submitted by their specific advisees.

### 🎓 Student
* **`StudentGroupController.php`**: Allows students to search by ID to form groups and invite advisers.
* **`StudentMilestoneController.php`**: The Kanban board logic. Dragging a card triggers `moveTask()` which recalculates the group's overall percentage.
* **`StudentMilestoneChecklistController.php`**: A read-only view of their requirements.
* **`StudentProposalController.php`**: Uploads new versions of documents and allows side-by-side version comparison/rollback.
* **`TaskSubmissionController.php`**: Uploads files attached to specific Kanban cards.
* **`StudentDefenseRequestController.php`**: Creates a pending request asking the Coordinator for a defense date.

---

## 3. 📂 Services (`app/Services/`)
*Helper classes that contain complex business logic so the controllers don't get too bloated.*

* **`NotificationService.php`**: Centralizes all the logic for dispatching alerts. For example, `adviserCommentOnMilestoneTask()` or `coordinatorAssignedMilestoneToGroup()`.
* **`ActivityLogService.php`**: Automates writing to the `ActivityLog` table whenever a user takes a significant action.
* **`StudentImportService.php`**: Processes the logic of reading a CSV file and running `firstOrCreate()` to prevent database crashes on duplicate uploads.

---

## 4. 📂 Middleware (`app/Http/Middleware/`)
*The "bouncers" at the door. These check requests before they reach the controllers.*

* **`Authenticate.php`**: Default Laravel middleware. Ensures the user is logged in.
* **`CheckStudentPasswordChange.php`**: A custom middleware that checks if a student's password is still the default. If yes, it forcefully redirects them to the change password screen.
* **Spatie Middleware (`role:coordinator`, etc.)**: Provided by the `spatie/laravel-permission` package. Used in `routes/web.php` to completely block standard Advisers from accessing Coordinator routes.

---

## 5. 📂 Routes (`routes/web.php`)
*The traffic cop. It maps URLs (like `/coordinator/dashboard`) to the correct Controller.*

* Utilizes `Route::middleware(['auth', 'role:...'])->group(...)` to protect entire sections of the website at once.
* Uses the `student` guard for all student-facing routes to ensure faculty cannot access student portals.

---

## 6. 📂 Views (`resources/views/`)
*The frontend Blade templates that the users actually see.*

* Uses standard **Blade Templating** (`@if`, `@foreach`) to dynamically render HTML based on database data.
* Heavily relies on **Tailwind CSS** or **Bootstrap** (depending on your setup) for styling.
* Integrates external Javascript libraries like **FullCalendar** for scheduling and **SweetAlert** for popup notifications.

---

### How to use this guide in your Defense:
If a panelist asks you: *"How does the system calculate the final grade?"*
1. Look at this map and find **Grading**. Ah, that's `RatingSheetController.php`.
2. Recall the logic: "In the `RatingSheetController`, we map the JSON array of criteria, use `array_sum` to calculate the total, and save it to the database."

If they ask: *"Where is the Kanban board logic located?"*
1. Look at the map under **Student**. That's `StudentMilestoneController.php`.
2. Recall the logic: "When a student drags a card, it triggers the `moveTask()` method in the `StudentMilestoneController`, which updates the task status and recalculates the total milestone percentage."
