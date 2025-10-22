@extends('layouts.coordinator')
@section('title', 'Defense Management')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-gavel me-2"></i>Defense Management
                    </h2>
                    <p class="text-muted mb-0">Manage defense requests and schedules for capstone projects</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('coordinator.defense.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Schedule
                    </a>
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
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $stats['pending_requests'] }}</h4>
                                    <p class="mb-0">Pending Requests</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $stats['approved_requests'] }}</h4>
                                    <p class="mb-0">Approved Requests</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
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
                                    <h4 class="mb-0">{{ $stats['scheduled_defenses'] }}</h4>
                                    <p class="mb-0">Scheduled Defenses</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $stats['this_week_defenses'] }}</h4>
                                    <p class="mb-0">This Week</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-week fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Filters
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('coordinator.defense.index') }}" id="filterForm">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    @foreach($filterOptions['statuses'] as $status)
                                        <option value="{{ $status }}" {{ $requestFilters['status'] == $status ? 'selected' : '' }}>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="defense_type" class="form-label">Defense Type</label>
                                <select name="defense_type" id="defense_type" class="form-select form-select-sm">
                                    <option value="">All Types</option>
                                    @foreach($filterOptions['defense_types'] as $type)
                                        <option value="{{ $type }}" {{ $requestFilters['defense_type'] == $type ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $type)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search Groups</label>
                                <input type="text" name="search" id="search" class="form-control form-control-sm" 
                                       placeholder="Search by group name..." value="{{ $requestFilters['search'] ?? '' }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-sm me-2">
                                    <i class="fas fa-search me-1"></i>Apply
                                </button>
                                <a href="{{ route('coordinator.defense.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if($defenseRequests->count() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>
                            Defense Requests ({{ $defenseRequests->count() }})
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Group</th>
                                        <th>Defense Type</th>
                                        <th>Status</th>
                                        <th>Requested</th>
                                        <th>Student Message</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($defenseRequests as $request)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $request->group->name }}</div>
                                                <small class="text-muted">
                                                    Members: {{ $request->group->members->pluck('name')->implode(', ') }}
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    Adviser: {{ $request->group->adviser->name ?? 'Not assigned' }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $request->defense_type_label }}</span>
                                            </td>
                                            <td>
                                                @if($request->status === 'pending')
                                                    <span class="badge bg-warning">Pending</span>
                                                @elseif($request->status === 'approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @elseif($request->status === 'rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                @elseif($request->status === 'scheduled')
                                                    <span class="badge bg-primary">Scheduled</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div>{{ $request->requested_at->format('M d, Y') }}</div>
                                                <small class="text-muted">{{ $request->requested_at->format('h:i A') }}</small>
                                            </td>
                                            <td>
                                                @if($request->student_message)
                                                    <div class="text-truncate" style="max-width: 200px;" title="{{ $request->student_message }}">
                                                        {{ $request->student_message }}
                                                    </div>
                                                @else
                                                    <span class="text-muted">No message</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($request->status === 'pending')
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="{{ route('coordinator.defense-requests.create-schedule', $request) }}" 
                                                           class="btn btn-success btn-sm">
                                                            <i class="fas fa-calendar-plus"></i> Schedule
                                                        </a>
                                                        <button type="button" class="btn btn-warning btn-sm" 
                                                                onclick="approveRequest({{ $request->id }})">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm" 
                                                                onclick="rejectRequest({{ $request->id }})">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    </div>
                                                @elseif($request->status === 'approved')
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="{{ route('coordinator.defense-requests.create-schedule', $request) }}" 
                                                           class="btn btn-success btn-sm">
                                                            <i class="fas fa-calendar-plus"></i> Create Schedule
                                                        </a>
                                                        <button type="button" class="btn btn-danger btn-sm" 
                                                                onclick="rejectRequest({{ $request->id }})">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    </div>
                                                @elseif($request->status === 'scheduled')
                                                    <span class="badge bg-success">Scheduled</span>
                                                    @if($request->defenseSchedule)
                                                        <br>
                                                        <small class="text-muted">
                                                            {{ $request->defenseSchedule->start_at->format('M d, Y') }}
                                                            at {{ $request->defenseSchedule->start_at->format('h:i A') }}
                                                        </small>
                                                    @endif
                                                @elseif($request->status === 'rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                @else
                                                    <span class="text-muted">No actions available</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-calendar me-2"></i>
                        Defense Schedules ({{ $defenseSchedules->count() }})
                    </h6>
                </div>
                <div class="card-body">
                    @if($defenseSchedules->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Group</th>
                                        <th>Defense Type</th>
                                        <th>Date & Time</th>
                                        <th>Room</th>
                                        <th>Panel Members</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($defenseSchedules as $schedule)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $schedule->group->name }}</div>
                                                <small class="text-muted">
                                                    Members: {{ $schedule->group->members->pluck('name')->implode(', ') }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $schedule->defense_type)) }}</span>
                                            </td>
                                            <td>
                                                <div>{{ $schedule->start_at->format('M d, Y') }}</div>
                                                <small class="text-muted">
                                                    {{ $schedule->start_at->format('h:i A') }} - {{ $schedule->end_at->format('h:i A') }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $schedule->room }}</span>
                                            </td>
                                            <td>
                                                @if($schedule->defensePanels->count() > 0)
                                                    <div class="small">
                                                        @foreach($schedule->defensePanels as $panel)
                                                            <div>{{ $panel->faculty->name }} ({{ ucfirst($panel->role) }})</div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-muted">No panel assigned</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('coordinator.defense.edit', $schedule) }}" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger btn-sm" 
                                                            onclick="deleteSchedule({{ $schedule->id }})">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                            <h5>No Defense Schedules Found</h5>
                            <p class="text-muted">
                                You don't have any defense schedules yet, or there are no groups assigned to your offerings.
                            </p>
                            <a href="{{ route('coordinator.defense.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create First Schedule
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Defense Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="coordinator_notes" class="form-label">Reason for Rejection</label>
                        <textarea name="coordinator_notes" id="coordinator_notes" class="form-control" rows="4" 
                                  placeholder="Please provide a reason for rejecting this defense request..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approveRequest(requestId) {
    if (confirm('Are you sure you want to approve this defense request?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/coordinator/defense-requests/${requestId}/approve`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectRequest(requestId) {
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    const form = document.getElementById('rejectForm');
    form.action = `/coordinator/defense-requests/${requestId}/reject`;
    modal.show();
}

function deleteSchedule(scheduleId) {
    if (confirm('Are you sure you want to delete this defense schedule?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/coordinator/defense/${scheduleId}`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        
        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection