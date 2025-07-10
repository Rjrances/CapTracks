<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\CoordinatorDashboardController;
use App\Http\Controllers\ChairpersonDashboardController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\EventController;


Route::get('/', fn () => redirect('/login'));

// Login & Logout
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public registration
Route::get('/register', [AuthController::class, 'showRegisterForm']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['auth'])->group(function () {
    Route::get('/student-dashboard', [StudentDashboardController::class, 'index']);
    Route::get('/coordinator-dashboard', [CoordinatorDashboardController::class, 'index']);
    Route::get('/chairperson-dashboard', [ChairpersonDashboardController::class, 'index']);
    Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
    Route::get('/events/{id}', [EventController::class, 'show'])->name('events.show');

    Route::get('/change-password', [AuthController::class, 'showChangePasswordForm']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Classes routes
    Route::get('/classes/create', [ClassController::class, 'create'])->name('classes.create');
    Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');

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

