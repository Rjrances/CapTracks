# Chairperson Features (Admin)

The Chairperson manages the academic environment, handles user roles, establishes class offerings, and imports student/faculty data.

## Artifact types (how this doc labels code)

| Tag | Meaning |
|-----|---------|
| **`[Controller]`** | `app/Http/Controllers/*.php` — HTTP layer: validation, redirects, JSON. |
| **`[Service]`** | `app/Services/*.php` — import/enrollment and other orchestration. |
| **`[JS]`** | `resources/views/chairperson/**/*.blade.php` inline scripts. |
| **`[Middleware]`** | Laravel **`auth`**, Spatie **`role:chairperson`**, applied on `/chairperson/*`; see §11. |

Snippets are tagged in headings so panel answers stay precise (**Controller** vs **Service** vs **JS**).

## 🔄 User Journey Flow (Top to Bottom)
If panelists ask for the "System Workflow" or "Use Case" of a Chairperson, explain this exact step-by-step flow:
1. **Set the Semester:** The Chairperson logs in and toggles the Active Academic Term (e.g., 1st Semester 2026).
2. **Import Data:** They upload the raw CSV files to populate the database with Students and Faculty.
3. **Assign Roles:** They go to the Role Management screen to assign specific teachers as Coordinators or Advisers.
4. **Create Offerings:** They create Class Sections (Offerings) and assign a specific Coordinator/Teacher to them.
5. **Enroll Students:** Finally, they enroll the imported students into their respective Class Offerings so the Coordinators can take over.

## 1. Role management (Spatie roles UI)

**Description:** **`[Controller]`** `RoleController` lists faculty (scoped to active term **semester** when set) and **`update`** accepts an array of **`roles[]`** validated against allowed slugs. It resolves **`User`** by **`faculty_id`** + semester, then calls **`assignRoles`** (project’s custom/API on **`User`**). **`chairperson/roles/index.blade.php`** posts via **`[JS]`** `fetch` (see §12).

**Core logic — `RoleController::update` (excerpt, `app/Http/Controllers/RoleController.php`):**
```php
$request->validate([
    'roles' => 'required|array',
    'roles.*' => 'in:chairperson,coordinator,teacher,adviser,panelist',
]);
$activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
$user = User::where('faculty_id', $faculty_id)
    ->when($activeTerm, fn ($q) => $q->where('semester', $activeTerm->semester))
    ->firstOrFail();

$user->assignRoles($request->roles);

if ($request->ajax()) {
    return response()->json(['success' => true, 'message' => 'User roles updated successfully.', ...]);
}
return redirect()->back()->with('success', 'User roles updated successfully.');
```

| Line (in fence) | What it does |
|-----------------|--------------|
| 1–4 | **`[Controller]`** Require at least one role; each must be an allowed Spatie name. |
| 5–13 | Find the **`User`** row for this **`faculty_id`** in the active term’s **semester** (if a term is active). |
| 15 | Sync/replace roles via **`assignRoles`** (implementation on **`User`** model). |
| 17–22 | JSON for **`[JS]`** AJAX from roles index, else classic redirect with flash. |

**Deployment note:** Verify **`php artisan route:list`** includes **`chairperson.roles`** routes pointing at **`RoleController`**. The Blade view and **`NotificationService`** reference **`route('chairperson.roles.index')`**; if that route is missing, add it under the chairperson middleware group.

## 2. Student CSV import (delegated service)

**Description:** **`[Controller]`** `ChairpersonStudentController::upload` is a **one-liner** delegating to **`[Service]`** `StudentImportService` (Maatwebsite Excel + **`StudentsImport`**). No manual `file()` loop in the controller.

**`[Controller]` — `ChairpersonStudentController::upload`:**
```php
public function upload(Request $request)
{
    return app(StudentImportService::class)->importFromRequest($request, StudentImportService::MODE_CHAIRPERSON);
}
```

**`[Service]` — `StudentImportService::importFromRequest` (excerpt):**
```php
$request->validate([
    'file' => 'required|file|mimes:csv|max:10240',
], [ /* friendly messages */ ]);

$file = $request->file('file');
$import = new StudentsImport($offeringId);
Excel::import($import, $file);

$createdCount = $import->getCreatedStudentsCount();
$existingCount = $import->getExistingStudentsCount();
// redirects with success / “all duplicates” error / validation failures
```

| Piece | What it does |
|--------|--------------|
| **`[Controller]`** | Resolves **`StudentImportService`** from the container and passes **`MODE_CHAIRPERSON`**. |
| **`[Service]`** | Validates **CSV**, max **10MB**; runs **`Excel::import`**; uses **`StudentsImport`** row logic ( **`firstOrCreate`** / dedupe happens inside that import class). |
| **Flash UX** | Counts created vs skipped; optional redirect to an **offering** when **`offering_id`** is posted with the form. |

### 🧠 Defense Tip: How do you prevent crashing when mass uploading CSVs?
If a panelist asks: *"What happens if a coordinator accidentally uploads the same student CSV file twice? Does the database crash?"*
**Your Answer:** *"No—the import goes through **`StudentImportService`** and the **`StudentsImport`** Maatwebsite class, which uses **`firstOrCreate`** (or equivalent) per row on **`student_id`. Re-uploading mostly hits existing IDs so rows are skipped instead of throwing duplicate key errors."*


## 3. Offerings & enrollment

**Description:** **`[Controller]`** `ChairpersonOfferingController::store` validates **offer_code**, **subject_title**, **subject_code**, **faculty_id** (`users.faculty_id`), **academic_term_id**; creates **`Offering`**; auto-**`assignRole('coordinator')`** on the teacher when needed. **`enrollStudent`** / **`enrollMultipleStudents`** call **`$student->enrollInOffering($offering)`** (not raw `attach` only).

**`store` (excerpt):**
```php
$request->validate([
    'offer_code' => 'required|string|unique:offerings,offer_code',
    'subject_title' => 'required|string|max:255',
    'subject_code' => 'required|string|max:255',
    'faculty_id' => 'required|exists:users,faculty_id',
    'academic_term_id' => 'required|exists:academic_terms,id',
]);
$data = $request->only('offer_code', 'subject_title', 'subject_code', 'faculty_id', 'academic_term_id');
$offering = Offering::create($data);
$teacher = User::where('faculty_id', $data['faculty_id'])->first();
if ($teacher && !$teacher->hasRole('coordinator')) {
    $teacher->assignRole('coordinator');
}
```

**`enrollStudent` (excerpt):**
```php
$request->validate([
    'student_id' => 'required|exists:students,student_id',
]);
$offering = Offering::where('id', $offeringId)->firstOrFail();
$student = Student::where('student_id', $request->student_id)->firstOrFail();
$student->enrollInOffering($offering);
```

