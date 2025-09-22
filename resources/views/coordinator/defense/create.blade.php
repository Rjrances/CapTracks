@extends('layouts.coordinator')
@section('title', 'Create Defense Schedule')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-plus me-2"></i>Create Defense Schedule
                    </h2>
                    <p class="text-muted mb-0">Schedule a defense for a group in your offerings</p>
                </div>
                <div>
                    <a href="{{ route('coordinator.defense.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Schedules
                    </a>
                </div>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> You can only create defense schedules for groups that belong to your coordinated offerings (capstone offer codes). 
                The academic term is automatically set to the current active term.
                <br><small class="text-muted">Available faculty for panel assignment: {{ $faculty->count() }} members</small>
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
                        <i class="fas fa-calendar-plus me-2"></i>Schedule Details
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('coordinator.defense.store') }}" method="POST" id="defenseForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="group_id" class="form-label">Group <span class="text-danger">*</span></label>
                                @if($groups->count() > 0)
                                    <select name="group_id" id="group_id" class="form-select @error('group_id') is-invalid @enderror" required>
                                        <option value="">Select a group</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
                                                {{ $group->name }} - {{ $group->offering->subject_code ?? 'No Offering' }}
                                                @if($group->adviser)
                                                    (Adviser: {{ $group->adviser->name }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>No Groups Available:</strong> You don't have any groups assigned to your offerings yet. 
                                        Please contact the chairperson to assign groups to your offerings.
                                    </div>
                                @endif
                                @error('group_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="stage" class="form-label">Defense Stage <span class="text-danger">*</span></label>
                                <select name="stage" id="stage" class="form-select @error('stage') is-invalid @enderror" required>
                                    <option value="">Select Defense Stage</option>
                                    <option value="proposal" {{ old('stage') == 'proposal' ? 'selected' : '' }}>Proposal Defense</option>
                                    <option value="60" {{ old('stage') == '60' ? 'selected' : '' }}>60% Defense</option>
                                    <option value="100" {{ old('stage') == '100' ? 'selected' : '' }}>100% Defense</option>
                                </select>
                                @error('stage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Academic Term</label>
                                @if($activeTerm)
                                    <div class="form-control-plaintext bg-light">
                                        <i class="fas fa-calendar-check text-success me-2"></i>
                                        <strong>{{ $activeTerm->school_year }} - {{ $activeTerm->semester }}</strong>
                                        <span class="badge bg-success ms-2">Active</span>
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>No Active Term:</strong> Please contact the chairperson to set an active academic term.
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="room" class="form-label">Room <span class="text-danger">*</span></label>
                                <input type="text" name="room" id="room" class="form-control @error('room') is-invalid @enderror" 
                                       value="{{ old('room') }}" placeholder="e.g., Room 101, Computer Lab 2" required>
                                @error('room')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" id="date" class="form-control @error('date') is-invalid @enderror" 
                                       value="{{ old('date') }}" min="{{ date('Y-m-d') }}" required>
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" id="start_time" class="form-control @error('start_time') is-invalid @enderror" 
                                       value="{{ old('start_time') }}" required>
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" id="end_time" class="form-control @error('end_time') is-invalid @enderror" 
                                       value="{{ old('end_time') }}" required>
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
                                    You can select teachers (imported faculty) and chairperson as additional panel members. 
                                    Coordinators and advisers are excluded from the selection.
                                </div>
                                <div id="panel-members-container">
                                    <div class="panel-member-row mb-2">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <select name="panel_members[0][faculty_id]" class="form-control faculty-select" required>
                                                    <option value="">Select Faculty</option>
                                                    @foreach($faculty as $member)
                                                        <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->faculty_id ?? 'N/A' }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-5">
                                                <select name="panel_members[0][role]" class="form-control" required>
                                                    <option value="">Select Role</option>
                                                    <option value="chair">Chair</option>
                                                    <option value="member">Member</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-danger btn-sm remove-panel-member" style="display: none;">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-success btn-sm" id="add-panel-member">Add Panel Member</button>
                                @error('panel_members')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('coordinator.defense.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" {{ !$activeTerm ? 'disabled' : '' }}>
                                <i class="fas fa-save me-2"></i>Create Schedule
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
    let panelCount = 1;
    loadInitialFaculty();
    document.getElementById('group_id').addEventListener('change', function() {
        const groupId = this.value;
        if (groupId) {
            loadFacultyForGroup(groupId);
        } else {
            clearFacultyOptions();
        }
    });
    function loadInitialFaculty() {
        const facultyData = @json($faculty);
        if (facultyData && facultyData.length > 0) {
            updateFacultyOptions(facultyData);
        }
    }
    function loadFacultyForGroup(groupId) {
        fetch('{{ route("coordinator.defense.available-faculty") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                group_id: groupId,
                date: document.getElementById('date').value || '',
                start_time: document.getElementById('start_time').value || '',
                end_time: document.getElementById('end_time').value || '',
                room: document.getElementById('room').value || ''
            })
        })
        .then(response => response.json())
        .then(data => {
            updateFacultyOptions(data.availableFaculty);
        })
        .catch(error => {
            console.error('Error loading faculty:', error);
        });
    }
    function updateFacultyOptions(faculty) {
        const facultySelects = document.querySelectorAll('.faculty-select');
        facultySelects.forEach(select => {
            const currentValue = select.value;
            select.innerHTML = '<option value="">Select Faculty</option>';
            faculty.forEach(member => {
                const option = document.createElement('option');
                option.value = member.id;
                option.textContent = `${member.name} (${member.faculty_id || 'N/A'})`;
                select.appendChild(option);
            });
            if (currentValue && faculty.some(f => f.id == currentValue)) {
                select.value = currentValue;
            }
        });
    }
    function clearFacultyOptions() {
        const facultySelects = document.querySelectorAll('.faculty-select');
        facultySelects.forEach(select => {
            select.innerHTML = '<option value="">Select Faculty</option>';
        });
    }
    document.getElementById('add-panel-member').addEventListener('click', function() {
        const panelMembersContainer = document.getElementById('panel-members-container');
        const newPanelRow = document.createElement('div');
        newPanelRow.className = 'panel-member-row mb-2';
        newPanelRow.innerHTML = `
            <div class="row">
                <div class="col-md-5">
                    <select name="panel_members[${panelCount}][faculty_id]" class="form-control faculty-select" required>
                        <option value="">Select Faculty</option>
                        @foreach($faculty as $member)
                            <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->faculty_id ?? 'N/A' }})</option>
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
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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
