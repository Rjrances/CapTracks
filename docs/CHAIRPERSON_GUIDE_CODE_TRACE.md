# Chairperson area — guide to routes, controllers, and services

This document summarizes **what each chairperson-facing controller and related service is for** in CapTracks. It mirrors the structure of **`docs/COORDINATOR_AND_ADVISER_CODE_TRACE.md`** and **`docs/STUDENT_GUIDE_CODE_TRACE.md`**.

**Route definitions:** `routes/web.php` — group:

`Route::middleware(['auth', 'role:chairperson'])->prefix('chairperson')->name('chairperson.')`.

**Access:** Users must be authenticated **web** users (`auth`) with Spatie role **`chairperson`**.

**Legacy alias:** `Route::get('/chairperson-dashboard', ...)` → **`chairperson-dashboard`** (same dashboard controller as `chairperson.dashboard` in practice — verify which URL your menus use).

---

## 1. Route map (chairperson prefix)

| Feature | HTTP | Route name | Controller |
|---------|------|------------|------------|
| Dashboard | GET | `chairperson.dashboard` | `ChairpersonDashboardController@index` |
| Offerings (CRUD) | GET/POST/PUT/DELETE | `chairperson.offerings.*` | `ChairpersonOfferingController` |
| Remove / enroll students on offering | DELETE/GET/POST | `chairperson.offerings.remove-student`, `.unenrolled-students`, `.enroll-student`, `.enroll-multiple-students` | `ChairpersonOfferingController` |
| Teachers / faculty registry | * | `chairperson.teachers.*` | `ChairpersonFacultyController` |
| Assign coordinator to teacher | POST | `chairperson.teachers.assign-coordinator`, `.remove-coordinator` | `ChairpersonFacultyController` |
| Students registry | GET/PUT/DELETE | `chairperson.students.*` | `ChairpersonStudentController` |
| Bulk delete students | DELETE | `chairperson.students.bulk-delete` | `ChairpersonStudentController` |
| CSV / upload students | GET/POST | `chairperson.upload-form`, `chairperson.upload-students` | View + `ChairpersonStudentController@upload` |
| Export students | GET | `chairperson.students.export` | `ChairpersonStudentController@export` |
| Notifications | GET/POST/DELETE | `chairperson.notifications*` | `ChairpersonController` |
| Calendar | GET | `chairperson.calendar` | `CalendarController@chairpersonCalendar` |

---

## 2. Controllers — purpose of each public action

### `ChairpersonDashboardController`

- **`index`** — High-level stats for the active academic term: groups with advisers, faculty headcount, defense-related counts, offerings count, defense completion counts; recent notifications visible to the logged-in user. View: **`dashboards.chairperson`**.

### `ChairpersonController`

Focused on **notifications** (same pattern as other roles):

- **`notifications`** — Lists notifications via **`Notification::visibleToWebUser($user)`**.
- **`markNotificationAsRead`**, **`markAllNotificationsAsRead`**, **`markMultipleAsRead`**, **`deleteNotification`**, **`deleteMultiple`** — Delegate persistence to **`NotificationService`** after verifying the notification is visible to this user (prevents cross-user access).

Also exposes **`getActiveTerm()`** helper for reuse.

### `ChairpersonOfferingController`

Manages **course offerings** (subject instances per term):

- **`index`** — List/filter offerings (term-scoped as implemented).
- **`create` / `store`** — Create offering with validation (subject codes, term linkage, etc.).
- **`show` / `edit` / `update` / `destroy`** — Standard CRUD for one offering.
- **`removeStudent`** — Drop one student’s enrollment from an offering.
- **`showUnenrolledStudents`** — Students eligible to add (same term/roster rules).
- **`enrollStudent` / `enrollMultipleStudents`** — Attach students to the offering (often used after imports).

### `ChairpersonFacultyController`

Manages **faculty / teacher** records used across capstone:

- **`index`** — Search/list teachers.
- **`create` / `createManual`** — CSV-oriented vs manual creation flows.
- **`store` / `storeManual`** — Persist new faculty; **`store`** may accept bulk upload via **`FacultyImportService`** (see below).
- **`upload`** — Endpoint used by the import form to process files.
- **`edit` / `update`** — Update profile/assignment fields.
- **`assignCoordinator` / `removeCoordinator`** — Link or unlink a **coordinator** role/relationship for that faculty member (implementation details in controller — ties offerings to coordinators).
- **`destroy`** — Remove a faculty record when business rules allow.