| Method | What it does |
|--------|----------------|
| **`store`** | **`[Controller]`** Creates offering row; ensures teacher gains **coordinator** Spatie role when first time. |
| **`enrollStudent`** | **`[Controller]`** Validates **`student_id`** as **natural key** `students.student_id`; **`[Model]`** **`enrollInOffering`** maintains pivot/enrollment rules. |

## 4. Academic terms (toggle active)

**Description:** **`[Controller]`** `AcademicTermController::toggleActive` uses a **DB transaction**, deactivates **all other** rows first, then flips the selected term—unless business rules block deactivating the last active term or activating an **archived** term.

**Core logic — `toggleActive` (excerpt):**
```php
if ($academicTerm->is_archived) {
    return redirect()->route('chairperson.academic-terms.index')
        ->with('error', 'Cannot activate an archived academic term.');
}
if ($academicTerm->is_active) {
    $hasOtherActiveTerm = AcademicTerm::where('id', '!=', $academicTerm->id)
        ->where('is_active', true)->exists();
    if (!$hasOtherActiveTerm) {
        return redirect()->route('chairperson.academic-terms.index')
            ->with('error', 'At least one academic term must remain active.');
    }
}

DB::transaction(function () use ($academicTerm) {
    AcademicTerm::where('id', '!=', $academicTerm->id)->update(['is_active' => false]);
    $academicTerm->update(['is_active' => !$academicTerm->is_active]);
});
```

| Block | What it does |
|--------|----------------|
| Archived guard | Cannot toggle active on archived terms. |
| “Last active” guard | Cannot turn off the only active term. |
| **Transaction** | Ensures **all others false** + **this row toggled** atomically. |

## 5. Critical Code Line-by-Line Breakdown (For 1000% Defense Readiness)

If your panelists want you to explain the code line-by-line, memorize these three most complex and critical Chairperson functions.

### A. Student CSV import (`[Controller]` → `[Service]`)
Panel Question: *"How do imports avoid duplicate rows and stay maintainable?"*

```php
// ChairpersonStudentController — thin controller
return app(StudentImportService::class)->importFromRequest($request, StudentImportService::MODE_CHAIRPERSON);

// StudentImportService — validates CSV, runs Maatwebsite import class
$request->validate(['file' => 'required|file|mimes:csv|max:10240']);
Excel::import(new StudentsImport($offeringId), $request->file('file'));
```

| # | What it does |
|---|----------------|
| 1 | **`[Controller]`** Delegates all branching to **`StudentImportService`** (single responsibility). |
| 2–3 | **`[Service]`** Enforces **10MB CSV**; **`StudentsImport`** encapsulates **`firstOrCreate` / upserts** per row (see `app/Imports/StudentsImport.php`). |
| 4 | Returns flash or redirects (with **offering** redirect when **`offering_id`** present). |

### B. Toggling the active term (`[Controller]` `AcademicTermController@toggleActive`)
Panel Question: *"How do you keep term activation consistent?"*

```php
DB::transaction(function () use ($academicTerm) {
    AcademicTerm::where('id', '!=', $academicTerm->id)->update(['is_active' => false]);
    $academicTerm->update(['is_active' => !$academicTerm->is_active]);
});
```

| # | What it does |
|---|----------------|
| 1 | **`DB::transaction`** wraps deactivate-all + toggle so partial updates cannot strand the app with **zero** active terms (guards above still apply). |
| 2–3 | Set every **other** row **`is_active = false`**, then flip **this** row. |

### C. Enrolling a student (`[Controller]` + model helper)
Panel Question: *"How is a student linked to an offering?"*

```php
$request->validate([
    'student_id' => 'required|exists:students,student_id',
]);
$offering = Offering::where('id', $offeringId)->firstOrFail();
$student = Student::where('student_id', $request->student_id)->firstOrFail();
$student->enrollInOffering($offering);
```

| # | What it does |
|---|----------------|
| 1–2 | Validate **`student_id`** against **`students.student_id`** (string key), not only numeric PK. |
| 3–4 | Load **`Offering`** and **`Student`**. |
| 5 | **`enrollInOffering`** on **`Student`** centralizes pivot writes / any placement rules. |

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

---

## 7. 🎤 The "Cheat Sheet" Defense Script
If a panelist points at these functions and asks you to explain them line-by-line without reading the syntax, use these exact scripts:

### A. CSV mass import (`ChairpersonStudentController@upload` → `StudentImportService`)
**The Code:**
```php
public function upload(Request $request) {
    return app(StudentImportService::class)->importFromRequest($request, StudentImportService::MODE_CHAIRPERSON);
}
// Dedupe/row mapping lives in app/Imports/StudentsImport.php (Maatwebsite Excel).
```
**Panel Question:** *"What happens if a coordinator accidentally uploads the same student CSV file twice? Does the database crash with duplicate errors?"*
* **The Goal:** To mass-import hundreds of students quickly without breaking the database.
* **The Process:** Loop through each row and check if the `student_id` already exists using Eloquent's `firstOrCreate`.

> *"Sir, the database will not crash because we handle de-duplication inside the backend upload loop.* 
> *The most important part is the Eloquent method called `firstOrCreate`. For every row, the system looks at the primary key, which is the Student ID. If it finds that ID in the database already, it simply skips that row entirely. If it doesn't find the ID, it inserts the new student and encrypts their default password. This prevents fatal Duplicate Entry SQL errors."*

### B. Toggling the active term (`[Controller]` — use full guards + transaction)
**The Code:**
```php
DB::transaction(function () use ($academicTerm) {
    AcademicTerm::where('id', '!=', $academicTerm->id)->update(['is_active' => false]);
    $academicTerm->update(['is_active' => !$academicTerm->is_active]);
});
```
**Panel Question:** *"How do you ensure only one semester is active at a time?"*
* **The Goal:** To switch the active academic term globally.
* **The Process:** Force all other terms to false before setting the requested term to true.

> *"Sir, to guarantee there is only ever one active semester, the system executes a raw UPDATE query targeting all academic terms where the ID does NOT match the one selected, and forces their `is_active` status to false. Once the rest are deactivated, it takes the specific term the user clicked on and updates it to true."*

### C. Enrolling a student (`ChairpersonOfferingController@enrollStudent`)
**The Code:**
```php
$request->validate(['student_id' => 'required|exists:students,student_id']);
$offering = Offering::where('id', $offeringId)->firstOrFail();
$student = Student::where('student_id', $request->student_id)->firstOrFail();
$student->enrollInOffering($offering);
```
**Panel Question:** *"Explain how a student is attached to a class section using Pivot Tables."*
* **The Goal:** To link a student to a class offering (section).
* **The Process:** Use Eloquent's `attach()` method to create a Many-to-Many record.

