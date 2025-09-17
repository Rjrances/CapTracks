@extends('layouts.student')
@section('title', 'My Defense Requests')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">My Defense Requests</h2>
            <p class="text-muted mb-0">Manage your defense requests and track their status</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('student.defense-requests.create') }}" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>Request Defense
            </a>
            <a href="{{ route('student.dashboard') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
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
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-users me-2"></i>Group Information
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Group: {{ $group->name }}</h6>
                    <p class="text-muted mb-0">{{ $group->description ?? 'No description' }}</p>
                </div>
                <div class="col-md-6">
                    <h6>Adviser: {{ $group->adviser->name ?? 'Not assigned' }}</h6>
                    <p class="text-muted mb-0">Members: {{ $group->members->pluck('name')->implode(', ') }}</p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Defense Requests ({{ $defenseRequests->count() }})
            </h5>
        </div>
        <div class="card-body">
            @if($defenseRequests->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Defense Type</th>
                                <th>Status</th>
                                <th>Requested Date</th>
                                <th>Student Message</th>
                                <th>Coordinator Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($defenseRequests as $request)
                                <tr>
                                    <td>
                                        <span class="badge bg-info">{{ $request->defense_type_label }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($request->status) {
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'scheduled' => 'primary',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $request->requested_at ? $request->requested_at->format('M d, Y h:i A') : 'N/A' }}
                                    </td>
                                    <td>
                                        @if($request->student_message)
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $request->student_message }}">
                                                {{ $request->student_message }}
                                            </span>
                                        @else
                                            <span class="text-muted">No message</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($request->coordinator_notes)
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $request->coordinator_notes }}">
                                                {{ $request->coordinator_notes }}
                                            </span>
                                        @else
                                            <span class="text-muted">No notes</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('student.defense-requests.show', $request) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($request->isPending())
                                                <form action="{{ route('student.defense-requests.cancel', $request) }}" 
                                                      method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to cancel this defense request?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Defense Requests Yet</h5>
                    <p class="text-muted mb-3">You haven't submitted any defense requests yet.</p>
                    <a href="{{ route('student.defense-requests.create') }}" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Request Your First Defense
                    </a>
                </div>
            @endif
        </div>
    </div>
    <div class="card mt-4 border-info">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0">
                <i class="fas fa-info-circle me-2"></i>How Defense Requests Work
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>1. Submit Request</h6>
                    <p class="text-muted small">Choose your defense type and preferred date/time</p>
                    <h6>2. Coordinator Review</h6>
                    <p class="text-muted small">Your coordinator will review and approve/reject</p>
                </div>
                <div class="col-md-6">
                    <h6>3. Schedule Creation</h6>
                    <p class="text-muted small">If approved, coordinator will create the schedule</p>
                    <h6>4. Panel Assignment</h6>
                    <p class="text-muted small">Faculty panel will be assigned for your defense</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
