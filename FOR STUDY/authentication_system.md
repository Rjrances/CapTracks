# 🔐 CapTrack Authentication System — Complete Technical Reference

This document explains every part of how authentication works in CapTrack — from the moment a user types their ID on the login page, to how the system decides what they can see, and what happens when they log out.

---

## 🏗️ Big Picture — The Dual-Guard Architecture

CapTrack has **one login page** but **two completely separate authentication pipelines** running underneath it. This is called a **Dual-Guard system** in Laravel.

```
User types ID + Password → clicks "Login Now"
              │
              ▼
    AuthController::login()
              │
    ┌─────────┴──────────┐
    ▼                    ▼
attemptFacultyLogin()   attemptStudentLogin()
checks user_accounts    checks student_accounts
table (faculty IDs)     table (student IDs)
    │                    │
    ▼                    ▼
Auth::login()           Auth::guard('student')->login()
  (web guard)             (student guard)
    │                    │
    ▼                    ▼
Session key:            Session key:
login_web_xxxxx         login_student_xxxxx
    │                    │
    ▼                    ▼
Redirect to their       Redirect to
role's dashboard        /student/dashboard
```

The key insight: both guards use **separate session keys** inside the same browser session cookie. This is why a student and a coordinator can be logged in at the same time on the same browser — they don't interfere with each other.

---

## 📦 The Two Guards — `config/auth.php`

This file is the foundation. It tells Laravel that two separate authentication systems exist.

```php
'guards' => [

    // Guard 1: For all faculty (chairperson, coordinator, adviser, panelist)
    'web' => [
        'driver'   => 'session',
        'provider' => 'users',       // reads from App\Models\User
    ],

    // Guard 2: For students only
    'student' => [
        'driver'   => 'session',
        'provider' => 'students',    // reads from App\Models\StudentAccount
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => App\Models\User::class,
    ],
    'students' => [
        'driver' => 'eloquent',
        'model'  => App\Models\StudentAccount::class,
    ],
],
```

---

## 🗄️ Database Tables — Where Credentials Live

There are **four tables** involved in authentication. They come in pairs: one for credentials, one for the actual profile.

### Pair 1: Students

| Table | Model File | Purpose |
|---|---|---|
| `student_accounts` | `app/Models/StudentAccount.php` | Stores the login credentials |
| `students` | `app/Models/Student.php` | Stores the actual student profile (name, ID, group, etc.) |

**`student_accounts` columns:**
| Column | What it stores |
|---|---|
| `student_id` | The ID number the student types to log in |
| `email` | Stored for reference, not used for login |
| `password` | Bcrypt-hashed. `null` = first-time login, no password set yet |
| `must_change_password` | Boolean. `true` = student is locked out until they change password |

**Why two separate tables?** So that if a student's login credentials need to be reset, it doesn't touch their academic records (group, milestones, submissions) stored in `students`.

---

### Pair 2: Faculty

| Table | Model File | Purpose |
|---|---|---|
| `user_accounts` | `app/Models/UserAccount.php` | Stores the login credentials |
| `users` | `app/Models/User.php` | Stores the actual faculty profile (name, role, department, semester) |

**`user_accounts` columns:**
| Column | What it stores |
|---|---|
| `faculty_id` | The ID number the faculty types to log in |
| `email` | Stored for reference |
| `password` | Bcrypt-hashed |
| `must_change_password` | Boolean flag for forced password change |

---

## ⚙️ The Login Flow — `AuthController` Line by Line

**File:** `app/Http/Controllers/AuthController.php`

### Entry Point: `login(Request $request)`

```php
public function login(Request $request)
{
    $request->validate([
        'school_id' => ['required'],
        'password'  => ['nullable'],  // nullable because first-time students have no password
    ]);

    // Try faculty first
    $facultyLoginResponse = $this->attemptFacultyLogin($request);
    if ($facultyLoginResponse) {
        return $facultyLoginResponse;
    }

    // If not faculty, try student
    $studentLoginResponse = $this->attemptStudentLogin($request);
    if ($studentLoginResponse) {
        return $studentLoginResponse;
    }

    // Neither matched
    return back()->withErrors(['school_id' => 'Invalid School ID.']);
}
```

The system tries **faculty first**, then **student**. If neither `user_accounts` nor `student_accounts` has a matching ID, the user sees "Invalid School ID."

---

### Faculty Login: `attemptFacultyLogin()`

```php
private function attemptFacultyLogin(Request $request)
{
    // Step 1: Find the credential record by faculty_id
    $userAccount = UserAccount::where('faculty_id', $request->school_id)->first();
    if (!$userAccount) {
        return null;  // Not a faculty ID → fall through to student check
    }

    // Step 2: Validate the password
    $passwordError = $this->validatePasswordInput($request->password, $userAccount->password);
    if ($passwordError) {
        return $passwordError;
    }

    // Step 3: Resolve the correct User profile for this semester
    $resolvedUser = $this->resolveFacultyUserForLogin($userAccount);
    if (!$resolvedUser) {
        return back()->withErrors(['school_id' => 'No matching faculty profile found.']);
    }

    // Step 4: Log in on the web guard and regenerate session
    Auth::login($resolvedUser);
    $request->session()->regenerate();

    // Step 5: Redirect based on their role
    return $this->redirectBasedOnRole($resolvedUser->primary_role);
}
```

**Important step — `resolveFacultyUserForLogin()`:**

Faculty members can exist in multiple semesters (a coordinator for 2024-2025 and again for 2025-2026). This method finds the RIGHT profile for the current active semester:

```php
private function resolveFacultyUserForLogin(UserAccount $userAccount): ?User
{
    // First: try to find their profile for the currently active academic term
    $activeTerm = AcademicTerm::where('is_active', true)->first();
    if ($activeTerm) {
        $activeTermUser = User::where('faculty_id', $userAccount->faculty_id)
            ->where('semester', $activeTerm->semester)
            ->first();
        if ($activeTermUser) {
            return $activeTermUser;  // Found their current-semester profile
        }
    }

    // Fallback: if no active term match, return the most recently created profile
    return User::where('faculty_id', $userAccount->faculty_id)->latest('id')->first();
}
```

This means if no active term is set, the system still works — it just picks their latest profile.

---

### Student Login: `attemptStudentLogin()`

```php
private function attemptStudentLogin(Request $request)
{
    // Step 1: Find the credential record by student_id
    $studentAccount = StudentAccount::where('student_id', $request->school_id)->first();
    if (!$studentAccount) {
        return null;  // Not a student ID → login fails entirely
    }

    // Step 2: Check if a DIFFERENT student is already logged in on this browser
    if (Auth::guard('student')->check()) {
        $activeAccount = Auth::guard('student')->user();  // Get the currently active StudentAccount from session
        if ($activeAccount->student_id !== $studentAccount->student_id) {
            $activeName = $activeAccount->student->name ?? 'Another student';
            return back()->withErrors([
                'school_id' => $activeName . ' is already logged in on this browser. Please log out first before switching accounts.'
            ]);
        }
    }

    // Step 3: First-time login check (must_change_password = true AND password is null)
    if ($studentAccount->must_change_password && is_null($studentAccount->password)) {
        Auth::guard('student')->login($studentAccount);
        $request->session()->regenerate();
        return redirect()->route('student.change-password')
            ->with('info', 'Welcome! Please set your password to continue.');
    }

    // Step 4: Validate the submitted password
    $passwordError = $this->validatePasswordInput($request->password, $studentAccount->password);
    if ($passwordError) {
        return $passwordError;
    }

    // Step 5: Log in on the student guard and regenerate session
    Auth::guard('student')->login($studentAccount);
    $request->session()->regenerate();

    return redirect()->route('student.dashboard');
}
```

#### Why does student login have a conflict check but faculty doesn't?

| | Faculty | Student |
|---|---|---|
| Conflict check | ❌ No | ✅ Yes |
| If 2nd account logs in | Silently replaces 1st session | Shows clear error with name |
| Why? | Faculty workstations are managed. Two coordinators open at once is not a real scenario. | Students may share computers. A clear error prevents confusion and data mix-up. |