> *"Sir, the relationship between Students and Offerings is Many-to-Many. When a chairperson enrolls a student, the system fetches the class offering and accesses its `students()` relationship. It then calls the `attach()` method, which automatically creates a new row in the `offering_student` pivot table, linking the two IDs securely."*

### D. Role updates (`[Controller]` `RoleController@update` + **`[JS]`**)
**The Code (server):**
```php
$request->validate([
    'roles' => 'required|array',
    'roles.*' => 'in:chairperson,coordinator,teacher,adviser,panelist',
]);
$user = User::where('faculty_id', $faculty_id)->when($activeTerm, ...)->firstOrFail();
$user->assignRoles($request->roles);
```
**Panel Question:** *"How are faculty roles stored?"*
* **The Goal:** Spatie **roles** per **`User`**, scoped to active-term **semester** when resolving the row.
* **Process:** The roles index **`[JS]`** collects checked **`roles[]`** checkboxes and POSTs to **`/chairperson/roles/{userId}`** (ensure route exists).

> *"Sir, the UI uses checkbox sets per faculty row; the controller validates **`roles[]`** and calls **`assignRoles`** on the **`User`**. Self-demotion would be a product rule to add in `RoleController` if required—current code focuses on **`faculty_id` + semester** lookup and Spatie sync."*

---

## 9. Methods Used (Simple Terms)

- `pluck('column')` - Gets only one column from query results (like IDs) instead of full rows.
- `whereIn('column', [...])` - Filters records that match any value in a list.
- `whereNotIn('column', [...])` - Excludes records that match values in a list.
- `withCount('relation')` - Adds relation counts without manual loops.
- `whereHas('relation', fn...)` - Filters by conditions inside related tables.
- `first()` - Gets the first matching row or `null`.
- `findOrFail(id)` - Finds by ID or throws a not found error.
- `create([...])` - Inserts a new database row.
- `update([...])` - Updates fields of existing rows.
- `delete()` - Removes a row.
- `exists()` - Returns true/false if any matching row exists.
- `collect([...])` - Creates a Laravel collection for chainable operations.
- `map(fn...)` - Transforms each item in a collection.
- `sortBy(...)` - Sorts collection items by one or more rules.
- `take(n)` - Gets only the first `n` items.
- `values()` - Reindexes collection keys to clean 0..n numbering.
- `unique('field')` - Removes duplicates by field.
- `toArray()` - Converts data to plain PHP array.
- `return back()->withErrors(...)->withInput()` - Returns user to form with errors and keeps previous input.
- `DB::beginTransaction()/commit()/rollback()` - All-or-nothing database save flow.
- `Carbon::parse(...)` - Converts date/time text into a date object.
- `response()->json([...])` - Returns JSON for frontend scripts.

### Symbols / Operators (Q&A quick guide)
- `?` (ternary) - Short if/else in one line.
- `??` (null coalescing) - Use fallback value when left side is `null`.
- `?:` (elvis shorthand) - Use left side if truthy, otherwise fallback.
- `?->` (null-safe operator) - Access property/method only if object is not `null`.
- `=>` - Key/value separator in arrays, and short function arrow syntax.
- `===` - Strict comparison (value and type must match).

## 10. Quick Oral Cheat Sheet (Top 10 Terms)

1. **`pluck`** - "Get only one column, like IDs, from many rows."
2. **`whereIn`** - "Filter rows that match any value in a list."
3. **`withCount`** - "Add relationship counts directly from DB, no manual loops."
4. **`whereHas`** - "Filter by a condition inside a related table."
5. **`create`** - "Insert a new database row quickly."
6. **`update`** - "Modify existing row values."
7. **`exists`** - "Fast yes/no check if a matching record exists."
8. **`sortBy`** - "Order results by a rule, like least workload first."
9. **`take(2)`** - "Get only the first two ranked candidates."
10. **`DB transaction`** - "All-or-nothing save: commit if all pass, rollback if any fail."

---

## 11. Codebase-aligned reference (defense study)

This section matches the **current CapTracks codebase** (`routes/web.php`, controllers under `app/Http/Controllers`, Blade under `resources/views/chairperson`). Use it alongside the narrative sections above; where older snippets in this document differ (for example manual CSV loops vs `StudentImportService`), treat **this section as authoritative** for filenames and behavior.

### Routing and security

- Chairperson routes use middleware **`auth`** + **`role:chairperson`** (**`[Type: Middleware]`** from Laravel + Spatie), URL prefix **`/chairperson`**, route names **`chairperson.*`** (see `routes/web.php`).
- **Checked in this repo:** `php artisan route:list --name=roles` reports **no** named routes matching `roles` — **`RoleController` is not wired** in `routes/web.php` until you register `chairperson.roles.index` / `chairperson.roles.update` (or equivalent).
- Users are normal Laravel **`web`** guard users (faculty/staff accounts).
- There is also a shortcut route **`/chairperson-dashboard`** that points to the same dashboard controller—useful to mention if panelists ask about “legacy” links.

### Controllers (what each does)

All rows are **`[Type: Controller]`** (`app/Http/Controllers/`).

| Controller | Purpose |
|------------|---------|
| **`ChairpersonDashboardController`** | Returns `view('dashboards.chairperson')`. Loads the **active** academic term, last few notifications (`Notification::visibleToWebUser`), and aggregates: groups that have an adviser, faculty count (scoped by active term semester when present), pending defense schedules (`scheduled`), offering count, total/completed defenses. |
| **`ChairpersonOfferingController`** | Full CRUD for **offerings** (offer code, subject, teacher `faculty_id`, academic term). **Business rule:** assigning a teacher to an offering may **`assignRole('coordinator')`** if they do not already have it; changing or deleting offerings may **`removeRole('coordinator')`** when the teacher no longer has any offerings. Deletes: **`detach`** all students from pivot first, then delete offering. Enrollment uses **`$student->enrollInOffering($offering)`** (not only raw `attach`). Supports single enroll, **bulk** enroll (`student_ids` JSON/array), unenrolled-students screen, remove one student. |
| **`ChairpersonFacultyController`** | Lists faculty (roles: teacher, adviser, panelist, coordinator, chairperson), filtered by active term semester. **CSV upload** delegates to **`FacultyImportService`**. Manual create/update, assign/remove coordinator role, delete. Validates email and faculty ID uniqueness **per semester**. Creates `User`, Spatie roles, and **`UserAccount`** for login when applicable. |
| **`ChairpersonStudentController`** | Student list: search, course filter, sort (whitelist), pagination; scoped to active term **`semester`** when set. **CSV export** streamed response. Edit/update (optional password bcrypt). Single delete; **bulk delete** via hidden form + JSON `student_ids`. **`upload()`** → **`StudentImportService::importFromRequest(..., MODE_CHAIRPERSON)`** (Maatwebsite Excel + `StudentsImport`, not a manual `file()` loop in the controller). |
| **`ChairpersonController`** | Notifications page + JSON endpoints: mark read (one / all / multiple), delete (one / multiple). Uses **`NotificationService`** for bulk mark-read; always scopes with **`Notification::visibleToWebUser`** so users cannot update others’ rows. |
| **`AcademicTermController`** | Lives **inside** the chairperson route group: resource CRUD for terms, **`toggleActive`**, **`toggleArchived`**. Drives which semester is “current” for filtering across the app. |
| **`CalendarController::chairpersonCalendar`** | Calendar view for chairperson (defense-related events—confirm against your branch). |

