# PHP & Laravel Concepts Guide

## Overview
This guide explains common PHP and Laravel functions, operators, and concepts used throughout the CapTracks system.

---

## Data & Arrays

### 1. `compact()`

**What it does:** Creates an array from variable names

**Location Example:** `AcademicTermController.php`
```php
return view('chairperson.academic-terms.index', compact('academicTerms'));
```

**Simple Explanation:**
`compact()` is a shortcut to create an array with variable names as keys.

**How it works:**
```php
$academicTerms = ['2024-2025', '2023-2024'];

// Using compact (short way)
compact('academicTerms')
// Returns: ['academicTerms' => ['2024-2025', '2023-2024']]

// Without compact (long way)
['academicTerms' => $academicTerms]
// Same result
```

**Real Example:**
```php
$name = 'John';
$age = 25;
$email = 'john@example.com';

compact('name', 'age', 'email')
// Returns: [
//   'name' => 'John',
//   'age' => 25,
//   'email' => 'john@example.com'
// ]
```

**Why it's useful:**
- Shorter code when passing many variables to views
- Less typing, cleaner code
- Automatically uses variable names as keys

---

### 2. `implode()`

**What it does:** Joins array elements into a string

**Location Example:** `ChairpersonFacultyController.php`
```php
$errorMessage .= "• " . implode("\n• ", $allErrors);
```

**Simple Explanation:**
Combines array items into one string with a separator between each item.

**Syntax:**
```php
implode(separator, array)
```

**Examples:**
```php
// Example 1: Simple list
$fruits = ['Apple', 'Banana', 'Orange'];
implode(', ', $fruits)
// Result: "Apple, Banana, Orange"

// Example 2: Line breaks
$errors = ['Invalid email', 'Password too short', 'Name required'];
implode("\n• ", $errors)
// Result: "Invalid email
//         • Password too short
//         • Name required"

// Example 3: No separator
$numbers = [1, 2, 3, 4];
implode($numbers)
// Result: "1234"
```

**Real-world use:**
```php
// Display multiple error messages nicely
$errors = ['Field is required', 'Email invalid'];
$message = "Errors found:\n• " . implode("\n• ", $errors);
// Output:
// Errors found:
// • Field is required
// • Email invalid
```

**Opposite function:** `explode()` splits a string into an array

---

### 3. `json_decode()`

**What it does:** Converts JSON string to PHP array/object

**Location Example:** `ChairpersonStudentController.php`
```php
$studentIds = json_decode($request->input('student_ids'), true);
```

**Simple Explanation:**
Turns JSON text (string) into PHP data you can work with.

**Syntax:**
```php
json_decode(json_string, associative_array_flag)
```

**Parameters:**
- First parameter: JSON string
- Second parameter (`true`): Return as array (if `false` or omitted, returns object)

**Examples:**
```php
// Example 1: JSON to array
$json = '{"name":"John","age":25}';
$array = json_decode($json, true);
// Result: ['name' => 'John', 'age' => 25]

// Example 2: JSON to object
$json = '{"name":"John","age":25}';
$object = json_decode($json);
// Result: object with $object->name = 'John'

// Example 3: Array of IDs
$json = '[1, 2, 3, 4, 5]';
$ids = json_decode($json, true);
// Result: [1, 2, 3, 4, 5]

// Example 4: From JavaScript
// JavaScript sends: JSON.stringify([101, 102, 103])
// PHP receives: "[101,102,103]"
$studentIds = json_decode($request->input('student_ids'), true);
// Result: [101, 102, 103]
```

**Why it's needed:**
- JavaScript sends data as JSON
- Forms with multiple selections send JSON
- APIs communicate using JSON

**Opposite function:** `json_encode()` converts PHP array to JSON string

---

### 4. `!in_array()`

**What it does:** Checks if value is NOT in an array

**Location Example:** `ChairpersonStudentController.php`
```php
if (!in_array($sortBy, $allowedSortFields)) {
    $sortBy = 'student_id';
}
```

**Simple Explanation:**
The `!` means "NOT". So `!in_array()` checks if something is NOT in a list.

**Breakdown:**
- `in_array($value, $array)` - Returns true if found
- `!in_array($value, $array)` - Returns true if NOT found
- `!` is the "not" operator (reverses true/false)

