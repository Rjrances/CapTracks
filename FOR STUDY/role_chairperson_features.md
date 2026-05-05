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

## 5. Critical Code Line-by-Line Breakdown (For 1000% Defense Readiness)

If your panelists want you to explain the code line-by-line, memorize these three most complex and critical Chairperson functions.

### A. Mass Importing Students via CSV (`ChairpersonStudentController@upload`)
Panel Question: *"Explain line-by-line how you prevent duplicate students from crashing the database during a CSV upload."*

```php
public function upload(Request $request) {
    // LINE 1: Validate that the uploaded file is strictly a CSV or TXT file, max 2MB.
    $request->validate(['file' => 'required|mimes:csv,txt|max:2048']);

    // LINE 2: Read the physical file from the server's temporary path into an array of lines.
    $file = file($request->file->getRealPath()); 
    
    // LINE 3: Remove the first line (index 0) because it usually contains column headers (e.g., "ID, Name, Email").
    $data = array_slice($file, 1); 

    // LINE 4: Loop through every remaining line in the CSV file array.
    foreach ($data as $line) { 
        
        // LINE 5: Parse the comma-separated string into a PHP array (e.g., [0] => ID, [1] => Name, [2] => Email).
        $row = str_getcsv($line); 
        
        // LINE 6: Check if the row actually has data (at least 3 columns) to avoid blank line errors.
        if (count($row) >= 3) { 
            
            // LINE 7: 'firstOrCreate' is an Eloquent method. First, it searches the DB for 'student_id' matching $row[0].
            // If it finds it, it skips. If it DOES NOT find it, it executes an INSERT query with the array below.
            Student::firstOrCreate(
                ['student_id' => $row[0]], 
                [
                    // LINE 8: Map the second column to the 'name' database field.
                    'name' => $row[1], 
                    
                    // LINE 9: Map the third column to the 'email' database field.
                    'email' => $row[2], 
                    
                    // LINE 10: Run the default string 'password123' through the Bcrypt hashing algorithm.
                    'password' => Hash::make('password123'), 
                ]
            );
        }
    }
    // LINE 11: Redirect the user back to the index page with a success flash message.
    return redirect()->route('chairperson.students.index')->with('success', 'Students imported successfully.'); 
}
```

### B. Toggling the Active Semester (`AcademicTermController@toggleActive`)
Panel Question: *"Explain line-by-line how you ensure only one semester is active at a time."*

```php
public function toggleActive(AcademicTerm $academicTerm) {
    // LINE 1: Execute a raw UPDATE query targeting ALL academic terms where the ID does NOT match the selected one.
    // LINE 2: Set 'is_active' to false for all of those other terms. This is the bulk deactivation step.
    AcademicTerm::where('id', '!=', $academicTerm->id)->update(['is_active' => false]);
    
    // LINE 3: Take the specific term the user clicked on, flip its current boolean status (e.g., false becomes true).
    $academicTerm->update(['is_active' => !$academicTerm->is_active]);
    
    // LINE 4: Redirect the user back to the previous page with a success message.
    return back()->with('success', 'Academic term status updated successfully.'); 
}
```

### C. Enrolling a Student into a Class (`ChairpersonOfferingController@enrollStudent`)
Panel Question: *"Explain line-by-line how a student is attached to a class section using Pivot Tables."*

```php
public function enrollStudent(Request $request, $offeringId) {
    // LINE 1: Validate the incoming request to ensure the submitted 'student_id' exists in the 'students' table.
    $request->validate(['student_id' => 'required|exists:students,id']); 
    
    // LINE 2: Query the 'Offerings' table to find the specific class section using the ID from the URL, throwing a 404 if not found.
    $offering = Offering::findOrFail($offeringId); 
    
    // LINE 3: Access the Many-to-Many 'students()' relationship defined in the Offering model.
    // LINE 4: Call the 'attach()' method, which automatically creates a new row in the 'offering_student' pivot table linking both IDs.
    $offering->students()->attach($request->student_id); 
    
    // LINE 5: Redirect back to the class roster page.
    return back()->with('success', 'Student enrolled successfully.'); 
}
```

