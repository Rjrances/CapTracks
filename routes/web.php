<?php

use Illuminate\Support\Facades\Route;

// Health check route for Railway
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'app' => config('app.name'),
        'env' => config('app.env')
    ]);
});

// Simple test route
Route::get('/test', function () {
    return 'CapTrack is working!';
});
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\CoordinatorDashboardController;
use App\Http\Controllers\ChairpersonDashboardController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ChairpersonController;
use App\Http\Controllers\ChairpersonOfferingController;
use App\Http\Controllers\ChairpersonFacultyController;
use App\Http\Controllers\ChairpersonStudentController;
use App\Http\Controllers\ClassController;

use App\Http\Controllers\MilestoneTemplateController;


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



   // coordinator Routes
Route::middleware(['auth', 'checkrole:coordinator,adviser'])->prefix('coordinator')->name('coordinator.')->group(function () {
    // Coordinator Dashboard
    Route::get('/dashboard', [CoordinatorDashboardController::class, 'index'])->name('dashboard');

    // View Class List by Semester
     Route::get('/classlist', [CoordinatorController::class, 'classlist'])->name('classlist.index');
    // Milestone Templates - REMOVED for Coordinator (only Chairperson can manage)
    // Route::resource('milestones', MilestoneTemplateController::class);
    // Route::patch('milestones/{milestone}/status', [MilestoneTemplateController::class, 'updateStatus'])->name('milestones.updateStatus');

// Milestone Tasks - REMOVED for Coordinator (only Chairperson can manage)
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

    // Calendar
    Route::get('/calendar', [\App\Http\Controllers\CalendarController::class, 'coordinatorCalendar'])->name('calendar');
    
    // Proposal Review (for coordinators to approve proposals from their offerings)
    Route::get('/proposals', [\App\Http\Controllers\CoordinatorProposalController::class, 'index'])->name('proposals.index');
    Route::get('/proposals/{id}', [\App\Http\Controllers\CoordinatorProposalController::class, 'show'])->name('proposals.show');
    Route::get('/proposals/{id}/review', [\App\Http\Controllers\CoordinatorProposalController::class, 'review'])->name('proposals.review');
    Route::put('/proposals/{id}', [\App\Http\Controllers\CoordinatorProposalController::class, 'update'])->name('proposals.update');
    Route::post('/proposals/bulk-update', [\App\Http\Controllers\CoordinatorProposalController::class, 'bulkUpdate'])->name('proposals.bulk-update');
    Route::get('/proposals/stats', [\App\Http\Controllers\CoordinatorProposalController::class, 'getStats'])->name('proposals.stats');
    
    // Milestone Templates Management
    Route::resource('milestones', MilestoneTemplateController::class);
    Route::patch('milestones/{milestone}/status', [MilestoneTemplateController::class, 'updateStatus'])->name('milestones.updateStatus');
});


    // Chairperson Routes
    Route::middleware(['checkrole:chairperson'])->prefix('chairperson')->name('chairperson.')->group(function () {

        Route::get('/dashboard', [ChairpersonDashboardController::class, 'index'])->name('dashboard');

        // Roles
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles/{faculty_id}', [RoleController::class, 'update'])->name('roles.update');

        // Offerings
        Route::get('/offerings', [ChairpersonOfferingController::class, 'index'])->name('offerings.index');
        Route::get('/offerings/create', [ChairpersonOfferingController::class, 'create'])->name('offerings.create');
        Route::post('/offerings', [ChairpersonOfferingController::class, 'store'])->name('offerings.store');
        Route::get('/offerings/{id}', [ChairpersonOfferingController::class, 'show'])->name('offerings.show');
        Route::get('/offerings/{id}/edit', [ChairpersonOfferingController::class, 'edit'])->name('offerings.edit');
        Route::put('/offerings/{id}', [ChairpersonOfferingController::class, 'update'])->name('offerings.update');
        Route::delete('/offerings/{id}', [ChairpersonOfferingController::class, 'destroy'])->name('offerings.delete');
        
        // Offering Student Management
        Route::delete('/offerings/{offeringId}/students/{studentId}', [ChairpersonOfferingController::class, 'removeStudent'])->name('offerings.remove-student');

        // Role Management

        // Teachers/Faculty Management
        Route::get('/teachers', [ChairpersonFacultyController::class, 'index'])->name('teachers.index');
        Route::get('/teachers/create', [ChairpersonFacultyController::class, 'create'])->name('teachers.create');
        Route::post('/teachers', [ChairpersonFacultyController::class, 'upload'])->name('teachers.store');
        Route::get('/teachers/create-manual', [ChairpersonFacultyController::class, 'createManual'])->name('teachers.create-manual');
        Route::post('/teachers/manual', [ChairpersonFacultyController::class, 'storeManual'])->name('teachers.store-manual');
        Route::get('/teachers/{id}/edit', [ChairpersonFacultyController::class, 'edit'])->name('teachers.edit');
        Route::put('/teachers/{id}', [ChairpersonFacultyController::class, 'update'])->name('teachers.update');
        Route::delete('/teachers/{id}', [ChairpersonFacultyController::class, 'destroy'])->name('teachers.delete');




        // Student Management
        Route::get('/students', [ChairpersonStudentController::class, 'index'])->name('students.index');
        Route::get('/students/export', [ChairpersonStudentController::class, 'export'])->name('students.export');
        Route::get('/students/{id}/edit', [ChairpersonStudentController::class, 'edit'])->name('students.edit');
        Route::put('/students/{id}', [ChairpersonStudentController::class, 'update'])->name('students.update');
        Route::delete('/students/bulk-delete', [ChairpersonStudentController::class, 'bulkDelete'])->name('students.bulk-delete');
        Route::delete('/students/{id}', [ChairpersonStudentController::class, 'destroy'])->name('students.delete');

        // Student Enrollment Management
        Route::get('/offerings/{offeringId}/unenrolled-students', [ChairpersonOfferingController::class, 'showUnenrolledStudents'])->name('offerings.unenrolled-students');
        Route::post('/offerings/{offeringId}/enroll-student', [ChairpersonOfferingController::class, 'enrollStudent'])->name('offerings.enroll-student');
        Route::post('/offerings/{offeringId}/enroll-multiple-students', [ChairpersonOfferingController::class, 'enrollMultipleStudents'])->name('offerings.enroll-multiple-students');
        
        // Student Import via Excel
        Route::get('/upload-students', fn () => view('chairperson.students.import'))->name('upload-form');
        Route::post('/upload-students', [ChairpersonStudentController::class, 'upload'])->name('upload-students');

        // Academic Terms
        Route::resource('academic-terms', \App\Http\Controllers\AcademicTermController::class);
        Route::post('/academic-terms/{academicTerm}/toggle-active', [\App\Http\Controllers\AcademicTermController::class, 'toggleActive'])->name('academic-terms.toggle-active');
        Route::post('/academic-terms/{academicTerm}/toggle-archived', [\App\Http\Controllers\AcademicTermController::class, 'toggleArchived'])->name('academic-terms.toggle-archived');

        // Notification Management
        Route::get('/notifications', [ChairpersonController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/mark-all-read', [ChairpersonController::class, 'markAllNotificationsAsRead'])->name('notifications.mark-all-read');
        Route::post('/notifications/{notification}/mark-read', [ChairpersonController::class, 'markNotificationAsRead'])->name('notifications.mark-read');
        Route::delete('/notifications/{notification}', [ChairpersonController::class, 'deleteNotification'])->name('notifications.delete');

        // Scheduling (Defense Schedules) - Moved to Coordinator
        // Route::resource('scheduling', \App\Http\Controllers\Chairperson\DefenseScheduleController::class)->parameters(['scheduling' => 'defenseSchedule']);
        // Route::patch('/scheduling/{defenseSchedule}/status', [\App\Http\Controllers\Chairperson\DefenseScheduleController::class, 'updateStatus'])->name('scheduling.update-status');
        // Route::get('/scheduling/available-faculty', [\App\Http\Controllers\Chairperson\DefenseScheduleController::class, 'getAvailableFaculty'])->name('scheduling.available-faculty');
        
        // Calendar
        Route::get('/calendar', [\App\Http\Controllers\CalendarController::class, 'chairpersonCalendar'])->name('calendar');
    });
});

