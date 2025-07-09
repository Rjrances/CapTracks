<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return redirect('/login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [AuthController::class, 'showRegisterForm']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/change-password', [AuthController::class, 'showChangePasswordForm']);
Route::post('/change-password', [AuthController::class, 'changePassword']);

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/student-dashboard', function () {
        return view('dashboards.student');
    });
});

Route::middleware(['auth', 'role:coordinator,chairperson'])->group(function () {
    Route::get('/coordinator-dashboard', function () {
        return view('dashboards.coordinator');
    });
});

Route::middleware(['auth', 'role:adviser,panelist'])->group(function () {
    Route::get('/adviser-dashboard', function () {
        return view('dashboards.adviser');
    });
});

// Optional: fallback dashboard route, redirecting users based on role if needed
Route::get('/dashboard', function () {
    $user = auth()->user();

    if (!$user) {
        return redirect('/login');
    }

    switch ($user->role) {
        case 'coordinator':
        case 'chairperson':
            return redirect('/coordinator-dashboard');
        case 'adviser':
        case 'panelist':
            return redirect('/adviser-dashboard');
        case 'student':
            return redirect('/student-dashboard');
        default:
            return 'Welcome to CapTrack Dashboard!';
    }
})->middleware('auth');