**Examples:**
```php
$allowedColors = ['red', 'blue', 'green'];

// in_array (checking if it IS in array)
in_array('red', $allowedColors)    // true (red IS in array)
in_array('yellow', $allowedColors) // false (yellow NOT in array)

// !in_array (checking if it is NOT in array)
!in_array('red', $allowedColors)    // false (red IS in array)
!in_array('yellow', $allowedColors) // true (yellow NOT in array)
```

**Real-world use:**
```php
// Security: Only allow safe sorting fields
$allowedSortFields = ['name', 'email', 'created_at'];
$sortBy = $request->input('sort', 'name');

// If user sends invalid field, use default
if (!in_array($sortBy, $allowedSortFields)) {
    $sortBy = 'name'; // Safe default
}

// Example flows:
// User sends 'email' → IS allowed → Use 'email'
// User sends 'password' → NOT allowed → Use 'name' (default)
// User sends 'DROP TABLE' → NOT allowed → Use 'name' (prevents SQL injection)
```

---

## Date & Time (Carbon)

### 5. `Carbon::parse()`

**What it does:** Converts date/time string into Carbon object

**Location Example:** `CalendarController.php`
```php
$startDate = \Carbon\Carbon::parse($defense->scheduled_at);
```

**Simple Explanation:**
Carbon is Laravel's date/time helper. `parse()` reads a date string and creates a Carbon object you can manipulate.

**What is Carbon?**
Carbon is a PHP library that makes working with dates easy. It's like a supercharged date object.

**Examples:**
```php
// Example 1: Parse string to Carbon
$date = \Carbon\Carbon::parse('2024-10-22');
// Now you can do: $date->addDays(5), $date->format('Y-m-d'), etc.

// Example 2: Parse datetime
$datetime = \Carbon\Carbon::parse('2024-10-22 14:30:00');
// Result: Carbon object representing October 22, 2024 at 2:30 PM

// Example 3: What you can do with Carbon
$startDate = \Carbon\Carbon::parse('2024-10-22 14:00:00');
$endDate = $startDate->copy()->addHours(2);
// $startDate: 2024-10-22 14:00:00
// $endDate: 2024-10-22 16:00:00

// Example 4: Formatting
$date = \Carbon\Carbon::parse('2024-10-22');
$date->format('F d, Y')  // "October 22, 2024"
$date->format('m/d/Y')   // "10/22/2024"
$date->diffForHumans()   // "2 days ago"
```

**Why use Carbon instead of regular PHP dates?**
```php
// Without Carbon (complicated)
$date = new DateTime('2024-10-22');
$date->modify('+5 days');
$formatted = $date->format('Y-m-d');

// With Carbon (simple)
$formatted = \Carbon\Carbon::parse('2024-10-22')->addDays(5)->format('Y-m-d');
```

---

### 6. `now()->subYears(30)`

**What it does:** Gets current date/time and subtracts 30 years

**Location Example:** `ChairpersonFacultyController.php`
```php
'birthday' => now()->subYears(30),
```

**Simple Explanation:**
- `now()` = Current date and time
- `subYears(30)` = Subtract 30 years
- Result: Date 30 years ago from today

**Examples:**
```php
// Example 1: Basic usage
now()               // 2024-10-22 (today)
now()->subYears(30) // 1994-10-22 (30 years ago)

// Example 2: Other time subtractions
now()->subDays(7)    // 7 days ago
now()->subMonths(6)  // 6 months ago
now()->subHours(2)   // 2 hours ago

// Example 3: Additions (opposite)
now()->addYears(5)   // 5 years from now
now()->addDays(10)   // 10 days from now

// Example 4: Combined operations
now()->subYears(25)->addMonths(3) // 25 years ago plus 3 months
```

**Why 30 specifically?**

The number **30** is chosen because:

1. **Reasonable Age Estimate**: Most faculty members are at least 30+ years old
   - Typical path: Bachelor's (4 years) + Master's (2 years) + PhD (4-6 years) = ~10-12 years
   - If starting college at 18, they'd be 28-30+ when qualified to teach
   - 30 is a safe, reasonable minimum age for faculty

2. **Database Requirement**: The `birthday` field is likely required (cannot be null)
   - System needs SOME date to save
   - 30 years ago is more realistic than 0 or 100

3. **Placeholder Value**: This is temporary data
   - Chairperson should update with real birthday later
   - Better than leaving null or using unrealistic age

