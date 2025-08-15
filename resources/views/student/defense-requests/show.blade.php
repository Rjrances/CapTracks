@extends('layouts.student')

@section('title', 'Defense Request Details')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Defense Request Details</h2>
            <p class="text-muted mb-0">View details of your defense request</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('student.defense-requests.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Requests
            </a>
            @if($defenseRequest->isPending())
                <form action="{{ route('student.defense-requests.cancel', $defenseRequest) }}" 
                      method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"
                            onclick="return confirm('Are you sure you want to cancel this defense request?')">
                        <i class="fas fa-times me-2"></i>Cancel Request
                    </button>
                </form>
            @endif
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

    <div class="row">
        <div class="col-md-8">
            <!-- Request Details -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Request Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Defense Type</h6>
                            <span class="badge bg-info fs-6">{{ $defenseRequest->defense_type_label }}</span>
                        </div>
                        <div class="col-md-6">
                            <h6>Status</h6>
                            @php
                                $statusClass = match($defenseRequest->status) {
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'scheduled' => 'primary',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $statusClass }} fs-6">
                                {{ ucfirst($defenseRequest->status) }}
                            </span>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Requested Date</h6>
                            <p class="mb-0">
                                {{ $defenseRequest->requested_at ? $defenseRequest->requested_at->format('F d, Y') : 'N/A' }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Requested Time</h6>
                            <p class="mb-0">
                                {{ $defenseRequest->requested_at ? $defenseRequest->requested_at->format('h:i A') : 'N/A' }}
                            </p>
                        </div>
                    </div>

                    @if($defenseRequest->student_message)
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <h6>Your Message to Coordinator</h6>
                                <div class="alert alert-info">
                                    {{ $defenseRequest->student_message }}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($defenseRequest->coordinator_notes)
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <h6>Coordinator Notes</h6>
                                <div class="alert alert-warning">
                                    {{ $defenseRequest->coordinator_notes }}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($defenseRequest->responded_at)
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <h6>Coordinator Response Date</h6>
                                <p class="mb-0">
                                    {{ $defenseRequest->responded_at->format('F d, Y h:i A') }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Defense Schedule (if scheduled) -->
            @if($defenseRequest->defenseSchedule)
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-check me-2"></i>Defense Schedule
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Scheduled Date</h6>
                                <p class="mb-0">
                                    {{ $defenseRequest->defenseSchedule->scheduled_date ? 
                                       \Carbon\Carbon::parse($defenseRequest->defenseSchedule->scheduled_date)->format('F d, Y') : 'N/A' }}
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6>Scheduled Time</h6>
                                <p class="mb-0">
                                    {{ $defenseRequest->defenseSchedule->scheduled_time ? 
                                       \Carbon\Carbon::parse($defenseRequest->defenseSchedule->scheduled_time)->format('h:i A') : 'N/A' }}
                                </p>
                            </div>
                        </div>
                        
                        @if($defenseRequest->defenseSchedule->room)
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <h6>Room</h6>
                                    <p class="mb-0">{{ $defenseRequest->defenseSchedule->room }}</p>
                                </div>
                            </div>
                        @endif

                        @if($defenseRequest->defenseSchedule->coordinator_notes)
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <h6>Schedule Notes</h6>
                                    <div class="alert alert-info">
                                        {{ $defenseRequest->defenseSchedule->coordinator_notes }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <!-- Group Information -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-users me-2"></i>Group Information
                    </h6>
                </div>
                <div class="card-body">
                    <h6>Group Name</h6>
                    <p class="mb-2">{{ $defenseRequest->group->name }}</p>
                    
                    <h6>Members</h6>
                    <ul class="list-unstyled mb-2">
                        @foreach($defenseRequest->group->members as $member)
                            <li><i class="fas fa-user me-1"></i>{{ $member->name }}</li>
                        @endforeach
                    </ul>
                    
                    <h6>Adviser</h6>
                    <p class="mb-0">{{ $defenseRequest->group->adviser->name ?? 'Not assigned' }}</p>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Request Timeline
                    </h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Request Submitted</h6>
                                <small class="text-muted">
                                    {{ $defenseRequest->requested_at ? $defenseRequest->requested_at->format('M d, Y h:i A') : 'N/A' }}
                                </small>
                            </div>
                        </div>
                        
                        @if($defenseRequest->responded_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-{{ $statusClass }}"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Coordinator Response</h6>
                                    <small class="text-muted">
                                        {{ $defenseRequest->responded_at->format('M d, Y h:i A') }}
                                    </small>
                                </div>
                            </div>
                        @endif
                        
                        @if($defenseRequest->defenseSchedule)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Schedule Created</h6>
                                    <small class="text-muted">
                                        {{ $defenseRequest->defenseSchedule->created_at->format('M d, Y h:i A') }}
                                    </small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="text-center mt-4">
        <a href="{{ route('student.defense-requests.index') }}" class="btn btn-primary">
            <i class="fas fa-list me-2"></i>View All Requests
        </a>
        
        @if($defenseRequest->isPending())
            <a href="{{ route('student.defense-requests.create') }}" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>Request Another Defense
            </a>
        @endif
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 20px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.timeline-content {
    margin-left: 10px;
}

.timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: -19px;
    top: 12px;
    width: 2px;
    height: 20px;
    background-color: #dee2e6;
}
</style>
@endsection
