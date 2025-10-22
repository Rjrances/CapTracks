<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\CoordinatorDashboardController;
use App\Http\Controllers\ChairpersonController;
use App\Http\Controllers\ChairpersonDashboardController;
use App\Http\Controllers\ChairpersonOfferingController;
use App\Http\Controllers\ChairpersonFacultyController;
use App\Http\Controllers\ChairpersonStudentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\MilestoneTemplateController;
use App\Http\Controllers\AcademicTermController;
use App\Http\Controllers\CoordinatorProposalController;
use App\Http\Controllers\Coordinator\DefenseScheduleController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\StudentPasswordController;
use App\Http\Controllers\ProjectSubmissionController;
use App\Http\Controllers\StudentGroupController;
use App\Http\Controllers\StudentProposalController;
use App\Http\Controllers\StudentMilestoneController;
use App\Http\Controllers\TaskSubmissionController;
use App\Http\Controllers\StudentDefenseRequestController;
use App\Http\Controllers\AdviserController;
use App\Http\Controllers\AdviserProposalController;
use App\Http\Controllers\NotificationController;

// Health check route for Railway
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'app' => config('app.name'),
        'env' => config('app.env')
    ]);
});

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

    // Coordinator Routes
Route::middleware(['auth', 'checkrole:coordinator,adviser'])->prefix('coordinator')->name('coordinator.')->group(function () {
    // Coordinator Dashboard
    Route::get('/dashboard', [CoordinatorDashboardController::class, 'index'])->name('dashboard');

    // View Class List by Semester
    Route::get('/classlist', [CoordinatorController::class, 'classlist'])->name('classlist.index');

    // Defense Scheduling
    Route::resource('defense', DefenseScheduleController::class);
    Route::get('/defense/available-faculty', [DefenseScheduleController::class, 'getAvailableFaculty'])->name('defense.available-faculty');

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
    Route::post('/notifications/mark-all-read', [CoordinatorController::class, 'markAllNotificationsAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/mark-multiple-read', [CoordinatorController::class, 'markMultipleAsRead'])->name('notifications.mark-multiple-read');
    Route::post('/notifications/{notification}/mark-read', [CoordinatorController::class, 'markNotificationAsRead'])->name('notifications.mark-read');
    Route::delete('/notifications/{notification}', [CoordinatorController::class, 'deleteNotification'])->name('notifications.delete');
    Route::delete('/notifications/delete-multiple', [CoordinatorController::class, 'deleteMultiple'])->name('notifications.delete-multiple');

    // Profile
    Route::get('/profile', [CoordinatorController::class, 'profile'])->name('profile');

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'coordinatorCalendar'])->name('calendar');

    // Proposal Review
    Route::get('/proposals', [CoordinatorProposalController::class, 'index'])->name('proposals.index');
    Route::get('/proposals/{id}', [CoordinatorProposalController::class, 'show'])->name('proposals.show');
    Route::get('/proposals/{id}/review', [CoordinatorProposalController::class, 'review'])->name('proposals.review');
    Route::put('/proposals/{id}', [CoordinatorProposalController::class, 'update'])->name('proposals.update');
    Route::post('/proposals/bulk-update', [CoordinatorProposalController::class, 'bulkUpdate'])->name('proposals.bulk-update');
    Route::get('/proposals/stats', [CoordinatorProposalController::class, 'getStats'])->name('proposals.stats');

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
    Route::delete('/offerings/{offeringId}/students/{studentId}', [ChairpersonOfferingController::class, 'removeStudent'])->name('offerings.remove-student');

    // Student Enrollment Management
    Route::get('/offerings/{offeringId}/unenrolled-students', [ChairpersonOfferingController::class, 'showUnenrolledStudents'])->name('offerings.unenrolled-students');
    Route::post('/offerings/{offeringId}/enroll-student', [ChairpersonOfferingController::class, 'enrollStudent'])->name('offerings.enroll-student');
    Route::post('/offerings/{offeringId}/enroll-multiple-students', [ChairpersonOfferingController::class, 'enrollMultipleStudents'])->name('offerings.enroll-multiple-students');

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

    // Student Import
    Route::get('/upload-students', fn () => view('chairperson.students.import'))->name('upload-form');
    Route::post('/upload-students', [ChairpersonStudentController::class, 'upload'])->name('upload-students');

    // Academic Terms
    Route::resource('academic-terms', AcademicTermController::class);
    Route::post('/academic-terms/{academicTerm}/toggle-active', [AcademicTermController::class, 'toggleActive'])->name('academic-terms.toggle-active');
    Route::post('/academic-terms/{academicTerm}/toggle-archived', [AcademicTermController::class, 'toggleArchived'])->name('academic-terms.toggle-archived');

    // Notification Management
    Route::get('/notifications', [ChairpersonController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/mark-all-read', [ChairpersonController::class, 'markAllNotificationsAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/mark-multiple-read', [ChairpersonController::class, 'markMultipleAsRead'])->name('notifications.mark-multiple-read');
    Route::post('/notifications/{notification}/mark-read', [ChairpersonController::class, 'markNotificationAsRead'])->name('notifications.mark-read');
    Route::delete('/notifications/{notification}', [ChairpersonController::class, 'deleteNotification'])->name('notifications.delete');
    Route::delete('/notifications/delete-multiple', [ChairpersonController::class, 'deleteMultiple'])->name('notifications.delete-multiple');

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'chairpersonCalendar'])->name('calendar');
});
});