### `ChairpersonStudentController`

Central **student registry** for the institution side:

- **`index`** — Paginated/filterable student list.
- **`export`** — Download roster (CSV/Excel depending on implementation).
- **`edit` / `update`** — Edit student metadata.
- **`destroy`** — Delete one student.
- **`bulkDelete`** — Delete many IDs in one request.
- **`upload`** — Single entry point for bulk import: it only delegates to **`StudentImportService::importFromRequest($request, StudentImportService::MODE_CHAIRPERSON)`** (see §4). No import logic lives in the controller itself.

The **`chairperson.upload-form`** route returns **`resources/views/chairperson/students/import.blade.php`** before POSTing to **`upload-students`**. You can open that page with **`?offering_id=...`** in the query string; the form then posts a hidden **`offering_id`**, and a successful import redirects to **that offering’s show page** with a message that students were enrolled (see **`StudentImportService`** redirect rules).

---

## 3. Services used by chairperson flows

| Service | Typical entry | Purpose |
|---------|----------------|---------|
| **`StudentImportService`** | `ChairpersonStudentController@upload` | Parse CSV/Excel, validate rows, create or update students, attach offerings/enrollments. |
| **`FacultyImportService`** | `ChairpersonFacultyController` (bulk paths) | Same idea for faculty roster files. |
| **`NotificationService`** | `ChairpersonController` notification actions | Consistent mark-read/delete behavior. |

**Not chairperson-exclusive but related:**

- **`StudentEnrollmentService`** — Used inside **import/jobs** (`StudentsImport`, seeders) to attach students to offerings in bulk.
- **`MilestoneAssignmentService`** — Used when coordinators assign milestone **templates** to groups (`MilestoneTemplateController`); affects students indirectly via Kanban data.

---

## 4. Student CSV import — upsert, “new” rows, updates, and enrollment

This is the full path from the upload form to the database. There is **no** separate “sync only changes” or “diff against last file” feature: **each import reads the entire CSV** and runs **one `model()` call per data row** (batched/chunked by Maatwebsite Excel for performance).

### 4.1 Request path

| Step | What runs |
|------|-----------|
| 1 | `POST chairperson.upload-students` → **`ChairpersonStudentController@upload`**. |
| 2 | **`StudentImportService::importFromRequest`** — validates **`file`**: required, **`.csv` only**, max **10MB**; empty file is rejected. |
| 3 | **`new StudentsImport($offeringId)`** where **`$offeringId`** = optional **`offering_id`** form/query field (used for redirect + legacy bulk enroll on that offering). |
| 4 | **`Excel::import($import, $file)`** (Maatwebsite / Laravel-Excel) — drives **`StudentsImport`**. |
| 5 | After the file finishes, the service reads counters from the import object and sets a **flash `success`** message (or **`error`** on validation / exceptions). |

**Coordinator re-use:** the same **`StudentImportService`** is used from the coordinator classlist import with **`MODE_COORDINATOR`**, which adds checks that the target offering belongs to the active term and the logged-in coordinator. The **row processing** in **`StudentsImport`** is the same.

### 4.2 How an existing student is recognized (and why re-import is safe)

In **`App\Imports\StudentsImport::model()`** (the heart of the import):

1. Load **`Student`** by **`student_id`** from the row:  
   `Student::where('student_id', $row['student_id'])->first()`.
2. If not found **and** the row has an **email**, try:  
   `Student::where('email', $row['email'])->first()`.  
   So a returning student can be matched by **ID first**, or by **email** if the ID was wrong/empty in theory — in practice IDs are required and normalized to 10 digits.

If a row matches an existing student, the code **does not skip** the row. It **always**:

- Builds the same **attribute** array (name parts, email, `school_year`, `semester` slot via **`ImportAcademicFieldsResolver`**, course, year level, `offer_code`, etc.).
- Calls **`$student->fill($attributes)`**.
- If **`$student->isDirty()`** — any column differs from what is already stored — it **`save()`** and increments **`$existingStudentsChangedCount`**.
- It **always** increments **`$updatedStudentsCount`** for that existing match, even when nothing changed (so the summary can say “X matched with no field changes”).

**Implication for “I edited the CSV and re-uploaded”:** any cell you change for an existing **`student_id`** is written on the next import, as long as validation passes. If the CSV row is **identical** to the database, **`isDirty()`** is false: no `UPDATE` query, and the student counts toward “existing, unchanged” in the flash message.