**Dashboard view path:** `ChairpersonDashboardController` uses **`resources/views/dashboards/chairperson.blade.php`**, not under `chairperson/`.

### Services tied to chairperson flows

All rows are **`[Type: Service]`** (`app/Services/`).

| Service | Where | Role |
|---------|--------|------|
| **`StudentImportService`** | `ChairpersonStudentController::upload` | Validates CSV (`mimes:csv`, max 10MB), runs Excel import, counts created vs existing students, redirects with flash messages. Coordinator mode (elsewhere) adds offering/term checks. |
| **`FacultyImportService`** | `ChairpersonFacultyController::upload` | Batch faculty import from uploaded file. |
| **`NotificationService`** | `ChairpersonController` | `markAsRead`, `markMultipleAsRead`—shared notification logic. |

**Related (not chairperson-only):** `StudentEnrollmentService` is used from **`StudentsImport`** / seeders for enrollment helpers—not invoked directly from chairperson controllers, but explains how imports tie into enrollment.

### Blade views that include JavaScript (`resources/views/chairperson/`)

Rows are **`[Type: JS]`** (Blade-embedded scripts).

| View | Typical JS behavior |
|------|---------------------|
| **`notifications.blade.php`** | AJAX/fetch to chairperson notification routes; CSRF; updates read state without full page reload where implemented. |
| **`students/index.blade.php`** | Select-all / indeterminate checkboxes; bulk delete form posting `student_ids` to **`chairperson.students.bulk-delete`**; single delete sets dynamic form action; Bootstrap tooltips. |
| **`students/import.blade.php`** | Client-side file size check (10MB), submit disable + loading state, optional auto-dismiss alerts. |
| **`offerings/unenrolled-students.blade.php`** | Selecting students and submitting enroll / bulk enroll. |
| **`offerings/edit.blade.php`** | Form UX (validation hints, dependent behavior—confirm in file). |
| **`roles/index.blade.php`** | Interactive role UI if present in your deployment. |

Other chairperson views (`teachers/*`, `offerings/*`, `academic-terms/*`) are mostly server-rendered forms and tables; **inline scripts are concentrated** in the rows above.

### Naming and layout notes

- Controller class file may appear as **`ChairpersonController.php`** (PSR-4 / disk casing)—keep class name and filename consistent in documentation.
- **`chairperson.roles.*`** — `RoleController` and `chairperson/roles/index.blade.php` expect named routes. As of a recent repo check, **`Route::` entries for `RoleController` may be absent** from `routes/web.php` even though the controller exists. Run `php artisan route:list --name=roles` and add chairperson routes if missing (otherwise `route('chairperson.roles.index')` will fail).

### One-line defense summary

**Chairperson** manages **term context**, **offerings** (with automatic **coordinator** role sync), **faculty** (import + manual), **students** (CRUD, export, CSV import via service), **notifications**, and **academic terms**—with enrollment bridging students to offerings so coordinators can operate downstream.

---

## 12. JavaScript in chairperson views (full code + explanations)

All subsections below are **`[Type: JS]`** — **actual inline JavaScript** from `resources/views/chairperson/` as of this documentation pass. Paths are from the project root.

Each subsection includes a **Line-by-line walkthrough** table: each numbered row explains **one statement or tight block** (opening braces, closing braces, and blank lines are omitted unless they matter). Read the table **next to** the code fence above it.

### How this ties to Laravel

- **`fetch`** calls hit named routes (`route(...)`) with **`X-CSRF-TOKEN`** or form **`_token`** so Laravel accepts POST/DELETE/PATCH.
- **JSON bodies** (`JSON.stringify`) match controller expectations (`notification_ids`, etc.).
- Forms use **`@csrf`** and **`@method('DELETE')`**; hidden forms avoid cluttering the UI until JS submits them.

---

### `chairperson/notifications.blade.php`

**Purpose:** Bulk-select notifications, mark all read, delete one or many—without leaving the page until a successful response triggers **`location.reload()`**.

