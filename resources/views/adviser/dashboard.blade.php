@extends('layouts.adviser')
@section('title', 'Teacher Dashboard')
@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 1200px;">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">Welcome, {{ auth()->check() ? auth()->user()->name : 'Teacher' }}!</h1>
                        <p class="text-muted mb-0">Manage your groups, invitations, and student projects</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('adviser.all-groups') }}" class="btn btn-primary">
                            <i class="fas fa-layer-group me-2"></i>All My Groups
                        </a>
                        <a href="{{ route('adviser.invitations') }}" class="btn btn-outline-primary">
                            <i class="fas fa-envelope me-2"></i>View Invitations
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
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Groups</h5>
                        <h3 class="mb-0">{{ $summaryStats['total_groups'] ?? 0 }}</h3>
                        <small>assigned</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Panel Groups</h5>
                        <h3 class="m-0">{{ $summaryStats['panel_groups'] ?? 0 }}</h3>
                        <small>assigned</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-secondary text-white h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Invitations</h5>
                        <h3 class="m-0">{{ $summaryStats['pending_invitations'] ?? 0 }}</h3>
                        <small>pending</small>
                    </div>
                </div>
            </div>
        </div>
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
                                        Current term for all academic operations and project supervision
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="text-muted small">Teacher View</span>
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
                                    <span class="text-muted small">Teacher View</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tasks me-2"></i>Recent Activities
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
                                            <span class="badge bg-{{ ($activity->type ?? 'general') == 'submission' ? 'success' : (($activity->type ?? 'general') == 'review' ? 'warning' : 'info') }}">
                                                {{ ucfirst($activity->type ?? 'general') }}
                                            </span>
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
                                <h6 class="text-muted">No recent activities</h6>
                                <p class="text-muted small">Activities will appear here as they occur.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('adviser.invitations') }}" class="btn btn-outline-primary">
                                <i class="fas fa-envelope me-2"></i>View Invitations
                            </a>
                            <a href="{{ route('adviser.groups') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-users me-2"></i>My Groups
                            </a>
                            <a href="{{ route('adviser.project.index') }}" class="btn btn-outline-info">
                                <i class="fas fa-file-alt me-2"></i>Project Reviews
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bell me-2"></i>Notifications
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($notifications->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($notifications as $notification)
                                    <div class="list-group-item px-0">
                                        <div class="d-flex align-items-start">
                                            <div class="me-3">
                                                <i class="fas fa-{{ $notification->icon ?? 'bell' }} text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">{{ $notification->title }}</h6>
                                                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-bell fa-2x text-muted mb-2"></i>
                                <p class="text-muted small mb-0">No notifications</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-envelope me-2"></i>Pending Invitations
                            @if($pendingInvitations->count() > 0)
                                <span class="badge bg-danger ms-2">{{ $pendingInvitations->count() }}</span>
                            @endif
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($pendingInvitations->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Group</th>
                                            <th>Members</th>
                                            <th>Message</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pendingInvitations as $invitation)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $invitation->group->name }}</div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">{{ $invitation->group->members->count() }} members</span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ Str::limit($invitation->message, 50) ?: 'No message' }}</small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ $invitation->created_at->diffForHumans() }}</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <form action="{{ route('adviser.invitations.respond', $invitation) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="status" value="accepted">
                                                            <button type="submit" class="btn btn-sm btn-success">
                                                                <i class="fas fa-check"></i> Accept
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('adviser.invitations.respond', $invitation) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="status" value="declined">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-times"></i> Decline
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No pending invitations</h6>
                                <p class="text-muted small">Invitations will appear here when students request your guidance.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>My Groups
                            @if($adviserGroups->count() > 0)
                                <span class="badge bg-primary ms-2">{{ $adviserGroups->count() }}</span>
                            @endif
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($adviserGroups->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Group Name</th>
                                            <th>Members</th>
                                            <th>Progress</th>
                                            <th>Next Milestone</th>
                                            <th>Submissions</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($adviserGroups as $group)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $group->name }}</div>
                                                    <small class="text-muted">{{ Str::limit($group->description, 50) }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">{{ $group->members->count() }} members</span>
                                                </td>
                                                <td>
                                                    <div class="progress mb-1" style="width: 100px; height: 6px;">
                                                        <div class="progress-bar 
                                                            @if($group->progress_percentage >= 80) bg-success
                                                            @elseif($group->progress_percentage >= 60) bg-warning
                                                            @else bg-danger
                                                            @endif" 
                                                            style="width: {{ $group->progress_percentage ?? 0 }}%"></div>
                                                    </div>
                                                    <small class="text-muted">{{ $group->progress_percentage ?? 0 }}%</small>
                                                </td>
                                                <td>
                                                    @if($group->next_milestone)
                                                        <div class="fw-semibold">{{ $group->next_milestone['name'] }}</div>
                                                        <small class="text-muted">{{ $group->next_milestone['progress'] }}% complete</small>
                                                        @if($group->next_milestone['target_date'])
                                                            <br><small class="text-muted">Due: {{ \Carbon\Carbon::parse($group->next_milestone['target_date'])->format('M d') }}</small>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">All complete!</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ $group->submissions_count ?? 0 }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                                                    <a href="{{ route('adviser.groups.details', $group) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No groups assigned yet</h6>
                                <p class="text-muted small">Groups will appear here when students invite you to be their teacher.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 
