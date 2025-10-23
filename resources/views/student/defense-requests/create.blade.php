@extends('layouts.student')
@section('title', 'Request Defense')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Request Defense</h2>
            <p class="text-muted mb-0">Submit a request for your defense schedule</p>
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
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-calendar-plus me-2"></i>Defense Request Form
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6 class="mb-2"><i class="fas fa-users me-2"></i>Group Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Group:</strong> {{ $group->name }}<br>
                                <strong>Members:</strong> {{ $group->members->pluck('name')->implode(', ') }}
                            </div>
                            <div class="col-md-6">
                                <strong>Adviser:</strong> {{ $group->adviser->name ?? 'Not assigned' }}<br>
                                <strong>Description:</strong> {{ $group->description ?? 'No description' }}
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('student.defense-requests.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="defense_type" class="form-label fw-bold">
                                <i class="fas fa-gavel me-1"></i>Defense Type *
                            </label>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Select the type of defense you are requesting. This should match your current milestone progress.
                            </div>
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
                                <strong>Proposal Defense:</strong> Initial project proposal review<br>
                                <strong>60% Progress Defense:</strong> Mid-project progress review<br>
                                <strong>100% Final Defense:</strong> Final project defense
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="preferred_date" class="form-label fw-bold">
                                <i class="fas fa-calendar me-1"></i>Preferred Date *
                            </label>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Please select a date at least 3 days from today to allow for coordinator review and scheduling.
                            </div>
                            <input type="date" name="preferred_date" id="preferred_date" 
                                   class="form-control" required 
                                   min="{{ date('Y-m-d', strtotime('+3 days')) }}"
                                   value="{{ old('preferred_date') }}">
                            <div class="form-text">
                                The coordinator will try to accommodate your preferred date, but final scheduling depends on faculty availability.
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="preferred_time" class="form-label fw-bold">
                                <i class="fas fa-clock me-1"></i>Preferred Time *
                            </label>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Select your preferred time for the defense. Morning slots (9 AM - 12 PM) and afternoon slots (1 PM - 4 PM) are typically available.
                            </div>
                            <input type="time" name="preferred_time" id="preferred_time" 
                                   class="form-control" required 
                                   value="{{ old('preferred_time', '09:00') }}">
                            <div class="form-text">
                                Defenses typically last 1-2 hours. The coordinator will confirm the exact duration.
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="student_message" class="form-label fw-bold">
                                <i class="fas fa-comment me-1"></i>Message to Coordinator (Optional)
                            </label>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Add any special requests, notes, or information that might help the coordinator schedule your defense.
                            </div>
                            <textarea name="student_message" id="student_message" 
                                      class="form-control" rows="4" 
                                      placeholder="Example: We prefer morning slots due to group member availability. Our project focuses on [brief description]...">{{ old('student_message') }}</textarea>
                            <div class="form-text">
                                This message will be visible to the coordinator when reviewing your request. Keep it professional and concise.
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="alert alert-success">
                                <h6 class="mb-2"><i class="fas fa-check-circle me-2"></i>Requirements Met</h6>
                                <ul class="mb-0">
                                    <li>You are part of a group</li>
                                    <li>Your group has an adviser assigned</li>
                                    <li>No pending defense requests</li>
                                </ul>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Submit Defense Request
                            </button>
                            <a href="{{ route('student.defense-requests.index') }}" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card mt-4 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>What Happens Next?
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>1. Request Submission</h6>
                            <p class="text-muted small">Your request will be submitted to the coordinator for review</p>
                            <h6>2. Coordinator Review</h6>
                            <p class="text-muted small">The coordinator will review your request within 1-2 business days</p>
                        </div>
                        <div class="col-md-6">
                            <h6>3. Approval/Rejection</h6>
                            <p class="text-muted small">You'll be notified of the coordinator's decision</p>
                            <h6>4. Schedule Creation</h6>
                            <p class="text-muted small">If approved, the coordinator will create the final defense schedule</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const minDate = new Date(today.getTime() + (3 * 24 * 60 * 60 * 1000));
    const minDateString = minDate.toISOString().split('T')[0];
    document.getElementById('preferred_date').min = minDateString;
    if (!document.getElementById('preferred_date').value) {
        document.getElementById('preferred_date').value = minDateString;
    }
});
</script>
@endpush
@endsection
