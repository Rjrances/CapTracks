# Student Role - Technical Documentation

## Overview
Students are users who work on capstone projects in groups. They submit proposals, manage milestones, track tasks, and request defenses.

---

## Controllers

### 1. StudentController
**File:** `app/Http/Controllers/StudentController.php`

**Purpose:** Handles general student operations like profile and notifications.

#### Functions:

**`index()`**
- **What it does:** Redirects to student dashboard
- **Returns:** Redirect to `student.dashboard` route

**`getAuthenticatedStudent()` (Private)**
- **What it does:** Gets the currently logged-in student
- **Returns:** Student model or null
- **How it works:** Checks if student is logged in using student guard

**`notifications()`**
- **What it does:** Shows all notifications for the student
- **Returns:** Notification list page with pagination (20 per page)
- **What you see:** All system messages, updates, and alerts

**`markNotificationAsRead($notificationId)`**
- **What it does:** Marks one notification as read
- **Parameters:** Notification ID
- **Returns:** JSON success/error response
- **Security:** Verifies notification belongs to this student

**`markAllNotificationsAsRead()`**
- **What it does:** Marks all student's notifications as read at once
- **Returns:** JSON with count of notifications marked
- **How it works:** Finds all unread notifications and updates them

**`deleteNotification($notificationId)`**
- **What it does:** Permanently deletes a notification
- **Parameters:** Notification ID
- **Returns:** JSON success/error response
- **Security:** Verifies notification belongs to this student

**`markMultipleAsRead(Request $request)`**
- **What it does:** Marks selected notifications as read (bulk operation)
- **Parameters:** Array of notification IDs
- **Returns:** JSON with count of notifications updated
- **Validation:** Checks all IDs exist and belong to student

**`deleteMultiple(Request $request)`**
- **What it does:** Deletes multiple notifications at once
- **Parameters:** Array of notification IDs
- **Returns:** JSON with count of notifications deleted
- **Validation:** Checks all IDs exist and belong to student

---

### 2. StudentDashboardController
**File:** `app/Http/Controllers/StudentDashboardController.php`

**Purpose:** Displays the main student dashboard with all project information.

#### Functions:

**`index()`**
- **What it does:** Shows the main dashboard page
- **Returns:** Dashboard view with all student data
- **Data provided:**
  - Active academic term
  - Student information
  - Group details
  - Overall project progress
  - Task statistics
  - Submissions count
  - Current milestone info
  - Recent tasks and activities
  - Upcoming deadlines
  - Adviser information
  - Defense schedules
  - Notifications
  - Proposal status
  - Subject/offering information

**`calculateOverallProgress($student, $group)` (Private)**
- **What it does:** Calculates total project completion percentage
- **Parameters:** Student and Group models
- **Returns:** Number (0-100)
- **How it works:** Averages progress across all group milestones

**`getTaskStatistics($student, $group)` (Private)**
- **What it does:** Counts tasks by status
- **Returns:** Array with completed, total, pending, and doing task counts
- **Categories:**
  - `completed` - Tasks marked as done
  - `total` - All tasks
  - `pending` - Not started tasks
  - `doing` - In-progress tasks

**`getSubmissionsCount($student)` (Private)**
- **What it does:** Counts how many documents the student submitted
- **Returns:** Number of submissions
- **Includes:** Proposals, finals, and other documents

**`getCurrentMilestoneInfo($student, $group)` (Private)**
- **What it does:** Gets information about current active milestone
- **Returns:** Array with name, description, progress, status
- **Logic:** Finds first incomplete milestone or returns last one if all complete

**`getRecentTasks($student, $group)` (Private)**
- **What it does:** Gets the 5 most recent tasks
- **Returns:** Collection of task objects
- **Data includes:** Name, description, status, completion, assigned person

**`getRecentActivities($student)` (Private)**
- **What it does:** Shows recent project activities
- **Returns:** Collection of 5 most recent activities
- **Types tracked:**
  - Document uploads
  - Task completions (last 7 days)
- **Sorted by:** Most recent first

**`getUpcomingDeadlines($student, $group)` (Private)**
- **What it does:** Gets next 5 upcoming deadlines
- **Returns:** Collection of deadlines
- **Types:**
  - Milestone target dates
  - Task deadlines
- **Flags:** Overdue and due-soon indicators

**`getAdviserInfo($group)` (Private)**
- **What it does:** Gets adviser assignment details
- **Returns:** Array with adviser, invitation status, can-invite flag
- **Information:**
  - Has adviser assigned (true/false)
  - Adviser details if assigned
  - Pending invitations
  - Whether student can send invitation

