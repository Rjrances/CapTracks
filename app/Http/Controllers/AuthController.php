<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;
use App\Models\UserAccount;
use App\Models\StudentAccount;
class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }
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
    private function redirectBasedOnRole($role)
    {
        if (is_array($role)) {
            if (in_array('coordinator', $role)) {
                return redirect()->route('coordinator.dashboard');
            }
            $role = $role[0];
        }
        return match ($role) {
            'chairperson' => redirect()->route('chairperson.dashboard'),
            'coordinator' => redirect()->route('coordinator.dashboard'),
            'adviser', 'panelist', 'teacher' => redirect()->route('adviser.dashboard'),
            'student' => redirect()->route('student.dashboard'),
            default => redirect('/login')->withErrors(['role' => 'Invalid role.']),
        };
    }
    public function logout(Request $request)
    {
        
        Auth::logout();
        Auth::guard('student')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
    public function showRegisterForm()
    {
        return view('auth.register');
    }
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:user_accounts|unique:student_accounts',
            'password' => 'required|min:8|confirmed',
            'role' => 'nullable|in:student,coordinator,adviser,panelist,chairperson,teacher',
        ]);
        $role = 'student';
        if (Auth::check() && Auth::user()->hasRole('chairperson') && $request->filled('role')) {
            $role = $request->role;
        }
        
        if ($role === 'student') {
            
            $student = Student::create([
                'student_id' => now()->timestamp,
                'name' => $request->name,
                'email' => $request->email,
            ]);
            
            
            StudentAccount::create([
                'student_id' => $student->student_id,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
        } else {

            $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
            $fullName = trim((string) $request->name);
            $nameSegments = preg_split('/\s+/', $fullName) ?: [];
            $firstName = $nameSegments[0] ?? $fullName;
            $lastName = count($nameSegments) > 1 ? $nameSegments[count($nameSegments) - 1] : $firstName;
            $middleName = count($nameSegments) > 2
                ? implode(' ', array_slice($nameSegments, 1, -1))
                : null;
            
            $user = User::create([
                'name' => $fullName,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'email' => $request->email,
                'birthday' => now()->subYears(25),
                'department' => 'N/A',
                'role' => $role,
                'faculty_id' => '100' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'semester' => $activeTerm ? $activeTerm->semester : 'Unknown',
            ]);
            $user->assignRoles([$role]);
            
            UserAccount::create([
                'faculty_id' => $user->faculty_id,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'must_change_password' => false,
            ]);
        }
        
        return redirect('/login')->with('success', 'Registration successful. Please log in.');
    }
    public function showChangePasswordForm()
    {
        if (!Auth::check() && !Auth::guard('student')->check()) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        return view('auth.change-password');
    }
    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            if ($studentAccount) {
                $studentAccount->password = Hash::make($request->password);
                $studentAccount->save();
                return redirect()->route('student.dashboard');
            }
            return redirect('/login')->withErrors(['auth' => 'Student session expired. Please log in again.']);
        }
        if (!Auth::check()) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        $user = Auth::user();
        $account = UserAccount::where('faculty_id', $user->faculty_id)->first();
        if ($account) {
            $account->password = Hash::make($request->password);
            $account->save();
        }
        return $this->redirectBasedOnRole($user->primary_role);
    }

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

    private function attemptStudentLogin(Request $request)
    {
        $studentAccount = StudentAccount::where('student_id', $request->school_id)->first();
        if (!$studentAccount) {
            return null;
        }

        // If a DIFFERENT student is already logged in on this browser, block the login
        // and show a clear message instead of a misleading error downstream.
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
}
