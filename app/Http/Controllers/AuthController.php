<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

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
        ]);

        $user = User::where('school_id', $request->school_id)->first();

        if (!$user) {
            return back()->withErrors(['school_id' => 'Invalid School ID.']);
        }

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
        Auth::logout();
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
        return view('auth.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

        return $this->redirectBasedOnRole($user->role);
    }
}
