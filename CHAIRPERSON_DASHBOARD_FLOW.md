# Chairperson Dashboard - Complete Flow Guide

## Overview
The Chairperson Dashboard is the central control panel for managing all academic operations, faculty, students, and capstone projects. It provides comprehensive oversight of the entire capstone project lifecycle.

---

## 1. DASHBOARD HOME
**Route:** `/chairperson-dashboard`
**Controller:** `ChairpersonDashboardController@index`

### Main Features:
- **Statistics Cards:**
  - Active Projects Count
  - Faculty Members Count  
  - Pending Defenses Count
  - Total Offerings Count

- **Upcoming Defenses:** Next 5 scheduled defenses
- **Recent Notifications:** Latest 5 notifications for chairperson role
- **Quick Actions:** Direct links to main management areas

---

## 2. ACADEMIC TERMS MANAGEMENT
**Route:** `/chairperson/academic-terms`
**Controller:** `AcademicTermController`

### Flow:
```
Academic Terms Management
├── View All Terms (Active/Archived)
├── Create New Term
│   ├── Set School Year (e.g., "2024-2025")
│   ├── Set Semester (e.g., "First Semester")
│   └── Mark as Active/Inactive
├── Edit Existing Term
├── Toggle Active Status (Only one can be active)
├── Archive/Unarchive Terms
└── Delete Terms (if no associated data)
```

### Key Features:
- **Multi-semester Support:** Same faculty/students can exist across semesters
- **Active Term Control:** Only one term can be active at a time
- **Data Isolation:** Each semester maintains separate data

---

## 3. OFFERINGS MANAGEMENT
**Route:** `/chairperson/offerings`
**Controller:** `ChairPersonController`

### Complete Flow:
```
Offerings Management
├── View Offerings
│   ├── Filter by Active Term
│   ├── Show All Terms Option
│   ├── Display: Subject, Teacher, Students Count, Term
│   └── Actions: View, Edit, Delete, Manage Students
│
├── Create New Offering
│   ├── Enter Subject Title (free text)
│   ├── Enter Subject Code (free text)
│   ├── Generate/Enter Offer Code (e.g., 11000, 11001)
│   ├── Select Teacher (filtered by active semester)
│   ├── Select Academic Term
│   └── Auto-assign Coordinator Role to Teacher
│
├── Edit Offering
│   ├── Modify Subject Details
│   ├── Change Teacher Assignment
│   ├── Update Academic Term
│   └── Update Coordinator Role if needed
│
├── View Offering Details
│   ├── Show Subject Information
│   ├── Display Assigned Teacher
│   ├── List Enrolled Students
│   ├── Add Students to Offering
│   └── Remove Students from Offering
│
├── Student Enrollment Management
│   ├── Enroll Individual Students
│   ├── Bulk Enroll Students (CSV Import)
│   ├── Remove Students
│   └── View Unenrolled Students
│
└── Delete Offering
    ├── Confirm Deletion
    ├── Remove All Student Enrollments
    └── Update Teacher Roles if needed
```

### Key Features:
- **Flexible Subject Creation:** Free text for subject titles and codes
- **Automatic Role Assignment:** Teachers handling subjects become coordinators
- **Multi-semester Support:** Same offer codes can exist across semesters
- **Bulk Operations:** CSV import for student enrollment

---

## 4. FACULTY/TEACHERS MANAGEMENT
**Route:** `/chairperson/teachers`
**Controller:** `ChairPersonController`

### Complete Flow:
```
Faculty Management
├── View Faculty List
│   ├── Filter by Active Semester
│   ├── Sort by Name, Role, Department
│   ├── Display: Name, Email, Role, Department, Faculty ID
│   └── Actions: Edit, Delete, View Details
│
├── Create Faculty (Two Methods)
│   │
│   ├── Method 1: Manual Entry
│   │   ├── Enter Name, Email, Faculty ID
│   │   ├── Select Department
│   │   ├── Set Role (Teacher by default)
│   │   ├── Assign to Active Semester
│   │   └── Create User Account
│   │
│   └── Method 2: CSV Import
│       ├── Upload Excel/CSV File
│       ├── Validate Data Format
│       ├── Check for Duplicates in Same Semester
│       ├── Bulk Create Faculty
│       └── Generate User Accounts
│
├── Edit Faculty
│   ├── Update Personal Information
│   ├── Change Role (Teacher, Adviser, Panelist, Coordinator, Chairperson)
│   ├── Modify Department
│   ├── Update Email (check uniqueness in same semester)
│   └── Reset Password (optional)
│
├── Role Management
│   ├── Automatic Role Assignment:
│   │   ├── Teacher → Coordinator (when assigned to offering)
│   │   └── Coordinator → Teacher (when removed from all offerings)
│   │
│   └── Manual Role Assignment:
│       ├── Assign Multiple Roles
│       ├── Primary Role Selection
│       └── Role Consistency Checks
│
└── Delete Faculty
    ├── Check for Associated Data
    ├── Remove from Offerings
    ├── Delete User Account
    └── Clean Up Related Records
```

