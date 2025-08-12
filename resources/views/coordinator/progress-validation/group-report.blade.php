@extends('layouts.coordinator')

@section('title', 'Group Readiness Report - ' . $group->name)

@section('content')
<div class="container mt-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('coordinator.progress-validation.dashboard') }}">Progress Validation</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $group->name }} - Readiness Report</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Group Progress Readiness Report</h2>
        <div>
            @if($report['is_ready'])
                <a href="{{ route('coordinator.defense.index') }}" class="btn btn-success">
                    <i class="fas fa-calendar-plus me-2"></i>Schedule Progress Review
                </a>
            @endif
        </div>
    </div>

    <!-- Group Information -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-users me-2"></i>Group Information
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Group Details</h6>
                    <p><strong>Name:</strong> {{ $group->name }}</p>
                    <p><strong>Description:</strong> {{ $group->description ?? 'No description' }}</p>
                    <p><strong>Adviser:</strong> {{ $group->adviser->name ?? 'No adviser assigned' }}</p>
                </div>
                <div class="col-md-6">
                    <h6>Members ({{ $group->members->count() }})</h6>
                    @foreach($group->members as $member)
                        <span class="badge bg-secondary me-1 mb-1">{{ $member->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Overall Readiness Status -->
    <div class="card mb-4">
        <div class="card-header {{ $report['is_ready'] ? 'bg-success' : 'bg-warning' }} text-white">
            <h5 class="mb-0">
                <i class="fas fa-{{ $report['is_ready'] ? 'check-circle' : 'exclamation-triangle' }} me-2"></i>
                Overall Readiness Status
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <h6>Overall Progress</h6>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar {{ $report['overall_progress'] >= 60 ? 'bg-success' : 'bg-warning' }}" 
                                 role="progressbar" 
                                 style="width: {{ $report['overall_progress'] }}%" 
                                 aria-valuenow="{{ $report['overall_progress'] }}" 
                                 aria-valuemin="0" aria-valuemax="100">
                                {{ $report['overall_progress'] }}%
                            </div>
                        </div>
                        <small class="text-muted">Target: 60% for group progress readiness</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <h4 class="mb-0 {{ $report['is_ready'] ? 'text-success' : 'text-warning' }}">
                            {{ $report['is_ready'] ? 'READY' : 'NOT READY' }}
                        </h4>
                        <small class="text-muted">for Progress Review</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Issues and Warnings -->
    @if(count($report['issues']) > 0 || count($report['warnings']) > 0)
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-exclamation-circle me-2"></i>Issues & Warnings
            </h5>
        </div>
        <div class="card-body">
            @if(count($report['issues']) > 0)
            <div class="mb-3">
                <h6 class="text-danger"><i class="fas fa-times-circle me-1"></i>Critical Issues</h6>
                <ul class="list-group list-group-flush">
                    @foreach($report['issues'] as $issue)
                        <li class="list-group-item text-danger">
                            <i class="fas fa-times me-2"></i>{{ $issue }}
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(count($report['warnings']) > 0)
            <div>
                <h6 class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Warnings</h6>
                <ul class="list-group list-group-flush">
                    @foreach($report['warnings'] as $warning)
                        <li class="list-group-item text-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>{{ $warning }}
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Milestones Status -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-tasks me-2"></i>Milestones Status
            </h5>
        </div>
        <div class="card-body">
            @if(count($report['milestones_status']) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Milestone</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Target Date</th>
                                <th>Required</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['milestones_status'] as $milestone)
                            <tr>
                                <td>
                                    <strong>{{ $milestone['name'] }}</strong>
                                    @if($milestone['is_overdue'])
                                        <br><small class="text-danger">Overdue</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar {{ $milestone['progress'] >= 80 ? 'bg-success' : ($milestone['progress'] >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                             role="progressbar" 
                                             style="width: {{ $milestone['progress'] }}%" 
                                             aria-valuenow="{{ $milestone['progress'] }}" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            {{ $milestone['progress'] }}%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $milestone['progress'] >= 80 ? 'bg-success' : ($milestone['progress'] >= 50 ? 'bg-warning' : 'bg-danger') }}">
                                        {{ $milestone['status'] }}
                                    </span>
                                </td>
                                <td>
                                    {{ $milestone['target_date'] ? $milestone['target_date']->format('M d, Y') : 'Not set' }}
                                </td>
                                <td>
                                    @if($milestone['is_required'])
                                        <span class="badge bg-primary">Required</span>
                                    @else
                                        <span class="badge bg-secondary">Optional</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-muted">
                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                    <p>No milestones assigned to this group yet.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Required Documents Status -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-file-alt me-2"></i>Required Documents for Progress Review
            </h5>
        </div>
        <div class="card-body">
            @if(count($report['documents_status']) > 0)
                <div class="row">
                    @foreach($report['documents_status'] as $document)
                    <div class="col-md-6 mb-3">
                        <div class="card {{ $document['submitted'] ? 'border-success' : 'border-warning' }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $document['name'] }}</h6>
                                        @if($document['submitted'])
                                            <small class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>Submitted
                                                @if($document['submission_date'])
                                                    on {{ \Carbon\Carbon::parse($document['submission_date'])->format('M d, Y') }}
                                                @endif
                                            </small>
                                        @else
                                            <small class="text-warning">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Not submitted
                                            </small>
                                        @endif
                                    </div>
                                    <div>
                                        @if($document['submitted'])
                                            <span class="badge bg-success">✓</span>
                                        @else
                                            <span class="badge bg-warning">!</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-muted">
                    <i class="fas fa-file-alt fa-2x mb-2"></i>
                    <p>No document requirements defined.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Recommendations -->
    @if(count($report['recommendations']) > 0)
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-lightbulb me-2"></i>Recommendations
            </h5>
        </div>
        <div class="card-body">
            <ul class="list-group list-group-flush">
                @foreach($report['recommendations'] as $recommendation)
                    <li class="list-group-item">
                        <i class="fas fa-arrow-right me-2 text-info"></i>{{ $recommendation }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="card">
        <div class="card-body text-center">
            @if($report['is_ready'])
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle me-2"></i>This group is ready for 60% defense!</h5>
                    <p class="mb-0">You can now proceed to schedule their defense.</p>
                </div>
                <a href="{{ route('coordinator.defense.index') }}" class="btn btn-success btn-lg">
                    <i class="fas fa-calendar-plus me-2"></i>Schedule Progress Review
                </a>
            @else
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>This group is not ready for 60% defense yet.</h5>
                    <p class="mb-0">Please address the issues above before scheduling.</p>
                </div>
                <a href="{{ route('coordinator.groups.show', $group) }}" class="btn btn-warning btn-lg">
                    <i class="fas fa-edit me-2"></i>Manage Group
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
