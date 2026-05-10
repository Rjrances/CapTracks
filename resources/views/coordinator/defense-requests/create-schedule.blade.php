@extends('layouts.coordinator')
@section('title', 'Schedule from request')
@section('content')
<div class="container-fluid">
        <x-coordinator.intro description="Approve this student request by picking date, room, and panel—same rules as creating a defense from scratch.">
            <a href="{{ route('coordinator.defense.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Defense management
            </a>
        </x-coordinator.intro>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-plus me-2"></i>Schedule defense
                    </h5>
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
                                @if($defenseRequest->preferred_date)
                                    <strong>Preferred Date:</strong> {{ $defenseRequest->preferred_date->format('M d, Y') }}<br>
                                @endif
                                @if($defenseRequest->preferred_time)
                                    <strong>Preferred Time:</strong> {{ $defenseRequest->preferred_time->format('h:i A') }}<br>
                                @endif
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
                                       class="form-control @error('scheduled_date') is-invalid @enderror" 
                                       value="{{ old('scheduled_date', $defenseRequest->preferred_date?->format('Y-m-d')) }}" 
                                       min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                                @error('scheduled_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="scheduled_time" class="form-label">Time *</label>
                                <input type="time" name="scheduled_time" id="scheduled_time" 
                                       class="form-control @error('scheduled_time') is-invalid @enderror" 
                                       value="{{ old('scheduled_time', $defenseRequest->preferred_time?->format('H:i')) }}" required>
                                @error('scheduled_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="room" class="form-label">Room *</label>
                                <input type="text" name="room" id="room" 
                                       class="form-control @error('room') is-invalid @enderror" 
                                       value="{{ old('room', 'Room 101') }}" placeholder="e.g., Room 101, Computer Lab, Conference Room A" required>
                                @error('room')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="coordinator_notes" class="form-label">Notes (Optional)</label>
                                <textarea name="coordinator_notes" id="coordinator_notes" 
                                          class="form-control @error('coordinator_notes') is-invalid @enderror" 
                                          rows="2" placeholder="Additional notes for the defense...">{{ old('coordinator_notes') }}</textarea>
                                @error('coordinator_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="milestone_override_reason" class="form-label">Milestone Override Reason (required only if milestone is incomplete)</label>
                            <textarea name="milestone_override_reason" id="milestone_override_reason"
                                      class="form-control @error('milestone_override_reason') is-invalid @enderror"
                                      rows="2"
                                      placeholder="Explain why this defense must proceed even if required milestone is not completed.">{{ old('milestone_override_reason') }}</textarea>
                            @error('milestone_override_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Student requests are blocked on incomplete milestones. Coordinator may override with documented reason.</small>
                        </div>
                        <hr class="my-4">
                        <h6 class="mb-3">
                            <i class="fas fa-users me-2"></i>Defense Panel Assignment
                        </h6>
                        <p class="text-muted small mb-3">
                            The panel includes the Adviser and Coordinator plus {{ $panelSlotCount }} invited faculty (Chair, Member, and additional Panelists).
                            The same panel will serve for all defense phases.
                        </p>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Adviser (Pre-assigned)</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <strong>{{ $defenseRequest->group->adviser->name ?? 'No adviser assigned' }}</strong>
                                    <input type="hidden" name="adviser_id" value="{{ $defenseRequest->group->adviser->id ?? '' }}">
                                </div>
                                <small class="text-muted">Adviser is pre-assigned by the chairperson and cannot be changed.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subject Coordinator (Pre-assigned)</label>
                                <div class="form-control-plaintext bg-light p-2 rounded border">
                                    <strong>{{ auth()->user()->name }}</strong>
                                    <input type="hidden" name="subject_coordinator_id" value="{{ auth()->user()->id }}">
                                </div>
                                <small class="text-muted">Subject coordinator is pre-assigned (you) and cannot be changed.</small>
                            </div>
                        </div>
                        @php
                            $panelLabels = [];
                            for ($i = 0; $i < $panelSlotCount; $i++) {
                                $panelLabels[] = $i === 0 ? 'Chair' : ($i === 1 ? 'Member' : 'Panelist');
                            }
                            $oldInvited = old('panel_invited_ids', []);
                        @endphp
                        <div class="row mb-4">
                            @for($i = 0; $i < $panelSlotCount; $i++)
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="panel_invited_ids_{{ $i }}">{{ $panelLabels[$i] }} *</label>
                                    <select name="panel_invited_ids[]" id="panel_invited_ids_{{ $i }}"
                                            class="form-select panel-invited-select @error('panel_invited_ids') is-invalid @enderror @error('panel_invited_ids.'.$i) is-invalid @enderror" required>
                                        <option value="">Select {{ $panelLabels[$i] }}</option>
                                        @foreach($availableFaculty as $faculty)
                                            <option value="{{ $faculty->id }}"
                                                {{ isset($oldInvited[$i]) && (string) $oldInvited[$i] === (string) $faculty->id ? 'selected' : '' }}>
                                                {{ $faculty->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('panel_invited_ids.'.$i)
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Adviser and coordinator are pre-assigned and excluded from this list.</small>
                                </div>
                            @endfor
                            @error('panel_invited_ids')
                                <div class="col-12"><div class="invalid-feedback d-block">{{ $message }}</div></div>
                            @enderror
                        </div>
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>Important Notes
                            </h6>
                            <ul class="mb-0 small">
                                <li>All invited faculty slots (Chair, Member, and Panelists) will receive notifications</li>
                                <li>The same panel will serve for all defense phases (Proposal, 60%, 100%)</li>
                                <li>Faculty can accept or decline panel invitations</li>
                                <li>Schedule changes can be made later if needed</li>
                            </ul>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('coordinator.defense.index') }}" class="btn btn-secondary">
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
function syncPanelInvitedDropdowns() {
    const selects = Array.from(document.querySelectorAll('.panel-invited-select'));
    if (!selects.length) {
        return;
    }
    const values = selects.map(s => s.value).filter(Boolean);
    selects.forEach(select => {
        const myVal = select.value;
        Array.from(select.options).forEach(option => {
            if (!option.value) {
                return;
            }
            const takenElsewhere = values.some(v => v === option.value && v !== myVal);
            option.hidden = takenElsewhere;
            option.disabled = takenElsewhere;
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.panel-invited-select').forEach(el => {
        el.addEventListener('change', syncPanelInvitedDropdowns);
    });
    syncPanelInvitedDropdowns();
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('scheduled_date').min = tomorrow.toISOString().split('T')[0];
    
    const dateInput = document.getElementById('scheduled_date');
    const timeInput = document.getElementById('scheduled_time');
    
    if (!dateInput.value) {
        const nextWeek = new Date();
        nextWeek.setDate(nextWeek.getDate() + 7);
        dateInput.value = nextWeek.toISOString().split('T')[0];
    }
    
    if (!timeInput.value) {
        timeInput.value = '09:00';
    }
    
    document.getElementById('room').placeholder = 'e.g., Room 101, Computer Lab, Conference Room A';
});
</script>
@endpush
@endsection