### Key Features:
- **Multi-semester Support:** Same faculty can exist across semesters
- **Automatic Role Management:** Smart role assignment based on offerings
- **Bulk Import:** CSV/Excel support for mass faculty creation
- **Flexible Roles:** Support for multiple role assignments

---

## 5. STUDENTS MANAGEMENT
**Route:** `/chairperson/students`
**Controller:** `ChairPersonController`

### Complete Flow:
```
Students Management
├── View Students List
│   ├── Filter by Active Semester
│   ├── Search by Name, ID, Email, Course
│   ├── Sort by Various Fields
│   ├── Display: Student ID, Name, Email, Course, Enrollments
│   └── Actions: Edit, Delete, Export
│
├── Student Import (CSV)
│   ├── Upload Student List File
│   ├── Validate Data Format
│   ├── Check for Duplicates in Same Semester
│   ├── Bulk Create Students
│   ├── Generate Student Accounts
│   └── Auto-enroll in Offerings (if specified)
│
├── Edit Student
│   ├── Update Personal Information
│   ├── Modify Course Information
│   ├── Update Email (check uniqueness in same semester)
│   └── Reset Password (optional)
│
├── Student Enrollment
│   ├── Enroll in Offerings
│   ├── Remove from Offerings
│   ├── View Enrollment History
│   └── Transfer Between Offerings
│
├── Bulk Operations
│   ├── Bulk Delete Students
│   ├── Bulk Export to CSV
│   └── Bulk Update Information
│
└── Delete Student
    ├── Check for Associated Groups
    ├── Remove from All Offerings
    ├── Delete Student Account
    └── Clean Up Related Records
```

### Key Features:
- **Multi-semester Support:** Same students can exist across semesters
- **Bulk Operations:** CSV import/export support
- **Flexible Enrollment:** Easy enrollment management
- **Data Export:** Comprehensive student data export

---

## 6. ROLES MANAGEMENT
**Route:** `/chairperson/roles`
**Controller:** `RoleController`

### Complete Flow:
```
Role Management System
├── View Role Assignments
│   ├── Filter by Active Semester
│   ├── Display All Faculty with Current Roles
│   ├── Show Multiple Role Assignments
│   └── Real-time Role Updates
│
├── Assign/Update Roles
│   ├── Select Faculty Member
│   ├── Check Multiple Roles:
│   │   ├── Chairperson (Admin)
│   │   ├── Coordinator (Subject Handler)
│   │   ├── Teacher (General Teaching)
│   │   ├── Adviser (Project Guidance)
│   │   └── Panelist (Defense Evaluation)
│   │
│   ├── Real-time Role Display
│   ├── Save Role Changes
│   └── Update User Permissions
│
├── Role Consistency Management
│   ├── Automatic Role Updates:
│   │   ├── Teacher → Coordinator (when assigned to offering)
│   │   └── Coordinator → Teacher (when removed from offerings)
│   │
│   └── Manual Role Overrides
│       ├── Force Role Changes
│       ├── Bulk Role Updates
│       └── Role History Tracking
│
└── Role Validation
    ├── Check Role Conflicts
    ├── Validate Permission Levels
    └── Ensure Data Consistency
```

### Key Features:
- **Multiple Role Support:** Users can have multiple roles simultaneously
- **Automatic Role Assignment:** Smart role management based on responsibilities
- **Real-time Updates:** Live role display and updates
- **Role Consistency:** Automatic validation and correction

---

## 7. CALENDAR MANAGEMENT
**Route:** `/chairperson/calendar`
**Controller:** `CalendarController@chairpersonCalendar`

### Features:
- **Upcoming Events:** Defense schedules, deadlines
- **Academic Calendar:** Term dates, holidays
- **Defense Scheduling:** View scheduled defenses
- **Event Management:** Add/edit academic events

---

## 8. DATA IMPORT/EXPORT SYSTEM

### Import Features:
```
Data Import System
├── Student Import
│   ├── CSV/Excel File Upload
│   ├── Data Validation
│   ├── Duplicate Checking (by semester)
│   ├── Bulk Student Creation
│   └── Auto-enrollment in Offerings
│
└── Faculty Import
    ├── CSV/Excel File Upload
    ├── Data Validation
    ├── Duplicate Checking (by semester)
    ├── Bulk Faculty Creation
    └── User Account Generation
```

