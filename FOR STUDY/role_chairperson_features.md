# Chairperson Features (Admin)

The Chairperson manages the academic environment, handles user roles, establishes class offerings, and imports student/faculty data.

## 🔄 User Journey Flow (Top to Bottom)
If panelists ask for the "System Workflow" or "Use Case" of a Chairperson, explain this exact step-by-step flow:
1. **Set the Semester:** The Chairperson logs in and toggles the Active Academic Term (e.g., 1st Semester 2026).
2. **Import Data:** They upload the raw CSV files to populate the database with Students and Faculty.
3. **Assign Roles:** They go to the Role Management screen to assign specific teachers as Coordinators or Advisers.
4. **Create Offerings:** They create Class Sections (Offerings) and assign a specific Coordinator/Teacher to them.
5. **Enroll Students:** Finally, they enroll the imported students into their respective Class Offerings so the Coordinators can take over.

## 1. Role Management

**Description:** The Chairperson can reassign roles for faculty members.

**Core Logic (`app/Http/Controllers/RoleController.php`):**
```php
public function update(Request $request, $faculty_id) {
    
    // Fetch the faculty member or throw a 404 error if not found
    $faculty = User::findOrFail($faculty_id); 
    
    // Prevent the chairperson from changing their own role and losing admin rights
    if ($faculty->id === Auth::id() && $request->role !== 'chairperson') { 
         
         // Send them back to the previous page with an error message
         return back()->with('error', 'You cannot change your own role.'); 
    }
    
    // Save the new role to the database
    $faculty->update(['role' => $request->role]); 
    
    // Refresh the page with a success message
    return back()->with('success', 'Role updated successfully.'); 
}
```

## 2. Data Imports (CSV/Excel Uploads)

**Description:** The system allows mass-importing teachers and students using direct CSV parsing.

**Core Logic (`app/Http/Controllers/ChairpersonStudentController.php`):**
```php
public function upload(Request $request) {
    
    // Ensure the uploaded file is specifically a CSV or Text file and under 2MB
    $request->validate([ 
        'file' => 'required|mimes:csv,txt|max:2048',
    ]);

    // Read the uploaded file into an array of lines
    $file = file($request->file->getRealPath()); 
    
    // Remove the first line (the column headers)
    $data = array_slice($file, 1); 

    // Loop through each student row in the CSV
    foreach ($data as $line) { 
        
        // Split the comma-separated line into an array (ID, Name, Email)
        $row = str_getcsv($line); 
        
        // Ensure the row has at least 3 columns
        if (count($row) >= 3) { 
            
            // Look for this Student ID
            // Check if student exists before creating to prevent duplicate SQL crashes
            Student::firstOrCreate(
                ['student_id' => $row[0]], 
                [
                    // Data: Set the Name
                    'name' => $row[1], 
                    
                    // Data: Set the Email
                    'email' => $row[2], 
                    
                    // Data: Encrypt and set default password
                    'password' => Hash::make('password123'), 
                ]
            );
        }
    }
    
    // Redirect to the student list page
    return redirect()->route('chairperson.students.index')->with('success', 'Students imported successfully.'); 
}
```

### 🧠 Defense Tip: How do you prevent crashing when mass uploading CSVs?
If a panelist asks: *"What happens if a coordinator accidentally uploads the same student CSV file twice? Does the database crash?"*
**Your Answer:** *"No, the database won't crash because we handle de-duplication inside the upload loop. We use an Eloquent method called `firstOrCreate`. It looks at the primary key (the `student_id`). If it finds that ID in the database already, it simply skips that row. If it doesn't find the ID, it inserts the new student. This guarantees that re-uploading an old CSV file won't result in fatal Duplicate Entry SQL errors."*


## 3. Class/Offering Management & Student Enrollment

**Description:** The Chairperson creates class sections (Offerings) and manually enrolls students into these specific sections.

**Core Logic (`app/Http/Controllers/ChairpersonOfferingController.php`):**
```php
// Creating a new class offering
public function store(Request $request) {
    
    // Validate that all required fields are filled properly
    $request->validate([ 
        'name' => 'required|string|max:255', // Class name must be a text string
        'academic_term_id' => 'required|exists:academic_terms,id', // Term must exist in the database
        'faculty_id' => 'required|exists:users,id', // Assigned Teacher must exist in the database
    ]);

    // Insert the new class section into the database
    Offering::create($request->all()); 
    
    // Redirect to the offerings page
    return redirect()->route('chairperson.offerings.index')->with('success', 'Class created successfully.'); 
}

// Enrolling a student into a class
public function enrollStudent(Request $request, $offeringId) {
    
    // Ensure the student exists
    $request->validate(['student_id' => 'required|exists:students,id']); 
    
    // Find the class section
    $offering = Offering::findOrFail($offeringId); 
    
    // Attach the student to the class using the offering_student pivot table
    $offering->students()->attach($request->student_id); 
    
    // Refresh the page
    return back()->with('success', 'Student enrolled successfully.'); 
}
```

## 4. Academic Terms

**Description:** Controls the active semester, directly affecting which groups and schedules are visible.

**Core Logic (`app/Http/Controllers/AcademicTermController.php`):**
```php
public function toggleActive(AcademicTerm $academicTerm) {
    
    // Forcefully set all OTHER academic terms to inactive (false)
    AcademicTerm::where('id', '!=', $academicTerm->id)->update(['is_active' => false]);
    
    // Toggle the selected term to active (true)
    $academicTerm->update(['is_active' => !$academicTerm->is_active]);
    
    // Refresh the page
    return back()->with('success', 'Academic term status updated successfully.'); 
}
```

## 5. Exhaustive Feature & Endpoint List (All Functions)
For complete system coverage, here is every single specific function the Chairperson can perform across the entire application:

**Dashboard & Global Data (`ChairpersonDashboardController` & `ChairPersonController`)**
- `ChairpersonDashboardController`: `index`
- `ChairPersonController`: `getActiveTerm`, `notifications`, `markNotificationAsRead`, `markAllNotificationsAsRead`, `deleteNotification`, `markMultipleAsRead`, `deleteMultiple`

**Class / Offering Management (`ChairpersonOfferingController`)**
- `ChairpersonOfferingController`: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `removeStudent`, `showUnenrolledStudents`, `enrollStudent`, `enrollMultipleStudents`

**User & Role Management (`ChairpersonFacultyController`, `ChairpersonStudentController`, `RoleController`)**
- `ChairpersonFacultyController`: `index`, `create`, `createManual`, `store`, `storeManual`, `upload`, `edit`, `update`, `assignCoordinator`, `removeCoordinator`, `destroy`
- `ChairpersonStudentController`: `index`, `export`, `edit`, `update`, `destroy`, `bulkDelete`, `upload`
- `RoleController`: `index`, `update`

**Academic Terms (`AcademicTermController`)**
- `AcademicTermController`: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `toggleActive`, `toggleArchived`

**Calendar & Scheduling (`CalendarController`)**
- `CalendarController`: `chairpersonCalendar`

**Authentication (`AuthController`)**
- `AuthController`: `showLoginForm`, `login`, `logout`, `showRegisterForm`, `register`, `showChangePasswordForm`, `changePassword`
