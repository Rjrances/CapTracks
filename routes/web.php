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


// Landing page
Route::get('/', fn () => view('welcome'))->name('welcome');

// Login & Logout
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');



// Password Management (accessible by both authenticated users and students)
Route::get('/change-password', [AuthController::class, 'showChangePasswordForm']);
Route::post('/change-password', [AuthController::class, 'changePassword']);

// Authenticated Routes (faculty/staff only)
Route::middleware(['auth'])->group(function () {

    // Dashboards
    Route::get('/coordinator-dashboard', [CoordinatorController::class, 'index'])->name('coordinator-dashboard');
    Route::get('/chairperson-dashboard', [ChairpersonDashboardController::class, 'index'])->name('chairperson-dashboard');

    // Class Management
    Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
    Route::get('/classes/create', [ClassController::class, 'create'])->name('classes.create');
    Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');

    // Events
    Route::get('/events', [CoordinatorController::class, 'events'])->name('events.index');
    Route::get('/events/{id}', [EventController::class, 'show'])->name('events.show');

   // coordinator Routes
Route::middleware(['auth', 'checkrole:coordinator,adviser'])->prefix('coordinator')->name('coordinator.')->group(function () {
    // Coordinator Dashboard
    Route::get('/dashboard', [CoordinatorDashboardController::class, 'index'])->name('dashboard');

    // View Class List by Semester
     Route::get('/classlist', [CoordinatorController::class, 'classlist'])->name('classlist.index');
    // Milestone Templates - REMOVED for Coordinator (only Chairperson can manage)
    // Route::resource('milestones', MilestoneTemplateController::class);
    // Route::patch('milestones/{milestone}/status', [MilestoneTemplateController::class, 'updateStatus'])->name('milestones.updateStatus');

// ✅ Milestone Tasks - REMOVED for Coordinator (only Chairperson can manage)
// Route::prefix('milestones/{milestone}')->name('milestones.')->group(function () {
//     Route::get('tasks', [MilestoneTaskController::class, 'index'])->name('tasks.index');
//     Route::post('tasks', [MilestoneTaskController::class, 'store'])->name('tasks.store');
//     Route::get('tasks/{task}/edit', [MilestoneTaskController::class, 'edit'])->name('tasks.edit');
//     Route::put('tasks/{task}', [MilestoneTaskController::class, 'update'])->name('tasks.update');
//     Route::delete('tasks/{task}', [MilestoneTaskController::class, 'destroy'])->name('tasks.destroy');
// });
    // Defense Scheduling (Manual)
    Route::resource('defense', \App\Http\Controllers\Coordinator\DefenseScheduleController::class);
    Route::get('/defense/available-faculty', [\App\Http\Controllers\Coordinator\DefenseScheduleController::class, 'getAvailableFaculty'])->name('defense.available-faculty');

    // Milestones - View only (no management) - REMOVED for coordinators
    // Route::get('/milestones', [CoordinatorController::class, 'milestones'])->name('milestones.index');



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

        // Roles
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles/{user}', [RoleController::class, 'update'])->name('roles.update');

        // Offerings
        Route::get('/offerings', [ChairpersonController::class, 'indexOfferings'])->name('offerings.index');
        Route::get('/offerings/create', [ChairpersonController::class, 'createOffering'])->name('offerings.create');
        Route::post('/offerings', [ChairpersonController::class, 'storeOffering'])->name('offerings.store');
        Route::get('/offerings/{id}', [ChairpersonController::class, 'showOffering'])->name('offerings.show');
        Route::get('/offerings/{id}/edit', [ChairpersonController::class, 'editOffering'])->name('offerings.edit');
        Route::put('/offerings/{id}', [ChairpersonController::class, 'updateOffering'])->name('offerings.update');
        Route::delete('/offerings/{id}', [ChairpersonController::class, 'deleteOffering'])->name('offerings.delete');
        
        // Offering Student Management
        Route::delete('/offerings/{offeringId}/students/{studentId}', [ChairpersonController::class, 'removeStudentFromOffering'])->name('offerings.remove-student');

        // Role Management
        Route::post('/fix-roles', [ChairpersonController::class, 'fixRoleInconsistencies'])->name('fix-roles');
        Route::post('/users/{userId}/update-role', [ChairpersonController::class, 'forceUpdateUserRole'])->name('users.update-role');
        Route::post('/force-update-all-roles', [ChairpersonController::class, 'forceUpdateAllRoles'])->name('force-update-all-roles');

        // Teachers/Faculty Management
        Route::get('/teachers', [ChairpersonController::class, 'teachers'])->name('teachers.index');
        Route::get('/teachers/create', [ChairpersonController::class, 'createFaculty'])->name('teachers.create');
        Route::post('/teachers', [ChairpersonController::class, 'storeFaculty'])->name('teachers.store');
        Route::get('/teachers/create-manual', [ChairpersonController::class, 'createFacultyManual'])->name('teachers.create-manual');
        Route::post('/teachers/manual', [ChairpersonController::class, 'storeFacultyManual'])->name('teachers.store-manual');
        Route::get('/teachers/{id}/edit', [ChairpersonController::class, 'editFaculty'])->name('teachers.edit');
        Route::put('/teachers/{id}', [ChairpersonController::class, 'updateFaculty'])->name('teachers.update');
        Route::delete('/teachers/{id}', [ChairpersonController::class, 'deleteFaculty'])->name('teachers.delete');




        // Student Management
        Route::get('/students', [ChairpersonController::class, 'indexStudents'])->name('students.index');
        Route::get('/students/export', [ChairpersonController::class, 'exportStudents'])->name('students.export');
        Route::get('/students/{id}/edit', [ChairpersonController::class, 'editStudent'])->name('students.edit');
        Route::put('/students/{id}', [ChairpersonController::class, 'updateStudent'])->name('students.update');
        Route::delete('/students/bulk-delete', [ChairpersonController::class, 'bulkDeleteStudents'])->name('students.bulk-delete');
        Route::delete('/students/{id}', [ChairpersonController::class, 'deleteStudent'])->name('students.delete');

        // Student Enrollment Management
        Route::get('/offerings/{offeringId}/unenrolled-students', [ChairpersonController::class, 'showUnenrolledStudents'])->name('offerings.unenrolled-students');
        Route::post('/offerings/{offeringId}/enroll-student', [ChairpersonController::class, 'enrollStudent'])->name('offerings.enroll-student');
        Route::post('/offerings/{offeringId}/enroll-multiple-students', [ChairpersonController::class, 'enrollMultipleStudents'])->name('offerings.enroll-multiple-students');
        
        // Student Import via Excel
        Route::get('/upload-students', fn () => view('chairperson.students.import'))->name('upload-form');
        Route::post('/upload-students', [ChairpersonController::class, 'uploadStudentList'])->name('upload-students');

        // Academic Terms
        Route::resource('academic-terms', \App\Http\Controllers\AcademicTermController::class);
        Route::post('/academic-terms/{academicTerm}/toggle-active', [\App\Http\Controllers\AcademicTermController::class, 'toggleActive'])->name('academic-terms.toggle-active');
        Route::post('/academic-terms/{academicTerm}/toggle-archived', [\App\Http\Controllers\AcademicTermController::class, 'toggleArchived'])->name('academic-terms.toggle-archived');

        // Scheduling (Defense Schedules) - Moved to Coordinator
        // Route::resource('scheduling', \App\Http\Controllers\Chairperson\DefenseScheduleController::class)->parameters(['scheduling' => 'defenseSchedule']);
        // Route::patch('/scheduling/{defenseSchedule}/status', [\App\Http\Controllers\Chairperson\DefenseScheduleController::class, 'updateStatus'])->name('scheduling.update-status');
        // Route::get('/scheduling/available-faculty', [\App\Http\Controllers\Chairperson\DefenseScheduleController::class, 'getAvailableFaculty'])->name('scheduling.available-faculty');
    });
});

