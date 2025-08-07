@extends('layouts.coordinator')

@section('content')
<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Welcome, {{ auth()->user()->name }}!</h1>
                    <p class="text-muted mb-0">Manage capstone projects, groups, and academic activities</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('coordinator.groups.index') }}" class="btn btn-primary">
                        <i class="fas fa-users me-2"></i>Manage Groups
                    </a>
                    <a href="{{ route('coordinator.milestones.index') }}" class="btn btn-outline-primary">
                        <i class="fas fa-flag me-2"></i>Milestones
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

    <!-- Project Management Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Students</h5>
                    <h3 class="mb-0">{{ $studentCount ?? 0 }}</h3>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-white" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Active Groups</h5>
                    <h3 class="mb-0">{{ $groupCount ?? 0 }}</h3>
                    <small>{{ $totalGroupMembers ?? 0 }} total members</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Faculty Members</h5>
                    <h3 class="mb-0">{{ $facultyCount ?? 0 }}</h3>
                    <small>advisers & panelists</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Submissions</h5>
                    <h3 class="mb-0">{{ $submissionCount ?? 0 }}</h3>
                    <small>{{ $pendingSubmissions ?? 0 }} pending review</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Status Overview -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>System Status Overview
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Group Assignment Status</h6>
                            <p class="text-muted mb-0">{{ $groupsWithAdviser ?? 0 }} groups have advisers, {{ $groupsWithoutAdviser ?? 0 }} need assignment</p>
                        </div>
                        <div class="text-end">
                            <div class="progress mb-2" style="width: 150px; height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ ($groupCount ?? 0) > 0 ? (($groupsWithAdviser ?? 0) / ($groupCount ?? 1)) * 100 : 0 }}%"></div>
                            </div>
                            <small class="text-muted">{{ ($groupCount ?? 0) > 0 ? round((($groupsWithAdviser ?? 0) / ($groupCount ?? 1)) * 100) : 0 }}% assigned</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities and Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-8">
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
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $activity->title }}</h6>
                                        <small class="text-muted">{{ $activity->description }}</small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary">{{ $activity->type }}</span>
                                        <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No recent activities</h6>
                            <p class="text-muted small">Activities will appear here as they occur.</p>
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
                        <a href="{{ route('coordinator.groups.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i>Create Group
                        </a>
                        <a href="{{ route('coordinator.milestones.create') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-flag me-2"></i>Create Milestone
                        </a>
                        <a href="{{ route('coordinator.events.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-calendar me-2"></i>Manage Events
                        </a>
                        <a href="{{ route('coordinator.classlist.index') }}" class="btn btn-outline-success">
                            <i class="fas fa-list me-2"></i>View Class List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pending Invitations -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope me-2"></i>Pending Invitations
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($pendingInvitations) && $pendingInvitations->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($pendingInvitations as $invitation)
                                <div class="list-group-item px-0">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <i class="fas fa-user-tie text-warning"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $invitation->faculty->name }}</h6>
                                            <small class="text-muted">{{ $invitation->group->name }}</small>
                                            <br>
                                            <small class="text-muted">{{ $invitation->created_at->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                            <p class="text-muted small mb-0">No pending invitations</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Groups and Submissions -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Recent Groups
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($recentGroups) && $recentGroups->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentGroups as $group)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $group->name }}</h6>
                                        <small class="text-muted">{{ $group->members->count() }} members</small>
                                        @if($group->adviser)
                                            <br>
                                            <small class="text-success">
                                                <i class="fas fa-user-tie me-1"></i>{{ $group->adviser->name }}
                                            </small>
                                        @else
                                            <br>
                                            <small class="text-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i>No adviser
                                            </small>
                                        @endif
                                    </div>
                                    <a href="{{ route('coordinator.groups.show', $group) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No groups yet</h6>
                            <p class="text-muted small">Groups will appear here when created.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>Recent Submissions
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($recentSubmissions) && $recentSubmissions->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentSubmissions as $submission)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ ucfirst($submission->type) }} Submission</h6>
                                        <small class="text-muted">{{ $submission->student->name }}</small>
                                        <br>
                                        <span class="badge bg-{{ $submission->status === 'approved' ? 'success' : ($submission->status === 'rejected' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($submission->status) }}
                                        </span>
                                    </div>
                                    <small class="text-muted">{{ $submission->created_at->diffForHumans() }}</small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No submissions yet</h6>
                            <p class="text-muted small">Submissions will appear here when students upload files.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Events and Deadlines -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar me-2"></i>Upcoming Events & Deadlines
                    </h5>
                </div>
                <div class="card-body">
                    @if((isset($events) && $events->count() > 0) || (isset($upcomingDeadlines) && $upcomingDeadlines->count() > 0))
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Event/Deadline</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($events as $event)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $event->title }}</div>
                                                <small class="text-muted">Event</small>
                                            </td>
                                            <td>
                                                <span class="text-primary">
                                                    {{ $event->date ? \Carbon\Carbon::parse($event->date)->format('M d, Y') : 'N/A' }}
                                                    @if($event->time)
                                                        at {{ \Carbon\Carbon::parse($event->time)->format('h:i A') }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">Event</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Scheduled</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('events.show', $event->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
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
                                                <span class="badge bg-warning">Deadline</span>
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
                            <h6 class="text-muted">No upcoming events or deadlines</h6>
                            <p class="text-muted small">Events and deadlines will appear here when scheduled.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
