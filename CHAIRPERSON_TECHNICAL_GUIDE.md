# Chairperson Role - Technical Documentation

## Overview
The Chairperson has the highest administrative authority in the system. They manage academic terms, faculty roles, student enrollments, offerings, and system-wide settings.

---

## Controllers

### 1. ChairpersonController
**File:** `app/Http/Controllers/ChairpersonController.php`

**Purpose:** Main entry point and notification management for chairperson.

#### Functions:

**`index()`**
- **What it does:** Redirects to chairperson dashboard
- **Returns:** Redirect to chairperson dashboard route

**`getActiveTerm()` (Private)**
- **What it does:** Gets the currently active academic term
- **Returns:** AcademicTerm model or null
- **Use:** Shared helper method for other functions

**`notifications()`**
- **What it does:** Shows all chairperson notifications
- **Returns:** Notification list view with pagination (20 per page)
- **Displays:** System alerts, updates, and important messages

**`markNotificationAsRead($notificationId)`**
- **What it does:** Marks one notification as read
- **Parameters:** Notification ID
- **Returns:** JSON success/error response
- **Security:** Verifies notification belongs to chairperson

**`markAllNotificationsAsRead()`**
- **What it does:** Marks all chairperson notifications as read at once
- **Returns:** JSON with count of notifications marked
- **How it works:** Updates all unread notifications for chairperson role

**`deleteNotification($notificationId)`**
- **What it does:** Permanently deletes a notification
- **Parameters:** Notification ID
- **Returns:** JSON success response

**`markMultipleAsRead(Request $request)`**
- **What it does:** Marks selected notifications as read (bulk operation)
- **Parameters:** Array of notification IDs
- **Returns:** JSON with count updated
- **Validation:** Checks all IDs exist and belong to chairperson role

**`deleteMultiple(Request $request)`**
- **What it does:** Deletes multiple notifications at once
- **Parameters:** Array of notification IDs
- **Returns:** JSON with count deleted

---

### 2. ChairpersonDashboardController
**File:** `app/Http/Controllers/ChairpersonDashboardController.php`

**Purpose:** Displays the main chairperson dashboard with system overview.

#### Functions:

**`index()`**
- **What it does:** Shows main dashboard with system-wide statistics
- **Returns:** Dashboard view
- **Data provided:**
  - Active academic term
  - Total offerings (class sections)
  - Total faculty members
  - Total students
  - Total groups
  - Recent students (5 newest)
  - Recent faculty (5 newest)
  - Recent groups (5 newest)
  - Recent notifications (5 newest)
  - Recent activities (10 items)

**`getRecentActivities()` (Private)**
- **What it does:** Gathers recent system-wide activities
- **Returns:** Collection of 10 most recent activities
- **Types tracked:**
  - New student registrations
  - New faculty registrations
  - New group creations
  - New offerings created
- **Sorted by:** Most recent first

---

### 3. ChairpersonOfferingController
**File:** `app/Http/Controllers/ChairpersonOfferingController.php`

**Purpose:** Manages class offerings (subject sections with assigned teachers and coordinators).

#### Functions:

**`index()`**
- **What it does:** Shows all offerings for active term
- **Returns:** Offering list view
- **Displays:**
  - Offer code (e.g., "CS199-1")
  - Subject title
  - Teacher name
  - Coordinator name
  - Enrolled students count
  - Academic term

**`create()`**
- **What it does:** Shows form to create new offering
- **Returns:** Offering creation form
- **Form includes:**
  - Subject code and title
  - Offer code (unique identifier)
  - Teacher selector
  - Coordinator selector
  - Academic term selector

**`store(Request $request)`**
- **What it does:** Creates a new offering
- **Parameters:** Subject code, title, offer code, teacher ID, coordinator ID, term ID
- **Validation:**
  - All fields required
  - Offer code must be unique
  - Teacher and coordinator must be faculty members
- **Returns:** Redirect to offerings list
- **Auto-assigns:** Coordinator and teacher roles to selected faculty

