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
        // Remove 'email' rule entirely
        // Password is optional for first-time login
    ]);

    $user = User::where('school_id', $request->school_id)->first();

    if (!$user) {
        return back()->withErrors(['school_id' => 'Invalid School ID.']);
    }

    // First-time login without password
    if (!$user->password) {
        Auth::login($user);
        return redirect('/change-password');
    }

    // Check password if exists
    if (!Hash::check($request->password, $user->password)) {
        return back()->withErrors(['password' => 'Incorrect password.']);
    }

    Auth::login($user);
    $request->session()->regenerate();

    if ($user->must_change_password) {
        return redirect('/change-password');
    }

    // Redirect based on role
    return match ($user->role) {
        'chairperson', 'coordinator' => redirect('/coordinator-dashboard'),
        'adviser', 'panelist' => redirect('/adviser-dashboard'),
        'student' => redirect('/student-dashboard'),
        default => redirect('/student-dashboard'),
    };
}


    private function redirectBasedOnRole($role)
    {
        return match ($role) {
            'coordinator', 'chairperson' => redirect('/coordinator-dashboard'),
            'adviser', 'panelist' => redirect('/adviser-dashboard'),
            'student' => redirect('/student-dashboard'),
            default => redirect('/student-dashboard'),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    // Registration removed – no more manual registration
    public function showRegisterForm()
    {
        abort(403, 'Registration is disabled.');
    }

    public function register(Request $request)
    {
        abort(403, 'Registration is disabled.');
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
