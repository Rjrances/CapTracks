# Chairperson Features (Admin)

The Chairperson manages the academic environment, handles user roles, establishes class offerings, and imports student/faculty data.

## 1. Role Management

**Description:** The Chairperson can reassign roles for faculty members.

**Core Logic (`app/Http/Controllers/RoleController.php`):**
```php
public function update(Request $request, $faculty_id) {
    $faculty = User::findOrFail($faculty_id);
    if ($faculty->id === Auth::id() && $request->role !== 'chairperson') {
         return back()->with('error', 'You cannot change your own role.');
    }
    $faculty->update(['role' => $request->role]);
    return back()->with('success', 'Role updated successfully.');
}
```
**Code Explanation:**
- `User::findOrFail($faculty_id);`: We fetch the specific faculty member from the database. If the ID doesn't exist, it automatically throws a 404 error (page not found), stopping execution safely.
- `if ($faculty->id === Auth::id() && $request->role !== 'chairperson')`: This is a safeguard. It checks if the logged-in Chairperson is trying to change their own role to something else (which would accidentally strip their admin rights).
- `$faculty->update(['role' => $request->role]);`: Actually saves the new role (like 'adviser' or 'coordinator') into the database.

## 2. Data Imports (CSV/Excel Uploads)

**Description:** The system allows mass-importing teachers and students using direct CSV parsing.

**Core Logic (`app/Http/Controllers/ChairpersonStudentController.php`):**
```php
public function upload(Request $request) {
    $request->validate([
        'file' => 'required|mimes:csv,txt|max:2048',
    ]);

    $file = file($request->file->getRealPath());
    $data = array_slice($file, 1); // Skip header

    foreach ($data as $line) {
        $row = str_getcsv($line);
        if (count($row) >= 3) {
            // Check if student exists before creating to prevent duplicates
            Student::firstOrCreate(
                ['student_id' => $row[0]],
                [
                    'name' => $row[1],
                    'email' => $row[2],
                    'password' => Hash::make('password123'), // Default password
                ]
            );
        }
    }
    return redirect()->route('chairperson.students.index')->with('success', 'Students imported successfully.');
}
```

### 🧠 Defense Tip: How do you prevent crashing when mass uploading CSVs?
If a panelist asks: *"What happens if a coordinator accidentally uploads the same student CSV file twice? Does the database crash?"*
**Your Answer:** *"No, the database won't crash because we handle de-duplication inside the upload loop. We use an Eloquent method called `firstOrCreate`. It looks at the primary key (the `student_id`). If it finds that ID in the database already, it simply skips that row. If it doesn't find the ID, it inserts the new student. This guarantees that re-uploading an old CSV file won't result in fatal Duplicate Entry SQL errors."*

**Code Explanation:**
- `$request->validate(...)`: Makes sure the uploaded file is strictly a CSV or text file under 2MB.
- `file(...)`: Reads the uploaded file into an array where each array item is one line of the file.
- `array_slice($file, 1)`: Removes the first row (the column headers like "Name, Email") so we only process actual student data.
- `str_getcsv($line)`: Splits the comma-separated text into an array (e.g., `$row[0]` is ID, `$row[1]` is Name).
- `Student::firstOrCreate(...)`: This looks for a student with the provided `student_id`. If it finds one, it skips. If it doesn't, it creates a new account with the default encrypted password `'password123'`.

## 3. Class/Offering Management & Student Enrollment

**Description:** The Chairperson creates class sections (Offerings) and manually enrolls students into these specific sections.

**Core Logic (`app/Http/Controllers/ChairpersonOfferingController.php`):**
```php
// Creating a new class offering
public function store(Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'academic_term_id' => 'required|exists:academic_terms,id',
        'faculty_id' => 'required|exists:users,id', // Assigned Teacher
    ]);

    Offering::create($request->all());
    return redirect()->route('chairperson.offerings.index')->with('success', 'Class created successfully.');
}

// Enrolling a student into a class
public function enrollStudent(Request $request, $offeringId) {
    $request->validate(['student_id' => 'required|exists:students,id']);
    
    $offering = Offering::findOrFail($offeringId);
    // Attach via pivot table/relationship
    $offering->students()->attach($request->student_id);
    
    return back()->with('success', 'Student enrolled successfully.');
}
```
**Code Explanation:**
- `Offering::create($request->all());`: Takes the validated form data (class name, term, and teacher) and inserts it as a new record in the `offerings` table.
- `$offering->students()->attach($request->student_id);`: Because an Offering can have many Students, and a Student can have many Offerings, this uses Laravel's `attach()` method to insert a record into the middleman pivot table (`offering_student`), linking the two together without duplicating actual student or offering rows.

## 4. Academic Terms

**Description:** Controls the active semester, directly affecting which groups and schedules are visible.

**Core Logic (`app/Http/Controllers/AcademicTermController.php`):**
```php
public function toggleActive(AcademicTerm $academicTerm) {
    AcademicTerm::where('id', '!=', $academicTerm->id)->update(['is_active' => false]);
    $academicTerm->update(['is_active' => !$academicTerm->is_active]);
    return back()->with('success', 'Academic term status updated successfully.');
}
```
**Code Explanation:**
- `AcademicTerm::where('id', '!=', $academicTerm->id)->update(['is_active' => false]);`: A mass update query. It targets every academic term in the database *except* the one we are currently trying to toggle, and forcefully sets their `is_active` status to `false`. This guarantees that only one term can ever be active at a time.
- `$academicTerm->update(['is_active' => !$academicTerm->is_active]);`: Toggles the selected term. If it was false, `!false` makes it true (active).
