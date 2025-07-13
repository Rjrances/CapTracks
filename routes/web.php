<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\CoordinatorDashboardController;
use App\Http\Controllers\ChairpersonDashboardController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ChairpersonController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MilestoneTemplateController;
use App\Http\Controllers\MilestoneTaskController;

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
    Route::get('/coordinator-dashboard', [CoordinatorController::class, 'index'])->name('coordinator-dashboard');
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

   // coordinator Routes
Route::middleware(['auth', 'checkrole:coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () {
    // Coordinator Dashboard
    Route::get('/dashboard', [CoordinatorDashboardController::class, 'index'])->name('dashboard');

    // View Class List by Semester
     Route::get('/classlist', [CoordinatorController::class, 'classlist'])->name('classlist.index');
    // Milestone Templates
Route::resource('milestones', MilestoneTemplateController::class);

// ✅ Milestone Tasks (nested under milestone)
Route::prefix('milestones/{milestone}')->name('milestones.')->group(function () {
    Route::get('tasks', [MilestoneTaskController::class, 'index'])->name('tasks.index');
    Route::post('tasks', [MilestoneTaskController::class, 'store'])->name('tasks.store');
    Route::get('tasks/{task}/edit', [MilestoneTaskController::class, 'edit'])->name('tasks.edit');
    Route::put('tasks/{task}', [MilestoneTaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [MilestoneTaskController::class, 'destroy'])->name('tasks.destroy');
});
    // Defense Scheduling
    Route::get('/defense/scheduling', [CoordinatorController::class, 'defenseScheduling'])->name('defense.scheduling');

    // Groups
    Route::get('/groups', [CoordinatorController::class, 'groups'])->name('groups.index');

    // Notifications
    Route::get('/notifications', [CoordinatorController::class, 'notifications'])->name('notifications');

    // Profile (optional)
    Route::get('/profile', [CoordinatorController::class, 'profile'])->name('profile');
});


    // Chairperson Routes
    Route::middleware(['checkrole:chairperson'])->prefix('chairperson')->name('chairperson.')->group(function () {

        Route::get('/dashboard', [ChairpersonDashboardController::class, 'index'])->name('dashboard');

        // Manage Roles
        Route::get('/manage-roles', [RoleController::class, 'index'])->name('manage-roles');
        Route::post('/manage-roles/{user}', [RoleController::class, 'update'])->name('roles.update');

        // Offerings
        Route::get('/offerings', [ChairpersonController::class, 'indexOfferings'])->name('offerings.index');
        Route::get('/offerings/create', [ChairpersonController::class, 'createOffering'])->name('offerings.create');
        Route::post('/offerings', [ChairpersonController::class, 'storeOffering'])->name('offerings.store');
        Route::get('/offerings/{id}/edit', [ChairpersonController::class, 'editOffering'])->name('offerings.edit');
        Route::put('/offerings/{id}', [ChairpersonController::class, 'updateOffering'])->name('offerings.update');
        Route::delete('/offerings/{id}', [ChairpersonController::class, 'deleteOffering'])->name('offerings.delete');

        // Teachers
        Route::get('/teachers', [ChairpersonController::class, 'teachers'])->name('teachers.index');
        Route::get('/teachers/create', [ChairpersonController::class, 'createTeacher'])->name('teachers.create');
        Route::post('/teachers', [ChairpersonController::class, 'storeTeacher'])->name('teachers.store');
        Route::get('/teachers/{id}/edit', [ChairpersonController::class, 'editTeacher'])->name('teachers.edit');
        Route::put('/teachers/{id}', [ChairpersonController::class, 'updateTeacher'])->name('teachers.update');


        // Schedules
        Route::get('/schedules', [ChairpersonController::class, 'schedules'])->name('schedules');

        // Student Import via Excel
        Route::get('/upload-students', fn () => view('chairperson.students.import'))->name('upload-form');
        Route::post('/upload-students', [ChairpersonController::class, 'uploadStudentList'])->name('upload-students');
    });
});