## 6. Exhaustive Feature & Endpoint List (All Functions)
For complete system coverage, here is every single specific function the Chairperson can perform across the entire application:

**Dashboard & Global Data (`ChairpersonDashboardController` & `ChairPersonController`)**
- `index()`: Aggregates global system statistics (total students, total groups, pending defenses) and loads the primary admin dashboard view.
- `getActiveTerm()`: A helper method that queries the database for the single `AcademicTerm` where `is_active = true`.
- `notifications()`: Retrieves all system alerts (e.g., new registrations, term changes) scoped to the Chairperson role.
- `markNotificationAsRead()` / `markAllNotificationsAsRead()` / `markMultipleAsRead()`: Updates the `is_read` boolean column to true for specific or all notifications, reducing the unread badge count.
- `deleteNotification()` / `deleteMultiple()`: Permanently removes selected notification records from the database to clear clutter.

**Class / Offering Management (`ChairpersonOfferingController`)**
- `index()`: Retrieves and paginates all class offerings (sections) for the active term.
- `create()` / `store()`: Renders the offering creation form and validates input (Course name, Teacher ID) before inserting a new `Offering` record into the database.
- `show()` / `edit()` / `update()`: Fetches a specific class offering allowing the chairperson to modify the assigned teacher or course code.
- `destroy()`: Deletes an offering and safely cascades or detaches related student enrollments.
- `showUnenrolledStudents()`: Executes a query on the `students` table, filtering out anyone who is already attached to an offering in the current active term.
- `enrollStudent()` / `enrollMultipleStudents()`: Uses Eloquent's `attach()` method to link one or multiple `student_id`s to the `offering_student` pivot table.
- `removeStudent()`: Uses Eloquent's `detach()` method to safely remove a student from a class section without deleting their account.

**User & Role Management (`ChairpersonFacultyController`, `ChairpersonStudentController`, `RoleController`)**
- `index()`: Lists all faculty or students with search and pagination features.
- `createManual()` / `storeManual()`: Allows manual form entry to create a single faculty account (generating a default encrypted password).
- `upload()`: The mass-import engine. Reads a CSV/Excel file, slices the headers, and loops through the rows using `firstOrCreate()` to insert hundreds of users instantly without duplicate SQL crashes.
- `export()`: Compiles the student database into a downloadable CSV file.
- `edit()` / `update()`: Modifies basic profile details (Name, ID, Email) for an existing user.
- `assignCoordinator()` / `removeCoordinator()`: Quick-action toggles that directly update the `role` column in the `users` table.
- `destroy()` / `bulkDelete()`: Deletes a specific user or an array of user IDs passed via a checkbox form.
- `update()` *(RoleController)*: Modifies a user's global permission level (e.g. promoting a Teacher to an Adviser), including a failsafe to prevent the Chairperson from accidentally demoting themselves.

**Academic Terms (`AcademicTermController`)**
- `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`: Standard CRUD operations for semester management.
- `toggleActive()`: The critical semester switch. When called, it first runs an `update(['is_active' => false])` on all terms, then sets the selected term to `true`. This guarantees only one semester is active at a time.
- `toggleArchived()`: Marks historical terms as archived, filtering them out of active dropdown menus system-wide.

**Calendar & Scheduling (`CalendarController`)**
- `chairpersonCalendar()`: Queries the `DefenseSchedule` model to fetch all scheduled defense events across the entire institution, formatting them into a JSON array for the FullCalendar JS library.

**Authentication (`AuthController`)**
- `login()` / `logout()`: Validates credentials against the encrypted `password` column and manages session tokens.
- `changePassword()`: Receives a new password, hashes it using `bcrypt()`, and updates the user's account row.
