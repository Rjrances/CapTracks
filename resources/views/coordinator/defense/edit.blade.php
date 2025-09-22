@extends('layouts.coordinator')
@section('title', 'Edit Defense Schedule')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Edit Defense Schedule
                    </h2>
                    <p class="text-muted mb-0">Modify defense schedule for {{ $defenseSchedule->group->name }}</p>
                </div>
                <div>
                    <a href="{{ route('coordinator.defense.show', $defenseSchedule->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Details
                    </a>
                </div>
            </div>
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
                        <hr>
                        <div class="mb-4">
                            <h6 class="mb-3">
                                <i class="fas fa-users me-2"></i>Panel Members
                            </h6>
                            <div class="form-group">
                                <label>Panel Members <span class="text-danger">*</span></label>
                                <div class="alert alert-info mb-3">
                                    <strong>Note:</strong> The group's adviser and offering coordinator are automatically included in the panel.
                                    You only need to add additional panel members below.
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
                                <div id="panel-members-container">
                                    @php $memberIndex = 0; @endphp
                                    @foreach($defenseSchedule->defensePanels as $panel)
                                        @if($panel->role == 'member' || $panel->role == 'chair')
                                            <div class="panel-member-row mb-2">
                                                <div class="row">
                                                    <div class="col-md-5">
                                                        <select name="panel_members[{{ $memberIndex }}][faculty_id]" class="form-control faculty-select" required>
                                                            <option value="">Select Faculty</option>
                                                            @foreach($faculty as $facultyMember)
                                                                <option value="{{ $facultyMember->id }}" {{ $panel->faculty_id == $facultyMember->id ? 'selected' : '' }}>
                                                                    {{ $facultyMember->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <input type="hidden" name="panel_members[{{ $memberIndex }}][id]" value="{{ $panel->id }}">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select name="panel_members[{{ $memberIndex }}][role]" class="form-control" required>
                                                            <option value="">Select Role</option>
                                                            <option value="chair" {{ $panel->role == 'chair' ? 'selected' : '' }}>Chair</option>
                                                            <option value="member" {{ $panel->role == 'member' ? 'selected' : '' }}>Member</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="button" class="btn btn-danger btn-sm remove-panel-member">Remove</button>
                                                    </div>
                                                </div>
                                            </div>
                                            @php $memberIndex++; @endphp
                                        @endif
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-success btn-sm" id="add-panel-member">Add Panel Member</button>
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
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let panelCount = {{ $defenseSchedule->defensePanels->where('role', 'member')->count() }};
    // Initialize faculty options for existing panel members
    function initializeFacultyOptions() {
        const facultySelects = document.querySelectorAll('.faculty-select');
        facultySelects.forEach(select => {
            // Faculty options should already be populated by Blade template
            // This function is just for any additional dynamic loading if needed
        });
    }
    
    // Initialize on page load
    initializeFacultyOptions();
    // Faculty options are pre-populated by Blade template
    // No need for dynamic loading since we have all the data
    document.getElementById('add-panel-member').addEventListener('click', function() {
        const panelMembersContainer = document.getElementById('panel-members-container');
        const newPanelRow = document.createElement('div');
        newPanelRow.className = 'panel-member-row mb-2';
        newPanelRow.innerHTML = `
            <div class="row">
                <div class="col-md-5">
                    <select name="panel_members[${panelCount}][faculty_id]" class="form-control faculty-select" required>
                        <option value="">Select Faculty</option>
                        @foreach($faculty as $facultyMember)
                            <option value="{{ $facultyMember->id }}">{{ $facultyMember->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <select name="panel_members[${panelCount}][role]" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="chair">Chair</option>
                        <option value="member">Member</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm remove-panel-member">Remove</button>
                </div>
            </div>
        `;
        panelMembersContainer.appendChild(newPanelRow);
        panelCount++;
        if (panelCount > 1) {
            document.querySelector('.remove-panel-member').style.display = 'block';
        }
        const groupId = document.getElementById('group_id').value;
        if (groupId) {
            loadFacultyForGroup(groupId);
        }
    });
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-panel-member')) {
            e.target.closest('.panel-member-row').remove();
            panelCount--;
            if (panelCount === 1) {
                document.querySelector('.remove-panel-member').style.display = 'none';
            }
        }
    });
    function checkDoubleBooking() {
        const date = document.getElementById('date').value;
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        const room = document.getElementById('room').value;
        if (date && startTime && endTime && room) {
            fetch('{{ route("coordinator.defense.available-faculty") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    date: date,
                    start_time: startTime,
                    end_time: endTime,
                    room: room
                })
            })
            .then(response => response.json())
            .then(data => {
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
    document.getElementById('end_time').addEventListener('change', checkDoubleBooking);
    document.getElementById('room').addEventListener('input', checkDoubleBooking);
    document.getElementById('defenseForm').addEventListener('submit', function(e) {
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        if (startTime && endTime && startTime >= endTime) {
            e.preventDefault();
            alert('End time must be after start time.');
            return false;
        }
    });
});
</script>
@endsection
