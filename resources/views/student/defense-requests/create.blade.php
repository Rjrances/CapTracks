@extends('layouts.student')
@section('title', 'Request Defense')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0">Notify your coordinator that your group is ready for a defense</p>
        </div>
        <a href="{{ route('student.defense-requests.index') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Requests
        </a>
    </div>

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

    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-calendar-plus me-2"></i>Request Defense Schedule
                    </h4>
                </div>
                <div class="card-body">

                    {{-- Group Info --}}
                    <div class="alert alert-info">
                        <h6 class="mb-2"><i class="fas fa-users me-2"></i>Group Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Group:</strong> {{ $group->name }}<br>
                                <strong>Members:</strong> {{ $group->members->pluck('name')->implode(', ') }}
                            </div>
                            <div class="col-md-6">
                                <strong>Adviser:</strong> {{ $group->adviser->name ?? 'Not assigned' }}
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('student.defense-requests.store') }}" method="POST">
                        @csrf

                        {{-- Defense Type --}}
                        <div class="mb-4">
                            <label for="defense_type" class="form-label fw-bold">
                                <i class="fas fa-gavel me-1"></i>Defense Type *
                            </label>
                            <select name="defense_type" id="defense_type" class="form-select" required>
                                <option value="">Choose defense type...</option>
                                <option value="proposal" {{ (old('defense_type', $defenseType ?? '') == 'proposal') ? 'selected' : '' }}>
                                    Proposal Defense
                                </option>
                                <option value="60_percent" {{ (old('defense_type', $defenseType ?? '') == '60_percent') ? 'selected' : '' }}>
                                    60% Progress Defense
                                </option>
                                <option value="100_percent" {{ (old('defense_type', $defenseType ?? '') == '100_percent') ? 'selected' : '' }}>
                                    100% Final Defense
                                </option>
                            </select>
                            <div class="form-text">
                                This request is a readiness heads-up for your coordinator. No schedule details are needed from students.
                            </div>
                        </div>

                        {{-- Info Note --}}
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Clicking <strong>Submit Request</strong> will notify your coordinator that your group is ready for defense scheduling.
                            The coordinator will contact you with the scheduled date and time.
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Submit Request
                            </button>
                            <a href="{{ route('student.defense-requests.index') }}" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- What Happens Next --}}
            <div class="card mt-4 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>What Happens Next?
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-paper-plane me-1 text-primary"></i> 1. Request Submitted</h6>
                            <p class="text-muted small">Your coordinator is notified that your group is ready for a defense.</p>
                            <h6><i class="fas fa-user-check me-1 text-warning"></i> 2. Coordinator Reviews</h6>
                            <p class="text-muted small">The coordinator will check your milestone completion and proposal status.</p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar-alt me-1 text-success"></i> 3. Schedule Created</h6>
                            <p class="text-muted small">The coordinator creates the defense schedule with date, time, and panel.</p>
                            <h6><i class="fas fa-bell me-1 text-info"></i> 4. You Get Notified</h6>
                            <p class="text-muted small">You'll be notified once the defense is officially scheduled.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