4. **Not Too Specific**: 
   - Could use 25, 35, or 40 - all would work
   - 30 is middle-ground estimate
   - Not trying to be accurate, just reasonable

**Why in faculty creation?**
```php
// Setting a default birthday (placeholder)
'birthday' => now()->subYears(30),
// Assumes faculty member is about 30 years old
// This is a placeholder - should be updated with real birthday later

// Real-world example:
// Today is 2024-10-22
// 30 years ago = 1994-10-22
// Faculty birthday set to: October 22, 1994
// This makes them 30 years old (reasonable for faculty)
```

**Could use other numbers:**
```php
now()->subYears(25) // Younger faculty (25 years old)
now()->subYears(35) // Older faculty (35 years old)
now()->subYears(40) // Senior faculty (40 years old)

// Why NOT these:
now()->subYears(18) // Too young - unlikely to be faculty
now()->subYears(70) // Too old - assumes everyone is senior
now()->subYears(5)  // Nonsense - 5-year-old faculty!
```

**Best Practice:**
```php
// In form, ask for real birthday
'birthday' => $request->input('birthday') ?: now()->subYears(30),
// Use provided birthday, or default to 30 years ago if not provided
```

**Available time methods:**
- `addYears()` / `subYears()`
- `addMonths()` / `subMonths()`
- `addDays()` / `subDays()`
- `addHours()` / `subHours()`
- `addMinutes()` / `subMinutes()`

---

### 7. `toISOString()`

**What it does:** Converts date to ISO 8601 format string

**Location Example:** `CalendarController.php`
```php
'end' => $endDate->toISOString(),
```

**Simple Explanation:**
Converts a Carbon date object into a standardized international date format.

**ISO 8601 Format:**
```
YYYY-MM-DDTHH:MM:SS.sssZ
```
- `YYYY` = Year (4 digits)
- `MM` = Month (2 digits)
- `DD` = Day (2 digits)
- `T` = Separator between date and time
- `HH:MM:SS` = Time
- `.sss` = Milliseconds
- `Z` = UTC timezone

**Examples:**
```php
$date = \Carbon\Carbon::parse('2024-10-22 14:30:00');
$date->toISOString()
// Result: "2024-10-22T14:30:00.000000Z"

$now = now();
$now->toISOString()
// Result: "2024-10-22T18:45:30.123456Z"
```

**Why use ISO format?**
- ✅ Internationally recognized standard
- ✅ Works with JavaScript Date objects
- ✅ Works with calendar libraries (FullCalendar)
- ✅ Includes timezone information
- ✅ Can be sorted alphabetically

**JavaScript compatibility:**
```php
// PHP
$date = now()->toISOString();
// "2024-10-22T14:30:00.000000Z"

// JavaScript
new Date("2024-10-22T14:30:00.000000Z")
// Creates proper Date object
```

---

## Operators & Conditionals

### 8. `status === 'scheduled' ?`

**What it does:** Ternary operator - shorthand if/else

**Location Example:** `CalendarController.php`
```php
'backgroundColor' => $defense->status === 'scheduled' ? '#ffc107' : '#28a745',
```

**Simple Explanation:**
This is a compact way to write if/else in one line.

**Syntax:**
```php
condition ? value_if_true : value_if_false
```

**Breakdown of the example:**
```php
$defense->status === 'scheduled' ? '#ffc107' : '#28a745'

// If status IS 'scheduled'  → use '#ffc107' (yellow)
// If status NOT 'scheduled' → use '#28a745' (green)
```

**Equivalent if/else:**
```php
// Ternary (short)
$color = $status === 'scheduled' ? '#ffc107' : '#28a745';

// If/else (long)
if ($status === 'scheduled') {
    $color = '#ffc107';
} else {
    $color = '#28a745';
}
```

**More examples:**
```php
// Example 1: Display text
$message = $isLoggedIn ? 'Welcome back!' : 'Please log in';

// Example 2: Nested ternary
$grade = $score >= 90 ? 'A' : ($score >= 80 ? 'B' : 'C');

// Example 3: Array value
$status = $isActive ? 'active' : 'inactive';
$color = $status === 'active' ? 'green' : 'red';
```

**Triple equals `===` vs Double equals `==`:**
```php
// === (strict comparison - recommended)
'5' === 5   // false (different types: string vs number)
1 === true  // false (different types)

// == (loose comparison)
'5' == 5    // true (values are "equal")
1 == true   // true (1 is "truthy")
```

