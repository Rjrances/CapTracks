<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\CoordinatorDashboardController;
use App\Http\Controllers\ChairpersonDashboardController;
use App\Http\Controllers\RoleController;

Route::get('/', fn () => redirect('/login'));

// Login & Logout
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public registration
Route::get('/register', [AuthController::class, 'showRegisterForm']);
Route::post('/register', [AuthController::class, 'register']);

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    Route::get('/student-dashboard', [StudentDashboardController::class, 'index']);
    Route::get('/coordinator-dashboard', [CoordinatorDashboardController::class, 'index']);
    Route::get('/chairperson-dashboard', [ChairpersonDashboardController::class, 'index']);

    Route::get('/change-password', [AuthController::class, 'showChangePasswordForm']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::middleware('checkrole:chairperson')->group(function () {
    Route::get('/manage-roles', [RoleController::class, 'index']);
    Route::post('/manage-roles/{user}', [RoleController::class, 'update'])->name('roles.update');


    Route::middleware(['auth', 'checkrole:chairperson'])->prefix('chairperson')->group(function () {
    Route::get('/dashboard', [ChairpersonDashboardController::class, 'index']);
    Route::get('/offerings', [ChairpersonController::class, 'offerings']);
    Route::get('/teachers', [ChairpersonController::class, 'teachers']);
    Route::get('/schedules', [ChairpersonController::class, 'schedules']);
    Route::get('/assign', [ChairpersonController::class, 'assign']);
});

});

});