// Student Routes
Route::prefix('student')->name('student.')->middleware([\App\Http\Middleware\StudentAuthMiddleware::class, \App\Http\Middleware\CheckStudentPasswordChange::class])->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

    // Password change routes (excluded from password change middleware)
    Route::get('/change-password', [StudentPasswordController::class, 'showChangePasswordForm'])->name('change-password')->withoutMiddleware(\App\Http\Middleware\CheckStudentPasswordChange::class);
    Route::post('/update-password', [StudentPasswordController::class, 'updatePassword'])->name('update-password')->withoutMiddleware(\App\Http\Middleware\CheckStudentPasswordChange::class);

    // Project Submission
    Route::get('/project', [ProjectSubmissionController::class, 'index'])->name('project');
    Route::get('/project/create', [ProjectSubmissionController::class, 'create'])->name('project.create');
    Route::post('/project', [ProjectSubmissionController::class, 'store'])->name('project.store');
    Route::get('/project/{id}', [ProjectSubmissionController::class, 'show'])->name('project.show');
    Route::delete('/project/{id}', [ProjectSubmissionController::class, 'destroy'])->name('project.destroy');

    // Group Management
    Route::get('/group', [StudentGroupController::class, 'show'])->name('group');
    Route::get('/groups', [StudentGroupController::class, 'index'])->name('group.index');
    Route::get('/group/create', [StudentGroupController::class, 'create'])->name('group.create');
    Route::post('/group', [StudentGroupController::class, 'store'])->name('group.store');
    Route::get('/group/edit', [StudentGroupController::class, 'edit'])->name('group.edit');
    Route::put('/group', [StudentGroupController::class, 'update'])->name('group.update');
    Route::post('/group/invite-adviser', [StudentGroupController::class, 'inviteAdviser'])->name('group.invite-adviser');
    Route::post('/group/invite-member', [StudentGroupController::class, 'inviteMember'])->name('group.invite-member');
    Route::delete('/group/remove-member/{memberId}', [StudentGroupController::class, 'removeMember'])->name('group.remove-member');

    // Group Invitations
    Route::get('/group/invitations', [StudentGroupController::class, 'invitations'])->name('group.invitations');
    Route::post('/group/accept-invitation/{invitationId}', [StudentGroupController::class, 'acceptInvitation'])->name('group.accept-invitation');
    Route::post('/group/decline-invitation/{invitationId}', [StudentGroupController::class, 'declineInvitation'])->name('group.decline-invitation');
    Route::delete('/group/cancel-invitation/{invitationId}', [StudentGroupController::class, 'cancelInvitation'])->name('group.cancel-invitation');

    // Proposal
    Route::get('/proposal', [StudentProposalController::class, 'index'])->name('proposal');
    Route::get('/proposal/create', [StudentProposalController::class, 'create'])->name('proposal.create');
    Route::post('/proposal', [StudentProposalController::class, 'store'])->name('proposal.store');
    Route::get('/proposal/{id}', [StudentProposalController::class, 'show'])->name('proposal.show');
    Route::get('/proposal/{id}/edit', [StudentProposalController::class, 'edit'])->name('proposal.edit');
    Route::put('/proposal/{id}', [StudentProposalController::class, 'update'])->name('proposal.update');

    // Milestones
    Route::get('/milestones', [StudentMilestoneController::class, 'index'])->name('milestones');
    Route::get('/milestones/create', [StudentMilestoneController::class, 'create'])->name('milestones.create');
    Route::post('/milestones', [StudentMilestoneController::class, 'store'])->name('milestones.store');
    Route::get('/milestones/{milestone}', [StudentMilestoneController::class, 'show'])->name('milestones.show');
    Route::get('/milestones/{milestone}/edit', [StudentMilestoneController::class, 'edit'])->name('milestones.edit');
    Route::put('/milestones/{milestone}', [StudentMilestoneController::class, 'update'])->name('milestones.update');
    Route::delete('/milestones/{milestone}', [StudentMilestoneController::class, 'destroy'])->name('milestones.destroy');
    Route::patch('/milestones/{milestone}/update-tasks', [StudentMilestoneController::class, 'updateMultipleTasks'])->name('milestones.update-tasks');
    Route::patch('/milestones/tasks/{taskId}/move', [StudentMilestoneController::class, 'moveTask'])->name('milestones.move-task');
    Route::patch('/milestones/{milestoneId}/bulk-update', [StudentMilestoneController::class, 'bulkUpdateTasks'])->name('milestones.bulk-update');
    Route::post('/milestones/{milestoneId}/recompute-progress', [StudentMilestoneController::class, 'recomputeProgress'])->name('milestones.recompute-progress');

    // Milestone Tasks
    Route::patch('/task/{groupMilestoneTask}/assign', [StudentMilestoneController::class, 'assignTask'])->name('milestones.assign-task');
    Route::delete('/task/{groupMilestoneTask}/unassign', [StudentMilestoneController::class, 'unassignTask'])->name('milestones.unassign-task');
    Route::patch('/task/{groupMilestoneTask}', [StudentMilestoneController::class, 'updateTask'])->name('milestones.update-task');

    // Task Submission
    Route::get('/task-submission/{task}/create', [TaskSubmissionController::class, 'create'])->name('task-submission.create');
    Route::post('/task-submission/{task}/store', [TaskSubmissionController::class, 'store'])->name('task-submission.store');
    Route::get('/task-submission/{submission}/show', [TaskSubmissionController::class, 'show'])->name('task-submission.show');

    // Defense Requests
    Route::get('/defense-requests', [StudentDefenseRequestController::class, 'index'])->name('defense-requests.index');
    Route::get('/defense-requests/create', [StudentDefenseRequestController::class, 'create'])->name('defense-requests.create');
    Route::post('/defense-requests', [StudentDefenseRequestController::class, 'store'])->name('defense-requests.store');
    Route::get('/defense-requests/{defenseRequest}', [StudentDefenseRequestController::class, 'show'])->name('defense-requests.show');
    Route::delete('/defense-requests/{defenseRequest}', [StudentDefenseRequestController::class, 'cancel'])->name('defense-requests.cancel');

    // Notification Management
    Route::get('/notifications', [StudentController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/mark-all-read', [StudentController::class, 'markAllNotificationsAsRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/mark-multiple-read', [StudentController::class, 'markMultipleAsRead'])->name('notifications.mark-multiple-read');
    Route::post('/notifications/{notification}/mark-read', [StudentController::class, 'markNotificationAsRead'])->name('notifications.mark-read');
    Route::delete('/notifications/{notification}', [StudentController::class, 'deleteNotification'])->name('notifications.delete');
    Route::delete('/notifications/delete-multiple', [StudentController::class, 'deleteMultiple'])->name('notifications.delete-multiple');

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'studentCalendar'])->name('calendar');
});

