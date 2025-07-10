<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\CoordinatorDashboardController;
use App\Http\Controllers\ChairpersonDashboardController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ChairpersonController;


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
    Route::get('/dashboard', [ChairpersonDashboardController::class, 'index'])->name('chairperson.dashboard');

    // Offerings (view/add/edit/delete logic should be inside ChairpersonController)
    Route::get('/offerings', [ChairpersonController::class, 'offerings'])->name('chairperson.offerings');
    Route::post('/offerings', [ChairpersonController::class, 'storeOffering'])->name('chairperson.offerings.store');
    Route::put('/offerings/{id}', [ChairpersonController::class, 'updateOffering'])->name('chairperson.offerings.update');
    Route::delete('/offerings/{id}', [ChairpersonController::class, 'deleteOffering'])->name('chairperson.offerings.delete');

    // View-only sections
    Route::get('/teachers', [ChairpersonController::class, 'teachers'])->name('chairperson.teachers');
    Route::get('/schedules', [ChairpersonController::class, 'schedules'])->name('chairperson.schedules');
});

});

});