### Export Features:
```
Data Export System
├── Student Data Export
│   ├── Filter by Semester/Course
│   ├── Include Enrollment Information
│   ├── Export to CSV Format
│   └── Downloadable Reports
│
└── Faculty Data Export
    ├── Filter by Role/Department
    ├── Include Role Information
    ├── Export to CSV Format
    └── Downloadable Reports
```

---

## 9. NOTIFICATION SYSTEM

### Features:
- **Role-based Notifications:** Specific to chairperson role
- **Real-time Updates:** Latest 5 notifications on dashboard
- **Event Notifications:** Defense schedules, role changes
- **System Alerts:** Important updates and reminders

---

## 10. MULTI-SEMESTER ARCHITECTURE

### Key Design Principles:
```
Multi-semester Support
├── Data Isolation
│   ├── Same IDs across semesters (faculty_id, student_id)
│   ├── Semester-specific data filtering
│   ├── Composite unique constraints
│   └── Cross-semester data relationships
│
├── Role Management
│   ├── Semester-specific role assignments
│   ├── Automatic role updates
│   ├── Role consistency across semesters
│   └── Historical role tracking
│
├── Academic Terms
│   ├── Multiple terms per academic year
│   ├── Active term management
│   ├── Term-based data filtering
│   └── Archive/restore functionality
│
└── Data Relationships
    ├── Faculty → Offerings (semester-specific)
    ├── Students → Enrollments (semester-specific)
    ├── Groups → Projects (semester-specific)
    └── Defenses → Schedules (semester-specific)
```

---

## 11. COMPLETE WORKFLOW EXAMPLES

### Example 1: Setting Up a New Semester
```
1. Create New Academic Term
   ├── Set school year and semester
   ├── Mark as active
   └── Archive previous term

2. Import Faculty for New Semester
   ├── Upload faculty CSV file
   ├── Validate data
   ├── Create faculty users
   └── Assign default roles

3. Create Offerings
   ├── Add subject offerings
   ├── Assign teachers (auto-coordinator role)
   ├── Set offer codes
   └── Link to academic term

4. Import Students
   ├── Upload student CSV file
   ├── Validate data
   ├── Create student users
   └── Auto-enroll in offerings

5. Manage Roles
   ├── Assign additional roles
   ├── Set up advisers and panelists
   └── Verify role assignments
```

### Example 2: Managing a Capstone Project
```
1. Create Offering
   ├── Subject: "Capstone Project I"
   ├── Code: "CS-CAP-401"
   ├── Assign teacher → becomes coordinator
   └── Set academic term

2. Enroll Students
   ├── Individual enrollment
   ├── Bulk CSV import
   └── Verify enrollments

3. Student Group Formation
   ├── Students form groups
   ├── Assign adviser
   └── Set project milestones

4. Project Management
   ├── Monitor progress
   ├── Schedule defenses
   ├── Assign panelists
   └── Track completions

5. Defense Process
   ├── Schedule defense
   ├── Assign defense panel
   ├── Conduct defense
   └── Record results
```

---

## 12. SECURITY & PERMISSIONS

### Access Control:
- **Chairperson Role:** Full system access
- **Role-based Permissions:** Different access levels
- **Semester Isolation:** Data access by semester
- **Audit Trail:** Track all changes and actions

### Data Validation:
- **Input Validation:** Comprehensive form validation
- **Duplicate Prevention:** Semester-specific uniqueness
- **Data Integrity:** Foreign key constraints
- **Error Handling:** User-friendly error messages

---

## 13. TECHNICAL ARCHITECTURE

### Database Design:
```
Multi-semester Tables
├── users (faculty_id, semester)
├── students (student_id, semester)  
├── offerings (offer_code, academic_term_id)
├── academic_terms (school_year, semester)
├── groups (offering_id, academic_term_id)
└── defense_schedules (group_id, academic_term_id)
```

### Key Relationships:
- **Users → Offerings:** Faculty teaching subjects
- **Students → Offerings:** Student enrollments
- **Groups → Offerings:** Project groups
- **Defenses → Groups:** Defense scheduling
- **All → Academic Terms:** Semester isolation

---

---

## 14. DETAILED FUNCTION FLOWS

### A. ChairpersonDashboardController Functions

