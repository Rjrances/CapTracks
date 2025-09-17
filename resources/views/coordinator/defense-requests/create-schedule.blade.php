@extends('layouts.coordinator')
@section('title', 'Create Defense Schedule')
@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-plus me-2"></i>Schedule Defense
                        </h5>
                        <a href="{{ route('coordinator.defense-requests.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Back to Requests
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>Defense Request Details
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Group:</strong> {{ $defenseRequest->group->name }}<br>
                                <strong>Defense Type:</strong> {{ $defenseRequest->defense_type_label }}<br>
                                <strong>Requested:</strong> {{ $defenseRequest->requested_at->format('M d, Y h:i A') }}
                            </div>
                            <div class="col-md-6">
                                <strong>Members:</strong> {{ $defenseRequest->group->members->pluck('name')->implode(', ') }}<br>
                                <strong>Adviser:</strong> {{ $defenseRequest->group->adviser->name ?? 'Not assigned' }}<br>
                                @if($defenseRequest->student_message)
                                    <strong>Message:</strong> "{{ $defenseRequest->student_message }}"
                                @endif
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('coordinator.defense-requests.store-schedule', $defenseRequest) }}" method="POST">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="scheduled_date" class="form-label">Date *</label>
                                <input type="date" name="scheduled_date" id="scheduled_date" 
                                       class="form-control @error('scheduled_date') is-invalid @error" 
                                       value="{{ old('scheduled_date') }}" 
                                       min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                                @error('scheduled_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="scheduled_time" class="form-label">Time *</label>
                                <input type="time" name="scheduled_time" id="scheduled_time" 
                                       class="form-control @error('scheduled_time') is-invalid @error" 
                                       value="{{ old('scheduled_time') }}" required>
                                @error('scheduled_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="room" class="form-label">Room *</label>
                                <input type="text" name="room" id="room" 
                                       class="form-control @error('room') is-invalid @error" 
                                       value="{{ old('room') }}" placeholder="e.g., Room 101, Computer Lab" required>
                                @error('room')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="coordinator_notes" class="form-label">Notes (Optional)</label>
                                <textarea name="coordinator_notes" id="coordinator_notes" 
                                          class="form-control @error('coordinator_notes') is-invalid @error" 
                                          rows="2" placeholder="Additional notes for the defense...">{{ old('coordinator_notes') }}</textarea>
                                @error('coordinator_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <hr class="my-4">
                        <h6 class="mb-3">
                            <i class="fas fa-users me-2"></i>Defense Panel Assignment
                        </h6>
                        <p class="text-muted small mb-3">
                            Assign the four required panel members. The same panel will serve for all defense phases.
                        </p>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="adviser_id" class="form-label">Adviser *</label>
                                <select name="adviser_id" id="adviser_id" 
                                        class="form-select @error('adviser_id') is-invalid @error" required>
                                    <option value="">Select Adviser</option>
                                    @foreach($availableFaculty as $faculty)
                                        <option value="{{ $faculty->id }}" 
                                                {{ old('adviser_id') == $faculty->id ? 'selected' : '' }}
                                                {{ $defenseRequest->group->adviser_id == $faculty->id ? 'selected' : '' }}>
                                            {{ $faculty->name }} ({{ $faculty->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('adviser_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="subject_coordinator_id" class="form-label">Subject Coordinator *</label>
                                <select name="subject_coordinator_id" id="subject_coordinator_id" 
                                        class="form-select @error('subject_coordinator_id') is-invalid @error" required>
                                    <option value="">Select Subject Coordinator</option>
                                    @foreach($availableFaculty as $faculty)
                                        <option value="{{ $faculty->id }}" 
                                                {{ old('subject_coordinator_id') == $faculty->id ? 'selected' : '' }}>
                                            {{ $faculty->name }} ({{ $faculty->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('subject_coordinator_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="panelist_1_id" class="form-label">Faculty Panelist #1 *</label>
                                <select name="panelist_1_id" id="panelist_1_id" 
                                        class="form-select @error('panelist_1_id') is-invalid @error" required>
                                    <option value="">Select Faculty Panelist</option>
                                    @foreach($availableFaculty as $faculty)
                                        <option value="{{ $faculty->id }}" 
                                                {{ old('panelist_1_id') == $faculty->id ? 'selected' : '' }}>
                                            {{ $faculty->name }} ({{ $faculty->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('panelist_1_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="panelist_2_id" class="form-label">Faculty Panelist #2 *</label>
                                <select name="panelist_2_id" id="panelist_2_id" 
                                        class="form-select @error('panelist_2_id') is-invalid @error" required>
                                    <option value="">Select Faculty Panelist</option>
                                    @foreach($availableFaculty as $faculty)
                                        <option value="{{ $faculty->id }}" 
                                                {{ old('panelist_2_id') == $faculty->id ? 'selected' : '' }}>
                                            {{ $faculty->name }} ({{ $faculty->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('panelist_2_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>Important Notes
                            </h6>
                            <ul class="mb-0 small">
                                <li>Only Faculty Panelists #1 and #2 will receive notifications</li>
                                <li>The same panel will serve for all defense phases (Proposal, 60%, 100%)</li>
                                <li>Faculty can accept or decline panel invitations</li>
                                <li>Schedule changes can be made later if needed</li>
                            </ul>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('coordinator.defense-requests.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-calendar-check me-1"></i>Schedule Defense
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('scheduled_date').min = tomorrow.toISOString().split('T')[0];
    const groupAdviserId = {{ $defenseRequest->group->adviser_id ?? 'null' }};
    if (groupAdviserId) {
        document.getElementById('adviser_id').value = groupAdviserId;
    }
});
</script>
@endpush
@endsection