**Always use `===` for safety!**

---

### 9. `? $activeTerm->semester : 'Unknown'`

**What it does:** Ternary operator with null check

**Location Example:** `ChairpersonFacultyController.php`
```php
'semester' => $activeTerm ? $activeTerm->semester : 'Unknown',
```

**Simple Explanation:**
If `$activeTerm` exists, use its semester. If not (null/false), use 'Unknown'.

**Breakdown:**
```php
$activeTerm ? $activeTerm->semester : 'Unknown'

// If $activeTerm exists     → Get $activeTerm->semester
// If $activeTerm is null    → Use 'Unknown'
```

**Why it's needed:**
Prevents errors when no active term exists. Without this check:
```php
// BAD: Will crash if no active term
'semester' => $activeTerm->semester,
// Error: Trying to get property of null

// GOOD: Safe with fallback
'semester' => $activeTerm ? $activeTerm->semester : 'Unknown',
// Returns 'Unknown' if no active term
```

**Examples:**
```php
// Example 1: User name
$displayName = $user ? $user->name : 'Guest';
// If user logged in → Show name
// If not logged in → Show 'Guest'

// Example 2: Default values
$city = $address ? $address->city : 'Not specified';

// Example 3: Count
$count = $items ? count($items) : 0;
```

---

### 10. `?: 'Not Enrolled'` (Elvis Operator)

**What it does:** Shorthand for returning first value or default

**Location Example:** `ChairpersonStudentController.php`
```php
$enrolledOfferings ?: 'Not Enrolled'
```

**Simple Explanation:**
If left side has a value, use it. If empty/null/false, use right side.

**Syntax:**
```php
value ?: default
```

**Full explanation:**
```php
// Elvis operator (short)
$result = $enrolledOfferings ?: 'Not Enrolled';

// Equivalent ternary (longer)
$result = $enrolledOfferings ? $enrolledOfferings : 'Not Enrolled';

// Equivalent if/else (longest)
if ($enrolledOfferings) {
    $result = $enrolledOfferings;
} else {
    $result = 'Not Enrolled';
}
```

**Examples:**
```php
// Example 1: Display value or default
$offerings = ['CS101', 'CS102'];
$display = $offerings ?: 'Not Enrolled';
// Result: ['CS101', 'CS102']

$offerings = [];
$display = $offerings ?: 'Not Enrolled';
// Result: 'Not Enrolled'

// Example 2: User input with default
$name = $request->input('name') ?: 'Anonymous';

// Example 3: Configuration with fallback
$timeout = $config['timeout'] ?: 30;
```

**What counts as "empty"?**
- `null`
- `false`
- `0`
- `''` (empty string)
- `[]` (empty array)

**Note:** PHP 7+ has null coalescing operator `??` which is often better:
```php
// Elvis ?: (checks if truthy)
$value = $var ?: 'default';

// Null coalescing ?? (checks only if null)
$value = $var ?? 'default';
```

---

## Database Queries

### 11. `->first()`

**What it does:** Gets the first record from query results

**Simple Explanation:**
Returns the first row from database query, or `null` if nothing found.

**Examples:**
```php
// Example 1: Get first user
$user = User::where('email', 'john@example.com')->first();
// Returns: User object or null

// Example 2: Get first active term
$activeTerm = AcademicTerm::where('is_active', true)->first();
// Returns: First active term or null

// Example 3: With ordering
$latestPost = Post::orderBy('created_at', 'desc')->first();
// Returns: Most recent post

// Example 4: Checking result
$user = User::where('id', 999)->first();
if ($user) {
    echo $user->name; // User exists
} else {
    echo 'User not found'; // No user
}
```

**Similar methods:**
```php
->first()     // First record or null
->firstOrFail() // First record or throw 404 error
->get()       // All matching records (collection)
->find($id)   // Find by primary key
```

---

### 12. `->firstOrFail()`

**What it does:** Gets first record or throws 404 error

**Simple Explanation:**
Like `first()`, but automatically shows 404 error page if nothing found.

**Difference from `first()`:**
```php
// Using first() - manual error handling needed
$student = Student::where('student_id', $id)->first();
if (!$student) {
    abort(404, 'Student not found');
}

// Using firstOrFail() - automatic error handling
$student = Student::where('student_id', $id)->firstOrFail();
// Automatically shows 404 page if not found
```

