# Coordinator Role - Technical Documentation

## Overview
Coordinators manage capstone projects for their specific subject offerings. They oversee groups, review proposals, create milestone templates, and schedule defenses.

---

## Controllers

### 1. CoordinatorController
**File:** `app/Http/Controllers/CoordinatorController.php`

**Purpose:** Main coordinator operations including groups, milestones, and general management.

#### Functions:

**`index()`**
- **What it does:** Redirects to coordinator dashboard
- **Returns:** Redirect to coordinator dashboard route

**`classlist()`**
- **What it does:** Shows list of students in coordinator's classes
- **Returns:** Student list view organized by offering
- **Displays:**
  - Student names and IDs
  - Enrollment status
  - Class sections

**`groups()`**
- **What it does:** Shows all groups in coordinator's offerings
- **Returns:** Group list view
- **Displays:**
  - Group names
  - Members count
  - Adviser assigned (if any)
  - Progress percentage
  - Offering/class

**`create()`**
- **What it does:** Shows form to create a new group manually
- **Returns:** Group creation form
- **Use:** Create groups for students who don't have one yet

**`store(Request $request)`**
- **What it does:** Saves a new group
- **Parameters:** Group name, description, academic term
- **Validation:**
  - Name required
  - Must belong to active academic term
- **Returns:** Redirect to groups list

**`show($group)`**
- **What it does:** Shows detailed information about one group
- **Parameters:** Group ID
- **Returns:** Group details view
- **Displays:**
  - All members
  - Adviser information
  - Milestones progress
  - Recent submissions
  - Defense requests

**`edit($group)`**
- **What it does:** Shows form to edit group details
- **Parameters:** Group ID
- **Returns:** Edit form
- **Can edit:** Name, description, assigned adviser

**`update(Request $request, $group)`**
- **What it does:** Updates group information
- **Parameters:** Group ID, updated data
- **Returns:** Redirect with success message

**`destroy($group)`**
- **What it does:** Deletes a group
- **Parameters:** Group ID
- **Returns:** Redirect with success message
- **Warning:** This removes all group data

**`assignAdviser($group)`**
- **What it does:** Shows form to assign adviser to a group
- **Parameters:** Group ID
- **Returns:** Adviser assignment form
- **Lists:** Available faculty members with adviser role

**`groupMilestones($id)`**
- **What it does:** Shows all milestones for a specific group
- **Parameters:** Group ID
- **Returns:** Milestone list view
- **Displays:**
  - Milestone names
  - Progress percentages
  - Target dates
  - Completion status

**`notifications(Request $request)`**
- **What it does:** Shows coordinator notifications
- **Parameters:** Optional filters (role, status, date)
- **Returns:** Notification list view
- **Filters:**
  - By role
  - By status (read/unread)
  - By date (today/week/month)

**`markNotificationAsRead($notificationId)`**
- **What it does:** Marks one notification as read
- **Parameters:** Notification ID
- **Returns:** JSON success/error response
- **Security:** Verifies notification belongs to coordinator

**`markAllNotificationsAsRead()`**
- **What it does:** Marks all coordinator notifications as read
- **Returns:** JSON with count of notifications updated

**`deleteNotification($notificationId)`**
- **What it does:** Deletes a notification
- **Parameters:** Notification ID
- **Returns:** JSON success response

**`markMultipleAsRead(Request $request)`**
- **What it does:** Marks selected notifications as read (bulk)
- **Parameters:** Array of notification IDs
- **Returns:** JSON with count updated
- **Validation:** All IDs must exist and belong to coordinator

**`deleteMultiple(Request $request)`**
- **What it does:** Deletes multiple notifications
- **Parameters:** Array of notification IDs
- **Returns:** JSON with count deleted

**`profile()`**
- **What it does:** Shows coordinator profile page
- **Returns:** Profile view
- **Displays:** Personal information, assigned offerings

---

### 2. CoordinatorDashboardController
**File:** `app/Http/Controllers/CoordinatorDashboardController.php`

**Purpose:** Displays coordinator dashboard with overview statistics.

#### Functions:

**`index(Request $request)`**
- **What it does:** Shows main coordinator dashboard
- **Returns:** Dashboard view with all statistics
- **Data provided:**
  - Active academic term
  - Student count (in coordinator's offerings)
  - Group count
  - Faculty count
  - Submission count
  - Groups with/without advisers
  - Total group members
  - Pending/approved/rejected submissions
  - Milestone templates count
  - Active milestones
  - Total/completed tasks
  - Recent students (5 newest)
  - Recent groups (5 newest)
  - Recent submissions (5 newest)
  - Recent notifications (5 newest)
  - Pending adviser invitations (5 newest)
  - Recent activities (8 items)
  - Upcoming deadlines (5 items)
  - Coordinated offerings (if teacher-coordinator)

**`getRecentActivities($selectedTerm)` (Private)**
- **What it does:** Gathers recent system activities
- **Parameters:** Active academic term
- **Returns:** Collection of 8 most recent activities
- **Types tracked:**
  - New groups created
  - Submissions uploaded
  - Adviser invitations sent
- **Sorted by:** Most recent first

**`getUpcomingDeadlines()` (Private)**
- **What it does:** Gets next 5 deadlines
- **Returns:** Collection of deadlines
- **Types:**
  - Active milestone deadlines
  - Proposal submission deadlines
  - Defense schedules
- **Shows:** Title, description, due date, type, urgency flags

---

### 3. CoordinatorProposalController
**File:** `app/Http/Controllers/CoordinatorProposalController.php`

**Purpose:** Reviews and approves/rejects project proposals from students.

#### Functions:

**`index()`**
- **What it does:** Shows all proposals from coordinator's offerings
- **Returns:** Proposal list view
- **Displays:**
  - Student name
  - Group name
  - Proposal title
  - Status (pending, approved by adviser, approved, rejected)
  - Submission date
  - Adviser feedback

**`show($id)`**
- **What it does:** Shows full proposal details
- **Parameters:** Proposal ID
- **Returns:** Detailed proposal view
- **Displays:**
  - All proposal information (title, objectives, methodology, etc.)
  - Attached file
  - Adviser comments
  - Current status

**`review($id)`**
- **What it does:** Shows form to review a proposal
- **Parameters:** Proposal ID
- **Returns:** Review form
- **Form includes:**
  - Approve/Reject options
  - Comment field (required)
  - Proposal information for reference

**`update(Request $request, $id)`**
- **What it does:** Approves or rejects a proposal
- **Parameters:** Proposal ID, status (approved/rejected), coordinator comment
- **Validation:**
  - Status must be approved or rejected
  - Comment required (min 10 characters)
- **Returns:** Redirect to proposals list
- **Notifications:** Sends notification to student about decision

**`bulkUpdate(Request $request)`**
- **What it does:** Approves/rejects multiple proposals at once
- **Parameters:** Array of proposal IDs, status, comment
- **Validation:**
  - All proposal IDs must exist
  - Status and comment required
- **Returns:** Redirect with count of proposals updated
- **Use:** Bulk approval/rejection

**`getStats()`**
- **What it does:** Gets proposal statistics for dashboard
- **Returns:** JSON with counts
- **Data:**
  - Total proposals
  - Pending review
  - Approved
  - Rejected
- **Use:** Dashboard charts/cards

---

### 4. MilestoneTemplateController
**File:** `app/Http/Controllers/MilestoneTemplateController.php`

**Purpose:** Creates and manages milestone templates that students use.

#### Functions:

**`index()`**
- **What it does:** Shows all milestone templates
- **Returns:** Template list view
- **Displays:**
  - Template names
  - Descriptions
  - Number of tasks
  - Status (active/inactive)
  - Created date

**`create()`**
- **What it does:** Shows form to create new milestone template
- **Returns:** Creation form
- **Form includes:**
  - Template name
  - Description
  - Task list (can add multiple tasks)

**`store(Request $request)`**
- **What it does:** Saves a new milestone template
- **Parameters:** Name, description, tasks array
- **Validation:**
  - Name required
  - At least one task required
- **Returns:** Redirect to templates list
- **Creates:**
  - MilestoneTemplate record
  - Associated MilestoneTask records

**`show($milestone)`**
- **What it does:** Shows template details
- **Parameters:** Template ID
- **Returns:** Detail view
- **Displays:**
  - Template information
  - All tasks in template
  - Which groups are using this template

**`edit($milestone)`**
- **What it does:** Shows form to edit template
- **Parameters:** Template ID
- **Returns:** Edit form
- **Can edit:** Name, description, tasks

**`update(Request $request, $milestone)`**
- **What it does:** Updates template
- **Parameters:** Template ID, updated data
- **Returns:** Redirect with success message
- **Note:** Doesn't affect groups already using this template

**`destroy($milestone)`**
- **What it does:** Deletes a template
- **Parameters:** Template ID
- **Returns:** Redirect with success message
- **Restriction:** Cannot delete if groups are using it

**`updateStatus(Request $request, $milestone)`**
- **What it does:** Activates or deactivates a template
- **Parameters:** Template ID, new status
- **Returns:** JSON success response
- **Statuses:**
  - `active`: Students can use it
  - `inactive`: Hidden from students

---

### 5. DefenseScheduleController (Coordinator namespace)
**File:** `app/Http/Controllers/Coordinator/DefenseScheduleController.php`

**Purpose:** Manages defense requests and schedules presentations.

#### Functions:

**`index()`**
- **What it does:** Shows all defense schedules
- **Returns:** Defense list view
- **Displays:**
  - Group names
  - Defense type (proposal, midterm, final)
  - Scheduled date and time
  - Room location
  - Panel members
  - Status

**`create()`**
- **What it does:** Shows form to manually create defense schedule
- **Returns:** Creation form
- **Form includes:**
  - Group selector
  - Defense type
  - Date and time
  - Room location
  - Panel member selection

**`store(Request $request)`**
- **What it does:** Creates a defense schedule
- **Parameters:** Group ID, defense type, date, time, room, panel members
- **Validation:**
  - All required fields
  - Date must be in future
  - Panel members must be faculty
- **Returns:** Redirect with success message
- **Notifications:** Notifies group and panel members

**`show($defense)`**
- **What it does:** Shows defense details
- **Parameters:** Defense schedule ID
- **Returns:** Detail view
- **Displays:**
  - All schedule information
  - Panel members and their roles
  - Group information
  - Related documents

**`edit($defense)`**
- **What it does:** Shows form to edit defense schedule
- **Parameters:** Defense schedule ID
- **Returns:** Edit form

**`update(Request $request, $defense)`**
- **What it does:** Updates defense schedule
- **Parameters:** Defense ID, updated information
- **Returns:** Redirect with success message
- **Notifications:** Notifies affected parties of changes

**`destroy($defense)`**
- **What it does:** Cancels/deletes a defense schedule
- **Parameters:** Defense schedule ID
- **Returns:** Redirect with success message
- **Notifications:** Notifies group and panel

**`getAvailableFaculty()`**
- **What it does:** Gets list of faculty available for panel
- **Returns:** JSON array of faculty members
- **Use:** AJAX for panel member selection
- **Filters:** Only active faculty

**`createSchedule($defenseRequest)`**
- **What it does:** Shows form to schedule a pending defense request
- **Parameters:** Defense request ID
- **Returns:** Schedule creation form
- **Pre-fills:** Group info, defense type from request

**`storeSchedule(Request $request, $defenseRequest)`**
- **What it does:** Approves defense request and creates schedule
- **Parameters:** Defense request ID, schedule details
- **Process:**
  1. Validates schedule information
  2. Creates defense schedule
  3. Updates request status to 'approved'
  4. Assigns panel members
- **Returns:** Redirect with success message
- **Notifications:** Notifies group and panel

**`editSchedule($defenseSchedule)`**
- **What it does:** Shows form to edit existing schedule
- **Parameters:** Defense schedule ID
- **Returns:** Edit form

**`updateSchedule(Request $request, $defenseSchedule)`**
- **What it does:** Updates defense schedule details
- **Parameters:** Schedule ID, updated details
- **Returns:** Redirect with success message

**`approve($defenseRequest)`**
- **What it does:** Approves a defense request without scheduling yet
- **Parameters:** Defense request ID
- **Returns:** JSON success response
- **Sets:** Request status to 'approved'

**`reject($defenseRequest)`**
- **What it does:** Rejects a defense request
- **Parameters:** Defense request ID
- **Validation:** Rejection reason required
- **Returns:** JSON success response
- **Notifications:** Notifies student with reason

---

### 6. CalendarController
**File:** `app/Http/Controllers/CalendarController.php`

**Purpose:** Displays calendar view with all important dates.

#### Functions:

**`coordinatorCalendar()`**
- **What it does:** Shows coordinator's calendar
- **Returns:** Calendar view
- **Events shown:**
  - Defense schedules
  - Milestone deadlines
  - Important academic dates
- **Format:** Interactive calendar with clickable events

---

## Key Terms

**Offering**: A specific class section you coordinate (subject + teacher + semester)

**Milestone Template**: A pre-made set of tasks that students can use for their project

**Defense Schedule**: Scheduled presentation where students explain their project to a panel

**Panel**: Group of faculty members who evaluate student defenses

**Academic Term**: School period (e.g., "2024-2025 First Semester")

**Active Term**: The current semester that's open for operations

**Bulk Operation**: Performing an action on multiple items at once

**Status Types**:
- `pending`: Waiting for review
- `approved`: Accepted
- `rejected`: Not accepted
- `active`: Currently in use
- `inactive`: Not available for use
- `scheduled`: Defense date set

**Proposal Review Flow**:
1. Student submits proposal
2. Adviser reviews first
3. Coordinator reviews after adviser approval
4. Both must approve for full approval

**Teacher-Coordinator**: A coordinator who is also teaching a class (can manage both roles)

**Validation**: Checking if data is correct before saving

**JSON Response**: Data sent back to JavaScript for dynamic updates

**AJAX**: Technology for updating page without full reload

**Collection**: Group of database records that can be filtered and sorted

**Pagination**: Splitting long lists into pages (e.g., 20 items per page)

**Filter**: Narrowing down lists based on criteria (status, date, etc.)

