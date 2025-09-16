@extends('layouts.adviser')

@section('title', 'Adviser Workspace')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-user-tie me-3 text-success"></i>
                        Adviser Workspace
                    </h1>
                    <p class="text-muted mb-0">Detailed workspace for managing your student groups and monitoring progress</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('adviser.all-groups') }}" class="btn btn-outline-primary">
                        <i class="fas fa-layer-group me-2"></i>All My Groups
                    </a>
                    <a href="{{ route('adviser.project.index') }}" class="btn btn-success">
                        <i class="fas fa-file-alt me-2"></i>Review Projects
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Workspace Statistics -->
    <div class="row mb-4 justify-content-center">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-user-tie fa-2x me-3"></i>
                        <div>
                            <h2 class="mb-0">{{ $workspaceStats['total_adviser_groups'] }}</h2>
                            <small class="text-white-50">Adviser Groups</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-gavel fa-2x me-3"></i>
                        <div>
                            <h2 class="mb-0">{{ $workspaceStats['total_panel_groups'] }}</h2>
                            <small class="text-white-50">Panel Groups</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-chart-line fa-2x me-3"></i>
                        <div>
                            <h2 class="mb-0">{{ round($workspaceStats['average_progress']) }}%</h2>
                            <small class="text-white-50">Average Progress</small>
                        </div>
                    </div>
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

        <!-- Groups List -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white border-0">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-tie me-2"></i>
                            <h5 class="mb-0 fw-bold">Adviser Groups - Detailed Workspace</h5>
                            <span class="badge bg-light text-success ms-auto">{{ $groups->count() }} group(s)</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($groups->count() > 0)
                            @foreach($groups as $group)
                                <div class="group-item p-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                                    <div class="row">
                                        <!-- Group Icon and Basic Info -->
                                        <div class="col-md-1">
                                            <div class="group-icon-wrapper">
                                                <span class="group-icon">
                                                    <i class="fas fa-users"></i>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Main Group Details -->
                                        <div class="col-md-7">
                                            <div class="group-content">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h6 class="fw-bold text-dark mb-0 me-3">
                                                        <i class="fas fa-layer-group me-2 text-success"></i>
                                                        {{ $group->name }}
                                                    </h6>
                                                    @if($group->overdue_tasks > 0)
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            {{ $group->overdue_tasks }} overdue
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                <div class="group-meta mb-3">
                                                    <span class="badge bg-info me-2">
                                                        <i class="fas fa-users me-1"></i>
                                                        {{ $group->members->count() }} members
                                                    </span>
                                                    <span class="badge bg-secondary me-2">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        {{ $group->academicTerm->full_name ?? 'No Term' }}
                                                    </span>
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-clock me-1"></i>
                                                        Created {{ $group->created_at->diffForHumans() }}
                                                    </span>
                                                </div>

                                                <!-- Progress Section -->
                                                <div class="progress-section mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <small class="text-muted fw-bold">Overall Progress</small>
                                                        <small class="text-muted">{{ $group->progress_percentage }}%</small>
                                                    </div>
                                                    <div class="progress mb-2" style="height: 10px;">
                                                        <div class="progress-bar bg-success" role="progressbar" 
                                                             style="width: {{ $group->progress_percentage }}%" 
                                                             aria-valuenow="{{ $group->progress_percentage }}" 
                                                             aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <small class="text-muted">{{ $group->submissions_count }} submissions</small>
                                                        @if($group->next_milestone)
                                                            <small class="text-info">
                                                                <i class="fas fa-flag me-1"></i>
                                                                Next: {{ $group->next_milestone['name'] }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Milestone Progress -->
                                                @if($group->milestone_progress && count($group->milestone_progress) > 0)
                                                    <div class="milestone-progress mb-3">
                                                        <small class="text-muted fw-bold mb-2 d-block">Milestone Progress</small>
                                                        <div class="row">
                                                            @foreach($group->milestone_progress as $milestone)
                                                                <div class="col-md-6 mb-2">
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <small class="text-muted">{{ $milestone['name'] }}</small>
                                                                        <small class="text-muted">{{ $milestone['progress'] }}%</small>
                                                                    </div>
                                                                    <div class="progress" style="height: 6px;">
                                                                        <div class="progress-bar {{ $milestone['is_overdue'] ? 'bg-danger' : 'bg-info' }}" 
                                                                             style="width: {{ $milestone['progress'] }}%"></div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                                @if($group->description)
                                                    <div class="group-description mb-3">
                                                        <div class="alert alert-light border-start border-success border-3">
                                                            <i class="fas fa-info-circle me-2 text-success"></i>
                                                            <strong>Description:</strong> {{ Str::limit($group->description, 150) }}
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Recent Activities and Actions -->
                                        <div class="col-md-4">
                                            <!-- Recent Activities -->
                                            @if($group->recent_activities && $group->recent_activities->count() > 0)
                                                <div class="recent-activities mb-3">
                                                    <h6 class="text-muted mb-2">
                                                        <i class="fas fa-history me-1"></i> Recent Activity
                                                    </h6>
                                                    <div class="activity-list" style="max-height: 120px; overflow-y: auto;">
                                                        @foreach($group->recent_activities as $activity)
                                                            <div class="activity-item mb-2 p-2 bg-light rounded">
                                                                <div class="d-flex align-items-start">
                                                                    <i class="fas fa-{{ $activity->icon }} text-{{ $activity->type === 'submission' ? 'primary' : 'success' }} me-2 mt-1"></i>
                                                                    <div class="flex-grow-1">
                                                                        <small class="fw-bold text-dark d-block">{{ $activity->title }}</small>
                                                                        <small class="text-muted">{{ $activity->description }}</small>
                                                                        <br><small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Action Buttons -->
                                            <div class="group-actions">
                                                <a href="{{ route('adviser.groups.details', $group) }}" class="btn btn-success btn-sm px-3 me-2 mb-2 w-100">
                                                    <i class="fas fa-eye me-1"></i> View Details
                                                </a>
                                                <a href="{{ route('adviser.project.index') }}" class="btn btn-outline-success btn-sm px-3 me-2 mb-2 w-100">
                                                    <i class="fas fa-file-alt me-1"></i> Review Projects
                                                </a>
                                                <a href="{{ route('adviser.proposal.index') }}" class="btn btn-outline-info btn-sm px-3 w-100">
                                                    <i class="fas fa-clipboard-check me-1"></i> Review Proposals
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                    <!-- Pagination -->
                    @if($groups->hasPages())
                        <div class="d-flex justify-content-center p-4 border-top">
                            {{ $groups->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="empty-state">
                            <i class="fas fa-user-tie fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No Adviser Groups</h5>
                            <p class="text-muted mb-0">You don't have any groups assigned as adviser yet.</p>
                            <div class="mt-3">
                                <a href="{{ route('adviser.all-groups') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-layer-group me-2"></i>View All My Groups
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS for better styling -->
<style>
.group-icon-wrapper {
    display: flex;
    align-items: center;
    justify-content-center;
}

.group-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content-center;
    font-size: 1.2rem;
    color: white;
    background: linear-gradient(135deg, #28a745, #20c997);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    transition: all 0.3s ease;
}

.group-item {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.group-item:hover {
    background-color: #f8f9fa;
    border-left-color: #28a745;
    transform: translateX(5px);
}

.group-meta .badge {
    font-size: 0.8rem;
    padding: 0.5rem 0.75rem;
}

.group-description .alert {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border: none;
    border-radius: 8px;
}

.group-actions .btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.group-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.empty-state {
    padding: 2rem;
}

.empty-state i {
    opacity: 0.6;
}

.card {
    border-radius: 12px;
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
}

.group-item:last-child {
    border-bottom: none;
}

/* Success theme colors */
.bg-success {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
}

.btn-success {
    background: linear-gradient(135deg, #28a745, #20c997);
    border: none;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.btn-success:hover {
    background: linear-gradient(135deg, #218838, #1e7e34);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
}
</style>
@endsection 