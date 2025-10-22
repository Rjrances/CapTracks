# Authentication System - Technical Documentation

## Overview

The CapTracks authentication system manages secure login, registration, and password management for two types of users:
- **Faculty**: Teachers, coordinators, advisers, panelists, and chairpersons
- **Students**: Capstone project students

The system uses Laravel's built-in authentication with **dual guards** to handle both user types separately.

---

## Controller

### AuthController
**File:** `app/Http/Controllers/AuthController.php`

**Purpose:** Handles all authentication operations including login, logout, registration, and password management.

---

## Functions

### 1. `showLoginForm()`

**What it does:** Displays the login page

**Parameters:** None

**Returns:** Login form view

**Access:** Public (anyone can access)

**View:** `resources/views/auth/login.blade.php`

**Form fields:**
- School ID (faculty_id or student_id)
- Password

---

### 2. `login(Request $request)`

**What it does:** Authenticates users and logs them into the system

**Parameters:**
- `school_id` - Faculty ID or Student ID (required)
- `password` - User password (required for existing accounts)

**Validation:**
- School ID is required
- Password is nullable (for first-time student login)

**Returns:** 
- Success: Redirects to role-specific dashboard
- Failure: Returns to login with error message

**Process Flow:**

```
1. Validate request data
   ↓
2. Search for Faculty account (UserAccount with faculty_id)
   ↓
   If FOUND:
   ├─ Validate password is provided
   ├─ Check password correctness
   ├─ Log in user with default Auth guard
   ├─ Regenerate session (security)
   └─ Redirect based on role (chairperson/coordinator/adviser/etc.)
   ↓
   If NOT FOUND:
3. Search for Student account (StudentAccount with student_id)
   ↓
   If FOUND:
   ├─ Check if first-time login (no password set)
   │  └─ If yes: Redirect to password change page
   ├─ Validate password is provided
   ├─ Check password correctness
   ├─ Log in student with 'student' guard
   ├─ Regenerate session (security)
   └─ Redirect to student dashboard
   ↓
   If NOT FOUND:
4. Return error "Invalid School ID"
```

**Security Features:**
- Validates password before login
- Uses `Hash::check()` for secure password comparison
- Regenerates session after login (prevents session fixation)
- Separate error messages for better user experience

**First-Time Student Login:**
- Students imported via CSV have no password initially
- System allows login without password on first attempt
- Immediately redirects to password change page
- Displays welcome message: "Please set your password to continue"

---

### 3. `redirectBasedOnRole($role)` (Private)

**What it does:** Determines where to send user after login based on their role

**Parameters:**
- `$role` - String or Array of role names

**Returns:** Redirect to appropriate dashboard

**Logic:**
```
If user has multiple roles:
├─ If 'coordinator' is among roles → Coordinator Dashboard
└─ Otherwise → Use first role in array

Single role routing:
├─ chairperson → Chairperson Dashboard
├─ coordinator → Coordinator Dashboard  
├─ adviser/panelist/teacher → Adviser Dashboard
├─ student → Student Dashboard
└─ unknown role → Login page with error
```

**Role Priority:**
- Coordinator role has highest priority if user has multiple roles
- Adviser, panelist, and teacher all use the same dashboard
- Invalid roles redirect back to login

**Dashboard Routes:**
- `chairperson.dashboard` - `/chairperson/dashboard`
- `coordinator.dashboard` - `/coordinator/dashboard`
- `adviser.dashboard` - `/adviser/dashboard`
- `student.dashboard` - `/student/dashboard`

---

### 4. `logout(Request $request)`

**What it does:** Logs out the current user and ends their session

**Parameters:** HTTP Request object

**Returns:** Redirect to login page

**Process:**
```
1. Logout from default Auth guard (faculty)
2. Logout from student guard (if student is logged in)
3. Invalidate current session (destroy session data)
4. Regenerate CSRF token (security)
5. Redirect to login page
```

**Security Features:**
- Logs out from BOTH guards (ensures clean logout)
- Invalidates session completely
- Regenerates CSRF token to prevent attacks
- No leftover authentication data

