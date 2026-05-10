@extends('layouts.coordinator')
@section('title', 'Edit Defense Schedule')
@section('content')
<div class="container-fluid">
        <x-coordinator.intro :description="'Update date, room, or panel for the defense of '.$defenseSchedule->group->name.'.'">
            <a href="{{ route('coordinator.defense.show', $defenseSchedule->id) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to details
            </a>
        </x-coordinator.intro>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> You can only edit defense schedules for groups that belong to your coordinated offerings (capstone offer codes).
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
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-calendar-edit me-2"></i>Schedule Details
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('coordinator.defense.update', $defenseSchedule->id) }}" method="POST" id="defenseForm">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="group_id" class="form-label">Group <span class="text-danger">*</span></label>
                                <select name="group_id" id="group_id" class="form-select @error('group_id') is-invalid @enderror" required>
                                    <option value="">Select a group</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ old('group_id', $defenseSchedule->group_id) == $group->id ? 'selected' : '' }}>
                                            {{ $group->name }} - {{ $group->offering->subject_code ?? 'No Offering' }}
                                            @if($group->adviser)
                                                (Adviser: {{ $group->adviser->name }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('group_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="stage" class="form-label">Defense Stage <span class="text-danger">*</span></label>
                                <select name="stage" id="stage" class="form-select @error('stage') is-invalid @enderror" required>
                                    <option value="">Select Defense Stage</option>
                                    <option value="proposal" {{ old('stage', $defenseSchedule->stage) == 'proposal' ? 'selected' : '' }}>Proposal Defense</option>
                                    <option value="60" {{ old('stage', $defenseSchedule->stage) == '60' ? 'selected' : '' }}>60% Defense</option>
                                    <option value="100" {{ old('stage', $defenseSchedule->stage) == '100' ? 'selected' : '' }}>100% Defense</option>
                                </select>
                                @error('stage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="academic_term_id" class="form-label">Academic Term <span class="text-danger">*</span></label>
                                <select name="academic_term_id" id="academic_term_id" class="form-select @error('academic_term_id') is-invalid @enderror" required>
                                    <option value="">Select Academic Term</option>
                                    @foreach($academicTerms as $term)
                                        <option value="{{ $term->id }}" {{ old('academic_term_id', $defenseSchedule->academic_term_id) == $term->id ? 'selected' : '' }}>
                                            {{ $term->school_year }} - {{ $term->semester }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('academic_term_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="room" class="form-label">Room <span class="text-danger">*</span></label>
                                <input type="text" name="room" id="room" class="form-control @error('room') is-invalid @enderror" 
                                       value="{{ old('room', $defenseSchedule->room) }}" placeholder="e.g., Room 101, Computer Lab 2" required>
                                @error('room')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="milestone_override_reason" class="form-label">Milestone Override Reason (required only if milestone is incomplete)</label>
                            <textarea
                                name="milestone_override_reason"
                                id="milestone_override_reason"
                                rows="2"
                                class="form-control @error('milestone_override_reason') is-invalid @enderror"
                                placeholder="Explain why this defense must proceed even if required milestone is not completed.">{{ old('milestone_override_reason', $defenseSchedule->milestone_override_reason) }}</textarea>
                            @error('milestone_override_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Students are blocked from requesting when milestone is incomplete. Coordinators may override with documented reason.</small>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" id="date" class="form-control @error('date') is-invalid @enderror" 
                                       value="{{ old('date', $defenseSchedule->start_at->format('Y-m-d')) }}" required>
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" id="start_time" class="form-control @error('start_time') is-invalid @enderror" 
                                       value="{{ old('start_time', $defenseSchedule->start_at->format('H:i')) }}" required>
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" id="end_time" class="form-control @error('end_time') is-invalid @enderror" 
                                       value="{{ old('end_time', $defenseSchedule->end_at->format('H:i')) }}" required>
                                @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div id="doubleBookingWarning" class="alert alert-warning d-none" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span id="warningMessage"></span>
                        </div>
                        <div id="timeOrderWarning" class="alert alert-danger d-none" role="alert">
                            <i class="fas fa-arrows-alt-v me-2"></i>
                            <span id="timeOrderMessage">End time must be after start time on the same day.</span>
                        </div>
                        <hr>
                        <div class="mb-4">
                            <h6 class="mb-3">
                                <i class="fas fa-users me-2"></i>Panel Members
                            </h6>
                            <div class="form-group">
                                <label>Panel Members <span class="text-danger">*</span></label>
                                <div class="alert alert-info mb-3">
                                    <strong>Note:</strong> The group's adviser and offering coordinator are automatically included in the panel.
                                    Select faculty for each invited slot (Chair, Member, and {{ max(0, $panelSlotCount - 2) }} additional Panelist{{ max(0, $panelSlotCount - 2) === 1 ? '' : 's' }}). Each slot must be a different faculty member.
                                    Changing <strong>Group</strong> reloads each dropdown to that group’s eligible faculty (same rules as creating a defense).
                                </div>
                                @if($defenseSchedule->group->adviser || ($defenseSchedule->group->offering && $defenseSchedule->group->offering->faculty_id))
                                    <div class="alert alert-success mb-3">
                                        <strong>Automatically Included:</strong>
                                        <ul class="mb-0 mt-2">
                                            @if($defenseSchedule->group->adviser)
                                                <li><strong>{{ $defenseSchedule->group->adviser->name }}</strong> - Adviser</li>
                                            @endif
                                            @if($defenseSchedule->group->offering && $defenseSchedule->group->offering->faculty_id)
                                                <li><strong>{{ $defenseSchedule->group->offering->faculty->name ?? 'Unknown' }}</strong> - Offering Coordinator</li>
                                            @endif
                                        </ul>
                                    </div>
                                @endif
                                @php
                                    $autoIncludedPanelIds = collect([
                                        optional($defenseSchedule->group->adviser)->id,
                                        optional(optional($defenseSchedule->group->offering)->faculty)->id,
                                    ])->filter()->map(fn ($id) => (string) $id)->all();
                                @endphp
                                <div id="panel-members-container">
                                    @foreach($invitedEditSlots as $idx => $slot)
                                        @php
                                            $roleLabel = match ($slot['role']) {
                                                'chair' => 'Chair',
                                                'member' => 'Member',
                                                default => 'Panelist',
                                            };
                                            $selectedId = (string) old('panel_members.'.$idx.'.faculty_id', $slot['selected_id']);
                                        @endphp
                                        <div class="panel-member-row mb-2">
                                            <div class="row">
                                                <div class="col-md-5">
                                                    <select name="panel_members[{{ $idx }}][faculty_id]" class="form-control faculty-select" required>
                                                        <option value="">Select Faculty</option>
                                                        @foreach($currentPanelFacultyOptions as $facultyMember)
                                                            @continue(in_array((string) $facultyMember['id'], $autoIncludedPanelIds, true))
                                                            <option
                                                                value="{{ $facultyMember['id'] }}"
                                                                {{ $selectedId === (string) $facultyMember['id'] ? 'selected' : '' }}
                                                            >
                                                                {{ $facultyMember['name'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="text" class="form-control" value="{{ $roleLabel }}" readonly>
                                                    <input type="hidden" name="panel_members[{{ $idx }}][role]" value="{{ $slot['role'] }}">
                                                </div>
                                                <div class="col-md-2">
                                                    <span class="badge bg-secondary">Required</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('panel_members')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('coordinator.defense.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
</div>
<script>
function defenseTimeInputToMinutes(value) {
    if (value == null || value === '') return null;
    const parts = String(value).split(':');
    const h = parseInt(parts[0], 10);
    const m = parseInt(parts[1] != null ? parts[1] : '0', 10);
    if (Number.isNaN(h) || Number.isNaN(m)) return null;
    return h * 60 + m;
}
function defenseUpdateTimeOrderWarning() {
    const startEl = document.getElementById('start_time');
    const endEl = document.getElementById('end_time');
    const box = document.getElementById('timeOrderWarning');
    if (!startEl || !endEl || !box) return;
    const sm = defenseTimeInputToMinutes(startEl.value);
    const em = defenseTimeInputToMinutes(endEl.value);
    if (sm === null || em === null) {
        box.classList.add('d-none');
        endEl.classList.remove('is-invalid');
        return;
    }
    if (em <= sm) {
        box.classList.remove('d-none');
        endEl.classList.add('is-invalid');
    } else {
        box.classList.add('d-none');
        endEl.classList.remove('is-invalid');
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const panelFacultyByGroupId = @json($panelFacultyByGroupId);

    function syncPanelDropdowns() {
        const selects = Array.from(document.querySelectorAll('.faculty-select'));
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

    function refillPanelSelectsForGroup(groupId) {
        const list = panelFacultyByGroupId[groupId] || [];
        document.querySelectorAll('.faculty-select').forEach(select => {
            const prev = select.value;
            select.innerHTML = '<option value="">Select Faculty</option>';
            list.forEach(f => {
                const opt = document.createElement('option');
                opt.value = f.id;
                opt.textContent = f.name;
                select.appendChild(opt);
            });
            if (prev && list.some(item => String(item.id) === prev)) {
                select.value = prev;
            }
        });
        syncPanelDropdowns();
    }

    document.querySelectorAll('.faculty-select').forEach(el => {
        el.addEventListener('change', syncPanelDropdowns);
    });
    const groupSelectEl = document.getElementById('group_id');
    if (groupSelectEl) {
        groupSelectEl.addEventListener('change', function () {
            refillPanelSelectsForGroup(this.value);
        });
    }
    syncPanelDropdowns();

    function checkDoubleBooking() {
        const date = document.getElementById('date').value;
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        const room = document.getElementById('room').value;
        defenseUpdateTimeOrderWarning();
        const sm = defenseTimeInputToMinutes(startTime);
        const em = defenseTimeInputToMinutes(endTime);
        if (sm !== null && em !== null && em <= sm) {
            document.getElementById('doubleBookingWarning').classList.add('d-none');
            return;
        }
        const groupId = document.getElementById('group_id')?.value;
        if (date && startTime && endTime && room && groupId) {
            fetch('{{ route("coordinator.defense.available-faculty") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    group_id: groupId,
                    date: date,
                    start_time: startTime,
                    end_time: endTime,
                    room: room
                })
            })
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                const orderBox = document.getElementById('timeOrderWarning');
                const orderMsg = document.getElementById('timeOrderMessage');
                if (!response.ok) {
                    document.getElementById('doubleBookingWarning').classList.add('d-none');
                    if (data.invalid_time_window && orderMsg && orderBox && data.message) {
                        orderMsg.textContent = data.message;
                        orderBox.classList.remove('d-none');
                    }
                    return;
                }
                if (orderBox) {
                    orderBox.classList.add('d-none');
                }
                defenseUpdateTimeOrderWarning();
                if (data.conflict) {
                    document.getElementById('warningMessage').textContent = data.message;
                    document.getElementById('doubleBookingWarning').classList.remove('d-none');
                } else {
                    document.getElementById('doubleBookingWarning').classList.add('d-none');
                }
            });
        }
    }
    document.getElementById('date').addEventListener('change', checkDoubleBooking);
    document.getElementById('start_time').addEventListener('change', checkDoubleBooking);
    document.getElementById('start_time').addEventListener('input', defenseUpdateTimeOrderWarning);
    document.getElementById('end_time').addEventListener('change', checkDoubleBooking);
    document.getElementById('end_time').addEventListener('input', defenseUpdateTimeOrderWarning);
    document.getElementById('room').addEventListener('input', checkDoubleBooking);
    defenseUpdateTimeOrderWarning();
    document.getElementById('defenseForm').addEventListener('submit', function(e) {
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        const sm = defenseTimeInputToMinutes(startTime);
        const em = defenseTimeInputToMinutes(endTime);
        if (sm !== null && em !== null && em <= sm) {
            e.preventDefault();
            defenseUpdateTimeOrderWarning();
            document.getElementById('timeOrderWarning')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            alert('End time must be after start time on the same day.');
            return false;
        }
    });
});
</script>
@endsection
