@extends('layouts.coordinator')

@section('title', 'Group Progress Validation')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Group Progress Validation</h2>
        <div>
            <a href="{{ route('coordinator.progress-validation.all-groups') }}" class="btn btn-outline-primary me-2">
                <i class="fas fa-list me-2"></i>All Groups Status
            </a>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-filter me-2"></i>Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('coordinator.progress-validation.dashboard') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <label for="academic_term_id" class="form-label">Academic Term</label>
                        <select name="academic_term_id" id="academic_term_id" class="form-select">
                            <option value="">All Terms</option>
                            @foreach($filterOptions['academic_terms'] as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['academic_term_id'] ?? '') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="adviser_id" class="form-label">Adviser</label>
                        <select name="adviser_id" id="adviser_id" class="form-select">
                            <option value="">All Advisers</option>
                            @foreach($filterOptions['advisers'] as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['adviser_id'] ?? '') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search Groups</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               placeholder="Search by group name..." 
                               value="{{ $filters['search'] ?? '' }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-1"></i>Apply
                        </button>
                        <a href="{{ route('coordinator.progress-validation.dashboard') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $stats['total_groups'] }}</h4>
                            <p class="mb-0">Total Groups</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $stats['ready_for_60_percent'] }}</h4>
                            <p class="mb-0">Ready for Progress Review</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $stats['needing_attention'] }}</h4>
                            <p class="mb-0">Need Attention</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $stats['no_adviser'] }}</h4>
                            <p class="mb-0">No Adviser</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-user-times fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ready Groups Section -->
    @if($readyGroups->count() > 0)
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-check-circle me-2"></i>
                Groups Ready for Progress Review ({{ $readyGroups->count() }})
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Group Name</th>
                            <th>Adviser</th>
                            <th>Members</th>
                            <th>Progress</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($readyGroups as $group)
                        <tr>
                            <td>
                                <strong>{{ $group->name }}</strong>
                                <br><small class="text-muted">{{ $group->description }}</small>
                            </td>
                            <td>{{ $group->adviser->name ?? 'No Adviser' }}</td>
                            <td>
                                @foreach($group->members->take(3) as $member)
                                    <span class="badge bg-secondary me-1">{{ $member->name }}</span>
                                @endforeach
                                @if($group->members->count() > 3)
                                    <span class="badge bg-secondary">+{{ $group->members->count() - 3 }} more</span>
                                @endif
                            </td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" 
                                         role="progressbar" 
                                         style="width: {{ $group->overall_progress_percentage }}%" 
                                         aria-valuenow="{{ $group->overall_progress_percentage }}" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        {{ $group->overall_progress_percentage }}%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('coordinator.progress-validation.group-report', $group) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>View Report
                                </a>
                                <a href="{{ route('coordinator.defense.index') }}" 
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-calendar-plus me-1"></i>Schedule Defense
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Groups Needing Attention -->
    @if($needingAttentionGroups->count() > 0)
    <div class="card mb-4">
        <div class="card-header bg-warning text-white">
            <h5 class="mb-0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Groups Needing Attention ({{ $needingAttentionGroups->count() }})
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Group Name</th>
                            <th>Adviser</th>
                            <th>Progress</th>
                            <th>Issues</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($needingAttentionGroups as $group)
                        @php
                            $report = app(\App\Services\ProgressValidationService::class)->get60PercentDefenseReadinessReport($group);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $group->name }}</strong>
                                <br><small class="text-muted">{{ $group->description }}</small>
                            </td>
                            <td>{{ $group->adviser->name ?? 'No Adviser' }}</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-warning" 
                                         role="progressbar" 
                                         style="width: {{ $group->overall_progress_percentage }}%" 
                                         aria-valuenow="{{ $group->overall_progress_percentage }}" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        {{ $group->overall_progress_percentage }}%
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if(count($report['issues']) > 0)
                                    <ul class="list-unstyled mb-0">
                                        @foreach(array_slice($report['issues'], 0, 2) as $issue)
                                            <li><small class="text-danger">• {{ $issue }}</small></li>
                                        @endforeach
                                        @if(count($report['issues']) > 2)
                                            <li><small class="text-muted">• +{{ count($report['issues']) - 2 }} more issues</small></li>
                                        @endif
                                    </ul>
                                @else
                                    <span class="text-muted">No major issues</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('coordinator.progress-validation.group-report', $group) }}" 
                                   class="btn btn-sm btn-outline-warning">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($readyGroups->count() === 0 && $needingAttentionGroups->count() === 0)
    <div class="card">
        <div class="card-body text-center">
            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
            <h5>No Groups Available</h5>
                            <p class="text-muted">There are no groups with sufficient progress for evaluation.</p>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
// Auto-refresh readiness status every 30 seconds
setInterval(function() {
    // You can add AJAX calls here to refresh the data
}, 30000);
</script>
@endpush
@endsection
