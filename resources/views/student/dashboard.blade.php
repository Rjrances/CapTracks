@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="container mt-5">
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
                    <h5 class="card-title">Pending Tasks</h5>
                    <h3 class="mb-0">{{ $taskStats['pending'] ?? 9 }}</h3>
                    <small>needs attention</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Submissions</h5>
                    <h3 class="mb-0">{{ $submissionsCount ?? 2 }}</h3>
                    <small>documents uploaded</small>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Recent Tasks -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>Recent Tasks
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($recentTasks) && $recentTasks->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentTasks as $task)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $task->name }}</h6>
                                        <small class="text-muted">{{ $task->description }}</small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($task->is_completed)
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-warning">Pending</span>
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

        <!-- Quick Actions -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('student.project.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-upload me-2"></i>Upload Document
                        </a>
                        <a href="{{ route('student.group') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-users me-2"></i>View Group
                        </a>
                        <a href="{{ route('student.milestones') }}" class="btn btn-outline-info">
                            <i class="fas fa-flag me-2"></i>View Milestones
                        </a>
                        <a href="{{ route('student.proposal') }}" class="btn btn-outline-success">
                            <i class="fas fa-file-alt me-2"></i>Proposal & Endorsement
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Recent Activities
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($recentActivities) && $recentActivities->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentActivities as $activity)
                                <div class="list-group-item px-0">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <i class="fas fa-{{ $activity->icon ?? 'circle' }} text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $activity->title }}</h6>
                                            <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
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
                                <thead>
                                    <tr>
                                        <th>Task/Milestone</th>
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
                                                <span class="text-{{ $deadline->is_overdue ? 'danger' : 'primary' }}">
                                                    {{ $deadline->due_date->format('M d, Y') }}
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
@endsection 