#### `index()`
**Purpose:** Display main dashboard with statistics and overview
**Flow:**
```
1. Get Active Academic Term
   ├── Query: AcademicTerm::where('is_active', true)->first()
   └── Set as $activeTerm

2. Get Upcoming Defenses (Next 30 days)
   ├── Query: DefenseSchedule::with(['group', 'defensePanels.faculty'])
   ├── Filter: start_at >= today AND start_at <= 30 days
   ├── Filter: status = 'scheduled'
   ├── Order: by start_at ASC
   ├── Limit: 5 records
   └── Set as $upcomingDefenses

3. Get Chairperson Notifications
   ├── Query: Notification::where('role', 'chairperson')
   ├── Order: latest first
   ├── Limit: 5 records
   └── Set as $notifications

4. Calculate Statistics
   ├── Active Projects Count
   │   ├── Query: Group::whereHas('adviser')
   │   ├── Filter: academic_term_id = activeTerm->id
   │   └── Count groups
   │
   ├── Faculty Count
   │   ├── Query: User::whereIn('role', ['adviser', 'panelist', 'teacher', 'coordinator', 'chairperson'])
   │   ├── Filter: semester = activeTerm->semester
   │   └── Count users
   │
   ├── Pending Reviews Count
   │   ├── Query: DefenseSchedule::where('status', 'scheduled')
   │   └── Count defenses
   │
   ├── Offerings Count
   │   ├── Query: Offering::where('academic_term_id', activeTerm->id)
   │   └── Count offerings
   │
   ├── Total Defenses Count
   │   ├── Query: DefenseSchedule::all()
   │   └── Count all defenses
   │
   └── Completed Defenses Count
       ├── Query: DefenseSchedule::where('status', 'completed')
       └── Count completed defenses

5. Return Dashboard View
   ├── Pass: $activeTerm, $upcomingDefenses, $notifications, $stats
   └── Render: chairperson.dashboard view
```

---

### B. ChairPersonController Functions

#### `getActiveTerm()`
**Purpose:** Helper method to get currently active academic term
**Flow:**
```
1. Query Academic Terms
   ├── Query: AcademicTerm::where('is_active', true)
   ├── Get: first() result
   └── Return: AcademicTerm object or null
```

#### `indexOfferings(Request $request)`
**Purpose:** Display all offerings with filtering options
**Flow:**
```
1. Get Active Term
   ├── Call: $this->getActiveTerm()
   └── Set as $activeTerm

2. Check Show All Terms Flag
   ├── Get: $request->get('show_all', false)
   └── Set as $showAllTerms

3. Build Offerings Query
   ├── Query: Offering::with(['teacher', 'academicTerm', 'students'])
   ├── Conditional Filter: If activeTerm exists AND not showAllTerms
   │   ├── Filter: where('academic_term_id', activeTerm->id)
   │   └── Apply filter
   ├── Order: by created_at DESC
   └── Execute: get()

4. Return View
   ├── Pass: $offerings, $activeTerm, $showAllTerms
   └── Render: chairperson.offerings.index
```

#### `createOffering()`
**Purpose:** Show form to create new offering
**Flow:**
```
1. Get Active Term
   ├── Call: $this->getActiveTerm()
   └── Set as $activeTerm

2. Get Available Teachers
   ├── Query: User::whereIn('role', ['teacher', 'adviser', 'panelist', 'coordinator'])
   ├── Conditional Filter: If activeTerm exists
   │   ├── Filter: where('semester', activeTerm->semester)
   │   └── Apply filter
   ├── Order: by name ASC
   └── Execute: get()

3. Get Academic Terms
   ├── Query: AcademicTerm::notArchived()
   └── Execute: get()

4. Return View
   ├── Pass: $teachers, $academicTerms, $activeTerm
   └── Render: chairperson.offerings.create
```

#### `storeOffering(Request $request)`
**Purpose:** Process and save new offering
**Flow:**
```
1. Validate Request Data
   ├── offer_code: required|string|unique:offerings,offer_code
   ├── subject_title: required|string|max:255
   ├── subject_code: required|string|max:255
   ├── faculty_id: required|exists:users,faculty_id
   └── academic_term_id: required|exists:academic_terms,id

2. Prepare Data
   ├── Extract: only required fields from request
   ├── Check: if academic_term_id is empty
   │   ├── Get: active term
   │   └── Set: academic_term_id from active term
   └── Set as $data

3. Create Offering
   ├── Call: Offering::create($data)
   └── Set as $offering

4. Auto-assign Coordinator Role
   ├── Find: User by faculty_id
   ├── Check: if teacher exists AND doesn't have coordinator role
   │   ├── Update: teacher->role = 'coordinator'
   │   ├── Save: teacher
   │   └── Log: role assignment
   └── Set success message

5. Redirect with Success
   ├── Route: chairperson.offerings.index
   └── Message: 'Offering added successfully. Teacher automatically assigned as coordinator.'
```