```javascript
function updateSelectedCount() {
    const selectedCount = document.querySelectorAll('.notification-checkbox:checked').length;
    const selectedCountElement = document.getElementById('selectedCount');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    if (selectedCountElement) selectedCountElement.textContent = selectedCount;
    if (deleteSelectedBtn) deleteSelectedBtn.disabled = selectedCount === 0;
}
function markAllAsRead() {
    if (!confirm('Mark all notifications as read?')) return;
    fetch('{{ route("chairperson.notifications.mark-all-read") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    }).then(response => response.json()).then(data => {
        if (data.success) location.reload();
        else alert('Error marking notifications as read: ' + (data.message || 'Unknown error'));
    }).catch(() => alert('Error marking notifications as read'));
}
function deleteNotification(notificationId) {
    if (!confirm('Are you sure you want to delete this notification?')) return;
    const urlTemplate = '{{ route("chairperson.notifications.delete", ["notification" => "__ID__"]) }}';
    fetch(urlTemplate.replace('__ID__', notificationId), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    }).then(response => response.json()).then(data => {
        if (data.success) location.reload();
        else alert('Error deleting notification: ' + (data.message || 'Unknown error'));
    }).catch(() => alert('Error deleting notification'));
}
function deleteSelected() {
    const selectedIds = Array.from(document.querySelectorAll('.notification-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) return;
    if (!confirm(`Are you sure you want to delete ${selectedIds.length} selected notification(s)?`)) return;
    fetch('{{ route("chairperson.notifications.delete-multiple") }}', {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
        body: JSON.stringify({ notification_ids: selectedIds })
    }).then(response => response.json()).then(data => {
        if (data.success) location.reload();
        else alert('Error deleting notifications: ' + (data.message || 'Unknown error'));
    }).catch(() => alert('Error deleting notifications'));
}
function refreshNotifications() { location.reload(); }
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.notification-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });
    }
    document.querySelectorAll('.notification-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    updateSelectedCount();
});
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1 | `function updateSelectedCount() {` | Defines a function the page calls whenever selection changes. |
| 2 | `const selectedCount = document.querySelectorAll('.notification-checkbox:checked').length;` | Uses a CSS selector to count how many notification row checkboxes are currently checked. |
| 3 | `const selectedCountElement = document.getElementById('selectedCount');` | Gets the span that shows “X of Y selected”. |
| 4 | `const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');` | Gets the bulk delete button so it can be enabled/disabled. |
| 5 | `if (selectedCountElement) selectedCountElement.textContent = selectedCount;` | Guard: only updates text if the element exists; sets visible count. |
| 6 | `if (deleteSelectedBtn) deleteSelectedBtn.disabled = selectedCount === 0;` | Disables delete when nothing is selected; enables when at least one. |
| 7 | `}` | Ends `updateSelectedCount`. |
| 8 | `function markAllAsRead() {` | Starts the handler for “mark all as read”. |
| 9 | `if (!confirm('Mark all notifications as read?')) return;` | Browser confirm dialog; exits if user clicks Cancel. |
| 10 | `fetch('{{ route(...) }}', {` | HTTP request to Laravel named route (Blade prints real URL). |
| 11 | `method: 'POST',` | POST matches route definition for mark-all-read. |
| 12 | `headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', ... }` | Sends CSRF token so Laravel accepts the request as legitimate. |
| 13 | `}).then(response => response.json())` | Parses JSON body from the HTTP response. |
| 14 | `.then(data => {` | Handles the decoded JSON object (`data`). |
| 15 | `if (data.success) location.reload();` | On success flag from server, reloads page to show updated read state. |
| 16 | `else alert('Error...' + (data.message \|\| 'Unknown error'));` | Shows server error message or a fallback string. |
| 17 | `}).catch(() => alert(...))` | Network/parse failure path. |
| 18 | `}` | Ends `markAllAsRead`. |
| 19 | `function deleteNotification(notificationId) {` | Deletes one notification by primary key. |
| 20 | `if (!confirm(...)) return;` | User must confirm before delete. |
| 21 | `const urlTemplate = '...__ID__...'` | Blade builds a URL pattern with placeholder `__ID__`. |
| 22 | `fetch(urlTemplate.replace('__ID__', notificationId), {` | Substitutes real id into REST-style delete URL. |
| 23 | `method: 'DELETE',` | Matches Laravel `Route::delete`. |
| 24 | `headers: { 'X-CSRF-TOKEN': ..., 'Content-Type': 'application/json' }` | Same CSRF header as POST; JSON content-type optional for DELETE body-less calls. |
| 25 | `.then(response => response.json())` | Parses JSON body (`success`, `message`). |
| 26 | `.then(data => { if (data.success) location.reload(); else alert(...) })` | Same success/error UX as mark-all. |
| 27 | `.catch(() => alert('Error deleting notification'))` | Network failure handler. |
| 28 | `function deleteSelected() {` | Bulk delete for all checked rows. |
| 29 | `const selectedIds = Array.from(...).map(cb => cb.value);` | Converts NodeList to array and collects each checkbox’s `value` (notification id). |
| 30 | `if (selectedIds.length === 0) return;` | Safety: nothing to do if empty. |
| 31 | `if (!confirm(\`...\${selectedIds.length}...\`)) return;` | Confirms count in message template literal. |
| 32 | `body: JSON.stringify({ notification_ids: selectedIds })` | Request body matches controller validation `notification_ids` array. |
| 33 | `function refreshNotifications() { location.reload(); }` | Manual refresh helper (e.g. toolbar button). |
| 34 | `document.addEventListener('DOMContentLoaded', function() {` | Runs after HTML is parsed so elements exist. |
| 35 | `const selectAll = document.getElementById('selectAll');` | Header checkbox that toggles all rows. |
| 36–38 | `if (selectAll) { selectAll.addEventListener('change', ...` | When header checkbox changes, set every `.notification-checkbox` to same checked state. |
| 37 | `updateSelectedCount();` | Refresh count after bulk toggle. |
| 40–42 | `document.querySelectorAll('.notification-checkbox').forEach(...` | Each row checkbox calls `updateSelectedCount` on change. |
| 43 | `updateSelectedCount();` | Initial count on page load. |
| 44 | `});` | Closes the `DOMContentLoaded` callback and the `addEventListener` call. |

**Summary:** `updateSelectedCount` keeps the toolbar in sync. `fetch` calls use CSRF and Laravel’s JSON responses; bulk delete sends **`notification_ids`**. `selectAll` mirrors row checkboxes.

---

### `chairperson/students/index.blade.php`

**Purpose:** Single-student delete and **bulk delete** via hidden forms; Bootstrap tooltips; integrates with the **global delete confirmation modal** in `resources/views/partials/delete-confirm-modal.blade.php` when layouts include it (forms with `@method('DELETE')` or `data-confirm-type="delete"` are intercepted so the user confirms in a modal before submit).

```javascript
function deleteStudent(studentId, studentName) {
    const form = document.getElementById('deleteStudentForm');
    form.action = `/chairperson/students/${studentId}`;
    form.dataset.confirmType = 'delete';
    form.dataset.confirmMessage = `Are you sure you want to delete student "${studentName}"? This action cannot be undone and will remove the student from all offerings and groups.`;
    form.requestSubmit();
}
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    initializeBulkSelection();
});
function initializeBulkSelection() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const studentCheckboxes = document.querySelectorAll('.student-checkbox');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        studentCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        updateBulkDeleteButton();
    });
    studentCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllState();
            updateBulkDeleteButton();
        });
    });
    function updateSelectAllState() {
        const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
        const totalCount = studentCheckboxes.length;
        if (checkedCount === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (checkedCount === totalCount) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
            selectAllCheckbox.checked = false;
        }
    }
    function updateBulkDeleteButton() {
        const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
        if (checkedCount > 0) {
            deleteSelectedBtn.style.display = 'inline-block';
            selectedCountSpan.textContent = checkedCount;
        } else {
            deleteSelectedBtn.style.display = 'none';
        }
    }
    deleteSelectedBtn.addEventListener('click', function() {
        const checkedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
        const studentIds = Array.from(checkedCheckboxes).map(cb => cb.value);
        if (studentIds.length === 0) {
            alert('Please select at least one student to delete.');
            return;
        }
        const form = document.getElementById('bulkDeleteForm');
        const input = document.getElementById('bulkDeleteStudentIds');
        if (form && input) {
            input.value = JSON.stringify(studentIds);
            form.dataset.confirmType = 'delete';
            form.dataset.confirmMessage = `Are you sure you want to delete ${studentIds.length} selected student(s)? This action cannot be undone and will remove the students from all offerings and groups.`;
            form.requestSubmit();
        } else {
            alert('Error: Form not found. Please refresh the page and try again.');
        }
    });
}
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1 | `function deleteStudent(studentId, studentName) {` | Called from each row’s delete control; receives DB `student_id` and display name for the confirm message. |
| 2 | `const form = document.getElementById('deleteStudentForm');` | Gets the hidden `<form>` that posts DELETE (includes `@csrf` and `@method('DELETE')`). |
| 3 | `form.action = \`/chairperson/students/${studentId}\`;` | Sets REST URL so Laravel routes to `ChairpersonStudentController@destroy`. |
| 4 | `form.dataset.confirmType = 'delete';` | Marks this submit as a delete for **`delete-confirm-modal`**: modal intercepts submit until user confirms. |
| 5 | `form.dataset.confirmMessage = \`...\`;` | Custom message shown in the modal body (includes student name). |
| 6 | `form.requestSubmit();` | Programmatically submits the form (triggers same validation/submit flow as a click). |
| 7 | `document.addEventListener('DOMContentLoaded', ...)` | Waits until DOM is ready before querying nodes. |
| 8 | `var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));` | Converts NodeList to Array (older Bootstrap 5 pattern for IE compatibility). |
| 9 | `var tooltipList = tooltipTriggerList.map(... new bootstrap.Tooltip(...)` | Initializes Bootstrap tooltips on every trigger element. |
| 10 | `initializeBulkSelection();` | Sets up bulk checkbox behavior after tooltips. |
| 11 | `function initializeBulkSelection() {` | Contains all bulk-select logic in one closure. |
| 12–15 | `const selectAllCheckbox ... studentCheckboxes ... deleteSelectedBtn ... selectedCountSpan` | Caches DOM refs used repeatedly. |
| 16 | `selectAllCheckbox.addEventListener('change', ...)` | When header “select all” changes, copy its `checked` to every `.student-checkbox`. |
| 17 | `updateBulkDeleteButton();` | Shows/hides bulk delete and updates count. |
| 18–22 | `studentCheckboxes.forEach(checkbox => { checkbox.addEventListener('change', ...` | Any row change updates tri-state header and button visibility. |
| 23 | `function updateSelectAllState() {` | Computes whether selection is none / partial / all. |
| 24–25 | `if (checkedCount === 0) { ... checked = false` | No rows checked: clear header checkbox, not indeterminate. |
| 26–27 | `else if (checkedCount === totalCount)` | All rows: header checked, not indeterminate. |
| 28–30 | `else { selectAllCheckbox.indeterminate = true; ... }` | Partial: Gmail-style dash state (`indeterminate`). |
| 31 | `function updateBulkDeleteButton() {` | Toggles bulk delete button and writes count into `selectedCountSpan`. |
| 32 | `deleteSelectedBtn.style.display = count > 0 ? 'inline-block' : 'none';` | Hides button when nothing selected. |
| 33 | `deleteSelectedBtn.addEventListener('click', ...` | Click handler for bulk delete (not inline HTML onclick). |
| 34 | `const studentIds = Array.from(checkedCheckboxes).map(cb => cb.value);` | Collects each selected row’s `value` (student id string). |
| 35–37 | `if (studentIds.length === 0) { alert(...); return; }` | Extra guard even though button hidden when empty. |
| 38–39 | `bulkDeleteForm` / `bulkDeleteStudentIds` | Targets hidden form posting to `chairperson.students.bulk-delete`. |
| 40 | `input.value = JSON.stringify(studentIds);` | Server expects JSON string in `student_ids` field. |
| 41–42 | `form.dataset.confirmType / confirmMessage` | Same global modal pattern as single delete. |
| 43 | `form.requestSubmit();` | Submits bulk delete form after modal confirms. |
| 44–46 | `else { alert('Error: Form not found...') }` | Defensive: missing DOM ids if markup changed. |
| 47–48 | Nested `}` closures | Close click handler, `initializeBulkSelection`, end of script block. |

**Summary:** Single delete sets **`form.action`** and **`dataset`** for the shared modal. Bulk mode fills **`#bulkDeleteStudentIds`** with **`JSON.stringify`** of selected ids. Header checkbox uses **`indeterminate`** for partial selection.

---

### `chairperson/students/import.blade.php`

**Purpose:** Client-side guardrails before POST to **`chairperson.upload-students`**; improves perceived performance with a loading state.

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('importForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    const fileInput = document.getElementById('fileInput');
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        const maxSize = 10 * 1024 * 1024;
        if (file && file.size > maxSize) {
            alert('File size exceeds 10MB limit. Please choose a smaller file.');
            this.value = '';
            return;
        }
    });
    form.addEventListener('submit', function() {
        if (!fileInput.files[0]) {
            alert('Please select a file to upload.');
            return false;
        }
        submitBtn.disabled = true;
        btnText.classList.add('d-none');
        btnLoading.classList.remove('d-none');
        setTimeout(() => {
            submitBtn.disabled = false;
        }, 100);
    });
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.classList.contains('alert-success') || alert.classList.contains('alert-danger')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 8000);
    });
});
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1 | `document.addEventListener('DOMContentLoaded', ...)` | Runs when DOM is ready so `#importForm`, `#submitBtn`, `#fileInput` exist. |
| 2–6 | `const form ... submitBtn ... btnText ... btnLoading ... fileInput` | Caches elements; assumes markup ids match Blade template. |
| 7 | `fileInput.addEventListener('change', function() {` | Fires when user picks a different file. |
| 8 | `const file = this.files[0];` | First (only) file from `<input type="file">`. |
| 9 | `const maxSize = 10 * 1024 * 1024;` | 10 MB in bytes (matches backend max upload rule). |
| 10–13 | `if (file && file.size > maxSize) { alert(...); this.value = ''; return; }` | Client-side rejection; clears input so user must pick again. |
| 14 | `form.addEventListener('submit', function() {` | Runs immediately before native form POST navigates away. |
| 15–18 | `if (!fileInput.files[0]) { alert(...); return false; }` | Blocks submit if no file chosen (extra UX guard beyond HTML `required`). |
| 19 | `submitBtn.disabled = true;` | Prevents double-submit while upload runs. |
| 20 | `btnText.classList.add('d-none');` | Hides normal button label (Bootstrap utility class). |
| 21 | `btnLoading.classList.remove('d-none');` | Shows spinner/text sibling inside button. |
| 22–24 | `setTimeout(() => { submitBtn.disabled = false; }, 100);` | Briefly re-enables button if navigation fails silently (edge-case recovery). |
| 25 | `const alerts = document.querySelectorAll('.alert');` | Finds flash-message alerts Laravel rendered. |
| 26 | `alerts.forEach(alert => { setTimeout(() => { ... }, 8000); });` | After 8 seconds, dismiss success/error alerts only. |
| 27 | `if (alert.classList.contains('alert-success') \|\| ... 'alert-danger'))` | Does not auto-close neutral info alerts. |
| 28 | `const bsAlert = new bootstrap.Alert(alert); bsAlert.close();` | Bootstrap 5 JS API to animate alert away. |