// Student dashboard and feature pages
Route::prefix('student')->name('student.')->middleware([\App\Http\Middleware\StudentAuthMiddleware::class, \App\Http\Middleware\CheckStudentPasswordChange::class])->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    
    // Password change routes (excluded from password change middleware)
    Route::get('/change-password', [\App\Http\Controllers\StudentPasswordController::class, 'showChangePasswordForm'])->name('change-password')->withoutMiddleware(\App\Http\Middleware\CheckStudentPasswordChange::class);
    Route::post('/update-password', [\App\Http\Controllers\StudentPasswordController::class, 'updatePassword'])->name('update-password')->withoutMiddleware(\App\Http\Middleware\CheckStudentPasswordChange::class);
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
    Route::post('/group/invite-member', [\App\Http\Controllers\StudentGroupController::class, 'inviteMember'])->name('group.invite-member');
    Route::delete('/group/remove-member/{memberId}', [\App\Http\Controllers\StudentGroupController::class, 'removeMember'])->name('group.remove-member');
    
    // Group invitation routes
    Route::get('/group/invitations', [\App\Http\Controllers\StudentGroupController::class, 'invitations'])->name('group.invitations');
    Route::post('/group/accept-invitation/{invitationId}', [\App\Http\Controllers\StudentGroupController::class, 'acceptInvitation'])->name('group.accept-invitation');
    Route::post('/group/decline-invitation/{invitationId}', [\App\Http\Controllers\StudentGroupController::class, 'declineInvitation'])->name('group.decline-invitation');
    Route::delete('/group/cancel-invitation/{invitationId}', [\App\Http\Controllers\StudentGroupController::class, 'cancelInvitation'])->name('group.cancel-invitation');
    
    Route::get('/proposal', [\App\Http\Controllers\StudentProposalController::class, 'index'])->name('proposal');
    Route::get('/proposal/create', [\App\Http\Controllers\StudentProposalController::class, 'create'])->name('proposal.create');
    Route::post('/proposal', [\App\Http\Controllers\StudentProposalController::class, 'store'])->name('proposal.store');
    Route::get('/proposal/{id}', [\App\Http\Controllers\StudentProposalController::class, 'show'])->name('proposal.show');
    Route::get('/proposal/{id}/edit', [\App\Http\Controllers\StudentProposalController::class, 'edit'])->name('proposal.edit');
    Route::put('/proposal/{id}', [\App\Http\Controllers\StudentProposalController::class, 'update'])->name('proposal.update');
    Route::get('/milestones', [\App\Http\Controllers\StudentMilestoneController::class, 'index'])->name('milestones');
    Route::get('/milestones/create', [\App\Http\Controllers\StudentMilestoneController::class, 'create'])->name('milestones.create');
    Route::post('/milestones', [\App\Http\Controllers\StudentMilestoneController::class, 'store'])->name('milestones.store');
    Route::get('/milestones/{milestone}', [\App\Http\Controllers\StudentMilestoneController::class, 'show'])->name('milestones.show');
    Route::get('/milestones/{milestone}/edit', [\App\Http\Controllers\StudentMilestoneController::class, 'edit'])->name('milestones.edit');
    Route::put('/milestones/{milestone}', [\App\Http\Controllers\StudentMilestoneController::class, 'update'])->name('milestones.update');
    Route::delete('/milestones/{milestone}', [\App\Http\Controllers\StudentMilestoneController::class, 'destroy'])->name('milestones.destroy');
    Route::patch('/milestones/{milestone}/update-tasks', [\App\Http\Controllers\StudentMilestoneController::class, 'updateMultipleTasks'])->name('milestones.update-tasks');
    Route::patch('/task/{groupMilestoneTask}/assign', [\App\Http\Controllers\StudentMilestoneController::class, 'assignTask'])->name('milestones.assign-task');
    
    // Task Submission Routes
    Route::get('/task-submission/{task}/create', [\App\Http\Controllers\TaskSubmissionController::class, 'create'])->name('task-submission.create');
    Route::post('/task-submission/{task}/store', [\App\Http\Controllers\TaskSubmissionController::class, 'store'])->name('task-submission.store');
    Route::get('/task-submission/{submission}/show', [\App\Http\Controllers\TaskSubmissionController::class, 'show'])->name('task-submission.show');
    Route::delete('/task/{groupMilestoneTask}/unassign', [\App\Http\Controllers\StudentMilestoneController::class, 'unassignTask'])->name('milestones.unassign-task');
    Route::patch('/task/{groupMilestoneTask}', [\App\Http\Controllers\StudentMilestoneController::class, 'updateTask'])->name('milestones.update-task');
    
    // NEW: Kanban functionality routes
    Route::patch('/milestones/tasks/{taskId}/move', [\App\Http\Controllers\StudentMilestoneController::class, 'moveTask'])->name('milestones.move-task');
    Route::patch('/milestones/{milestoneId}/bulk-update', [\App\Http\Controllers\StudentMilestoneController::class, 'bulkUpdateTasks'])->name('milestones.bulk-update');
    Route::post('/milestones/{milestoneId}/recompute-progress', [\App\Http\Controllers\StudentMilestoneController::class, 'recomputeProgress'])->name('milestones.recompute-progress');
    
    // Defense Request Routes
    Route::get('/defense-requests', [\App\Http\Controllers\StudentDefenseRequestController::class, 'index'])->name('defense-requests.index');
    Route::get('/defense-requests/create', [\App\Http\Controllers\StudentDefenseRequestController::class, 'create'])->name('defense-requests.create');
    Route::post('/defense-requests', [\App\Http\Controllers\StudentDefenseRequestController::class, 'store'])->name('defense-requests.store');
    Route::get('/defense-requests/{defenseRequest}', [\App\Http\Controllers\StudentDefenseRequestController::class, 'show'])->name('defense-requests.show');
    Route::delete('/defense-requests/{defenseRequest}', [\App\Http\Controllers\StudentDefenseRequestController::class, 'cancel'])->name('defense-requests.cancel');
    
    // Notification Management
    Route::get('/notifications', [\App\Http\Controllers\StudentController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\StudentController::class, 'markAllNotificationsAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{notification}/mark-read', [\App\Http\Controllers\StudentController::class, 'markNotificationAsRead'])->name('notifications.mark-read');
    Route::delete('/notifications/{notification}', [\App\Http\Controllers\StudentController::class, 'deleteNotification'])->name('notifications.delete');
    
    // Calendar
    Route::get('/calendar', [\App\Http\Controllers\CalendarController::class, 'studentCalendar'])->name('calendar');
});