**Use Cases:**
- User clicks "Logout" button
- Session timeout
- Security-required logout

---

### 5. `showRegisterForm()`

**What it does:** Displays the registration page

**Parameters:** None

**Returns:** Registration form view

**Access:** Public (though typically used internally)

**View:** `resources/views/auth/register.blade.php`

**Form fields:**
- Name
- Email
- Password
- Password Confirmation
- Role (optional - only chairperson can set this)

---

### 6. `register(Request $request)`

**What it does:** Creates new user accounts

**Parameters:**
- `name` - Full name (required, max 255 characters)
- `email` - Email address (required, must be unique)
- `password` - Password (required, min 8 characters)
- `password_confirmation` - Password confirmation (must match)
- `role` - User role (optional, only for chairperson)

**Validation:**
- Name: Required, string, max 255 characters
- Email: Required, valid email format, unique across both user_accounts and student_accounts
- Password: Required, minimum 8 characters, must have confirmation
- Role: Optional, must be one of: student, coordinator, adviser, panelist, chairperson, teacher

**Returns:** Redirect to login page with success message

**Process Flow:**

```
1. Validate all input data
   ↓
2. Determine account type:
   ├─ If logged-in chairperson sets role → Use that role
   └─ Otherwise → Default to 'student'
   ↓
3. If creating STUDENT account:
   ├─ Create Student record
   │  ├─ student_id = current timestamp
   │  ├─ name = provided name
   │  └─ email = provided email
   ├─ Create StudentAccount record
   │  ├─ student_id = from Student record
   │  ├─ email = provided email
   │  └─ password = hashed password
   └─ Complete
   ↓
4. If creating FACULTY account:
   ├─ Get active academic term
   ├─ Generate faculty_id (100XXX format, random)
   ├─ Create User record
   │  ├─ name = provided name
   │  ├─ email = provided email
   │  ├─ birthday = 25 years ago (default)
   │  ├─ department = 'N/A'
   │  ├─ role = selected role
   │  ├─ faculty_id = generated ID
   │  └─ semester = active term or 'Unknown'
   ├─ Create UserAccount record
   │  ├─ faculty_id = from User record
   │  ├─ email = provided email
   │  ├─ password = hashed password
   │  └─ must_change_password = false
   └─ Complete
   ↓
5. Redirect to login with success message
```

**Security Features:**
- Passwords are hashed using `Hash::make()` (bcrypt)
- Email uniqueness checked across both user types
- Role assignment only allowed for chairperson
- Auto-generated IDs prevent conflicts

**Note:** Registration is primarily for internal use. Most users are added via CSV import.

---

### 7. `showChangePasswordForm()`

**What it does:** Displays the password change form

**Parameters:** None

**Returns:** Password change form view

**Access:** Requires authentication (student or faculty)

**View:** `resources/views/auth/change-password.blade.php`

**Security Check:**
- Must be logged in as student OR faculty
- If not logged in, redirects to login page

**Form fields:**
- New Password
- Confirm New Password

**Use Cases:**
- First-time student login (forced)
- Voluntary password change
- Security-required password reset

---

### 8. `changePassword(Request $request)`

**What it does:** Updates user's password

**Parameters:**
- `password` - New password (required, min 8 characters)
- `password_confirmation` - Password confirmation (must match)

**Validation:**
- Password required
- Minimum 8 characters
- Must match confirmation

**Returns:**
- Student: Redirect to student dashboard
- Faculty: Redirect to role-specific dashboard

**Process Flow:**

```
1. Validate new password
   ↓
2. Check if user is STUDENT:
   ├─ Get authenticated student from student guard
   ├─ Update StudentAccount password with hashed value
   ├─ Save changes
   └─ Redirect to student dashboard
   ↓
   If not student:
3. Check if user is FACULTY:
   ├─ Get authenticated user from default guard
   ├─ Find UserAccount by faculty_id
   ├─ Update password with hashed value
   ├─ Save changes
   └─ Redirect based on role
   ↓
   If neither:
4. Redirect to login with error
```