**`getDefenseInfo($group)` (Private)**
- **What it does:** Gets defense schedule information
- **Returns:** Array with scheduled defenses, pending requests, can-request flag
- **Information:**
  - Scheduled defense sessions
  - Pending defense requests
  - Whether student can request defense (needs adviser first)

**`getNotifications($student)` (Private)**
- **What it does:** Gets 5 most recent unread notifications
- **Returns:** Collection of notifications
- **Use:** Quick notification preview on dashboard

**`getExistingProposal($student)` (Private)**
- **What it does:** Finds student's latest proposal
- **Returns:** Proposal submission or null
- **Use:** Shows proposal status on dashboard

**`getOfferingInfo($group, $student)` (Private)**
- **What it does:** Gets current class/subject enrollment info
- **Returns:** Array with offering details
- **Information:**
  - Offer code (e.g., "CS199-1")
  - Subject code and title
  - Teacher name
  - Coordinator name

---

### 3. StudentPasswordController
**File:** `app/Http/Controllers/StudentPasswordController.php`

**Purpose:** Handles password changes for students.

#### Functions:

**`showChangePasswordForm()`**
- **What it does:** Shows the change password page
- **Returns:** Password change form
- **Required:** Student must be logged in

**`updatePassword(Request $request)`**
- **What it does:** Updates student's password
- **Parameters:** Current password, new password, confirmation
- **Validation:**
  - Current password must be correct
  - New password minimum 8 characters
  - New and confirmation must match
- **Returns:** Redirect with success/error message
- **Security:** Hashes password before saving

---

### 4. ProjectSubmissionController
**File:** `app/Http/Controllers/ProjectSubmissionController.php`

**Purpose:** Manages document submissions (proposals, finals, others).

#### Functions:

**`index()`**
- **What it does:** Shows all student's submitted documents
- **Returns:** List of submissions
- **Displays:** Title, type, status, feedback, submission date

**`create()`**
- **What it does:** Shows form to upload a new document
- **Returns:** Upload form
- **Form includes:** Type selector, file upload, title, description fields

**`store(Request $request)`**
- **What it does:** Saves a new document submission
- **Parameters:** File, type, title, optional description
- **Validation:**
  - File required (PDF, DOCX)
  - Type must be: proposal, final, or other
  - Title required
- **Returns:** Redirect to submissions list with success message
- **File storage:** Stores in `submissions/` folder

**`show($id)`**
- **What it does:** Shows details of one submission
- **Parameters:** Submission ID
- **Returns:** Detailed view with feedback, status, download link
- **Displays:**
  - Document information
  - Adviser comments
  - Approval status
  - Download button

**`destroy($id)`**
- **What it does:** Deletes a submission
- **Parameters:** Submission ID
- **Returns:** Redirect with success message
- **Restriction:** Can only delete pending submissions (not approved/rejected)
- **Also deletes:** Physical file from storage

---

### 5. StudentGroupController
**File:** `app/Http/Controllers/StudentGroupController.php`

**Purpose:** Manages group creation, members, and invitations.

#### Functions:

**`index()`**
- **What it does:** Shows all available groups and invitations
- **Returns:** List of groups and pending invitations
- **Use:** Find groups to join or see invitations

**`show()`**
- **What it does:** Shows student's current group details
- **Returns:** Group information page
- **Displays:**
  - Group name and description
  - All members
  - Adviser (if assigned)
  - Pending invitations sent

**`create()`**
- **What it does:** Shows form to create a new group
- **Returns:** Group creation form
- **Restriction:** Student cannot already be in a group

**`store(Request $request)`**
- **What it does:** Creates a new group
- **Parameters:** Group name, description
- **Validation:**
  - Name required, max 255 characters
  - Student not already in a group
- **Returns:** Redirect to group page
- **Auto-sets:** Creator as first member

**`edit()`**
- **What it does:** Shows form to edit group details
- **Returns:** Edit form
- **Can edit:** Name and description only

**`update(Request $request)`**
- **What it does:** Updates group information
- **Parameters:** New name, new description
- **Returns:** Redirect to group page with success message

**`inviteMember(Request $request)`**
- **What it does:** Sends invitation to another student to join group
- **Parameters:** Student ID to invite
- **Validation:**
  - Student exists
  - Not already in this group
  - No pending invitation already sent
- **Returns:** JSON success/error
- **Creates:** GroupInvitation record with 'pending' status

**`inviteAdviser(Request $request)`**
- **What it does:** Sends invitation to faculty to be group adviser
- **Parameters:** Faculty ID, invitation message
- **Validation:**
  - Group doesn't already have adviser
  - No pending adviser invitation
  - Faculty member exists