---

### Password Validation: `validatePasswordInput()`

Shared by both faculty and student login paths:

```php
private function validatePasswordInput(?string $plainPassword, ?string $hashedPassword)
{
    // Block empty password submissions
    if (empty($plainPassword)) {
        return back()->withErrors(['password' => 'Password is required.']);
    }

    // Hash::check() extracts the salt from $hashedPassword, re-hashes $plainPassword
    // with that same salt, and compares — timing-safe, never reveals the actual hash
    if (empty($hashedPassword) || !Hash::check($plainPassword, $hashedPassword)) {
        return back()->withErrors(['password' => 'Incorrect password.']);
    }

    return null;  // null means "no error, proceed"
}
```

---

### Role-Based Redirect: `redirectBasedOnRole()`

After a successful faculty login, the system sends the user to the correct dashboard based on their role:

```php
private function redirectBasedOnRole($role)
{
    // Faculty with dual roles (coordinator + adviser) → coordinator wins
    if (is_array($role)) {
        if (in_array('coordinator', $role)) {
            return redirect()->route('coordinator.dashboard');
        }
        $role = $role[0];
    }

    return match ($role) {
        'chairperson'                    => redirect()->route('chairperson.dashboard'),
        'coordinator'                    => redirect()->route('coordinator.dashboard'),
        'adviser', 'panelist', 'teacher' => redirect()->route('adviser.dashboard'),
        'student'                        => redirect()->route('student.dashboard'),
        default                          => redirect('/login')->withErrors(['role' => 'Invalid role.']),
    };
}
```

Note: if a faculty has multiple roles (array), **coordinator takes priority** — they land on the coordinator dashboard and can switch to adviser view from there.

---

## 🔒 Password Change Flow

### Faculty Password Change
Available at `/change-password`. Accessible only if `Auth::check()` (web guard) is active:
1. Finds the `UserAccount` by `faculty_id`
2. Hashes the new password with `Hash::make()`
3. Saves and redirects back to their role's dashboard

### Student Password Change (First-Time)
Available at `/student/change-password`. Handled by `StudentPasswordController`.

The flow for a **first-time student** (imported via CSV with no password):
1. `must_change_password = true` AND `password = null` in `student_accounts`
2. On login, the system logs them into the student guard immediately
3. `CheckStudentPasswordChange` middleware intercepts every request except the change-password route
4. Student sets a password → `must_change_password` remains `true` but `password` is now set
5. From this point, the middleware check `must_change_password && is_null(password)` is `false`, so they pass through freely

---

## 🛡️ Middleware — Route Protection

Middleware registered in `bootstrap/app.php`:

```php
$middleware->alias([
    'studentauth'             => StudentAuthMiddleware::class,
    'student.password.change' => CheckStudentPasswordChange::class,
    'role'                    => RoleMiddleware::class,        // Spatie package
    'permission'              => PermissionMiddleware::class,  // Spatie package
]);
```

### `StudentAuthMiddleware` — `app/Http/Middleware/StudentAuthMiddleware.php`

Runs on **every `/student/*` request**. Two checks:

```php
// Check 1: Is anyone logged in on the student guard at all?
if (!Auth::guard('student')->check()) {
    return redirect('/login')->withErrors(['auth' => 'Please log in.']);
}

// Check 2: Is the logged-in account actually a student account?
$student = Auth::guard('student')->user();
if (!$student->isStudent()) {
    return redirect('/login')->withErrors(['auth' => 'Access denied. Student account required.']);
}
```

`isStudent()` is defined in `StudentAccount.php` and always returns `true` — it exists as a type-safety guard.

---

### `CheckStudentPasswordChange` — `app/Http/Middleware/CheckStudentPasswordChange.php`

Runs on **every `/student/*` request** (except the change-password routes themselves):

```php
if (Auth::guard('student')->check()) {
    $studentAccount = Auth::guard('student')->user();

    if ($studentAccount->must_change_password &&
        !$request->routeIs('student.change-password') &&
        !$request->routeIs('student.update-password')) {

        return redirect()->route('student.change-password')
            ->with('warning', 'You must change your password before continuing.');
    }
}
```

