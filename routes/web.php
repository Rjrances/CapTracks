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
    Route::get('/events', [CoordinatorController::class, 'events'])->name('events.index');
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
    Route::patch('milestones/{milestone}/status', [MilestoneTemplateController::class, 'updateStatus'])->name('milestones.updateStatus');

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
    Route::get('/groups/create', [CoordinatorController::class, 'create'])->name('groups.create');
    Route::post('/groups', [CoordinatorController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}', [CoordinatorController::class, 'show'])->name('groups.show');
    Route::get('/groups/{group}/edit', [CoordinatorController::class, 'edit'])->name('groups.edit');
    Route::put('/groups/{group}', [CoordinatorController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [CoordinatorController::class, 'destroy'])->name('groups.destroy');
    Route::get('/groups/{group}/assign-adviser', [CoordinatorController::class, 'assignAdviser'])->name('groups.assignAdviser');
    Route::get('/groups/{group}/milestones', [CoordinatorController::class, 'groupMilestones'])->name('groups.milestones');

    // Notifications
    Route::get('/notifications', [CoordinatorController::class, 'notifications'])->name('notifications');

    // Profile (optional)
    Route::get('/profile', [CoordinatorController::class, 'profile'])->name('profile');

    // Events
    Route::get('/events', [CoordinatorController::class, 'events'])->name('events.index');
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

        // Teachers/Faculty Management
        Route::get('/teachers', [ChairpersonController::class, 'teachers'])->name('teachers.index');
        Route::get('/teachers/create', [ChairpersonController::class, 'createFaculty'])->name('teachers.create');
        Route::post('/teachers', [ChairpersonController::class, 'storeFaculty'])->name('teachers.store');
        Route::get('/teachers/{id}/edit', [ChairpersonController::class, 'editFaculty'])->name('teachers.edit');
        Route::put('/teachers/{id}', [ChairpersonController::class, 'updateFaculty'])->name('teachers.update');
        Route::delete('/teachers/{id}', [ChairpersonController::class, 'deleteFaculty'])->name('teachers.delete');


        // Schedules
        Route::resource('schedules', \App\Http\Controllers\ScheduleController::class);

        // Student Import via Excel
        Route::get('/upload-students', fn () => view('chairperson.students.import'))->name('upload-form');
        Route::post('/upload-students', [ChairpersonController::class, 'uploadStudentList'])->name('upload-students');
    });
});

// Student dashboard and feature pages
Route::middleware(['auth', 'checkrole:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/project', [\App\Http\Controllers\ProjectSubmissionController::class, 'index'])->name('project');
    Route::get('/project/create', [\App\Http\Controllers\ProjectSubmissionController::class, 'create'])->name('project.create');
    Route::post('/project', [\App\Http\Controllers\ProjectSubmissionController::class, 'store'])->name('project.store');
    Route::get('/project/{id}', [\App\Http\Controllers\ProjectSubmissionController::class, 'show'])->name('project.show');
    Route::delete('/project/{id}', [\App\Http\Controllers\ProjectSubmissionController::class, 'destroy'])->name('project.destroy');
    Route::get('/group', [\App\Http\Controllers\StudentGroupController::class, 'show'])->name('group');
    Route::get('/groups', [\App\Http\Controllers\StudentGroupController::class, 'index'])->name('group.index');
    Route::get('/group/create', [\App\Http\Controllers\StudentGroupController::class, 'create'])->name('group.create');
    Route::post('/group', [\App\Http\Controllers\StudentGroupController::class, 'store'])->name('group.store');
    Route::get('/group/edit', [\App\Http\Controllers\StudentGroupController::class, 'edit'])->name('group.edit');
    Route::put('/group', [\App\Http\Controllers\StudentGroupController::class, 'update'])->name('group.update');
    
    // Group management routes
    Route::post('/group/invite-adviser', [\App\Http\Controllers\StudentGroupController::class, 'inviteAdviser'])->name('group.invite-adviser');
    Route::post('/group/add-member', [\App\Http\Controllers\StudentGroupController::class, 'addMember'])->name('group.add-member');
    Route::delete('/group/remove-member/{memberId}', [\App\Http\Controllers\StudentGroupController::class, 'removeMember'])->name('group.remove-member');
    
    Route::get('/proposal', fn () => 'Proposal & Endorsement Page (to be implemented)')->name('proposal');
    Route::get('/milestones', fn () => 'Milestones Page (to be implemented)')->name('milestones');
});

// Adviser/Faculty Routes
Route::middleware(['auth', 'checkrole:adviser,panelist'])->prefix('adviser')->name('adviser.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AdviserController::class, 'dashboard'])->name('dashboard');
    Route::get('/invitations', [\App\Http\Controllers\AdviserController::class, 'invitations'])->name('invitations');
    Route::post('/invitations/{invitation}/respond', [\App\Http\Controllers\AdviserController::class, 'respondToInvitation'])->name('invitations.respond');
    Route::get('/groups', [\App\Http\Controllers\AdviserController::class, 'myGroups'])->name('groups');
    Route::get('/groups/{group}', [\App\Http\Controllers\AdviserController::class, 'groupDetails'])->name('groups.details');
    
    // Project review routes
    Route::get('/project', [\App\Http\Controllers\ProjectSubmissionController::class, 'index'])->name('project.index');
    Route::get('/project/{id}', [\App\Http\Controllers\ProjectSubmissionController::class, 'show'])->name('project.show');
    Route::get('/project/{id}/edit', [\App\Http\Controllers\ProjectSubmissionController::class, 'edit'])->name('project.edit');
    Route::put('/project/{id}', [\App\Http\Controllers\ProjectSubmissionController::class, 'update'])->name('project.update');
});
