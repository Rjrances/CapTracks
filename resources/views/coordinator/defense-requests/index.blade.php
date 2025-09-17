@extends('layouts.coordinator')
@section('title', 'Defense Requests')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Defense Requests</h2>
        <div class="text-muted">
            <i class="fas fa-info-circle me-1"></i>Manage student defense requests
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-filter me-2"></i>Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('coordinator.defense-requests.index') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            @foreach($filterOptions['statuses'] as $status)
                                <option value="{{ $status }}" {{ $filters['status'] == $status ? 'selected' : '' }}>
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
                                <option value="{{ $type }}" {{ $filters['defense_type'] == $type ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search Groups</label>
                        <input type="text" name="search" id="search" class="form-control form-control-sm" 
                               placeholder="Search by group name..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm me-2">
                            <i class="fas fa-search me-1"></i>Apply
                        </button>
                        <a href="{{ route('coordinator.defense-requests.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Defense Requests ({{ $defenseRequests->count() }})
            </h6>
        </div>
        <div class="card-body">
            @if($defenseRequests->count() > 0)
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
                                        <div class="small">
                                            {{ $request->requested_at->format('M d, Y') }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $request->requested_at->format('h:i A') }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($request->student_message)
                                            <small class="text-muted">
                                                "{{ Str::limit($request->student_message, 50) }}"
                                            </small>
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
                                        @elseif($request->status === 'scheduled')
                                            <span class="badge bg-success">Scheduled</span>
                                            @if($request->defenseSchedule)
                                                <br>
                                                <small class="text-muted">
                                                    {{ $request->defenseSchedule->scheduled_date->format('M d, Y') }}
                                                    at {{ $request->defenseSchedule->scheduled_time->format('h:i A') }}
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted">No actions available</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-4">
                    {{ $defenseRequests->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>No Defense Requests Found</h5>
                    <p class="text-muted">
                        @if($filters['status'] || $filters['defense_type'] || $filters['search'])
                            No requests match the current filters. Try adjusting your search criteria.
                        @else
                            There are no defense requests currently available.
                        @endif
                    </p>
                </div>
            @endif
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
                        <label for="coordinator_notes" class="form-label">Reason for Rejection *</label>
                        <textarea name="coordinator_notes" id="coordinator_notes" class="form-control" 
                                  rows="3" placeholder="Please provide a reason for rejecting this defense request..." required></textarea>
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
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterSelects = document.querySelectorAll('#filterForm select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
});
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
</script>
@endpush
@endsection
