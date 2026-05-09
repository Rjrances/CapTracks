# Login Logic and Validations (Final)

This file explains the current CapTracks login flow using the actual implementation in:

- `app/Http/Controllers/AuthController.php`
- `resources/views/auth/login.blade.php`
- `app/Http/Middleware/CheckStudentPasswordChange.php`

---

## 1) Login Form (UI)

Source: `resources/views/auth/login.blade.php`

```php
<form method="POST" action="/login" class="space-y-5">
    @csrf
    <div>
        <label for="school_id">ID Number</label>
        <input type="text" name="school_id" id="school_id" required />
    </div>
    <div>
        <label for="password">
            Password <span>(Leave blank for first-time login or students)</span>
        </label>
        <input type="password" name="password" id="password" />
    </div>
    <button type="submit">Login Now</button>
</form>
```

Line-by-line:
1. Uses `POST /login`.
2. `@csrf` prevents CSRF attacks.
3. `school_id` is the unified identifier for both faculty and students.
4. Password field is optional at HTML level to support first-time student setup cases.
5. Submit sends credentials to `AuthController@login`.

---

## 2) Main Login Entry Point

Source: `AuthController@login`

```php
public function login(Request $request)
{
    $request->validate([
        'school_id' => ['required'],
        'password' => ['nullable'],
    ]);

    $facultyLoginResponse = $this->attemptFacultyLogin($request);
    if ($facultyLoginResponse) {
        return $facultyLoginResponse;
    }

    $studentLoginResponse = $this->attemptStudentLogin($request);
    if ($studentLoginResponse) {
        return $studentLoginResponse;
    }

    return back()->withErrors(['school_id' => 'Invalid School ID.']);
}
```

Line-by-line:
1. Validate request shape: `school_id` required, `password` nullable.
2. Try faculty login first (looks up `UserAccount` by `faculty_id`).
3. If faculty login returns a redirect/error response, return it immediately.
4. If no faculty account matched, try student login (`StudentAccount` by `student_id`).
5. If student login returns a response, return it.
6. If neither account type matched, send `Invalid School ID`.

---

## 3) Faculty Login Logic

Source: `AuthController::attemptFacultyLogin`

```php
private function attemptFacultyLogin(Request $request)
{
    $userAccount = UserAccount::where('faculty_id', $request->school_id)->first();
    if (!$userAccount) {
        return null;
    }

    $passwordError = $this->validatePasswordInput($request->password, $userAccount->password);
    if ($passwordError) {
        return $passwordError;
    }

    $resolvedUser = $this->resolveFacultyUserForLogin($userAccount);
    if (!$resolvedUser) {
        return back()->withErrors(['school_id' => 'No matching faculty profile found for this account.']);
    }

    Auth::login($resolvedUser);
    $request->session()->regenerate();

    return $this->redirectBasedOnRole($resolvedUser->primary_role);
}
```

Line-by-line:
1. Look up credentials in `user_accounts` using entered ID.
2. Return `null` if no faculty account exists (lets main flow try student).
3. Validate password against hash.
4. Resolve the concrete `User` profile (active-term preferred).
5. If no profile found for the account, return validation error.
6. Log in under default `web` guard.
7. Regenerate session ID (session fixation protection).
8. Redirect to role dashboard.

---

## 4) Active-Term Faculty Resolution

Source: `AuthController::resolveFacultyUserForLogin`

```php
private function resolveFacultyUserForLogin(UserAccount $userAccount): ?User
{
    $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();

    if ($activeTerm) {
        $activeTermUser = User::where('faculty_id', $userAccount->faculty_id)
            ->where('semester', $activeTerm->semester)
            ->first();

        if ($activeTermUser) {
            return $activeTermUser;
        }
    }

    return User::where('faculty_id', $userAccount->faculty_id)
        ->latest('id')
        ->first();
}
```

Line-by-line:
1. Get active academic term if it exists.
2. Prefer user profile matching same `faculty_id` and active term semester.
3. If none found, fallback to latest faculty profile by `faculty_id`.

---

## 5) Student Login Logic

Source: `AuthController::attemptStudentLogin`

