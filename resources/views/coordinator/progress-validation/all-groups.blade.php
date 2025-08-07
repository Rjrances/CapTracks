@extends('layouts.coordinator')

@section('title', 'All Groups - 60% Defense Status')

@section('content')
<div class="container mt-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('coordinator.progress-validation.dashboard') }}">Progress Validation</a></li>
            <li class="breadcrumb-item active" aria-current="page">All Groups Status</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">All Groups - 60% Defense Status</h2>
        <div>
            <a href="{{ route('coordinator.progress-validation.dashboard') }}" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filter Options -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>Filter Options
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="status-filter" class="form-label">Status</label>
                    <select id="status-filter" class="form-select">
                        <option value="">All Status</option>
                        <option value="ready">Ready for 60% Defense</option>
                        <option value="needs-attention">Needs Attention</option>
                        <option value="not-ready">Not Ready</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="progress-filter" class="form-label">Progress Range</label>
                    <select id="progress-filter" class="form-select">
                        <option value="">All Progress</option>
                        <option value="0-20">0-20%</option>
                        <option value="21-40">21-40%</option>
                        <option value="41-60">41-60%</option>
                        <option value="61-80">61-80%</option>
                        <option value="81-100">81-100%</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="adviser-filter" class="form-label">Adviser</label>
                    <select id="adviser-filter" class="form-select">
                        <option value="">All Advisers</option>
                        <option value="assigned">Has Adviser</option>
                        <option value="unassigned">No Adviser</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" id="search" class="form-control" placeholder="Search groups...">
                </div>
            </div>
        </div>
    </div>

    <!-- Groups Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-users me-2"></i>All Groups ({{ $groups->count() }})
            </h5>
        </div>
        <div class="card-body">
            @if($groups->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover" id="groups-table">
                        <thead>
                            <tr>
                                <th>Group Name</th>
                                <th>Adviser</th>
                                <th>Members</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Issues</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groups as $groupData)
                            @php
                                $group = $groupData['group'];
                                $report = $groupData['report'];
                            @endphp
                            <tr class="group-row" 
                                data-status="{{ $report['is_ready'] ? 'ready' : ($group->overall_progress_percentage >= 40 ? 'needs-attention' : 'not-ready') }}"
                                data-progress="{{ $group->overall_progress_percentage }}"
                                data-adviser="{{ $group->adviser_id ? 'assigned' : 'unassigned' }}"
                                data-name="{{ strtolower($group->name) }}">
                                <td>
                                    <strong>{{ $group->name }}</strong>
                                    <br><small class="text-muted">{{ $group->description }}</small>
                                </td>
                                <td>
                                    @if($group->adviser)
                                        <span class="badge bg-success">{{ $group->adviser->name }}</span>
                                    @else
                                        <span class="badge bg-danger">No Adviser</span>
                                    @endif
                                </td>
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
                                        <div class="progress-bar {{ $group->overall_progress_percentage >= 60 ? 'bg-success' : ($group->overall_progress_percentage >= 40 ? 'bg-warning' : 'bg-danger') }}" 
                                             role="progressbar" 
                                             style="width: {{ $group->overall_progress_percentage }}%" 
                                             aria-valuenow="{{ $group->overall_progress_percentage }}" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            {{ $group->overall_progress_percentage }}%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($report['is_ready'])
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Ready
                                        </span>
                                    @elseif($group->overall_progress_percentage >= 40)
                                        <span class="badge bg-warning">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Needs Attention
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>Not Ready
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if(count($report['issues']) > 0)
                                        <ul class="list-unstyled mb-0">
                                            @foreach(array_slice($report['issues'], 0, 2) as $issue)
                                                <li><small class="text-danger">• {{ Str::limit($issue, 50) }}</small></li>
                                            @endforeach
                                            @if(count($report['issues']) > 2)
                                                <li><small class="text-muted">• +{{ count($report['issues']) - 2 }} more issues</small></li>
                                            @endif
                                        </ul>
                                    @else
                                        <span class="text-success">
                                            <i class="fas fa-check-circle me-1"></i>No issues
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('coordinator.progress-validation.group-report', $group) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>View Report
                                        </a>
                                        @if($report['is_ready'])
                                            <a href="{{ route('coordinator.defense.scheduling') }}" 
                                               class="btn btn-sm btn-success">
                                                <i class="fas fa-calendar-plus me-1"></i>Schedule
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-muted">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <h5>No Groups Found</h5>
                    <p>There are no groups available for 60% defense evaluation.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusFilter = document.getElementById('status-filter');
    const progressFilter = document.getElementById('progress-filter');
    const adviserFilter = document.getElementById('adviser-filter');
    const searchInput = document.getElementById('search');
    const groupRows = document.querySelectorAll('.group-row');

    function filterGroups() {
        const statusValue = statusFilter.value;
        const progressValue = progressFilter.value;
        const adviserValue = adviserFilter.value;
        const searchValue = searchInput.value.toLowerCase();

        groupRows.forEach(row => {
            let show = true;

            // Status filter
            if (statusValue && row.dataset.status !== statusValue) {
                show = false;
            }

            // Progress filter
            if (progressValue) {
                const progress = parseInt(row.dataset.progress);
                const [min, max] = progressValue.split('-').map(Number);
                if (progress < min || progress > max) {
                    show = false;
                }
            }

            // Adviser filter
            if (adviserValue && row.dataset.adviser !== adviserValue) {
                show = false;
            }

            // Search filter
            if (searchValue && !row.dataset.name.includes(searchValue)) {
                show = false;
            }

            row.style.display = show ? '' : 'none';
        });
    }

    // Add event listeners
    statusFilter.addEventListener('change', filterGroups);
    progressFilter.addEventListener('change', filterGroups);
    adviserFilter.addEventListener('change', filterGroups);
    searchInput.addEventListener('input', filterGroups);
});
</script>
@endpush
@endsection