**Security Features:**
- Password hashed before saving
- Session verification before allowing change
- Minimum 8 character requirement
- Confirmation prevents typos

---

## Authentication Guards

### What are Guards?

Guards define **how** users are authenticated for each request. CapTracks uses two guards:

### 1. Default Guard (Faculty)
- **Name:** `web` (default)
- **User Type:** Faculty (teachers, coordinators, advisers, chairperson, panelists)
- **Table:** `user_accounts`
- **Primary Key:** `faculty_id`
- **Model:** `User`
- **Usage:** `Auth::login()`, `Auth::user()`, `Auth::check()`

### 2. Student Guard
- **Name:** `student`
- **User Type:** Students only
- **Table:** `student_accounts`
- **Primary Key:** `student_id`
- **Model:** `StudentAccount`
- **Usage:** `Auth::guard('student')->login()`, `Auth::guard('student')->user()`

**Why Two Guards?**
- Students and faculty have different data structures
- Different authentication requirements
- Separate session management
- Better security isolation

---

## User Types & Models

### Faculty Users

**Tables:**
- `users` - Personal information
- `user_accounts` - Authentication credentials
- `user_roles` - Role assignments

**Key Fields:**
- `faculty_id` - Unique identifier (e.g., "100234")
- `email` - Login email
- `password` - Hashed password
- `name` - Full name
- `department` - Faculty department

**Roles:**
- Chairperson - Highest admin
- Coordinator - Manages groups/proposals
- Adviser - Guides student groups
- Teacher - Teaches the class
- Panelist - Evaluates defenses

**Multiple Roles:**
- One faculty can have multiple roles
- Example: Teacher + Adviser
- System prioritizes coordinator role for dashboard

### Student Users

**Tables:**
- `students` - Personal information
- `student_accounts` - Authentication credentials

**Key Fields:**
- `student_id` - Unique identifier (number or ID string)
- `email` - Login email
- `password` - Hashed password
- `name` - Full name
- `must_change_password` - Boolean flag

**Role:**
- Only one role: Student

---

## Authentication Flows

### Login Flow Diagram

```
┌─────────────────┐
│  User enters:   │
│  - School ID    │
│  - Password     │
└────────┬────────┘
         ↓
┌────────────────────────┐
│  Is it Faculty ID?     │
│  (Check user_accounts) │
└──┬─────────────────┬───┘
   │ YES             │ NO
   ↓                 ↓
┌──────────────┐  ┌──────────────────┐
│ Faculty Login│  │ Is it Student ID?│
├──────────────┤  │ (Check student_  │
│1. Check pwd  │  │  accounts)       │
│2. Auth login │  └─────┬────────┬───┘
│3. Get role   │    YES │        │ NO
│4. Redirect   │        ↓        ↓
│   based on   │  ┌─────────┐  ┌──────┐
│   role       │  │First-   │  │Error:│
└──────────────┘  │time?    │  │Invalid│
                  └┬───┬────┘  │ID    │
              YES │   │ NO     └──────┘
                  ↓   ↓
           ┌──────┐ ┌────────────┐
           │Force │ │Check pwd   │
           │pwd   │ │Auth login  │
           │change│ │Redirect to │
           └──────┘ │dashboard   │
                    └────────────┘
```

### First-Time Student Login Flow

```
1. Student imported via CSV (no password set)
   ↓
2. Student enters student_id only (no password)
   ↓
3. System finds student account
   ↓
4. Checks: must_change_password = true && password = null
   ↓
5. Logs in student automatically
   ↓
6. Redirects to password change page
   ↓
7. Student sets password
   ↓
8. Password saved (hashed)
   ↓
9. Redirected to student dashboard
   ↓
10. Future logins require password
```

### Logout Flow

```
1. User clicks logout
   ↓
2. System calls Auth::logout() for faculty guard
   ↓
3. System calls Auth::guard('student')->logout() for student guard
   ↓
4. Session invalidated (all data destroyed)
   ↓
5. CSRF token regenerated
   ↓
6. Redirect to login page
   ↓
7. User is completely logged out
```

### Registration Flow