// Adviser Routes
Route::middleware(['auth'])->prefix('adviser')->name('adviser.')->group(function () {
    Route::get('/dashboard', [AdviserController::class, 'dashboard'])->name('dashboard');

    // Invitations
    Route::get('/invitations', [AdviserController::class, 'invitations'])->name('invitations');
    Route::post('/invitations/{invitation}/respond', [AdviserController::class, 'respondToInvitation'])->name('invitations.respond');

    // Groups
    Route::get('/groups', [AdviserController::class, 'myGroups'])->name('groups');
    Route::get('/all-groups', [AdviserController::class, 'allGroups'])->name('all-groups');
    Route::get('/groups/{group}', [AdviserController::class, 'groupDetails'])->name('groups.details');
    Route::get('/panel-submissions', [AdviserController::class, 'panelSubmissions'])->name('panel-submissions');

    // Project Review
    Route::get('/projects', [ProjectSubmissionController::class, 'index'])->name('project.index');
    Route::get('/projects/{id}', [ProjectSubmissionController::class, 'show'])->name('project.show');
    Route::get('/projects/{id}/edit', [ProjectSubmissionController::class, 'edit'])->name('project.edit');
    Route::put('/projects/{id}', [ProjectSubmissionController::class, 'update'])->name('project.update');

    // Proposal Review
    Route::get('/proposals', [AdviserProposalController::class, 'index'])->name('proposal.index');
    Route::get('/proposals/{id}', [AdviserProposalController::class, 'show'])->name('proposal.show');
    Route::get('/proposals/{id}/edit', [AdviserProposalController::class, 'edit'])->name('proposal.edit');
    Route::put('/proposals/{id}', [AdviserProposalController::class, 'update'])->name('proposal.update');
    Route::post('/proposals/bulk-update', [AdviserProposalController::class, 'bulkUpdate'])->name('proposal.bulk-update');
    Route::get('/proposals/stats', [AdviserProposalController::class, 'getStats'])->name('proposal.stats');

    // Notification Management
    Route::post('/notifications/mark-all-read', [AdviserController::class, 'markAllNotificationsAsRead'])->name('notifications.mark-all-read-adviser');
    Route::post('/notifications/{notification}/mark-read', [AdviserController::class, 'markNotificationAsRead'])->name('notifications.mark-read-adviser');

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'adviserCalendar'])->name('calendar');
});

// Coordinator Defense Request Management
Route::middleware(['auth', 'checkrole:coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () {
    Route::get('/defense-requests/{defenseRequest}/create-schedule', [DefenseScheduleController::class, 'createSchedule'])->name('defense-requests.create-schedule');
    Route::post('/defense-requests/{defenseRequest}/store-schedule', [DefenseScheduleController::class, 'storeSchedule'])->name('defense-requests.store-schedule');
    Route::get('/defense-requests/{defenseSchedule}/edit-schedule', [DefenseScheduleController::class, 'editSchedule'])->name('defense-requests.edit-schedule');
    Route::put('/defense-requests/{defenseSchedule}/update-schedule', [DefenseScheduleController::class, 'updateSchedule'])->name('defense-requests.update-schedule');
    Route::post('/defense-requests/{defenseRequest}/approve', [DefenseScheduleController::class, 'approve'])->name('defense-requests.approve');
    Route::post('/defense-requests/{defenseRequest}/reject', [DefenseScheduleController::class, 'reject'])->name('defense-requests.reject');
});
