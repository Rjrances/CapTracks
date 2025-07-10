<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\School;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm() {
        return view('auth.login');
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Redirect based on role or password change requirement
            if (Auth::user()->must_change_password) {
                return redirect('/change-password');
            }

            // Role-based redirect
            switch (Auth::user()->role) {
                case 'coordinator':
                case 'chairperson':
                    return redirect('/coordinator-dashboard');
                case 'adviser':
                case 'panelist':
                    return redirect('/adviser-dashboard');
                case 'student':
                    return redirect('/student-dashboard');
                default:
                    return redirect('/student-dashboard');
            }
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ]);
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function showRegisterForm()
{
    $schools = School::all(); // Make sure you import School model
    return view('auth.register', compact('schools'));
}


    public function register(Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8|confirmed',
        // role input will be ignored if you're enforcing 'student' always
    ]);

    $user = User::create([
        'school_id' => $request->school_id,
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => 'student', // ✅ always student
        'must_change_password' => true,
    ]);

    return redirect('/login')->with('success', 'Registration successful. Please log in.');
}





    public function showChangePasswordForm() {
        return view('auth.change-password');
    }

    public function changePassword(Request $request) {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

        return redirect('/dashboard'); // or wherever you want
    }
}