- **Returns:** Redirect with success message
- **Creates:** AdviserInvitation record

**`removeMember($memberId)`**
- **What it does:** Removes a student from the group
- **Parameters:** Student ID to remove
- **Returns:** Redirect with success message
- **Restriction:** Cannot remove last member

**`invitations()`**
- **What it does:** Shows all invitations received by student
- **Returns:** List of pending group invitations
- **Displays:** Group name, who invited, when, members count

**`acceptInvitation($invitationId)`**
- **What it does:** Accepts invitation to join a group
- **Parameters:** Invitation ID
- **Process:**
  1. Removes student from current group (if any)
  2. Adds student to new group
  3. Marks invitation as accepted
- **Returns:** Redirect to new group page

**`declineInvitation($invitationId)`**
- **What it does:** Rejects invitation to join a group
- **Parameters:** Invitation ID
- **Returns:** Redirect with message
- **Sets:** Invitation status to 'declined'

**`cancelInvitation($invitationId)`**
- **What it does:** Cancels an invitation student sent
- **Parameters:** Invitation ID
- **Returns:** Redirect with message
- **Security:** Verifies student sent this invitation

---

### 6. StudentProposalController
**File:** `app/Http/Controllers/StudentProposalController.php`

**Purpose:** Manages project proposals (special type of submission).

#### Functions:

**`index()`**
- **What it does:** Shows student's proposals
- **Returns:** Proposal list
- **Displays:** Title, status, adviser feedback, coordinator feedback

**`create()`**
- **What it does:** Shows proposal creation form
- **Returns:** Detailed proposal form
- **Fields:**
  - Title
  - Objectives
  - Methodology
  - Timeline
  - Expected outcomes
  - File attachment

**`store(Request $request)`**
- **What it does:** Submits a new proposal
- **Validation:**
  - Title required
  - File required (PDF/DOCX)
  - Objectives, methodology recommended
- **Returns:** Redirect to proposals list
- **Status:** Set to 'pending' for review

**`show($id)`**
- **What it does:** Shows full proposal details
- **Parameters:** Proposal ID
- **Returns:** Detailed proposal view
- **Displays:**
  - All proposal information
  - Adviser feedback
  - Coordinator feedback
  - Approval status
  - Download link

**`edit($id)`**
- **What it does:** Shows form to edit proposal
- **Parameters:** Proposal ID
- **Returns:** Edit form
- **Restriction:** Can only edit if rejected or pending
- **Cannot edit:** Approved proposals

**`update(Request $request, $id)`**
- **What it does:** Updates proposal after rejection
- **Parameters:** Proposal ID, updated information
- **Returns:** Redirect with success message
- **Resets:** Status back to 'pending' for re-review

---

### 7. StudentMilestoneController
**File:** `app/Http/Controllers/StudentMilestoneController.php`

**Purpose:** Manages project milestones and tasks (Kanban board).

#### Functions:

**`index()`**
- **What it does:** Shows all group milestones
- **Returns:** List of milestones with progress
- **Displays:**
  - Milestone name
  - Progress percentage
  - Status (pending, in_progress, completed)
  - Target date
  - Task counts

**`create()`**
- **What it does:** Shows form to add a milestone
- **Returns:** Milestone creation form
- **Selects from:** Pre-defined milestone templates (set by coordinator)

**`store(Request $request)`**
- **What it does:** Adds a new milestone to group
- **Parameters:** Milestone template ID, target date
- **Returns:** Redirect to milestones list
- **Creates:** Group milestone with tasks from template

**`show($milestone)`**
- **What it does:** Shows Kanban board for one milestone
- **Parameters:** Milestone ID
- **Returns:** Kanban board view
- **Columns:**
  - To Do (pending tasks)
  - Doing (in-progress tasks)
  - Done (completed tasks)
- **Features:** Drag-and-drop to change status

**`edit($milestone)`**
- **What it does:** Shows form to edit milestone
- **Parameters:** Milestone ID
- **Returns:** Edit form
- **Can edit:** Target date, status

**`update(Request $request, $milestone)`**
- **What it does:** Updates milestone details
- **Parameters:** Milestone ID, new information
- **Returns:** Redirect with success message

**`destroy($milestone)`**
- **What it does:** Deletes a milestone
- **Parameters:** Milestone ID
- **Returns:** Redirect with success message
- **Also deletes:** All tasks under this milestone

**`updateMultipleTasks(Request $request, $milestone)`**
- **What it does:** Updates multiple tasks at once
- **Parameters:** Milestone ID, array of task updates
- **Returns:** JSON success response
- **Use:** Batch operations on tasks