**Summary:** Client-side size check matches **`StudentImportService`**. Submit handler swaps button into loading state. Alerts fade after 8s via **`bootstrap.Alert`**.

---

### `chairperson/offerings/unenrolled-students.blade.php`

**Purpose:** Wire **Bootstrap modals** for enrolling one student or many; bulk selection mirrors the students index pattern.

```javascript
document.addEventListener('DOMContentLoaded', function () {

    /* ── Single Add Modal ─────────────────────────── */
    document.querySelectorAll('.single-add-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('singleStudentName').textContent = this.dataset.studentName;
            document.getElementById('singleOfferingCode').textContent = this.dataset.offeringCode;
            document.getElementById('singleAddStudentId').value      = this.dataset.studentId;
            document.getElementById('singleAddForm').action          = this.dataset.action;
        });
    });

    document.getElementById('singleConfirmBtn').addEventListener('click', function () {
        document.getElementById('singleAddForm').submit();
    });

    /* ── Bulk Add Modal ───────────────────────────── */
    const selectAllCheckbox = document.getElementById('selectAll');
    const studentCheckboxes = document.querySelectorAll('.student-checkbox');
    const addSelectedBtn    = document.getElementById('addSelectedBtn');
    const selectedCountSpan = document.getElementById('selectedCount');

    selectAllCheckbox.addEventListener('change', function () {
        studentCheckboxes.forEach(cb => cb.checked = this.checked);
        updateBulkAddButton();
    });

    studentCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            updateSelectAllState();
            updateBulkAddButton();
        });
    });

    function updateSelectAllState() {
        const checked = document.querySelectorAll('.student-checkbox:checked').length;
        const total   = studentCheckboxes.length;
        selectAllCheckbox.indeterminate = checked > 0 && checked < total;
        selectAllCheckbox.checked       = checked === total;
    }

    function updateBulkAddButton() {
        const count = document.querySelectorAll('.student-checkbox:checked').length;
        addSelectedBtn.style.display = count > 0 ? 'inline-block' : 'none';
        selectedCountSpan.textContent = count;
    }

    /* Populate bulk modal content before it shows */
    document.getElementById('bulkConfirmModal').addEventListener('show.bs.modal', function () {
        const checked      = document.querySelectorAll('.student-checkbox:checked');
        const studentIds   = Array.from(checked).map(cb => cb.value);
        const studentNames = Array.from(checked).map(cb => cb.dataset.studentName);

        document.getElementById('bulkCount').textContent = studentIds.length;

        const list = document.getElementById('bulkStudentList');
        list.innerHTML = studentNames.map(name =>
            `<li class="py-1 border-bottom"><i class="fas fa-user text-primary me-2 small"></i>${name}</li>`
        ).join('');

        document.getElementById('bulkAddStudentIds').value = JSON.stringify(studentIds);
    });

    document.getElementById('bulkConfirmBtn').addEventListener('click', function () {
        document.getElementById('bulkAddForm').submit();
    });

    updateBulkAddButton();
});
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1 | `document.addEventListener('DOMContentLoaded', function () {` | Entry point after DOM ready. |
| 2–9 | `.single-add-btn` loop | Each “add” button carries **`data-student-name`**, **`data-offering-code`**, **`data-student-id`**, **`data-action`** (POST URL). Click copies into modal text fields and hidden student id + sets **`singleAddForm.action`**. |
| 10 | `singleConfirmBtn` click | Submits **`singleAddForm`** (actual POST enroll one student). |
| 11–14 | `selectAllCheckbox`, `studentCheckboxes`, etc. | Same bulk selection pattern as student list. |
| 15–17 | `selectAllCheckbox.addEventListener('change', ...)` | Toggles every row checkbox to match header. |
| 18–21 | Per-checkbox `change` | Calls **`updateSelectAllState`** and **`updateBulkAddButton`**. |
| 22–27 | `updateSelectAllState` | Sets **`indeterminate`** when some but not all checked; full check when all. |
| 28–33 | `updateBulkAddButton` | Shows “Add selected” only when count > 0; updates count span. |
| 34 | `bulkConfirmModal` `show.bs.modal` | Bootstrap fires before modal becomes visible—good place to refresh dynamic content. |
| 35–36 | `checked`, `studentIds`, `studentNames` | Reads currently checked boxes; ids from `.value`, names from **`dataset.studentName`**. |
| 37 | `bulkCount.textContent = studentIds.length` | Shows how many will be enrolled. |
| 38–40 | `list.innerHTML = studentNames.map(...).join('')` | Builds `<li>` HTML list for confirmation (template literal per name). |
| 41 | `bulkAddStudentIds.value = JSON.stringify(studentIds)` | Hidden input for **`enrollMultipleStudents`** body/query. |
| 42 | `bulkConfirmBtn` → `bulkAddForm.submit()` | Final POST after user confirms in modal. |
| 43 | `updateBulkAddButton();` | Initial visibility state on load. |

**Summary:** Single enroll wires **`data-action`** into form **`action`**. Bulk enroll rebuilds modal body on each open and posts JSON ids.

---

### `chairperson/offerings/edit.blade.php`

**Purpose:** When the chairperson picks a **subject title** from a dropdown, auto-fill **subject code** for consistency (reduces typos).

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const subjectTitleSelect = document.getElementById('subject_title');
    const subjectCodeInput = document.getElementById('subject_code');

    const subjectCodeMapping = {
        'Capstone Project I': 'CS-CAP-401',
        'Capstone Project II': 'CS-CAP-402',
        'Thesis I': 'CS-THS-301',
        'Thesis II': 'CS-THS-302'
    };

    subjectTitleSelect.addEventListener('change', function() {
        const selectedTitle = this.value;

        if (selectedTitle && subjectCodeMapping[selectedTitle]) {
            subjectCodeInput.value = subjectCodeMapping[selectedTitle];
        } else {
            subjectCodeInput.value = '';
        }
    });

    const initialTitle = subjectTitleSelect.value;
    if (initialTitle && subjectCodeMapping[initialTitle]) {
        subjectCodeInput.value = subjectCodeMapping[initialTitle];
    }
});
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1 | `document.addEventListener('DOMContentLoaded', ...)` | Ensures `#subject_title` and `#subject_code` exist. |
| 2–3 | `subjectTitleSelect`, `subjectCodeInput` | Dropdown for human-readable title; text input for formal code. |
| 4–9 | `subjectCodeMapping = { ... }` | JavaScript object: keys are exact title strings from `<option>` values; values are department codes. |
| 10 | `subjectTitleSelect.addEventListener('change', ...)` | Runs whenever user picks another title. |
| 11 | `const selectedTitle = this.value;` | Current `<select>` value string. |
| 12–15 | `if (selectedTitle && subjectCodeMapping[selectedTitle])` | If title exists in map, auto-fill code; else clear code (custom title). |
| 16–18 | `const initialTitle = subjectTitleSelect.value` | Handles edit form repopulated by Laravel after validation error. |
| 19–21 | `if (initialTitle && subjectCodeMapping[initialTitle])` | On first paint, sync code field without waiting for user interaction. |

**Summary:** Lookup object avoids mismatched title/code pairs; **`initialTitle`** block keeps fields consistent on server-rendered edit pages.

---

### `chairperson/roles/index.blade.php`

**Purpose:** Update Spatie **roles** per user via **`fetch`** to `POST /chairperson/roles/{userId}` without full page reload; live badge preview under each row.

```javascript
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.update-user-roles').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const userName = this.dataset.userName;
            const button = this;
            button.disabled = true;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Updating...';

            const checkedRoles = [];
            const checkboxes = document.querySelectorAll(`input[name="roles[${userId}][]"]:checked`);
            checkboxes.forEach(checkbox => {
                checkedRoles.push(checkbox.value);
            });

            if (checkedRoles.length === 0) {
                alert('Please select at least one role for ' + userName);
                button.disabled = false;
                button.innerHTML = originalText;
                return;
            }

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'POST');
            checkedRoles.forEach(role => {
                formData.append('roles[]', role);
            });

            fetch(`/chairperson/roles/${userId}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCurrentRolesDisplay(userId);

                    showNotification('success', `Roles updated successfully for ${userName}`);
                } else {
                    showNotification('error', 'Error updating roles: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'Error updating roles. Please try again.');
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = originalText;
            });
        });
    });
    document.querySelectorAll('.role-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const label = this.nextElementSibling;
            if (this.checked) {
                label.classList.add('text-primary');
            } else {
                label.classList.remove('text-primary');
            }
            const userId = this.name.match(/roles\[(.*?)\]/)[1];
            updateCurrentRolesDisplay(userId);
        });
    });
});