#### `editOffering($id)`
**Purpose:** Show form to edit existing offering
**Flow:**
```
1. Get Offering
   ├── Query: Offering::with(['teacher', 'academicTerm', 'students'])
   ├── Filter: where('id', $id)
   └── Get: firstOrFail()

2. Get Available Teachers
   ├── Query: User::whereIn('role', ['teacher', 'adviser', 'panelist', 'coordinator'])
   ├── Conditional Filter: If offering has academicTerm
   │   ├── Filter: where('semester', offering->academicTerm->semester)
   │   └── Apply filter
   ├── Order: by name ASC
   └── Execute: get()

3. Get Academic Terms
   ├── Query: AcademicTerm::notArchived()
   └── Execute: get()

4. Return View
   ├── Pass: $offering, $teachers, $academicTerms
   └── Render: chairperson.offerings.edit
```

#### `updateOffering(Request $request, $id)`
**Purpose:** Process and save offering updates
**Flow:**
```
1. Validate Request Data
   ├── offer_code: required|string|unique:offerings,offer_code,{$id}
   ├── subject_title: required|string|max:255
   ├── subject_code: required|string|max:255
   ├── faculty_id: required|exists:users,faculty_id
   └── academic_term_id: required|exists:academic_terms,id

2. Get Existing Offering
   ├── Query: Offering::where('id', $id)
   └── Get: firstOrFail()

3. Store Old Teacher ID
   ├── Get: offering->faculty_id
   └── Set as $oldTeacherId

4. Update Offering
   ├── Update: offering with new data
   └── Save changes

5. Handle Teacher Role Changes
   ├── Get: new teacher by faculty_id
   ├── Check: if new teacher exists AND doesn't have coordinator role
   │   ├── Update: new teacher->role = 'coordinator'
   │   ├── Save: new teacher
   │   └── Log: role assignment
   │
   ├── Check: if old teacher exists AND has no other offerings
   │   ├── Update: old teacher->role = 'teacher'
   │   ├── Save: old teacher
   │   └── Log: role change
   │
   └── Set success message

6. Redirect with Success
   ├── Route: chairperson.offerings.index
   └── Message: 'Offering updated successfully.'
```

#### `deleteOffering($id)`
**Purpose:** Delete an offering
**Flow:**
```
1. Get Offering
   ├── Query: Offering::with('teacher')
   ├── Filter: where('id', $id)
   └── Get: firstOrFail()

2. Store Offering Details
   ├── Get: offering->teacher
   ├── Get: offering->subject_code
   └── Store for message

3. Delete Offering
   ├── Call: offering->delete()
   └── Remove from database

4. Redirect with Success
   ├── Route: chairperson.offerings.index
   └── Message: "Offering '{subject_code}' deleted successfully."
```

#### `showOffering($id)`
**Purpose:** Display offering details
**Flow:**
```
1. Get Offering
   ├── Query: Offering::where('id', $id)
   └── Get: firstOrFail()

2. Return View
   ├── Pass: $offering
   └── Render: chairperson.offerings.show
```

#### `indexStudents(Request $request)`
**Purpose:** Display students with filtering and pagination
**Flow:**
```
1. Get Active Term
   ├── Call: $this->getActiveTerm()
   └── Set as $activeTerm

2. Build Students Query
   ├── Query: Student::query()
   ├── Conditional Filter: If activeTerm exists
   │   ├── Filter: where('semester', activeTerm->semester)
   │   └── Apply filter

3. Apply Search Filter
   ├── Check: if search parameter exists
   │   ├── Search: name, student_id, email, course
   │   ├── Use: LIKE with wildcards
   │   └── Apply search filter

4. Apply Course Filter
   ├── Check: if course parameter exists
   │   ├── Filter: where('course', course)
   │   └── Apply course filter

5. Apply Sorting
   ├── Get: sort_by parameter (default: 'student_id')
   ├── Get: sort_order parameter (default: 'asc')
   ├── Validate: allowed sort fields
   ├── Order: by sort_by, sort_order
   └── Apply sorting

6. Get Additional Data
   ├── Query: distinct courses for filter dropdown
   └── Set as $courses

7. Paginate Results
   ├── Load: with(['offerings', 'groups'])
   ├── Paginate: 20 records per page
   ├── Append: request parameters
   └── Set as $students

8. Return View
   ├── Pass: $students, $courses, $activeTerm, $sortBy, $sortOrder
   └── Render: chairperson.students.index
```