// Student dashboard and feature pages
Route::prefix('student')->name('student.')->group(function () {
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
    Route::get('/milestones', [\App\Http\Controllers\StudentMilestoneController::class, 'index'])->name('milestones');
    Route::get('/milestones/{milestone}', [\App\Http\Controllers\StudentMilestoneController::class, 'show'])->name('milestones.show');
    Route::patch('/milestones/{milestone}/update-tasks', [\App\Http\Controllers\StudentMilestoneController::class, 'updateMultipleTasks'])->name('milestones.update-tasks');
    Route::patch('/task/{groupMilestoneTask}/assign', [\App\Http\Controllers\StudentMilestoneController::class, 'assignTask'])->name('milestones.assign-task');
    Route::delete('/task/{groupMilestoneTask}/unassign', [\App\Http\Controllers\StudentMilestoneController::class, 'unassignTask'])->name('milestones.unassign-task');
    Route::patch('/task/{groupMilestoneTask}', [\App\Http\Controllers\StudentMilestoneController::class, 'updateTask'])->name('milestones.update-task');
    
    // Defense Request Routes
    Route::post('/group/request-defense', [\App\Http\Controllers\StudentGroupController::class, 'requestDefense'])->name('group.request-defense');
});

// Adviser/Faculty Routes
Route::middleware(['auth', 'checkrole:adviser,coordinator'])->prefix('adviser')->name('adviser.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AdviserController::class, 'dashboard'])->name('dashboard');
    Route::get('/invitations', [\App\Http\Controllers\AdviserController::class, 'invitations'])->name('invitations');
    Route::post('/invitations/{invitation}/respond', [\App\Http\Controllers\AdviserController::class, 'respondToInvitation'])->name('invitations.respond');
    Route::get('/groups', [\App\Http\Controllers\AdviserController::class, 'myGroups'])->name('groups');
    Route::get('/groups/{group}', [\App\Http\Controllers\AdviserController::class, 'groupDetails'])->name('groups.details');
    
    // ✅ NEW: Task management routes
    Route::get('/tasks', [\App\Http\Controllers\AdviserController::class, 'tasksIndex'])->name('tasks.index');
    Route::get('/groups/{group}/tasks', [\App\Http\Controllers\AdviserController::class, 'groupTasks'])->name('groups.tasks');
    Route::patch('/tasks/{task}', [\App\Http\Controllers\AdviserController::class, 'updateTask'])->name('tasks.update');
    
    // Project review routes
    Route::get('/project', [\App\Http\Controllers\ProjectSubmissionController::class, 'index'])->name('project.index');
    Route::get('/project/{id}', [\App\Http\Controllers\ProjectSubmissionController::class, 'show'])->name('project.show');
    Route::get('/project/{id}/edit', [\App\Http\Controllers\ProjectSubmissionController::class, 'edit'])->name('project.edit');
    Route::put('/project/{id}', [\App\Http\Controllers\ProjectSubmissionController::class, 'update'])->name('project.update');
});

