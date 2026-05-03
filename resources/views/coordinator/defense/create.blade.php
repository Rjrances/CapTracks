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
                <br><small class="text-muted">Panel Chair/Member lists exclude the group adviser and subject coordinator (they are added automatically).</small>
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
                                        <strong>{{ $activeTerm->school_year }}</strong>
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
                                    Select exactly two slots only: one Chair and one Member.
                                </div>
                                <div id="panel-members-container">
                                    <div class="panel-member-row mb-2">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <select name="panel_members[0][faculty_id]" class="form-control faculty-select" required>
                                                    <option value="">Select Faculty</option>
                                                </select>
                                            </div>
                                            <div class="col-md-5">
                                                <input type="text" class="form-control" value="Chair" readonly>
                                                <input type="hidden" name="panel_members[0][role]" value="chair">
                                            </div>
                                            <div class="col-md-2">
                                                <span class="badge bg-secondary">Required</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel-member-row mb-2">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <select name="panel_members[1][faculty_id]" class="form-control faculty-select" required>
                                                    <option value="">Select Faculty</option>
                                                </select>
                                            </div>
                                            <div class="col-md-5">
                                                <input type="text" class="form-control" value="Member" readonly>
                                                <input type="hidden" name="panel_members[1][role]" value="member">
                                            </div>
                                            <div class="col-md-2">
                                                <span class="badge bg-secondary">Required</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @error('panel_members')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-info" id="auto-assign-panel">
                                <i class="fas fa-magic me-2"></i>Auto-Assign Panel
                            </button>
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
    let currentAvailableFaculty = [];
    let latestDoubleBookingRequestId = 0;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const panelFacultyByGroupId = @json($panelFacultyByGroupId);
    const initialPanelPicks = @json([
        old('panel_members.0.faculty_id'),
        old('panel_members.1.faculty_id'),
    ]);
    const groupSelectEl = document.getElementById('group_id');
    if (groupSelectEl) {
        groupSelectEl.addEventListener('change', function() {
            const groupId = this.value;
            if (groupId) {
                loadFacultyForGroup(groupId);
            } else {
                clearFacultyOptions();
            }
        });
    }
    loadInitialFaculty();
    function loadInitialFaculty() {
        const groupSelect = document.getElementById('group_id');
        const gid = groupSelect ? groupSelect.value : '';
        if (gid) {
            loadFacultyForGroup(gid);
        } else {
            clearFacultyOptions();
        }
    }
    function loadFacultyForGroup(groupId) {
        const base = panelFacultyByGroupId[groupId] || [];
        const date = document.getElementById('date').value || '';
        const startTime = document.getElementById('start_time').value || '';
        const endTime = document.getElementById('end_time').value || '';
        const room = document.getElementById('room').value || '';

        if (!date || !startTime || !endTime || !room) {
            currentAvailableFaculty = base;
            updateFacultyOptions(base);
            return Promise.resolve(base);
        }

        return fetch('{{ route("coordinator.defense.available-faculty") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                group_id: groupId,
                date: date,
                start_time: startTime,
                end_time: endTime,
                room: room
            })
        })
        .then(response => response.json())
        .then(data => {
            const list = data.availableFaculty || base;
            currentAvailableFaculty = list;
            updateFacultyOptions(list);
            return list;
        })
        .catch(error => {
            console.error('Error loading faculty:', error);
            currentAvailableFaculty = base;
            updateFacultyOptions(base);
            return base;
        });
    }
    function updateFacultyOptions(faculty) {
        const facultySelects = document.querySelectorAll('.faculty-select');
        facultySelects.forEach((select, index) => {
            let currentValue = select.value;
            if (!currentValue && initialPanelPicks[index] != null && initialPanelPicks[index] !== '') {
                currentValue = String(initialPanelPicks[index]);
            }
            select.innerHTML = '<option value="">Select Faculty</option>';
            faculty.forEach(member => {
                const option = document.createElement('option');
                option.value = member.id;
                option.textContent = `${member.name} (${member.faculty_id != null ? member.faculty_id : 'N/A'})`;
                select.appendChild(option);
            });
            if (currentValue && faculty.some(f => String(f.id) === String(currentValue))) {
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
    document.getElementById('auto-assign-panel').addEventListener('click', function() {
        autoAssignPanelMembers();
    });

    async function autoAssignPanelMembers() {
        const facultySelects = Array.from(document.querySelectorAll('.faculty-select'));
        const roleInputs = Array.from(document.querySelectorAll('input[name$="[role]"]'));
        const groupId = document.getElementById('group_id').value;
        const date = document.getElementById('date').value;
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        const room = document.getElementById('room').value;

        if (facultySelects.length === 0) {
            return;
        }

        if (!groupId || !date || !startTime || !endTime || !room) {
            alert('Please select group, date, start time, end time, and room before auto-assigning.');
            return;
        }

        await loadFacultyForGroup(groupId);

        if (currentAvailableFaculty.length < 2) {
            alert('Not enough available faculty to auto-assign panel members.');
            return;
        }
        const selectedFacultyIds = currentAvailableFaculty
            .slice(0, 2)
            .map(member => String(member.id));

        facultySelects.forEach((select, index) => {
            if (index < 2) {
                select.value = selectedFacultyIds[index];
                roleInputs[index].value = index === 0 ? 'chair' : 'member';
            }
        });
    }
    function checkDoubleBooking() {
        const groupId = document.getElementById('group_id').value;
        const date = document.getElementById('date').value;
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        const room = document.getElementById('room').value;

        if (!groupId || !date || !startTime || !endTime || !room) {
            document.getElementById('doubleBookingWarning').classList.add('d-none');
            return;
        }

        const requestId = ++latestDoubleBookingRequestId;
        const requestPayload = {
            group_id: groupId,
            date: date,
            start_time: startTime,
            end_time: endTime,
            room: room
        };

        const requestSignature = JSON.stringify(requestPayload);

        if (groupId && date && startTime && endTime && room) {
            fetch('{{ route("coordinator.defense.available-faculty") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(requestPayload)
            })
            .then(response => response.json())
            .then(data => {
                const currentSignature = JSON.stringify({
                    group_id: document.getElementById('group_id').value,
                    date: document.getElementById('date').value,
                    start_time: document.getElementById('start_time').value,
                    end_time: document.getElementById('end_time').value,
                    room: document.getElementById('room').value
                });

                if (requestId !== latestDoubleBookingRequestId || requestSignature !== currentSignature) {
                    return;
                }

                currentAvailableFaculty = data.availableFaculty || [];
                updateFacultyOptions(currentAvailableFaculty);
                if (data.conflict) {
                    document.getElementById('warningMessage').textContent = data.message;
                    document.getElementById('doubleBookingWarning').classList.remove('d-none');
                } else {
                    document.getElementById('doubleBookingWarning').classList.add('d-none');
                }
            })
            .catch(() => {
                if (requestId === latestDoubleBookingRequestId) {
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