#### `uploadStudentList(Request $request)`
**Purpose:** Import students from CSV/Excel file
**Flow:**
```
1. Validate File
   ├── file: required|file|mimes:xlsx,xls,csv|max:10240
   ├── Check: file size not 0
   └── Validate file format

2. Process File
   ├── Get: file details (name, size)
   ├── Log: import start
   ├── Get: offering_id parameter (optional)
   └── Create: StudentsImport instance

3. Execute Import
   ├── Call: Excel::import($import, $file)
   ├── Handle: import process
   └── Log: import completion

4. Handle Offering Enrollment (if offering_id provided)
   ├── Get: offering by ID
   ├── Find: recently created students (within 2 minutes)
   ├── Enroll: each student in offering
   └── Log: enrollment details

5. Prepare Success Message
   ├── Base: "Students imported successfully from '{filename}'!"
   ├── Add: enrollment message if applicable
   └── Set message

6. Redirect with Result
   ├── If offering_id: redirect to offering show page
   ├── Else: redirect back
   └── Message: success message

7. Handle Errors
   ├── ValidationException: show validation errors
   ├── General Exception: show generic error message
   ├── Specific Error Types: custom error messages
   └── Log: error details
```

#### `enrollStudent(Request $request, $offeringId)`
**Purpose:** Enroll single student in offering
**Flow:**
```
1. Validate Request
   ├── student_id: required|exists:students,student_id
   └── Validate student exists

2. Get Entities
   ├── Get: offering by ID
   ├── Get: student by student_id
   └── Both: firstOrFail()

3. Enroll Student
   ├── Call: student->enrollInOffering($offering)
   └── Create enrollment record

4. Redirect with Success
   ├── Route: chairperson.offerings.show
   ├── Pass: $offeringId
   └── Message: "Student '{name}' has been enrolled in {subject_code}."
```

#### `enrollMultipleStudents(Request $request, $offeringId)`
**Purpose:** Enroll multiple students in offering
**Flow:**
```
1. Validate Input
   ├── Get: student_ids from request
   ├── Parse: JSON if string
   ├── Check: array not empty
   └── Validate input format

2. Get Offering
   ├── Query: Offering::where('id', $offeringId)
   └── Get: firstOrFail()

3. Process Each Student
   ├── Initialize: counters and arrays
   ├── Loop: through student_ids
   │   ├── Get: student by student_id
   │   ├── Enroll: student in offering
   │   ├── Track: success/failure
   │   └── Log: enrollment details
   │
   ├── Collect: enrolled names
   └── Collect: error messages

4. Prepare Response
   ├── Count: successful enrollments
   ├── Build: success message with names
   ├── Add: error details if any
   └── Set message

5. Redirect with Result
   ├── Route: chairperson.offerings.show
   ├── Pass: $offeringId
   ├── If errors: warning message
   ├── Else: success message
   └── Return response

6. Handle Exceptions
   ├── Log: error details
   ├── Set: error message
   └── Redirect: with error
```

#### `bulkDeleteStudents(Request $request)`
**Purpose:** Delete multiple students at once
**Flow:**
```
1. Validate Input
   ├── Get: student_ids from request (JSON)
   ├── Parse: JSON to array
   ├── Check: array not empty
   └── Validate input format

2. Process Each Student
   ├── Initialize: counters and arrays
   ├── Loop: through student_ids
   │   ├── Get: student by student_id
   │   ├── Store: student name
   │   ├── Delete: student record
   │   ├── Track: success/failure
   │   └── Log: deletion details
   │
   ├── Collect: deleted names
   └── Collect: error messages

3. Prepare Response
   ├── Count: successful deletions
   ├── Build: success message with names
   ├── Add: error details if any
   └── Set message

4. Redirect with Result
   ├── Route: chairperson.students.index
   ├── If errors: warning message
   ├── Else: success message
   └── Return response

5. Handle Exceptions
   ├── Log: error details
   ├── Set: error message
   └── Redirect: with error
```

#### `storeFacultyManual(Request $request)`
**Purpose:** Create faculty member manually
**Flow:**
```
1. Get Active Term
   ├── Call: $this->getActiveTerm()
   └── Set as $activeTerm

2. Validate Request
   ├── name: required|string|max:255
   ├── email: required|email
   ├── faculty_id: required|string|max:20
   └── department: nullable|string|max:255

3. Check Duplicates in Same Semester
   ├── If activeTerm exists
   │   ├── Check: email uniqueness in semester
   │   ├── Check: faculty_id uniqueness in semester
   │   └── Return errors if duplicates found
   │
   └── Validate semester-specific uniqueness

4. Create User
   ├── Call: User::create()
   ├── Fields: name, email, department, role='teacher', faculty_id, semester
   └── Set as $user

5. Create User Account
   ├── Call: UserAccount::create()
   ├── Fields: faculty_id, user_id, email, password='password123'
   └── Create account record

6. Redirect with Success
   ├── Route: chairperson.teachers.index
   └── Message: 'Faculty member added successfully!'
```

