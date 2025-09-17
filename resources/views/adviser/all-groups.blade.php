@extends('layouts.adviser')
@section('title', 'All My Groups')
@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-layer-group me-3 text-primary"></i>
                        All My Groups
                    </h1>
                    <p class="text-muted mb-0">View all groups where you are involved as adviser or panel member</p>
                </div>
                <div class="d-flex gap-2">
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
    <div class="row mb-4 justify-content-center">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-layer-group fa-2x me-3"></i>
                        <div>
                            <h2 class="mb-0">{{ $summaryStats['total_groups'] }}</h2>
                            <small class="text-white-50">Total Groups</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-user-tie fa-2x me-3"></i>
                        <div>
                            <h2 class="mb-0">{{ $summaryStats['adviser_groups'] }}</h2>
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
                            <h2 class="mb-0">{{ $summaryStats['panel_groups'] }}</h2>
                            <small class="text-white-50">Panel Groups</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-file-alt fa-2x me-3"></i>
                        <div>
                            <h2 class="mb-0">{{ $summaryStats['pending_submissions'] }}</h2>
                            <small class="text-white-50">Pending Review</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills nav-fill" id="groupTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="pill" data-bs-target="#all" type="button" role="tab">
                        <i class="fas fa-layer-group me-2"></i>All Groups ({{ $summaryStats['total_groups'] }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="adviser-tab" data-bs-toggle="pill" data-bs-target="#adviser" type="button" role="tab">
                        <i class="fas fa-user-tie me-2"></i>Adviser Groups ({{ $summaryStats['adviser_groups'] }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="panel-tab" data-bs-toggle="pill" data-bs-target="#panel" type="button" role="tab">
                        <i class="fas fa-gavel me-2"></i>Panel Groups ({{ $summaryStats['panel_groups'] }})
                    </button>
                </li>
            </ul>
        </div>
    </div>
    <div class="tab-content" id="groupTabsContent">
        <div class="tab-pane fade show active" id="all" role="tabpanel">
            @if($allGroups->count() > 0)
                @foreach($allGroups as $group)
                    <div class="card mb-4">
                        <div class="card-header {{ $group->role_type === 'adviser' ? 'bg-success' : 'bg-info' }} text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="fas {{ $group->role_type === 'adviser' ? 'fa-user-tie' : 'fa-gavel' }} me-2"></i>
                                        {{ $group->name }}
                                        <span class="badge bg-light text-dark ms-2">
                                            {{ $group->role_type === 'adviser' ? 'Adviser' : 'Panel Member' }}
                                        </span>
                                        @if($group->role_type === 'panel' && isset($group->panel_role))
                                            <span class="badge bg-secondary ms-1">{{ ucfirst($group->panel_role) }}</span>
                                        @endif
                                    </h5>
                                    <small class="text-white-50">{{ $group->members->count() }} members • {{ $group->academicTerm->full_name ?? 'No Term' }}</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('adviser.groups.details', $group) }}" class="btn btn-outline-light btn-sm">
                                        <i class="fas fa-eye me-1"></i> Group Details
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    @if($group->role_type === 'adviser' && isset($group->progress_percentage))
                                        <div class="progress-section mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="text-muted fw-bold">Overall Progress</small>
                                                <small class="text-muted">{{ $group->progress_percentage }}%</small>
                                            </div>
                                            <div class="progress mb-2" style="height: 10px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: {{ $group->progress_percentage }}%" 
                                                     aria-valuenow="{{ $group->progress_percentage }}" 
                                                     aria-valuemax="100">
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
                                    @elseif($group->role_type === 'panel' && isset($group->defense_schedule))
                                        <div class="alert alert-info border-start border-info border-3 mb-3">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-calendar-alt me-2"></i>
                                                <div>
                                                    <strong>Defense Schedule:</strong><br>
                                                    <span class="text-muted">{{ $group->defense_schedule->stage_label }} - {{ $group->defense_schedule->formatted_date_time }}</span>
                                                    @if($group->defense_schedule->room)
                                                        <br><span class="text-muted">Room: {{ $group->defense_schedule->room }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
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
                                </div>
                                <div class="col-md-4">
                                    @php
                                        $groupData = $submissionsByGroup[$group->id] ?? null;
                                        $groupSubmissions = $groupData['submissions'] ?? collect();
                                    @endphp
                                    <div class="submissions-section">
                                        <h6 class="text-muted mb-3">
                                            <i class="fas fa-file-alt me-1"></i> Recent Submissions
                                        </h6>
                                        @if($groupSubmissions->count() > 0)
                                            <div class="submissions-list" style="max-height: 200px; overflow-y: auto;">
                                                @foreach($groupSubmissions as $submission)
                                                    <div class="submission-item mb-3 p-3 border rounded">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div class="flex-grow-1">
                                                                <h6 class="mb-1 text-dark">{{ $submission->student->name ?? 'Unknown' }}</h6>
                                                                <small class="text-muted">{{ $submission->title ?? 'Untitled' }}</small>
                                                            </div>
                                                            <span class="badge {{ $submission->status === 'approved' ? 'bg-success' : ($submission->status === 'rejected' ? 'bg-danger' : 'bg-warning') }}">
                                                                {{ ucfirst($submission->status) }}
                                                            </span>
                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small class="text-muted">
                                                                @if($submission->submitted_at)
                                                                    {{ $submission->submitted_at->diffForHumans() }}
                                                                @else
                                                                    Not submitted
                                                                @endif
                                                            </small>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="{{ route('adviser.project.show', $submission->id) }}" class="btn btn-outline-primary btn-sm">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                                <a href="{{ route('adviser.project.edit', $submission->id) }}" class="btn btn-outline-success btn-sm">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            @if($groupSubmissions->count() >= 5)
                                                <div class="text-center mt-2">
                                                    <a href="{{ route('adviser.project.index') }}" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-plus me-1"></i> View All Submissions
                                                    </a>
                                                </div>
                                            @endif
                                        @else
                                            <div class="text-center py-3">
                                                <i class="fas fa-file-alt fa-2x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">No submissions yet</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Groups Assigned</h5>
                        <p class="text-muted">You don't have any groups assigned as adviser or panel member yet.</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="{{ route('adviser.invitations') }}" class="btn btn-primary">
                                <i class="fas fa-envelope me-2"></i>Check Invitations
                            </a>
                            <a href="{{ route('adviser.groups') }}" class="btn btn-outline-primary">
                                <i class="fas fa-briefcase me-2"></i>View My Groups
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="tab-pane fade" id="adviser" role="tabpanel">
            @if($adviserGroups->count() > 0)
                @foreach($adviserGroups as $group)
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-tie me-2"></i>
                                        {{ $group->name }}
                                        <span class="badge bg-light text-dark ms-2">Adviser</span>
                                    </h5>
                                    <small class="text-white-50">{{ $group->members->count() }} members • {{ $group->academicTerm->full_name ?? 'No Term' }}</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('adviser.groups.details', $group) }}" class="btn btn-outline-light btn-sm">
                                        <i class="fas fa-eye me-1"></i> Group Details
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    @if(isset($group->progress_percentage))
                                        <div class="progress-section mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="text-muted fw-bold">Overall Progress</small>
                                                <small class="text-muted">{{ $group->progress_percentage }}%</small>
                                            </div>
                                            <div class="progress mb-2" style="height: 10px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: {{ $group->progress_percentage }}%" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <div class="group-description-section">
                                        <h6 class="text-muted mb-3">
                                            <i class="fas fa-info-circle me-1"></i> Group Information
                                        </h6>
                                        <div class="group-actions mt-3">
                                            @if($group->role_type === 'adviser')
                                                <a href="{{ route('adviser.project.index') }}" class="btn btn-outline-success btn-sm w-100 mb-2">
                                                    <i class="fas fa-file-alt me-1"></i> View Projects
                                                </a>
                                            @elseif($group->role_type === 'panel')
                                                <a href="{{ route('adviser.panel-submissions') }}" class="btn btn-outline-primary btn-sm w-100 mb-2">
                                                    <i class="fas fa-file-alt me-1"></i> View Submissions
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Adviser Groups</h5>
                        <p class="text-muted">You don't have any groups assigned as adviser yet.</p>
                    </div>
                </div>
            @endif
        </div>
        <div class="tab-pane fade" id="panel" role="tabpanel">
            @if($panelGroups->count() > 0)
                @foreach($panelGroups as $group)
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="fas fa-gavel me-2"></i>
                                        {{ $group->name }}
                                        <span class="badge bg-light text-dark ms-2">Panel Member</span>
                                        @if(isset($group->panel_role))
                                            <span class="badge bg-secondary ms-1">{{ ucfirst($group->panel_role) }}</span>
                                        @endif
                                    </h5>
                                    <small class="text-white-50">{{ $group->members->count() }} members • {{ $group->academicTerm->full_name ?? 'No Term' }}</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('adviser.groups.details', $group) }}" class="btn btn-outline-light btn-sm">
                                        <i class="fas fa-eye me-1"></i> Group Details
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    @if(isset($group->defense_schedule))
                                        <div class="alert alert-info border-start border-info border-3 mb-3">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-calendar-alt me-2"></i>
                                                <div>
                                                    <strong>Defense Schedule:</strong><br>
                                                    <span class="text-muted">{{ $group->defense_schedule->stage_label }} - {{ $group->defense_schedule->formatted_date_time }}</span>
                                                    @if($group->defense_schedule->room)
                                                        <br><span class="text-muted">Room: {{ $group->defense_schedule->room }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <div class="group-description-section">
                                        <h6 class="text-muted mb-3">
                                            <i class="fas fa-info-circle me-1"></i> Group Information
                                        </h6>
                                        <div class="group-actions mt-3">
                                            <a href="{{ route('adviser.panel-submissions') }}" class="btn btn-outline-primary btn-sm w-100 mb-2">
                                                <i class="fas fa-file-alt me-1"></i> View Submissions
                                            </a>
                                            @if(isset($group->defense_schedule))
                                                <a href="#" class="btn btn-outline-info btn-sm w-100 mb-2">
                                                    <i class="fas fa-calendar me-1"></i> Defense Schedule
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-gavel fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Panel Groups</h5>
                        <p class="text-muted">You don't have any groups assigned as panel member yet.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
<style>
.nav-pills .nav-link {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}
.nav-pills .nav-link:hover {
    background-color: rgba(0,123,255,0.1);
}
.nav-pills .nav-link.active {
    background-color: #007bff !important;
    color: white !important;
}
.submission-item {
    transition: all 0.3s ease;
}
.submission-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}
.activity-item {
    transition: all 0.3s ease;
}
.activity-item:hover {
    background-color: #f8f9fa !important;
}
.progress {
    border-radius: 10px;
    background-color: #e9ecef;
}
.progress-bar {
    border-radius: 10px;
    transition: width 0.6s ease;
}
.card {
    border-radius: 12px;
    overflow: hidden;
}
.card-header {
    background: linear-gradient(135deg, #007bff, #6f42c1) !important;
}
.card-header.bg-success {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
}
.card-header.bg-info {
    background: linear-gradient(135deg, #17a2b8, #6f42c1) !important;
}
.description-item {
    transition: all 0.3s ease;
}
.description-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}
.group-meta-item {
    transition: all 0.3s ease;
}
.group-meta-item:hover {
    background-color: #f8f9fa !important;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('#groupTabs button[data-bs-toggle="pill"]');
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function(event) {
            console.log('Switched to tab:', event.target.getAttribute('data-bs-target'));
        });
    });
});
</script>
@endsection