// Coordinator Defense Request Routes
Route::middleware(['auth', 'checkrole:coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () {
    Route::get('/defense-requests', [\App\Http\Controllers\DefenseRequestController::class, 'index'])->name('defense-requests.index');
    Route::get('/defense-requests/{defenseRequest}/create-schedule', [\App\Http\Controllers\DefenseRequestController::class, 'createSchedule'])->name('defense-requests.create-schedule');
    Route::post('/defense-requests/{defenseRequest}/store-schedule', [\App\Http\Controllers\DefenseRequestController::class, 'storeSchedule'])->name('defense-requests.store-schedule');
    Route::get('/defense-requests/{defenseSchedule}/edit-schedule', [\App\Http\Controllers\DefenseRequestController::class, 'editSchedule'])->name('defense-requests.edit-schedule');
    Route::put('/defense-requests/{defenseSchedule}/update-schedule', [\App\Http\Controllers\DefenseRequestController::class, 'updateSchedule'])->name('defense-requests.update-schedule');
    Route::post('/defense-requests/{defenseRequest}/approve', [\App\Http\Controllers\DefenseRequestController::class, 'approve'])->name('defense-requests.approve');
    Route::post('/defense-requests/{defenseRequest}/reject', [\App\Http\Controllers\DefenseRequestController::class, 'reject'])->name('defense-requests.reject');
    
    // Enhanced Scheduling (Defense Schedules) - Moved from Chairperson
    Route::resource('scheduling', \App\Http\Controllers\Chairperson\DefenseScheduleController::class)->parameters(['scheduling' => 'defenseSchedule']);
    Route::patch('/scheduling/{defenseSchedule}/status', [\App\Http\Controllers\Chairperson\DefenseScheduleController::class, 'updateStatus'])->name('scheduling.update-status');
    Route::get('/scheduling/available-faculty', [\App\Http\Controllers\Chairperson\DefenseScheduleController::class, 'getAvailableFaculty'])->name('scheduling.available-faculty');
});

// Notification routes
Route::middleware(['auth'])->group(function () {
    Route::post('/notifications/{notification}/mark-read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-multiple-read', [\App\Http\Controllers\NotificationController::class, 'markMultipleAsRead'])->name('notifications.mark-multiple-read');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications/delete-multiple', [\App\Http\Controllers\NotificationController::class, 'deleteMultiple'])->name('notifications.delete-multiple');
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'getNotifications'])->name('notifications.get');
});