**When to use:**
```php
// Use firstOrFail() when:
// - Viewing single resource page
// - Resource MUST exist
// - Want automatic 404 page

public function show($id) {
    $student = Student::findOrFail($id);
    return view('student.show', compact('student'));
}

// Use first() when:
// - Need custom error handling
// - It's okay if nothing found
// - Want to check existence

public function checkEmail($email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        return 'Email taken';
    }
    return 'Email available';
}
```

---

### 13. `->count()`

**What it does:** Counts the number of records

**Simple Explanation:**
Returns how many rows match the query.

**Examples:**
```php
// Example 1: Count all students
$totalStudents = Student::count();
// Returns: 150 (number)

// Example 2: Count with condition
$activeUsers = User::where('status', 'active')->count();
// Returns: 75

// Example 3: Count relationship
$group = Group::find(1);
$memberCount = $group->members()->count();
// Returns: 5 (members in this group)

// Example 4: Count for display
$pendingCount = Proposal::where('status', 'pending')->count();
echo "You have {$pendingCount} pending proposals";
// "You have 12 pending proposals"
```

**On collections:**
```php
// On database query (efficient - counts in database)
$count = Student::where('active', true)->count();
// Runs: SELECT COUNT(*) FROM students WHERE active = 1

// On collection (less efficient - loads all then counts)
$students = Student::where('active', true)->get();
$count = $students->count();
// Loads all students into memory, then counts
```

---

### 14. `Student::query()`

**What it does:** Starts a new query builder instance

**Location Example:** `ChairpersonStudentController.php`
```php
$query = Student::query();
```

**Simple Explanation:**
Creates an empty query that you can add conditions to step by step.

**Why use it?**
Allows you to build complex queries conditionally.

**Examples:**
```php
// Example 1: Basic usage
$query = Student::query();
// Same as: $query = Student::where(...)
// But more flexible for adding conditions

// Example 2: Conditional filters
$query = Student::query();

if ($request->has('search')) {
    $query->where('name', 'like', '%' . $request->search . '%');
}

if ($request->has('status')) {
    $query->where('status', $request->status);
}

$students = $query->get();

// Example 3: Building complex query
$query = User::query();

// Add conditions based on user input
if ($filterByActive) {
    $query->where('active', true);
}

if ($filterByDepartment) {
    $query->where('department', $department);
}

if ($sortBy) {
    $query->orderBy($sortBy, $sortDirection);
}

$users = $query->paginate(20);
```

**Why not just chain directly?**
```php
// Without query() - hard to add conditional filters
$students = Student::where('active', true)->get();

// With query() - easy to add conditions
$query = Student::query();
if ($includeInactive) {
    // Can choose whether to filter
}
$query->where('active', true);
$students = $query->get();
```

---

### 15. `->orderBy($sortBy, $sortDirection)`

**What it does:** Sorts query results

**Location Example:** `ChairpersonStudentController.php`
```php
$teachers = $query->orderBy($sortBy, $sortDirection);
```

**Simple Explanation:**
Determines the order of results (ascending or descending by a field).

**Parameters:**
- `$sortBy`: Column name to sort by
- `$sortDirection`: `'asc'` (ascending) or `'desc'` (descending)

**Examples:**
```php
// Example 1: Sort by name A-Z
Student::orderBy('name', 'asc')->get();
// Returns: [Alice, Bob, Charlie, David...]

// Example 2: Sort by name Z-A
Student::orderBy('name', 'desc')->get();
// Returns: [Zack, Yolanda, Xavier, William...]

// Example 3: Sort by newest first
Post::orderBy('created_at', 'desc')->get();
// Returns: Most recent posts first

// Example 4: Sort by oldest first
Student::orderBy('created_at', 'asc')->get();
// Returns: Oldest registrations first

// Example 5: Multiple sorts
User::orderBy('department', 'asc')
    ->orderBy('name', 'asc')
    ->get();
// First sort by department, then by name within each department
```

**Common use case - user-controlled sorting:**
```php
// User clicks column header to sort
$sortBy = $request->input('sort', 'name'); // Default: name
$sortDirection = $request->input('direction', 'asc'); // Default: asc

// Security: Validate allowed columns
$allowed = ['name', 'email', 'created_at'];
if (!in_array($sortBy, $allowed)) {
    $sortBy = 'name';
}

$users = User::orderBy($sortBy, $sortDirection)->get();
```