**Implication for “newly added” people:** a row is **created** only when **no** student exists for that **`student_id`** (and the email fallback does not find someone). Then **`new Student(...); $student->save()`** runs and **`$createdStudentsCount`** increases. So “newly added” in the message means **new database rows from this file**, not “rows that weren’t in the previous file” (the app does not remember previous files).

### 4.3 Student accounts and passwords

**`ensureStudentAccount()`** uses **`StudentAccount::firstOrCreate(['student_id' => ...], [...])`**. If the import **changes** the student’s email, the account’s email is updated. Import **does not** email students and **does not** reset passwords; the UI copy in **`import.blade.php`** says new students use **“Email me a temporary password”** on the student login page.

### 4.4 Enrollment after import (offer codes)

When **`afterImport`** fires (Excel **`AfterImport`** event):

1. **`StudentEnrollmentService::enrollStudentsByOfferCode(collect($this->importedStudents))`** — for **every** student touched in this import, if **`offer_code`** is set on the model, resolve **`Offering`** by **`offer_code`** and enroll via **`$student->enrollInOfferingByCode()`**. Results are **logged** (success / failure / offering not found); failures do not always surface in the chairperson flash message beyond the high-level success string.
2. If the import was constructed with **`$this->offeringId`** (hidden field from **`?offering_id=`**), a **second path** runs: all **`importedStudentIds`** from that run are loaded and **`$student->enrollInOffering($offering)`** is called for the **specific** offering. That covers “import from offering page” behavior even when **`offer_code`** column is redundant.

So **automatic enrollment** is **post-processing**, not a separate CSV-only step: it depends on **`offer_code`** on the student row and/or the **`offering_id`** passed into the import.

### 4.5 Validation and academic columns

Row rules live in **`StudentsImport::rules()`** and **`prepareForValidation()`**: e.g. **`student_id`** must be **10 digits** (padded with leading zeros if shorter), **`semester`** must match an **`academic_terms.semester`** value (full term labels in the semester column are rejected — use **`school_year`** + short slot **`1st` / `2nd` / `summer`**), **`offer_code`** must exist on **`offerings.offer_code`** when present. **`ImportAcademicFieldsResolver`** normalizes **`school_year`** and semester slot from the row.

### 4.6 Flash summary text

**`StudentImportService`** builds the user-visible sentence from:

- **`getCreatedStudentsCount()`** — new students.
- **`getUpdatedStudentsCount()`** — existing rows processed (includes unchanged).
- **`getExistingStudentsChangedCount()`** — existing rows that actually **`save()`**’d.
- **`getExistingStudentsUnchangedCount()`** — derived as updated − changed.

If **both** created and updated are zero, import is treated as failure (“no student rows could be processed”).

---

## 5. JavaScript on chairperson pages (including import)

Chairperson screens are primarily **server-rendered Blade + Bootstrap**. There is **no** dedicated `resources/js/chairperson*.js` bundle in **`resources/js`** (global **`resources/js/app.js`** only pulls in **`bootstrap.js`**).

### `chairperson/students/import.blade.php` (inline script)

| Behavior | Purpose |
|----------|---------|
| **`change` on file input** | Client-side check: if size **> 10MB**, alert and clear input (mirrors server validation). |
| **`submit` on form** | Requires a file; **disables** the submit button and swaps label to a **spinner** (“Importing…”). **Note:** a **`setTimeout(..., 100)`** re-enables the button after 100ms while the form still submits — the main goal is immediate feedback; the request is a normal **full page POST**, not AJAX. |
| **Alert auto-close** | After **8 seconds**, tries to close **success** / **danger** alerts via **`bootstrap.Alert`** (optional UX polish). |

There is **no** fetch/XHR import progress: the browser waits for **`StudentImportService`** to finish and then loads the **redirect** response with flash data.

For other chairperson views, search **`resources/views/chairperson/`** for `<script` if you need to trace behavior.

---

## 6. How this ties to students

| Chairperson action | Effect on student experience |
|--------------------|------------------------------|
| Create offering + enroll students | Students appear in the correct class roster for groups and milestones. |
| Import faculty + assign coordinator | Coordinators/advisers appear in workflows (invitations, defense scheduling). |
| Maintain accurate student records | Login/import/classlist features stay aligned with **`students`** table data. |

For **student Kanban and AJAX**, see **`docs/STUDENT_GUIDE_CODE_TRACE.md`** §5.

For **student import upsert and enrollment**, see **§4** above.
