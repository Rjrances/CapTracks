<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;
use App\Models\UserAccount;
use App\Models\StudentAccount;
use App\Models\Role;
class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'school_id' => ['required'],
            'password' => ['nullable'],
        ]);
        
        // Check by faculty_id
        $userAccount = UserAccount::where('faculty_id', $request->school_id)->first();
        
        if ($userAccount) {
            $user = $userAccount->user;
            if ($user) {
                if (empty($request->password)) {
                    return back()->withErrors(['password' => 'Password is required.']);
                }
                if (!Hash::check($request->password, $userAccount->password)) {
                    return back()->withErrors(['password' => 'Incorrect password.']);
                }
                Auth::login($user);
                $request->session()->regenerate();
                return $this->redirectBasedOnRole($user->getPrimaryRoleAttribute());
            }
        }
        
        // Check by student_id
        $studentAccount = StudentAccount::where('student_id', $request->school_id)->first();
        if ($studentAccount) {
            if ($studentAccount->must_change_password && is_null($studentAccount->password)) {
                // First time login
                Auth::guard('student')->login($studentAccount);
                $request->session()->regenerate();
                
                return redirect()->route('student.change-password')
                    ->with('info', 'Welcome! Please set your password to continue.');
            }
            
            if (empty($request->password)) {
                return back()->withErrors(['password' => 'Password is required.']);
            }
            if (!Hash::check($request->password, $studentAccount->password)) {
                return back()->withErrors(['password' => 'Incorrect password.']);
            }
            
            Auth::guard('student')->login($studentAccount);
            $request->session()->regenerate();
            
            return redirect()->route('student.dashboard');
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
            // Get active term for semester
            $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
            
            // Create faculty user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'birthday' => now()->subYears(25),
                'department' => 'N/A',
                'role' => $role,
                'faculty_id' => '100' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT), // Generate faculty_id
                'semester' => $activeTerm ? $activeTerm->semester : 'Unknown',
            ]);
            
            // Create faculty account
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
        return $this->redirectBasedOnRole($user->getPrimaryRoleAttribute());
    }
}