**Sort directions:**
- `'asc'` = Ascending (1, 2, 3... or A, B, C...)
- `'desc'` = Descending (3, 2, 1... or Z, Y, X...)

---

## Collection Methods

### 16. `->pluck('name')->join(', ')`

**What it does:** Extracts values and joins them into a string

**Location Example:** `CalendarController.php`
```php
'students' => $defense->group->members->pluck('name')->join(', ')
```

**Simple Explanation:**
Gets specific field from each item, then combines into comma-separated string.

**Breakdown:**
```php
$defense->group->members->pluck('name')->join(', ')

// Step 1: Get all group members
$defense->group->members
// Result: Collection of Student objects

// Step 2: Extract just the names
->pluck('name')
// Result: ['John Doe', 'Jane Smith', 'Bob Wilson']

// Step 3: Join with comma and space
->join(', ')
// Result: "John Doe, Jane Smith, Bob Wilson"
```

**Examples:**
```php
// Example 1: Get list of emails
$users = User::all();
$emails = $users->pluck('email');
// Result: ['john@email.com', 'jane@email.com', 'bob@email.com']

// Example 2: Get names and join
$students = Student::all();
$nameList = $students->pluck('name')->join(', ');
// Result: "Alice, Bob, Charlie, David"

// Example 3: Get IDs
$groups = Group::where('active', true)->get();
$ids = $groups->pluck('id');
// Result: [1, 2, 3, 4, 5]

// Example 4: Pluck with keys
$users = User::all();
$userEmails = $users->pluck('email', 'id');
// Result: [
//   1 => 'john@email.com',
//   2 => 'jane@email.com'
// ]
```

**Without pluck:**
```php
// Manual way (without pluck)
$members = $defense->group->members;
$names = [];
foreach ($members as $member) {
    $names[] = $member->name;
}
$nameString = implode(', ', $names);

// With pluck (one line)
$nameString = $defense->group->members->pluck('name')->join(', ');
```

**Join options:**
```php
$collection->join(', ')      // "A, B, C"
$collection->join(' and ')   // "A and B and C"
$collection->join("\n")      // "A\nB\nC" (line breaks)
$collection->join(', ', ' and ') // "A, B and C" (different last separator)
```

---

## String & Number Formatting

### 17. `number_format($file->getSize() / 1024, 2)`

**What it does:** Formats file size from bytes to KB with 2 decimal places

**Location Example:** `ChairpersonFacultyController.php`
```php
$fileSize = number_format($file->getSize() / 1024, 2);
```

**Simple Explanation:**
Converts file size and formats it nicely for display.

**Parameters breakdown:**
- `$file->getSize()` - File size in bytes
- `/ 1024` - Divide by 1024 to convert bytes to kilobytes
- `, 2` - Show 2 decimal places

**Why 1024?**
```
Computer storage units:
1 KB (Kilobyte) = 1024 bytes
1 MB (Megabyte) = 1024 KB = 1,048,576 bytes
1 GB (Gigabyte) = 1024 MB

File size conversions:
5000 bytes ÷ 1024 = 4.88 KB
1048576 bytes ÷ 1024 ÷ 1024 = 1 MB
```

**Examples:**
```php
// Example 1: File size 5000 bytes
$bytes = 5000;
$kb = number_format($bytes / 1024, 2);
// Result: "4.88" (KB)

// Example 2: Larger file
$bytes = 1048576; // 1 MB in bytes
$kb = number_format($bytes / 1024, 2);
// Result: "1024.00" (KB)

// Example 3: Without formatting
$bytes = 5000;
$kb = $bytes / 1024;
// Result: 4.8828125 (ugly)

// Example 4: With formatting
$kb = number_format($bytes / 1024, 2);
// Result: "4.88" (nice!)
```

**Full `number_format()` syntax:**
```php
number_format(number, decimals, decimal_separator, thousands_separator)

// Basic
number_format(1234.5678, 2)
// Result: "1,234.57"

// Custom separators
number_format(1234.5678, 2, '.', ',')
// Result: "1,234.57"

// European style
number_format(1234.5678, 2, ',', '.')
// Result: "1.234,57"

// No decimals
number_format(1234.5678, 0)
// Result: "1,235"
```

