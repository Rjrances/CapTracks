<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\CoordinatorDashboardController;
use App\Http\Controllers\ChairpersonDashboardController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ChairpersonController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\EventController;

// Redirect root to login
Route::get('/', fn () => redirect('/login'));

// Login & Logout
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Routes
Route::middleware(['auth'])->group(function () {

    // Dashboards
    Route::get('/student-dashboard', [StudentDashboardController::class, 'index'])->name('student-dashboard');
    Route::get('/coordinator-dashboard', [CoordinatorDashboardController::class, 'index'])->name('coordinator-dashboard');
    Route::get('/chairperson-dashboard', [ChairpersonDashboardController::class, 'index'])->name('chairperson-dashboard');

    // Class Management
    Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
    Route::get('/classes/create', [ClassController::class, 'create'])->name('classes.create');
    Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');

    // Events
    Route::get('/events/{id}', [EventController::class, 'show'])->name('events.show');

    // Password Management
    Route::get('/change-password', [AuthController::class, 'showChangePasswordForm']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Chairperson Routes
    Route::middleware(['checkrole:chairperson'])->prefix('chairperson')->group(function () {

        Route::get('/dashboard', [ChairpersonDashboardController::class, 'index'])->name('chairperson.dashboard');

        // Manage Roles
        Route::get('/manage-roles', [RoleController::class, 'index'])->name('chairperson.manage-roles');
        Route::post('/manage-roles/{user}', [RoleController::class, 'update'])->name('chairperson.roles.update');

        // Offerings
        Route::get('/offerings', [ChairpersonController::class, 'indexOfferings'])->name('chairperson.offerings.index');
        Route::get('/offerings/create', [ChairpersonController::class, 'createOffering'])->name('chairperson.offerings.create');
        Route::post('/offerings', [ChairpersonController::class, 'storeOffering'])->name('chairperson.offerings.store');
        Route::get('/offerings/{id}/edit', [ChairpersonController::class, 'editOffering'])->name('chairperson.offerings.edit'); // ✅ Added
        Route::put('/offerings/{id}', [ChairpersonController::class, 'updateOffering'])->name('chairperson.offerings.update');
        Route::delete('/offerings/{id}', [ChairpersonController::class, 'deleteOffering'])->name('chairperson.offerings.delete');

        // Teachers & Schedules
        Route::get('/teachers', [ChairpersonController::class, 'teachers'])->name('chairperson.teachers');
        Route::get('/schedules', [ChairpersonController::class, 'schedules'])->name('chairperson.schedules');

        // Student Import via Excel
        Route::get('/upload-students', function () {
            return view('chairperson.students.import');
        })->name('chairperson.upload-form');

        Route::post('/upload-students', [ChairpersonController::class, 'uploadStudentList'])->name('chairperson.upload-students');
    });
});