// Adviser/Faculty Routes
Route::middleware(['auth'])->prefix('adviser')->name('adviser.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AdviserController::class, 'dashboard'])->name('dashboard');
    Route::get('/invitations', [\App\Http\Controllers\AdviserController::class, 'invitations'])->name('invitations');
    Route::post('/invitations/{invitation}/respond', [\App\Http\Controllers\AdviserController::class, 'respondToInvitation'])->name('invitations.respond');
    Route::get('/groups', [\App\Http\Controllers\AdviserController::class, 'myGroups'])->name('groups');
    Route::get('/all-groups', [\App\Http\Controllers\AdviserController::class, 'allGroups'])->name('all-groups');
    Route::get('/panel-submissions', [\App\Http\Controllers\AdviserController::class, 'panelSubmissions'])->name('panel-submissions');
    Route::get('/groups/{group}', [\App\Http\Controllers\AdviserController::class, 'groupDetails'])->name('groups.details');
    

    
    // Project review routes
    Route::get('/projects', [\App\Http\Controllers\ProjectSubmissionController::class, 'index'])->name('project.index');
    Route::get('/projects/{id}', [\App\Http\Controllers\ProjectSubmissionController::class, 'show'])->name('project.show');
    Route::get('/projects/{id}/edit', [\App\Http\Controllers\ProjectSubmissionController::class, 'edit'])->name('project.edit');
    Route::put('/projects/{id}', [\App\Http\Controllers\ProjectSubmissionController::class, 'update'])->name('project.update');
    
    // Proposal review routes
    Route::get('/proposals', [\App\Http\Controllers\AdviserProposalController::class, 'index'])->name('proposal.index');
    Route::get('/proposals/{id}', [\App\Http\Controllers\AdviserProposalController::class, 'show'])->name('proposal.show');
    Route::get('/proposals/{id}/edit', [\App\Http\Controllers\AdviserProposalController::class, 'edit'])->name('proposal.edit');
    Route::put('/proposals/{id}', [\App\Http\Controllers\AdviserProposalController::class, 'update'])->name('proposal.update');
    Route::post('/proposals/bulk-update', [\App\Http\Controllers\AdviserProposalController::class, 'bulkUpdate'])->name('proposal.bulk-update');
    Route::get('/proposals/stats', [\App\Http\Controllers\AdviserProposalController::class, 'getStats'])->name('proposal.stats');
    
    // Notification management
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\AdviserController::class, 'markAllNotificationsAsRead'])->name('notifications.mark-all-read-adviser');
    Route::post('/notifications/{notification}/mark-read', [\App\Http\Controllers\AdviserController::class, 'markNotificationAsRead'])->name('notifications.mark-read-adviser');
    
    // Calendar
    Route::get('/calendar', [\App\Http\Controllers\CalendarController::class, 'adviserCalendar'])->name('calendar');
});