function updateCurrentRolesDisplay(userId) {
    const container = document.getElementById(`current-roles-${userId}`);
    if (!container) return;
    const checkedRoles = [];
    const checkboxes = document.querySelectorAll(`input[name="roles[${userId}][]"]:checked`);
    checkboxes.forEach(checkbox => {
        checkedRoles.push(checkbox.value);
    });

    const roleColors = {
        'chairperson': 'danger',
        'coordinator': 'primary',
        'teacher': 'secondary',
        'adviser': 'success',
        'panelist': 'warning'
    };

    if (checkedRoles.length === 0) {
        container.innerHTML = '<span class="text-muted"><i class="fas fa-user-slash me-1"></i>No roles assigned</span>';
    } else {
        const badges = checkedRoles.map(role =>
            `<span class="badge bg-${roleColors[role] || 'secondary'} me-1 mb-1">${role.charAt(0).toUpperCase() + role.slice(1)}</span>`
        ).join('');
        container.innerHTML = badges;
    }
}

function showNotification(type, message) {
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
```

**Line-by-line walkthrough**

| # | Code | What it does |
|---|------|----------------|
| 1 | Outer `DOMContentLoaded` | Registers handlers after DOM parse. |
| 2 | `.update-user-roles` forEach | One “Update roles” button per faculty row. |
| 3 | `const userId = this.dataset.userId` | From `data-user-id` on button—scopes checkboxes for that user. |
| 4 | `const userName = this.dataset.userName` | For alerts and validation messages. |
| 5–8 | `button.disabled = true`, spinner HTML | Prevents double-click; shows loading feedback. |
| 9–12 | `checkedRoles` array, `querySelectorAll(\`input[name="roles[${userId}][]"]:checked\`)` | Collects Spatie role names checked for **this** user only. |
| 13–18 | `if (checkedRoles.length === 0)` | Blocks empty submission; restores button. |
| 19–23 | `FormData`, `_token`, `_method` | Mimics Laravel form: CSRF + POST (route may expect POST body). |
| 24 | `formData.append('roles[]', role)` | Repeated keys build PHP array `roles[]`. |
| 25 | `fetch(\`/chairperson/roles/${userId}\`, { method: 'POST', body: formData })` | AJAX update endpoint for one user id. |
| 26 | `'X-Requested-With': 'XMLHttpRequest'` | Marks request as AJAX (optional server-side detection). |
| 27–31 | `.then(response => response.json())` | Expects JSON not redirect. |
| 32–36 | `if (data.success)` | Calls **`updateCurrentRolesDisplay`** + **`showNotification`**. |
| 37–40 | `.catch` / `.finally` | Logs error; always restores button label and enabled state. |
| 41–51 | `.role-checkbox` change listener | Highlights label text when checked; regex **`roles[(.*?)]`** extracts userId from input `name`; refreshes badge preview live. |
| 52 | `function updateCurrentRolesDisplay(userId)` | Syncs read-only badge container under row. |
| 53–56 | `getElementById(\`current-roles-${userId}\`)` | Per-user span id in Blade. |
| 57–59 | Re-query checked boxes for that user | Same selector pattern as save. |
| 60–66 | `roleColors` map | Bootstrap contextual colors per role slug. |
| 67–74 | `if (checkedRoles.length === 0)` vs badges | Empty state message vs **`map`** of badge spans joined into HTML string. |
| 75 | `function showNotification(type, message)` | Creates dismissible Bootstrap alert at top of `.container`. |
| 76 | `existingAlerts.forEach(alert => alert.remove())` | Avoids stacking duplicate alerts. |
| 77–81 | `createElement`, `innerHTML`, `insertBefore` | Injects new alert as first child. |
| 82–86 | `setTimeout(..., 5000)` | Auto-remove after 5 seconds if still attached. |

**Summary:** Role updates use **`FormData`** + **`roles[]`** for Laravel; **`updateCurrentRolesDisplay`** mirrors checkbox state into badges; **`showNotification`** gives transient feedback without full reload.

**Defense tip:** Mention **`X-Requested-With: XMLHttpRequest`** so Laravel can treat the request as AJAX if your controller branches on that.