Even if a student is authenticated, if `must_change_password` is `true`, they are **redirected away from every single page** except the password change form. They cannot access dashboard, milestones, or anything else until the password is set.

---

### `role` Middleware — Spatie Permission Package

Runs on faculty route groups. Checks the `model_has_roles` database table:

```php
// Only coordinators and advisers can access coordinator routes
Route::middleware(['auth', 'role:coordinator|adviser'])->prefix('coordinator')...

// Only chairpersons can access chairperson routes
Route::middleware(['auth', 'role:chairperson'])->prefix('chairperson')...
```

`auth` checks the `web` guard session. `role:coordinator` checks the Spatie roles assigned to that `User` model in the database.

---

## 🔄 Full Route Protection Summary

```
/login                     → Public. No middleware.
/change-password           → Semi-public. Accessible if either guard is active.

/student/*                 → [StudentAuthMiddleware] + [CheckStudentPasswordChange]
/coordinator/*             → [auth] + [role:coordinator|adviser]
/chairperson/*             → [auth] + [role:chairperson]
/adviser/*                 → [auth]  (no role restriction — any logged-in faculty can access)
```

---

## 🌐 Multi-Role Browser Session Behavior

Same browser, multiple tabs:

| Combination | Works? | Reason |
|---|---|---|
| Student + Coordinator | ✅ Yes | `student` guard ≠ `web` guard → separate session keys |
| Student + Adviser | ✅ Yes | Different guards |
| Student + Chairperson | ✅ Yes | Different guards |
| Coordinator + Chairperson | ❌ No | Both use `web` guard → one overwrites the other |
| Coordinator + Adviser | ❌ No | Both use `web` guard |
| Panelist + Adviser | ❌ No | Both use `web` guard |
| Student A + Student B | ❌ No | Both use `student` guard → Student B's login shows error message |

**What happens when there's a conflict (same guard):**
- **Faculty conflict:** The second login silently replaces the first. Tab 1 gets a redirect to login on its next request.
- **Student conflict:** The system detects the active session, reads the name from `Auth::guard('student')->user()->student->name`, and shows: *"Henry Moore is already logged in on this browser. Please log out first before switching accounts."*

---

## 🚪 Logout — `logout()`

```php
public function logout(Request $request)
{
    Auth::logout();                         // Clears the web guard session (faculty)
    Auth::guard('student')->logout();       // Clears the student guard session
    $request->session()->invalidate();      // Destroys the entire PHP session (all data)
    $request->session()->regenerateToken(); // Issues a new CSRF token (security)
    return redirect('/login');
}
```

A single logout clears **both guards at once**. So no matter which role was active, everything is wiped cleanly. The session ID itself is also destroyed and a new one is created, preventing session fixation attacks.

---

## 📋 Quick Reference — How to Call Auth in Each Context

| Context | How to get the logged-in user | Returns |
|---|---|---|
| Faculty controller | `Auth::user()` | `User` model |
| Faculty controller | `Auth::check()` | `true`/`false` |
| Student controller | `Auth::guard('student')->user()` | `StudentAccount` model |
| Student controller | `Auth::guard('student')->check()` | `true`/`false` |
| Get student profile | `Auth::guard('student')->user()->student` | `Student` model |
| Get faculty profile | `Auth::user()` (already the `User` model) | `User` model |

---

## 🧩 Model Relationship Map

```
StudentAccount (student_accounts table)
    │ belongs to
    ▼
Student (students table)
    │ belongs to many
    ▼
Group → GroupMilestone → GroupMilestoneTask

UserAccount (user_accounts table)
    │ belongs to
    ▼
User (users table)
    │ has many roles via Spatie
    ▼
model_has_roles → roles table
(chairperson | coordinator | adviser | panelist | teacher)
```

**The separation of credential tables from profile tables is intentional:**
- Credentials can be reset without touching academic records
- A faculty member's `UserAccount` is shared across semesters; their `User` profile is recreated per term
- A student's `StudentAccount` is permanent; their `Student` profile holds all their capstone history
