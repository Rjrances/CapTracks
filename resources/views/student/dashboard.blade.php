@extends('layouts.student')

@section('title', 'Student Dashboard')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 1200px;">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">Welcome, {{ auth()->check() ? auth()->user()->name : session('student_name') }}!</h1>
                        <p class="text-muted mb-0">Track your capstone project progress</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('student.project') }}" class="btn btn-primary">
                            <i class="fas fa-file-alt me-2"></i>My Submissions
                        </a>
                        <a href="{{ route('student.group') }}" class="btn btn-outline-primary">
                            <i class="fas fa-users me-2"></i>My Group
                        </a>
                        <a href="{{ route('student.defense-requests.index') }}" class="btn btn-outline-success">
                            <i class="fas fa-gavel me-2"></i>Defense Requests
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Current Academic Term Context -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Current Academic Term Context
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($activeTerm ?? null)
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <h4 class="mb-0 me-3">{{ $activeTerm->full_name }}</h4>
                                        <span class="badge bg-success fs-6">Active</span>
                                    </div>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Current term for all academic operations and project work
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="text-muted small">Student View</span>
                                </div>
                            </div>
                        @else
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <h4 class="mb-0 me-3 text-warning">No Active Term</h4>
                                        <span class="badge bg-warning fs-6">Inactive</span>
                                    </div>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Please contact your coordinator about the current academic term
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="text-muted small">Student View</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Progress Overview -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Overall Progress</h5>
                        <h3 class="mb-0">{{ $overallProgress ?? 25 }}%</h3>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-white" style="width: {{ $overallProgress ?? 25 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Completed Tasks</h5>
                        <h3 class="mb-0">{{ $taskStats['completed'] ?? 3 }}</h3>
                        <small>of {{ $taskStats['total'] ?? 12 }} total</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">In Progress</h5>
                        <h3 class="mb-0">{{ $taskStats['doing'] ?? 2 }}</h3>
                        <small>currently working</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Pending Tasks</h5>
                        <h3 class="mb-0">{{ $taskStats['pending'] ?? 7 }}</h3>
                        <small>needs attention</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adviser & Defense Information -->
        @if($group)
        <div class="row mb-4">
            <!-- Adviser Information -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Adviser Information
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($adviserInfo['has_adviser'])
                            <div class="text-center">
                                <i class="fas fa-user-tie fa-3x text-primary mb-3"></i>
                                <h5 class="mb-2">{{ $adviserInfo['adviser']->name }}</h5>
                                <p class="text-muted mb-2">{{ $adviserInfo['adviser']->email }}</p>
                                <span class="badge bg-success fs-6">Assigned</span>
                            </div>
                        @elseif($adviserInfo['invitations']->count() > 0)
                            <div class="text-center">
                                <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                <h5 class="mb-2">Pending Invitations</h5>
                                <p class="text-muted mb-2">{{ $adviserInfo['invitations']->count() }} invitation(s) sent</p>
                                <span class="badge bg-warning fs-6">Awaiting Response</span>
                                
                                <div class="mt-3">
                                    @foreach($adviserInfo['invitations'] as $invitation)
                                        <div class="border rounded p-2 mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                {{ $invitation->faculty->name }}
                                                <br>
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $invitation->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-center">
                                <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                                <h5 class="mb-2">No Adviser Assigned</h5>
                                <p class="text-muted mb-3">You need an adviser to proceed with your project</p>
                                @if($adviserInfo['can_invite'])
                                    <a href="{{ route('student.group') }}" class="btn btn-primary">
                                        <i class="fas fa-envelope me-2"></i>Invite Adviser
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Defense Schedule Information -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>Defense Schedule
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($defenseInfo['scheduled_defenses']->count() > 0)
                            <div class="text-center mb-3">
                                <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                                <h5 class="mb-2">Scheduled Defenses</h5>
                            </div>
                            @foreach($defenseInfo['scheduled_defenses'] as $defense)
                                <div class="border rounded p-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ ucfirst(str_replace('_', ' ', $defense->defense_type)) }} Defense</h6>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                {{ $defense->start_at ? $defense->start_at->format('M d, Y') : 'TBA' }}
                                                <br>
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $defense->start_at ? $defense->start_at->format('h:i A') : 'TBA' }}
                                            </small>
                                        </div>
                                        <span class="badge bg-success">Scheduled</span>
                                    </div>
                                </div>
                            @endforeach
                        @elseif($defenseInfo['pending_requests']->count() > 0)
                            <div class="text-center mb-3">
                                <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                <h5 class="mb-2">Pending Requests</h5>
                            </div>
                            @foreach($defenseInfo['pending_requests'] as $request)
                                <div class="border rounded p-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ ucfirst(str_replace('_', ' ', $request->defense_type)) }} Defense</h6>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                Requested {{ $request->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                        <span class="badge bg-warning">Pending</span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center">
                                <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                                <h5 class="mb-2">No Defense Scheduled</h5>
                                <p class="text-muted mb-3">Defense schedules will appear here when scheduled</p>
                                @if($defenseInfo['can_request'])
                                    <a href="{{ route('student.group') }}" class="btn btn-warning">
                                        <i class="fas fa-rocket me-2"></i>Request Defense
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Current Milestone -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-flag me-2"></i>Current Milestone
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ $milestoneInfo['name'] ?? 'Proposal Development' }}</h6>
                                <p class="text-muted mb-0">{{ $milestoneInfo['description'] ?? 'Working on initial project proposal' }}</p>
                                @if(isset($milestoneInfo['status']))
                                    <span class="badge bg-{{ $milestoneInfo['status'] === 'completed' ? 'success' : ($milestoneInfo['status'] === 'in_progress' ? 'warning' : 'secondary') }} mt-2">
                                        {{ ucfirst(str_replace('_', ' ', $milestoneInfo['status'])) }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-end">
                                <div class="progress mb-2" style="width: 150px; height: 8px;">
                                    <div class="progress-bar bg-success" style="width: {{ $milestoneInfo['progress'] ?? 60 }}%"></div>
                                </div>
                                <small class="text-muted">{{ $milestoneInfo['progress'] ?? 60 }}% complete</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Row -->
        <div class="row mb-4">
            <!-- Recent Tasks -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-tasks me-2"></i>Recent Tasks
                            </h5>
                            <a href="{{ route('student.milestones') }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-columns me-1"></i>Kanban Board
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(isset($recentTasks) && $recentTasks->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recentTasks as $task)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ $task->name }}</h6>
                                            <small class="text-muted">{{ $task->description }}</small>
                                            @if($task->assigned_to)
                                                <br><small class="text-info"><i class="fas fa-user me-1"></i>{{ $task->assigned_to }}</small>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($task->status === 'done')
                                                <span class="badge bg-success">Done</span>
                                            @elseif($task->status === 'doing')
                                                <span class="badge bg-warning">Doing</span>
                                            @else
                                                <span class="badge bg-secondary">Pending</span>
                                            @endif
                                            <button class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No tasks assigned yet</h6>
                                <p class="text-muted small">Tasks will appear here when your adviser assigns them.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar - Quick Actions & Recent Activities -->
            <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('student.project.create') }}" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Upload Document
                            </a>
                            <a href="{{ route('student.group') }}" class="btn btn-outline-primary">
                                <i class="fas fa-users me-2"></i>View Group
                            </a>
                            <a href="{{ route('student.milestones') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-columns me-2"></i>Kanban Board
                            </a>
                            <a href="{{ route('student.proposal') }}" class="btn btn-outline-success">
                                <i class="fas fa-file-alt me-2"></i>Proposal & Endorsement
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Recent Activities
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(isset($recentActivities) && $recentActivities->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recentActivities as $activity)
                                    <div class="list-group-item px-0 border-0">
                                        <div class="d-flex align-items-start">
                                            <div class="me-2">
                                                <i class="fas fa-{{ $activity->icon ?? 'circle' }} text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">{{ $activity->title }}</h6>
                                                <small class="text-muted">{{ $activity->description }}</small>
                                                <br><small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                                <p class="text-muted small mb-0">No recent activities</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Deadlines -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar me-2"></i>Upcoming Deadlines
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(isset($upcomingDeadlines) && $upcomingDeadlines->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Task/Milestone</th>
                                            <th>Type</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($upcomingDeadlines as $deadline)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $deadline->title }}</div>
                                                    <small class="text-muted">{{ $deadline->description }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $deadline->type === 'milestone' ? 'primary' : 'info' }}">
                                                        {{ ucfirst($deadline->type) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-{{ $deadline->is_overdue ? 'danger' : 'primary' }}">
                                                        {{ $deadline->due_date ? $deadline->due_date->format('M d, Y') : 'TBA' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($deadline->is_overdue)
                                                        <span class="badge bg-danger">Overdue</span>
                                                    @elseif($deadline->is_due_soon)
                                                        <span class="badge bg-warning">Due Soon</span>
                                                    @else
                                                        <span class="badge bg-success">On Track</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No upcoming deadlines</h6>
                                <p class="text-muted small">Deadlines will appear here when they are set.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 