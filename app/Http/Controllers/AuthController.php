<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;

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

        // First, try to find user in users table (faculty/staff)
        $user = User::where('school_id', $request->school_id)->first();

        if ($user) {
            // Faculty/Staff login
            // First-time login: No password yet
            if (!$user->password) {
                Auth::login($user);
                return redirect('/change-password');
            }

            // If password is set, check if provided password matches
            if (!Hash::check($request->password, $user->password)) {
                return back()->withErrors(['password' => 'Incorrect password.']);
            }

            Auth::login($user);
            $request->session()->regenerate();

            if ($user->must_change_password) {
                return redirect('/change-password');
            }

            return $this->redirectBasedOnRole($user->role);
        }

        // If not found in users table, try students table
        $student = Student::where('student_id', $request->school_id)->first();

        if ($student) {
            // Student login - check password
            // First-time login: No password yet
            if (!$student->password) {
                $request->session()->put('student_id', $student->id);
                $request->session()->put('student_name', $student->name);
                $request->session()->put('student_email', $student->email);
                $request->session()->put('student_role', 'student');
                $request->session()->put('is_student', true);
                $request->session()->put('must_change_password', $student->must_change_password);

                // Regenerate session to ensure it's saved
                $request->session()->regenerate();

                return redirect('/change-password');
            }

            // If password is set, check if provided password matches
            if (!Hash::check($request->password, $student->password)) {
                return back()->withErrors(['password' => 'Incorrect password.']);
            }

            // Store student info in session
            $request->session()->put('student_id', $student->id);
            $request->session()->put('student_name', $student->name);
            $request->session()->put('student_email', $student->email);
            $request->session()->put('student_role', 'student');
            $request->session()->put('is_student', true);
            $request->session()->put('must_change_password', $student->must_change_password);

            // Regenerate session to ensure it's saved
            $request->session()->regenerate();

            // Check if student must change password
            if ($student->must_change_password) {
                return redirect('/change-password');
            }

            return redirect()->route('student.dashboard');
        }

        // If not found in either table
        return back()->withErrors(['school_id' => 'Invalid School ID.']);
    }

    private function redirectBasedOnRole($role)
{
    return match ($role) {
        'chairperson' => redirect()->route('chairperson.dashboard'),
        'coordinator' => redirect()->route('coordinator-dashboard'),
        'adviser', 'panelist' => redirect()->route('adviser.dashboard'),
        'student' => redirect()->route('student-dashboard'),
        default => redirect('/login')->withErrors(['role' => 'Invalid role.']),
    };
}


    public function logout(Request $request)
    {
        // Logout faculty/staff
        Auth::logout();
        
        // Clear student session data
        $request->session()->forget(['student_id', 'student_name', 'student_email', 'student_role', 'is_student', 'must_change_password']);
        
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
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'nullable|in:student,coordinator,adviser,panelist,chairperson',
        ]);

        $role = 'student';

        if (Auth::check() && Auth::user()->role === 'chairperson' && $request->filled('role')) {
            $role = $request->role;
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'must_change_password' => true,
        ]);

        return redirect('/login')->with('success', 'Registration successful. Please log in.');
    }

    public function showChangePasswordForm()
    {
        // Check if user is authenticated (faculty/staff or student)
        if (!Auth::check() && !session('is_student')) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        
        return view('auth.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        // Check if it's a student changing password
        if (session('is_student')) {
            $student = Student::find(session('student_id'));
            if ($student) {
                $student->password = Hash::make($request->password);
                $student->must_change_password = false;
                $student->save();
                
                // Clear the must_change_password flag from session
                $request->session()->forget('must_change_password');
                
                return redirect()->route('student.dashboard');
            }
            
            // If student not found, redirect to login
            return redirect('/login')->withErrors(['auth' => 'Student session expired. Please log in again.']);
        }

        // Faculty/staff changing password
        if (!Auth::check()) {
            return redirect('/login')->withErrors(['auth' => 'Please log in to access this page.']);
        }
        
        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

        return $this->redirectBasedOnRole($user->role);
    }
}