#### `storeFaculty(Request $request)`
**Purpose:** Import faculty from CSV/Excel file
**Flow:**
```
1. Validate File
   ├── file: required|file|mimes:xlsx,xls,csv|max:10240
   ├── Check: file size not 0
   └── Validate file format

2. Process File
   ├── Get: file details (name, size)
   ├── Log: import start
   ├── Get: active term for semester
   └── Create: FacultyImport instance with semester

3. Execute Import
   ├── Call: Excel::import($import, $file)
   ├── Handle: import process
   └── Log: import completion

4. Prepare Success Message
   ├── Base: "Faculty imported successfully from '{filename}'!"
   └── Set message

5. Redirect with Success
   ├── Route: chairperson.teachers.index
   └── Message: success message

6. Handle Errors
   ├── ValidationException: show validation errors
   ├── General Exception: show generic error message
   ├── Specific Error Types: custom error messages
   └── Log: error details
```

---

### C. RoleController Functions

#### `index(Request $request)`
**Purpose:** Display role management interface
**Flow:**
```
1. Get Sorting Parameters
   ├── Get: sort parameter (default: 'faculty_id')
   ├── Get: direction parameter (default: 'asc')
   └── Set sorting options

2. Get Active Term
   ├── Query: AcademicTerm::where('is_active', true)
   └── Get: first()

3. Define Role Information
   ├── Create: roles array with descriptions
   ├── Roles: chairperson, coordinator, teacher, adviser, panelist, student
   ├── Each: name, description, permissions
   └── Set as $roles

4. Get Users for Role Assignment
   ├── Query: User::with('roles')
   ├── Select: specific fields only
   ├── Filter: by active semester
   ├── Order: by sort parameters
   ├── Paginate: 20 records
   └── Set as $allUsers

5. Count Users by Role
   ├── Loop: through each role (except student)
   │   ├── Query: User::whereIn('role', [...])
   │   ├── Filter: by active semester
   │   ├── Filter: by specific role
   │   ├── Count: users
   │   └── Set: user_count for role
   │
   └── Update: roles array with counts

6. Return View
   ├── Pass: $roles, $allUsers, $activeTerm, $sortBy, $sortDirection
   └── Render: chairperson.roles.index
```

#### `update(Request $request, $faculty_id)`
**Purpose:** Update user roles
**Flow:**
```
1. Validate Request
   ├── roles: required|array
   ├── roles.*: in:chairperson,coordinator,teacher,adviser,panelist
   └── Validate role assignments

2. Get Active Term
   ├── Query: AcademicTerm::where('is_active', true)
   └── Get: first()

3. Find User
   ├── Query: User::where('faculty_id', $faculty_id)
   ├── Filter: by active semester
   └── Get: firstOrFail()

4. Update Roles
   ├── Call: user->assignRoles($request->roles)
   ├── Process: role assignments
   └── Update: user roles

5. Prepare Response
   ├── If AJAX request
   │   ├── Return: JSON response
   │   ├── Include: success status, message, user_roles
   │   └── Send: JSON response
   │
   ├── Else: regular request
   │   ├── Redirect: back
   │   └── Message: 'User roles updated successfully.'
   │
   └── Handle response format

6. Handle Exceptions
   ├── Log: error details
   ├── If AJAX: return JSON error
   ├── Else: redirect with error
   └── Handle: exception cases
```

---

### D. AcademicTermController Functions

#### `index()`
**Purpose:** Display all academic terms
**Flow:**
```
1. Get Academic Terms
   ├── Query: AcademicTerm::orderBy('school_year', 'desc')
   ├── Order: by semester
   └── Get: all terms

2. Return View
   ├── Pass: $academicTerms
   └── Render: chairperson.academic-terms.index
```

#### `store(Request $request)`
**Purpose:** Create new academic term
**Flow:**
```
1. Validate Request
   ├── school_year: required|string|max:255
   ├── semester: required|in:First Semester,Second Semester,Summer
   └── Validate input

2. Handle Active Term
   ├── If: request has is_active AND is_active is true
   │   ├── Update: all other terms to inactive
   │   └── Ensure: only one active term
   │
   └── Process: active term logic

3. Create Academic Term
   ├── Call: AcademicTerm::create()
   ├── Fields: school_year, semester (combined), is_active, is_archived=false
   └── Create: new term

4. Redirect with Success
   ├── Route: chairperson.academic-terms.index
   └── Message: 'Academic term created successfully.'
```