```
1. User fills registration form
   ↓
2. System validates all data
   ↓
3. Check email uniqueness
   ↓
4. Determine account type:
   ├─ Chairperson can assign any role
   └─ Others default to student
   ↓
5. If STUDENT:
   ├─ Create Student record
   ├─ Create StudentAccount record
   └─ Hash and save password
   ↓
6. If FACULTY:
   ├─ Generate faculty_id
   ├─ Create User record
   ├─ Create UserAccount record
   └─ Hash and save password
   ↓
7. Redirect to login
   ↓
8. User can now log in
```

### Password Change Flow

```
1. User navigates to change password page
   ↓
2. System checks authentication
   ├─ If not logged in → Redirect to login
   └─ If logged in → Show form
   ↓
3. User enters new password (twice)
   ↓
4. System validates:
   ├─ Minimum 8 characters
   └─ Passwords match
   ↓
5. Identify user type:
   ├─ Student Guard → Update StudentAccount
   └─ Faculty Guard → Update UserAccount
   ↓
6. Hash new password
   ↓
7. Save to database
   ↓
8. Redirect to dashboard
   ↓
9. User can log in with new password
```

---

## Security Features

### Password Security

✅ **Hashing**
- All passwords stored using `bcrypt` algorithm
- `Hash::make()` creates secure hash
- `Hash::check()` verifies without exposing password
- Hashes are one-way (cannot be reversed)

✅ **Validation**
- Minimum 8 characters required
- Confirmation field prevents typos
- Password cannot be empty (except first-time student)

✅ **Storage**
- Passwords NEVER stored in plain text
- Database shows hash like: `$2y$10$abcd...`
- Even admins cannot see real passwords

### Session Security

✅ **Session Regeneration**
- After login: `$request->session()->regenerate()`
- Prevents session fixation attacks
- Each login gets fresh session ID

✅ **Session Invalidation**
- After logout: `$request->session()->invalidate()`
- Destroys all session data
- Old session ID becomes useless

✅ **CSRF Protection**
- Token regenerated after logout
- Protects against cross-site request forgery
- Laravel handles this automatically

### Authentication Security

✅ **Guard Separation**
- Students and faculty use different guards
- No cross-contamination of sessions
- Better isolation of user types

✅ **Specific Error Messages**
- "Password is required" - clear feedback
- "Incorrect password" - specific issue
- "Invalid School ID" - clear problem
- Helps users without exposing security info

✅ **Role Verification**
- Role checked before every redirect
- Invalid roles rejected
- Prevents unauthorized access

---

## Common Scenarios

### Scenario 1: Faculty First Login
```
1. Chairperson creates faculty account via CSV import
2. Faculty_id: 100234
3. Default password: faculty_id (100234)
4. Faculty logs in:
   - School ID: 100234
   - Password: 100234
5. System logs them in
6. Redirected to role-specific dashboard
7. Should change password for security
```

### Scenario 2: Student First Login
```
1. Chairperson imports student via CSV
2. Student_id: 2020-12345
3. No password set (password = null)
4. Student logs in:
   - School ID: 2020-12345
   - Password: (left empty)
5. System detects first-time login
6. Auto-logs in student
7. Forces password change
8. Student sets password: "MyNewPass123"
9. Redirected to student dashboard
10. Next login requires password
```

### Scenario 3: Forgot Password
```
Currently, the system doesn't have a "forgot password" feature.
Users must contact the chairperson to reset their password.

Manual Reset Process:
1. User contacts chairperson
2. Chairperson resets password in database
3. User logs in with new password
4. User changes password to personal choice
```

### Scenario 4: Multiple Roles
```
Faculty with roles: Teacher + Adviser + Coordinator

1. Logs in with faculty_id
2. System detects multiple roles
3. Checks for 'coordinator' role first
4. Redirects to coordinator dashboard
5. User can switch views if needed
```

### Scenario 5: Session Timeout
```
1. User logged in and idle for long time
2. Session expires (configured in Laravel)
3. User tries to access page
4. Middleware detects expired session
5. Redirects to login page
6. User must log in again
```

---

## Important Configuration

