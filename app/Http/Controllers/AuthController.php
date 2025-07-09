<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
                    return redirect('/dashboard');
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

    public function showRegisterForm() {
        return view('auth.register');
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'must_change_password' => true,
        ]);

        Auth::login($user);
        return redirect('/change-password');
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
