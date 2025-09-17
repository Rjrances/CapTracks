@extends('layouts.coordinator')
@section('title', 'Group Milestones - ' . $group->name)
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1">Milestones for: {{ $group->name }}</h1>
            <p class="text-muted mb-0">View and monitor milestone progress (Read-only access)</p>
        </div>
        <a href="{{ route('coordinator.groups.show', $group->id) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Group
        </a>
    </div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-users me-2"></i>Group Information
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-semibold">Group Details</h6>
                    <p><strong>Name:</strong> {{ $group->name }}</p>
                    <p><strong>Description:</strong> {{ $group->description ?? 'No description provided' }}</p>
                    <p><strong>Adviser:</strong> 
                        @if($group->adviser)
                            <span class="badge bg-success">{{ $group->adviser->name }}</span>
                        @else
                            <span class="badge bg-danger">No adviser assigned</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-semibold">Members ({{ $group->members->count() }})</h6>
                    @foreach($group->members as $member)
                        <span class="badge bg-secondary me-1 mb-1">{{ $member->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-chart-line me-2"></i>Overall Progress
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <h6>Project Completion</h6>
                        @php
                            $overallProgress = $group->groupMilestones->count() > 0 
                                ? round($group->groupMilestones->avg('progress_percentage'))
                                : 0;
                        @endphp
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar {{ $overallProgress >= 60 ? 'bg-success' : ($overallProgress >= 40 ? 'bg-warning' : 'bg-danger') }}" 
                                 role="progressbar" 
                                 style="width: {{ $overallProgress }}%" 
                                 aria-valuenow="{{ $overallProgress }}" 
                                 aria-valuemin="0" aria-valuemax="100">
                                {{ $overallProgress }}%
                            </div>
                        </div>
                        <small class="text-muted">Overall project completion percentage</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <h4 class="mb-0 {{ $overallProgress >= 60 ? 'text-success' : ($overallProgress >= 40 ? 'text-warning' : 'text-danger') }}">
                            {{ $overallProgress }}%
                        </h4>
                        <small class="text-muted">Complete</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-flag me-2"></i>Assigned Milestones (View Only)
                </h5>
                <span class="badge bg-primary">{{ $group->groupMilestones->count() }} assigned</span>
            </div>
        </div>
        <div class="card-body p-0">
            @if($group->groupMilestones->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Milestone</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Target Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group->groupMilestones as $groupMilestone)
                            <tr>
                                <td>
                                    <strong>{{ $groupMilestone->milestoneTemplate->name }}</strong>
                                    @if($groupMilestone->notes)
                                        <br><small class="text-muted">{{ Str::limit($groupMilestone->notes, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar {{ $groupMilestone->progress_percentage >= 80 ? 'bg-success' : ($groupMilestone->progress_percentage >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                             role="progressbar" 
                                             style="width: {{ $groupMilestone->progress_percentage }}%" 
                                             aria-valuenow="{{ $groupMilestone->progress_percentage }}" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            {{ $groupMilestone->progress_percentage }}%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($groupMilestone->status) {
                                            'completed' => 'success',
                                            'almost_done' => 'warning',
                                            'in_progress' => 'info',
                                            default => 'secondary'
                                        };
                                        $statusText = ucfirst(str_replace('_', ' ', $groupMilestone->status));
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ $statusText }}</span>
                                </td>
                                <td>
                                    @if($groupMilestone->target_date)
                                        <span class="text-{{ $groupMilestone->is_overdue ? 'danger' : 'primary' }}">
                                            {{ \Carbon\Carbon::parse($groupMilestone->target_date)->format('M d, Y') }}
                                        </span>
                                        @if($groupMilestone->is_overdue)
                                            <br><small class="text-danger">Overdue</small>
                                        @endif
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="#" class="btn btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <span class="btn btn-outline-secondary disabled" title="Coordinator can only view milestones">
                                            <i class="fas fa-eye"></i> View Only
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-flag fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">No milestones assigned yet</h6>
                    <p class="text-muted small">This group has no milestones assigned. Contact the group's adviser to assign milestones.</p>
                </div>
            @endif
        </div>
    </div>
    <div class="card mt-4 border-warning">
        <div class="card-header bg-warning text-dark">
            <h6 class="mb-0">
                <i class="fas fa-bolt me-2"></i>Quick Actions
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <a href="{{ route('coordinator.groups.show', $group->id) }}" class="btn btn-outline-primary w-100">
                        <i class="fas fa-chart-bar me-2"></i>View Group Details
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('coordinator.groups.assignAdviser', $group->id) }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-user-tie me-2"></i>Manage Adviser
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('coordinator.groups.show', $group->id) }}" class="btn btn-outline-info w-100">
                        <i class="fas fa-users me-2"></i>View Group Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 