### Guard Configuration
**File:** `config/auth.php`

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',  // Faculty
    ],
    'student' => [
        'driver' => 'session',
        'provider' => 'students',
    ],
],
```

### Session Configuration
**File:** `config/session.php`

- Session lifetime: Configured in minutes
- Session driver: Usually 'file' or 'database'
- Session cookie: Encrypted and http-only

### Password Configuration
**File:** `config/hashing.php`

- Driver: bcrypt (default)
- Bcrypt rounds: 10 (balance of speed and security)

---

## Middleware

### Authentication Middleware
**Purpose:** Ensures user is logged in before accessing protected pages

**Usage in routes:**
```php
Route::middleware('auth')->group(function () {
    // Faculty routes
});

Route::middleware('auth:student')->group(function () {
    // Student routes
});
```

### How it works:
1. User tries to access protected page
2. Middleware checks if authenticated
3. If yes → Allow access
4. If no → Redirect to login

---

## Error Handling

### Login Errors

**"Invalid School ID"**
- Cause: No account found with that ID
- Solution: Check ID spelling, contact admin

**"Password is required"**
- Cause: Password field left empty
- Solution: Enter password

**"Incorrect password"**
- Cause: Password doesn't match database
- Solution: Check caps lock, retry, or reset

### Registration Errors

**"Email has already been taken"**
- Cause: Email exists in database
- Solution: Use different email or log in

**"Password confirmation doesn't match"**
- Cause: Two password fields don't match
- Solution: Retype carefully

**"Password must be at least 8 characters"**
- Cause: Password too short
- Solution: Use longer password

---

## Best Practices

### For Users

✅ **Password Management**
- Change default password immediately
- Use strong passwords (letters, numbers, symbols)
- Don't share passwords
- Don't reuse passwords from other sites

✅ **Session Management**
- Always log out when done
- Don't leave account open on public computers
- Close browser after logout on shared computers

✅ **Security Awareness**
- Don't enter credentials on suspicious pages
- Verify you're on correct URL
- Report suspicious activity

### For Administrators

✅ **Account Creation**
- Use CSV import for bulk users
- Set must_change_password for new faculty
- Verify email addresses are correct
- Keep records of created accounts

✅ **Security Policies**
- Require strong passwords
- Regular password changes
- Monitor for suspicious logins
- Disable inactive accounts

✅ **Maintenance**
- Regularly review active sessions
- Clear old session data
- Monitor failed login attempts
- Keep Laravel security patches updated

---

## Troubleshooting

### Problem: Can't log in as student
**Check:**
1. Is student account created?
2. Is student_id correct?
3. If first time, try without password
4. If not first time, is password correct?

### Problem: Can't log in as faculty
**Check:**
1. Is faculty account created?
2. Is faculty_id correct?
3. Is password correct? (default is faculty_id)
4. Are roles assigned?

### Problem: Redirected to wrong dashboard
**Check:**
1. What roles are assigned?
2. Is role assignment correct?
3. Multiple roles? (coordinator has priority)

### Problem: Password change fails
**Check:**
1. Are you logged in?
2. Is password at least 8 characters?
3. Do both password fields match?
4. Check for special characters causing issues

### Problem: Session expires too quickly
**Solution:**
- Adjust session lifetime in `config/session.php`
- Default is usually 120 minutes

---

## Key Terms

**Authentication**: Verifying who you are (login process)

**Authorization**: Verifying what you can do (role permissions)

**Guard**: Laravel's way of defining how users authenticate

**Session**: Temporary storage of your login state while browsing

**CSRF Token**: Security token preventing fake form submissions

**Hash**: One-way encryption of passwords

**Bcrypt**: Strong password hashing algorithm

**Session Fixation**: Attack where hacker steals session ID

**Session Regeneration**: Creating new session ID (security measure)

**Primary Key**: Unique identifier (faculty_id or student_id)

**Middleware**: Code that runs before accessing routes

**Validation**: Checking if data meets requirements

**Redirect**: Sending user to different page

**Model**: PHP class representing database table

**Default Password**: Initial password set by system

**Must Change Password**: Flag requiring password update