```php
private function attemptStudentLogin(Request $request)
{
    $studentAccount = StudentAccount::where('student_id', $request->school_id)->first();
    if (!$studentAccount) {
        return null;
    }

    if (Auth::guard('student')->check()) {
        $activeAccount = Auth::guard('student')->user();
        if ($activeAccount->student_id !== $studentAccount->student_id) {
            $activeName = $activeAccount->student->name ?? 'Another student';
            return back()->withErrors([
                'school_id' => $activeName . ' is already logged in on this browser. Please log out first before switching accounts.'
            ]);
        }
    }

    if ($studentAccount->must_change_password && is_null($studentAccount->password)) {
        Auth::guard('student')->login($studentAccount);
        $request->session()->regenerate();
        return redirect()->route('student.change-password')
            ->with('info', 'Welcome! Please set your password to continue.');
    }

    $passwordError = $this->validatePasswordInput($request->password, $studentAccount->password);
    if ($passwordError) {
        return $passwordError;
    }

    Auth::guard('student')->login($studentAccount);
    $request->session()->regenerate();
    return redirect()->route('student.dashboard');
}
```

Line-by-line:
1. Look up account from `student_accounts` by student ID.
2. Return `null` if not student account (lets caller continue).
3. If another student is already logged in this browser, block account switching and show clear message.
4. If first-time account (`must_change_password` + no stored hash), log in then force password setup page.
5. Otherwise validate password hash.
6. On success, log in to `student` guard.
7. Regenerate session.
8. Redirect to student dashboard.

---

## 6) Password Validation Method

Source: `AuthController::validatePasswordInput`

```php
private function validatePasswordInput(?string $plainPassword, ?string $hashedPassword)
{
    if (empty($plainPassword)) {
        return back()->withErrors(['password' => 'Password is required.']);
    }

    if (empty($hashedPassword) || !Hash::check($plainPassword, $hashedPassword)) {
        return back()->withErrors(['password' => 'Incorrect password.']);
    }

    return null;
}
```

Line-by-line:
1. Reject empty password (except first-time branch already handled earlier).
2. Reject invalid hash comparison.
3. Return `null` when valid.

---

## 7) Role-based Redirect Mapping

Source: `AuthController::redirectBasedOnRole`

```php
return match ($role) {
    'chairperson' => redirect()->route('chairperson.dashboard'),
    'coordinator' => redirect()->route('coordinator.dashboard'),
    'adviser', 'panelist', 'teacher' => redirect()->route('adviser.dashboard'),
    'student' => redirect()->route('student.dashboard'),
    default => redirect('/login')->withErrors(['role' => 'Invalid role.']),
};
```

Line-by-line:
1. Chairperson goes to chairperson dashboard.
2. Coordinator goes to coordinator dashboard.
3. Adviser/panelist/teacher share adviser dashboard space.
4. Student goes to student dashboard.
5. Unknown role returns error to login.

---

## 8) Post-login Student Password Gate Middleware

Source: `CheckStudentPasswordChange`

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

Line-by-line:
1. Runs only for authenticated student sessions.
2. Checks `must_change_password`.
3. Allows only change-password routes while flag is true.
4. Redirects all other student route requests until password update is done.

---

## 9) Logout Logic

Source: `AuthController::logout`

```php
Auth::logout();
Auth::guard('student')->logout();
$request->session()->invalidate();
$request->session()->regenerateToken();
return redirect('/login');
```

Line-by-line:
1. Logout `web` guard.
2. Logout `student` guard.
3. Invalidate full session.
4. Regenerate CSRF token.
5. Return user to login page.

---

## 10) Validation and Error Summary

At login, users may see:

- `Invalid School ID.` when no account exists in both faculty and student account tables.
- `Password is required.` when non-first-time login has empty password.
- `Incorrect password.` on hash mismatch.
- `No matching faculty profile found for this account.` when account exists but no usable `users` profile.
- Student switching guard message when a different student is already logged into same browser session.

---

## 11) Quick Test Checklist

1. Faculty login with correct ID/password -> role dashboard redirect.
2. Student first-time account (must change + null hash) -> forced `/student/change-password`.
3. Student wrong password -> error shown.
4. Different student tries login while one student active in same browser -> blocked with explicit message.
5. Logout clears both guards and returns to `/login`.