#### `update(Request $request, AcademicTerm $academicTerm)`
**Purpose:** Update existing academic term
**Flow:**
```
1. Validate Request
   ├── school_year: required|string|max:255
   ├── semester: required|in:First Semester,Second Semester,Summer
   └── Validate input

2. Handle Active Term
   ├── If: request has is_active AND is_active is true
   │   ├── Update: all other terms (except current) to inactive
   │   └── Ensure: only one active term
   │
   └── Process: active term logic

3. Update Academic Term
   ├── Call: academicTerm->update()
   ├── Fields: school_year, semester (combined), is_active, is_archived
   └── Update: existing term

4. Redirect with Success
   ├── Route: chairperson.academic-terms.index
   └── Message: 'Academic term updated successfully.'
```

#### `toggleActive(AcademicTerm $academicTerm)`
**Purpose:** Toggle active status of academic term
**Flow:**
```
1. Check Archive Status
   ├── If: term is archived
   │   ├── Return: error message
   │   └── Cannot activate archived term
   │
   └── Validate: term status

2. Process Toggle
   ├── Start: database transaction
   │   ├── Update: all other terms to inactive
   │   ├── Toggle: current term active status
   │   └── Commit: transaction
   │
   └── Ensure: atomic operation

3. Prepare Response
   ├── Get: fresh term status
   ├── Set: status message (activated/deactivated)
   └── Prepare: response

4. Redirect with Result
   ├── Route: chairperson.academic-terms.index
   └── Message: "Academic term {status} successfully."
```

#### `toggleArchived(AcademicTerm $academicTerm)`
**Purpose:** Toggle archive status of academic term
**Flow:**
```
1. Check Active Status
   ├── If: term is active
   │   ├── Return: error message
   │   └── Cannot archive active term
   │
   └── Validate: term status

2. Toggle Archive Status
   ├── Call: academicTerm->update()
   ├── Field: is_archived (toggle)
   └── Update: term status

3. Prepare Response
   ├── Get: fresh term status
   ├── Set: status message (archived/unarchived)
   └── Prepare: response

4. Redirect with Result
   ├── Route: chairperson.academic-terms.index
   └── Message: "Academic term {status} successfully."
```

---

## 15. COMPLETE FUNCTION INTERACTION FLOW

### Typical User Journey - Creating and Managing a Capstone Project:

```
1. Chairperson Dashboard Access
   ├── ChairpersonDashboardController@index
   ├── Display: statistics, upcoming defenses, notifications
   └── Quick access to all management areas

2. Academic Term Setup
   ├── AcademicTermController@index (view terms)
   ├── AcademicTermController@create (new term form)
   ├── AcademicTermController@store (create term)
   ├── AcademicTermController@toggleActive (activate term)
   └── Set: current working semester

3. Faculty Management
   ├── ChairPersonController@facultyManagement (view faculty)
   ├── ChairPersonController@createFacultyManual (manual entry)
   ├── ChairPersonController@storeFacultyManual (save faculty)
   ├── ChairPersonController@storeFaculty (CSV import)
   └── Manage: faculty for semester

4. Create Subject Offering
   ├── ChairPersonController@createOffering (offering form)
   ├── ChairPersonController@storeOffering (save offering)
   ├── Auto-assign: coordinator role to teacher
   └── Create: subject for students

5. Student Management
   ├── ChairPersonController@indexStudents (view students)
   ├── ChairPersonController@uploadStudentList (CSV import)
   ├── ChairPersonController@enrollStudent (individual enrollment)
   ├── ChairPersonController@enrollMultipleStudents (bulk enrollment)
   └── Enroll: students in offerings

6. Role Management
   ├── RoleController@index (view roles)
   ├── RoleController@update (assign roles)
   ├── Auto-update: roles based on responsibilities
   └── Manage: faculty permissions

7. Ongoing Management
   ├── ChairPersonController@editOffering (modify offerings)
   ├── ChairPersonController@updateOffering (save changes)
   ├── ChairPersonController@deleteStudent (remove students)
   ├── ChairPersonController@bulkDeleteStudents (bulk operations)
   └── Maintain: system data

8. Data Export
   ├── ChairPersonController@exportStudents (export data)
   ├── Generate: CSV reports
   └── Download: student/faculty data
```

This comprehensive flow shows how the Chairperson Dashboard manages the entire capstone project lifecycle from semester setup to project completion, with full multi-semester support and automated role management.