**Complete file size formatter:**
```php
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

formatFileSize(5000);        // "4.88 KB"
formatFileSize(1048576);     // "1.00 MB"
formatFileSize(1073741824);  // "1.00 GB"
```

---

### 18. `str_contains(strtolower($e->getMessage()), 'duplicate entry')`

**What it does:** Checks if a string contains another string (case-insensitive)

**Location Example:** `ChairpersonFacultyController.php`
```php
if (str_contains(strtolower($e->getMessage()), 'duplicate entry')) {
```

**Simple Explanation:**
Searches for text inside another text, ignoring uppercase/lowercase.

**Breakdown:**
```php
str_contains(strtolower($e->getMessage()), 'duplicate entry')

// Step 1: Get error message
$e->getMessage()
// Result: "SQLSTATE[23000]: Duplicate entry 'john@email.com' for key 'email'"

// Step 2: Convert to lowercase
strtolower($e->getMessage())
// Result: "sqlstate[23000]: duplicate entry 'john@email.com' for key 'email'"

// Step 3: Check if contains 'duplicate entry'
str_contains(..., 'duplicate entry')
// Result: true (found)
```

**Examples:**
```php
// Example 1: Simple search
str_contains('Hello World', 'World')
// Result: true

str_contains('Hello World', 'world')
// Result: false (case-sensitive!)

// Example 2: Case-insensitive search
str_contains(strtolower('Hello World'), 'world')
// Result: true

// Example 3: Error message detection
$error = 'Database connection failed';
if (str_contains(strtolower($error), 'connection')) {
    echo 'Connection problem detected';
}

// Example 4: Checking email
$email = 'john@example.com';
if (str_contains($email, '@example.com')) {
    echo 'Example.com user';
}
```

**Why use `strtolower()`?**
```php
// Without strtolower (misses uppercase)
str_contains('DUPLICATE ENTRY', 'duplicate entry')
// Result: false (case doesn't match)

// With strtolower (catches all)
str_contains(strtolower('DUPLICATE ENTRY'), 'duplicate entry')
// Result: true (both lowercase)
```

**Similar functions:**
```php
str_contains($haystack, $needle)  // Check if contains
str_starts_with($str, $prefix)    // Check if starts with
str_ends_with($str, $suffix)      // Check if ends with
strpos($haystack, $needle)        // Find position (old way)
```

---

## HTTP Responses

### 19. `response()->stream($callback, 200, $headers)`

**What it does:** Sends file download response to browser

**Location Example:** `ChairpersonStudentController.php`
```php
return response()->stream($callback, 200, $headers);
```

**Simple Explanation:**
Creates a download response. The `200` is the HTTP status code meaning "OK, success".

**What is 200?**
HTTP status codes tell the browser what happened:

**Common HTTP Status Codes:**
```
2xx = Success
200 OK              - Request succeeded
201 Created         - New resource created
204 No Content      - Success but no data to send

3xx = Redirect
301 Moved Permanently - Resource moved
302 Found            - Temporary redirect
304 Not Modified     - Use cached version

4xx = Client Error
400 Bad Request      - Invalid request
401 Unauthorized     - Login required
403 Forbidden        - Not allowed
404 Not Found        - Resource doesn't exist
422 Unprocessable    - Validation failed

5xx = Server Error
500 Internal Server Error - Something broke
502 Bad Gateway          - Server communication failed
503 Service Unavailable  - Server overloaded/down
```

**In this context:**
```php
return response()->stream($callback, 200, $headers);

// 200 = "OK, here's your file"
// Browser knows request succeeded
// Download starts
```

**Examples:**
```php
// Example 1: Successful response
return response()->json(['message' => 'Success'], 200);

// Example 2: Created resource
return response()->json(['id' => 123], 201);

// Example 3: Not found
return response()->json(['error' => 'Not found'], 404);

// Example 4: Validation error
return response()->json(['errors' => $errors], 422);

// Example 5: Server error
return response()->json(['error' => 'Server error'], 500);
```

**Laravel helper shortcuts:**
```php
// Manual status code
return response()->json($data, 200);

// Automatic 200 (default)
return response()->json($data);

// 404 shortcut
abort(404);

// 403 shortcut
abort(403, 'You cannot do this');
```