**`assignTask(Request $request, $groupMilestoneTask)`**
- **What it does:** Assigns a task to a group member
- **Parameters:** Task ID, student ID
- **Returns:** JSON success response
- **Updates:** Task's assigned_to field

**`unassignTask($groupMilestoneTask)`**
- **What it does:** Removes assignment from a task
- **Parameters:** Task ID
- **Returns:** JSON success response
- **Sets:** assigned_to to null

**`updateTask(Request $request, $groupMilestoneTask)`**
- **What it does:** Updates a single task
- **Parameters:** Task ID, new status/details
- **Returns:** JSON success response
- **Can update:** Status, description, deadline, assigned person

**`moveTask(Request $request, $taskId)`**
- **What it does:** Moves task between Kanban columns
- **Parameters:** Task ID, new status
- **Validation:** Status must be: pending, doing, or done
- **Returns:** JSON success response
- **Use:** Drag-and-drop on Kanban board

**`bulkUpdateTasks(Request $request, $milestoneId)`**
- **What it does:** Updates multiple tasks in one operation
- **Parameters:** Milestone ID, array of task changes
- **Returns:** JSON success response
- **Use:** Bulk status changes

**`recomputeProgress(Request $request, $milestoneId)`**
- **What it does:** Recalculates milestone progress percentage
- **Parameters:** Milestone ID
- **Returns:** JSON with new progress value
- **Formula:** (Completed tasks / Total tasks) × 100

---

### 8. TaskSubmissionController
**File:** `app/Http/Controllers/TaskSubmissionController.php`

**Purpose:** Handles file submissions for individual tasks.

#### Functions:

**`create($task)`**
- **What it does:** Shows form to submit work for a task
- **Parameters:** Task ID
- **Returns:** Upload form
- **Form includes:** File upload, description, notes

**`store(Request $request, $task)`**
- **What it does:** Submits work for a task
- **Parameters:** Task ID, file, description
- **Validation:**
  - File required
  - Description optional
- **Returns:** Redirect to milestone board
- **Updates:** Task status to 'done' automatically

**`show($submission)`**
- **What it does:** Shows submitted task work
- **Parameters:** Submission ID
- **Returns:** Submission details view
- **Displays:**
  - File download link
  - Description
  - Submission date
  - Task information

---

### 9. StudentDefenseRequestController
**File:** `app/Http/Controllers/StudentDefenseRequestController.php`

**Purpose:** Manages defense presentation requests and schedules.

#### Functions:

**`index()`**
- **What it does:** Shows all defense requests and schedules
- **Returns:** List of requests
- **Displays:**
  - Request status (pending, approved, scheduled, rejected)
  - Defense type (proposal, midterm, final)
  - Scheduled date and time (if approved)
  - Room location
  - Panel members

**`create()`**
- **What it does:** Shows form to request a defense
- **Returns:** Request form
- **Requirements:**
  - Must have an adviser
  - Must meet progress requirements
- **Form includes:** Defense type, preferred date, notes

**`store(Request $request)`**
- **What it does:** Submits a defense request
- **Parameters:** Defense type, preferred date, notes
- **Validation:**
  - Group must have adviser
  - Defense type required
- **Returns:** Redirect with success message
- **Status:** Set to 'pending' for coordinator review

**`show($defenseRequest)`**
- **What it does:** Shows detailed defense information
- **Parameters:** Defense request ID
- **Returns:** Defense details view
- **Displays:**
  - All request information
  - If scheduled: date, time, room, panel members
  - Coordinator feedback
  - Request status

**`cancel($defenseRequest)`**
- **What it does:** Cancels a defense request
- **Parameters:** Defense request ID
- **Returns:** Redirect with message
- **Restriction:** Can only cancel pending requests

---

## Key Terms

**Academic Term**: School period (e.g., "2024-2025 First Semester")

**Offering**: A specific class section (subject + teacher + coordinator)

**Group**: Team of students working on the same capstone project

**Milestone**: Major project checkpoint (e.g., "Chapter 1", "Final Defense")

**Task**: Smaller job within a milestone

**Proposal**: Document explaining the project idea and plan

**Defense**: Presentation to a panel of teachers to explain your project

**Kanban Board**: Visual board showing task progress (To Do → Doing → Done)

**Status Types**:
- `pending`: Waiting for review/approval
- `approved`: Accepted/approved
- `rejected`: Not accepted
- `in_progress`: Currently working on it
- `completed`: Finished

**Guard**: Security system that checks if user is logged in correctly

**Validation**: Checking if data is correct before saving

**JSON Response**: Data sent back to JavaScript (for dynamic updates)

**Redirect**: Sending user to a different page

**Collection**: A group of database records that can be filtered and sorted