// Coordinator Defense Request Routes
Route::middleware(['auth', 'checkrole:coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () {
    Route::get('/defense-requests/{defenseRequest}/create-schedule', [\App\Http\Controllers\Coordinator\DefenseScheduleController::class, 'createSchedule'])->name('defense-requests.create-schedule');
    Route::post('/defense-requests/{defenseRequest}/store-schedule', [\App\Http\Controllers\Coordinator\DefenseScheduleController::class, 'storeSchedule'])->name('defense-requests.store-schedule');
    Route::get('/defense-requests/{defenseSchedule}/edit-schedule', [\App\Http\Controllers\Coordinator\DefenseScheduleController::class, 'editSchedule'])->name('defense-requests.edit-schedule');
    Route::put('/defense-requests/{defenseSchedule}/update-schedule', [\App\Http\Controllers\Coordinator\DefenseScheduleController::class, 'updateSchedule'])->name('defense-requests.update-schedule');
    Route::post('/defense-requests/{defenseRequest}/approve', [\App\Http\Controllers\Coordinator\DefenseScheduleController::class, 'approve'])->name('defense-requests.approve');
    Route::post('/defense-requests/{defenseRequest}/reject', [\App\Http\Controllers\Coordinator\DefenseScheduleController::class, 'reject'])->name('defense-requests.reject');
    
});

// General notification routes (for coordinators and other authenticated users)
Route::group([], function () {
    Route::post('/notifications/{notification}/mark-read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-multiple-read', [\App\Http\Controllers\NotificationController::class, 'markMultipleAsRead'])->name('notifications.mark-multiple-read');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications/delete-multiple', [\App\Http\Controllers\NotificationController::class, 'deleteMultiple'])->name('notifications.delete-multiple');
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'getNotifications'])->name('notifications.get');
});