**`show($offering)`**
- **What it does:** Shows offering details
- **Parameters:** Offering ID
- **Returns:** Detail view
- **Displays:**
  - All offering information
  - Enrolled students list
  - Unenrolled students (available to add)
  - Groups in this offering

**`edit($offering)`**
- **What it does:** Shows form to edit offering
- **Parameters:** Offering ID
- **Returns:** Edit form
- **Can edit:** Subject info, teacher, coordinator

**`update(Request $request, $offering)`**
- **What it does:** Updates offering information
- **Parameters:** Offering ID, updated data
- **Returns:** Redirect with success message

**`destroy($offering)`**
- **What it does:** Deletes an offering
- **Parameters:** Offering ID
- **Returns:** Redirect with success message
- **Warning:** Removes all enrollments and group associations

**`removeStudent($offering, $student)`**
- **What it does:** Removes a student from an offering
- **Parameters:** Offering ID, Student ID
- **Returns:** Redirect with success message
- **Effect:** Student loses access to that class's groups/milestones

**`showUnenrolledStudents($offering)`**
- **What it does:** Shows students not yet enrolled in this offering
- **Parameters:** Offering ID
- **Returns:** View with available students
- **Use:** To add more students to the offering

**`enrollStudent(Request $request, $offering)`**
- **What it does:** Adds one student to an offering
- **Parameters:** Offering ID, Student ID
- **Validation:** Student must exist and not be enrolled already
- **Returns:** Redirect with success message

**`enrollMultipleStudents(Request $request, $offering)`**
- **What it does:** Adds multiple students to an offering at once
- **Parameters:** Offering ID, array of student IDs
- **Validation:** All student IDs must be valid
- **Returns:** Redirect with count of students enrolled

---

### 4. ChairpersonFacultyController
**File:** `app/Http/Controllers/ChairpersonFacultyController.php`

**Purpose:** Manages faculty accounts, roles, and imports.

#### Functions:

**`index()`**
- **What it does:** Shows all faculty members
- **Returns:** Faculty list view
- **Displays:**
  - Faculty ID
  - Name
  - Email
  - Assigned roles (adviser, coordinator, teacher, panelist)
  - Registration date

**`create()`**
- **What it does:** Shows faculty upload options
- **Returns:** View with manual and CSV upload options

**`createManual()`**
- **What it does:** Shows form to manually add one faculty member
- **Returns:** Manual creation form
- **Form includes:**
  - Faculty ID
  - First name, last name
  - Email
  - Department
  - Initial password

**`store(Request $request)`**
- **What it does:** Processes CSV file upload of multiple faculty
- **Parameters:** CSV file
- **Validation:**
  - File required (CSV format)
  - CSV must have correct columns
- **Returns:** Redirect with count of faculty imported
- **CSV Format:** faculty_id, first_name, last_name, email, department
- **Auto-creates:** FacultyAccount and UserAccount for each row

**`storeManual(Request $request)`**
- **What it does:** Saves one manually entered faculty member
- **Parameters:** Faculty ID, names, email, department, password
- **Validation:**
  - Faculty ID must be unique
  - Email must be valid and unique
  - Password minimum 8 characters
- **Returns:** Redirect with success message
- **Creates:** FacultyAccount and UserAccount

**`upload(Request $request)`**
- **What it does:** Alternative CSV upload handler
- **Parameters:** CSV file
- **Similar to:** `store()` method
- **Use:** Different route for CSV upload

**`edit($faculty)`**
- **What it does:** Shows form to edit faculty information
- **Parameters:** Faculty ID
- **Returns:** Edit form
- **Can edit:** Name, email, department

**`update(Request $request, $faculty)`**
- **What it does:** Updates faculty information
- **Parameters:** Faculty ID, updated data
- **Returns:** Redirect with success message

**`destroy($faculty)`**
- **What it does:** Deletes a faculty member
- **Parameters:** Faculty ID
- **Returns:** Redirect with success message
- **Warning:** Also deletes associated user account and role assignments

---

### 5. ChairpersonStudentController
**File:** `app/Http/Controllers/ChairpersonStudentController.php`

**Purpose:** Manages student accounts and imports.

#### Functions:

**`index()`**
- **What it does:** Shows all students
- **Returns:** Student list view
- **Displays:**
  - Student ID
  - Name
  - Email
  - Current group (if any)
  - Enrollment status
  - Registration date

**`export()`**
- **What it does:** Downloads all students as CSV file
- **Returns:** CSV file download
- **Columns:** student_id, first_name, last_name, email, group
- **Use:** Backup or external processing

**`edit($student)`**
- **What it does:** Shows form to edit student information
- **Parameters:** Student ID
- **Returns:** Edit form
- **Can edit:** Name, email, enrollment status

**`update(Request $request, $student)`**
- **What it does:** Updates student information
- **Parameters:** Student ID, updated data
- **Returns:** Redirect with success message

**`destroy($student)`**
- **What it does:** Deletes one student
- **Parameters:** Student ID
- **Returns:** Redirect with success message
- **Warning:** Also removes from group and deletes user account

**`bulkDelete(Request $request)`**
- **What it does:** Deletes multiple students at once
- **Parameters:** Array of student IDs
- **Validation:** All student IDs must exist
- **Returns:** Redirect with count of students deleted

**`upload(Request $request)`**
- **What it does:** Imports students from CSV file
- **Parameters:** CSV file
- **Validation:**
  - File required (CSV format)
  - Must have correct columns
- **Returns:** Redirect with count of students imported
- **CSV Format:** student_id, first_name, last_name, email
- **Auto-creates:** StudentAccount and UserAccount for each row
- **Default password:** student_id (students should change it)

---

### 6. AcademicTermController
**File:** `app/Http/Controllers/AcademicTermController.php`

**Purpose:** Manages academic terms (semesters/school years).

#### Functions:

**`index()`**
- **What it does:** Shows all academic terms
- **Returns:** Academic term list view
- **Displays:**
  - Academic year (e.g., "2024-2025")
  - Semester (First, Second, Summer)
  - Start and end dates
  - Status (active/inactive)

**`create()`**
- **What it does:** Shows form to create new academic term
- **Returns:** Creation form
- **Form includes:**
  - Academic year
  - Semester selector
  - Start date
  - End date

**`store(Request $request)`**
- **What it does:** Creates new academic term
- **Parameters:** Academic year, semester, start date, end date
- **Validation:**
  - All fields required
  - Start date must be before end date
  - No overlapping terms
- **Returns:** Redirect to terms list
- **Auto-sets:** Status to 'inactive' (must manually activate)

**`show($term)`**
- **What it does:** Shows academic term details
- **Parameters:** Term ID
- **Returns:** Detail view
- **Displays:**
  - Term information
  - Associated offerings
  - Enrolled students count
  - Active groups count

**`edit($term)`**
- **What it does:** Shows form to edit academic term
- **Parameters:** Term ID
- **Returns:** Edit form
- **Can edit:** Dates, semester info

**`update(Request $request, $term)`**
- **What it does:** Updates academic term
- **Parameters:** Term ID, updated data
- **Returns:** Redirect with success message

**`destroy($term)`**
- **What it does:** Deletes an academic term
- **Parameters:** Term ID
- **Returns:** Redirect with success message
- **Restriction:** Cannot delete active term
- **Warning:** Deletes associated offerings and enrollments

**`activate($term)`**
- **What it does:** Sets a term as the active term
- **Parameters:** Term ID
- **Process:**
  1. Deactivates all other terms
  2. Activates selected term
- **Returns:** Redirect with success message
- **Rule:** Only one term can be active at a time

**`deactivate($term)`**
- **What it does:** Deactivates an academic term
- **Parameters:** Term ID
- **Returns:** Redirect with success message
- **Effect:** Students/faculty can't access this term's data

**`changeSemester(Request $request)`**
- **What it does:** Switches to a different active term
- **Parameters:** Term ID to activate
- **Process:**
  1. Validates term exists
  2. Deactivates current term
  3. Activates new term
- **Returns:** Redirect to dashboard
- **Use:** Semester transitions

---

### 7. RoleController
**File:** `app/Http/Controllers/RoleController.php`

**Purpose:** Manages faculty role assignments.

#### Functions:

**`index()`**
- **What it does:** Shows all faculty with their assigned roles
- **Returns:** Role management view
- **Displays:**
  - Faculty names
  - Checkboxes for each role (adviser, coordinator, teacher, panelist)
  - Current role assignments

**`update(Request $request, $faculty)`**
- **What it does:** Updates roles for one faculty member
- **Parameters:** Faculty ID, array of role names
- **Validation:**
  - Roles array can be empty (removes all roles)
  - Each role must be valid (adviser, coordinator, teacher, panelist)
- **Process:**
  1. Removes all existing roles for this faculty
  2. Adds selected roles
  3. Creates UserRole records
- **Returns:** Redirect with success message
- **Notifications:** May notify faculty of role change

**`getRoleDistribution()`**
- **What it does:** Gets count of faculty in each role
- **Returns:** JSON with role counts
- **Data:**
  - adviser_count
  - coordinator_count
  - teacher_count
  - panelist_count
- **Use:** Dashboard statistics

---

## Key Terms

**Academic Term**: A semester or school period (e.g., "2024-2025 First Semester")

**Active Term**: The current semester that the system is operating in. Only one can be active at a time.

**Offering**: A class section combining:
- Subject (what is taught)
- Teacher (who teaches)
- Coordinator (who manages capstone projects)
- Students (who are enrolled)

**Offer Code**: Unique identifier for an offering (e.g., "CS199-1")

**Faculty Account**: Teacher/professor account in the system

**Student Account**: Student account in the system

**User Account**: General login account (links to either faculty or student)

**Roles**: Responsibilities assigned to faculty
- **Adviser**: Guides student groups on their projects
- **Coordinator**: Manages groups and proposals for their subject
- **Teacher**: Teaches the class
- **Panelist**: Evaluates student defenses

**CSV Upload**: Bulk import using a spreadsheet file (Excel exported as CSV)

**UserRole**: Database record linking a user to a role

**Activation/Deactivation**: Turning academic terms on/off for use

**Bulk Operation**: Performing an action on multiple items at once

**Validation**: Checking if data is correct before saving

**JSON Response**: Data sent back to JavaScript for dynamic updates

**Export**: Downloading data as a file (usually CSV)

**Import**: Uploading data from a file (usually CSV)

**Unique Constraint**: Database rule preventing duplicate values (e.g., no two students with same ID)

**Cascade Delete**: When deleting parent record, automatically delete related records

**Required Field**: Data that must be provided (cannot be empty)

**Optional Field**: Data that can be left empty

**Pagination**: Splitting long lists into pages (e.g., 20 items per page)

**Auto-assign**: Automatically giving a role or assignment without manual selection

**Default Value**: Value automatically set if not provided (e.g., status = 'inactive')

**Session**: Period a user is logged in to the system

---

## Important Notes

### Role Assignment Process
1. First, create faculty account
2. Then, assign roles using Role Management page
3. Faculty can have multiple roles
4. Roles determine what pages/actions faculty can access

### Academic Term Management
1. Create academic term (starts as inactive)
2. Set up offerings for that term
3. Import students and faculty
4. Activate the term when ready
5. Only one term can be active at a time
6. Deactivate term when semester ends

### Offering Creation Workflow
1. Ensure faculty accounts exist
2. Assign coordinator and teacher roles to faculty
3. Create offering with subject details
4. Assign teacher and coordinator
5. Enroll students (manual or CSV)
6. Groups are created within offerings

### CSV Import Requirements
**Faculty CSV must have columns:**
- faculty_id
- first_name
- last_name
- email
- department

**Student CSV must have columns:**
- student_id
- first_name
- last_name
- email

**Important:** First row should be headers, data starts from row 2

### Security Considerations
- Only chairperson can activate/deactivate terms
- Only chairperson can assign roles
- Deleting faculty removes all their assignments
- Deleting students removes them from groups
- Active term controls what data is accessible

### Best Practices
1. Create next semester's term before current one ends
2. Keep old terms inactive for record-keeping
3. Don't delete terms unless absolutely necessary
4. Always verify CSV format before importing
5. Regularly backup student and faculty lists using export