**File download status codes:**
```php
// Success
return response()->download($file, 'export.csv', $headers, 200);

// Not found
abort(404, 'File not found');

// Forbidden
abort(403, 'No permission to download');
```

---

## Special Cases

### 20. `roleKey === 'student'` Purpose

**What it does:** Skips counting students in faculty role distribution

**Location Example:** `RoleController.php`
```php
foreach ($roles as $roleKey => &$role) {
    if ($roleKey === 'student') {
        $role['user_count'] = 0;
        continue;
    }
```

**Simple Explanation:**
Student role is handled separately because students aren't faculty. This prevents errors.

**Why skip student role?**

1. **Students use different table**
   ```php
   // Faculty roles use: user_roles table
   // Students use: student_accounts table
   // Can't count students same way as faculty
   ```

2. **Students don't have multiple roles**
   ```php
   // Faculty can be: Teacher + Adviser + Coordinator
   // Students are only: Student
   // No role assignment needed
   ```

3. **Different authentication system**
   ```php
   // Faculty use: Auth::user()
   // Students use: Auth::guard('student')->user()
   // Separate systems
   ```

**What would happen without this check:**
```php
// Without check
$count = UserRole::where('role_id', $studentRole->id)->count();
// Error! UserRole doesn't have student entries
// Only has: adviser, coordinator, teacher, panelist

// With check
if ($roleKey === 'student') {
    $role['user_count'] = 0; // Set to 0, skip query
    continue; // Skip to next role
}
// Safe! Only counts faculty roles
```

**Real example:**
```php
$roles = [
    'chairperson' => [...],
    'coordinator' => [...],
    'adviser' => [...],
    'teacher' => [...],
    'panelist' => [...],
    'student' => [...]  // This one needs special handling
];

foreach ($roles as $roleKey => &$role) {
    if ($roleKey === 'student') {
        // Don't count - students are in different system
        $role['user_count'] = 0;
        continue; // Skip rest of loop for this role
    }
    
    // For faculty roles, count normally
    $role['user_count'] = UserRole::where('role_id', $role['id'])->count();
}
```

**Summary:**
- Faculty roles: Counted from `user_roles` table
- Student role: Set to 0, not counted (different system)
- This prevents database errors and logical inconsistencies

---

## Quick Reference

### Data Functions
| Function | Purpose | Example |
|----------|---------|---------|
| `compact()` | Create array from variables | `compact('name', 'age')` |
| `implode()` | Join array to string | `implode(', ', $array)` |
| `json_decode()` | Parse JSON string | `json_decode($json, true)` |
| `in_array()` | Check if in array | `in_array('red', $colors)` |
| `!in_array()` | Check if NOT in array | `!in_array('blue', $colors)` |

### Date Functions (Carbon)
| Function | Purpose | Example |
|----------|---------|---------|
| `Carbon::parse()` | Parse date string | `Carbon::parse('2024-10-22')` |
| `now()` | Current datetime | `now()` |
| `subYears()` | Subtract years | `now()->subYears(5)` |
| `addDays()` | Add days | `now()->addDays(7)` |
| `toISOString()` | ISO format | `$date->toISOString()` |

### Database Functions
| Function | Purpose | Example |
|----------|---------|---------|
| `first()` | Get first result | `User::first()` |
| `firstOrFail()` | First or 404 | `User::findOrFail($id)` |
| `count()` | Count results | `User::count()` |
| `query()` | Start query builder | `User::query()` |
| `orderBy()` | Sort results | `orderBy('name', 'asc')` |

### Collection Functions
| Function | Purpose | Example |
|----------|---------|---------|
| `pluck()` | Extract column | `$users->pluck('name')` |
| `join()` | Join to string | `$names->join(', ')` |

### String Functions
| Function | Purpose | Example |
|----------|---------|---------|
| `str_contains()` | Check if contains | `str_contains($str, 'word')` |
| `strtolower()` | Convert lowercase | `strtolower('HELLO')` |
| `number_format()` | Format number | `number_format(1234.56, 2)` |

### Operators
| Operator | Purpose | Example |
|----------|---------|---------|
| `condition ? a : b` | Ternary if/else | `$x ? 'yes' : 'no'` |
| `value ?: default` | Elvis (value or default) | `$name ?: 'Guest'` |
| `===` | Strict equals | `$a === $b` |
| `!` | NOT operator | `!in_array()` |

