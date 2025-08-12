@extends('layouts.chairperson')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Edit Schedule</h4>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('chairperson.scheduling.update', $defenseSchedule) }}" method="POST" id="defenseForm">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="defense_request_id" class="form-label">Defense Request</label>
                                    <select name="defense_request_id" id="defense_request_id" class="form-select @error('defense_request_id') is-invalid @enderror" required>
                                        <option value="">Select Defense Request</option>
                                        @foreach($defenseRequests as $request)
                                            <option value="{{ $request->id }}" 
                                                {{ old('defense_request_id', $defenseSchedule->defense_request_id) == $request->id ? 'selected' : '' }}>
                                                {{ $request->group->name ?? 'N/A' }} - {{ $request->defense_type ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('defense_request_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="defense_type" class="form-label">Defense Type</label>
                                    <select name="defense_type" id="defense_type" class="form-select @error('defense_type') is-invalid @enderror" required>
                                        <option value="">Select Type</option>
                                        <option value="proposal" {{ old('defense_type', $defenseSchedule->defense_type) == 'proposal' ? 'selected' : '' }}>Proposal Defense</option>
                                        <option value="60_percent" {{ old('defense_type', $defenseSchedule->defense_type) == '60_percent' ? 'selected' : '' }}>60% Progress Defense</option>
                                        <option value="100_percent" {{ old('defense_type', $defenseSchedule->defense_type) == '100_percent' ? 'selected' : '' }}>100% Final Defense</option>
                                    </select>
                                    @error('defense_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="room" class="form-label">Room</label>
                                    <input type="text" name="room" id="room" 
                                           class="form-control @error('room') is-invalid @enderror" 
                                           value="{{ old('room', $defenseSchedule->room) }}" required>
                                    @error('room')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="scheduled_date" class="form-label">Scheduled Date</label>
                                    <input type="date" name="scheduled_date" id="scheduled_date" 
                                           class="form-control @error('scheduled_date') is-invalid @enderror" 
                                           value="{{ old('scheduled_date', $defenseSchedule->scheduled_date->format('Y-m-d')) }}" required>
                                    @error('scheduled_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="scheduled_time" class="form-label">Scheduled Time</label>
                                    <input type="time" name="scheduled_time" id="scheduled_time" 
                                           class="form-control @error('scheduled_time') is-invalid @enderror" 
                                           value="{{ old('scheduled_time', $defenseSchedule->scheduled_time->format('H:i')) }}" required>
                                    @error('scheduled_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="coordinator_notes" class="form-label">Coordinator Notes</label>
                            <textarea name="coordinator_notes" id="coordinator_notes" rows="3" 
                                      class="form-control @error('coordinator_notes') is-invalid @enderror">{{ old('coordinator_notes', $defenseSchedule->coordinator_notes) }}</textarea>
                            @error('coordinator_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('chairperson.scheduling.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Side Panel - Panelist Management -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Panelists
                        <span class="badge bg-primary ms-2" id="panelistCount">{{ $defenseSchedule->panelists->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div id="panelistsContainer">
                        @foreach($defenseSchedule->panelists as $index => $panelist)
                            <div class="panelist-item border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $panelist->name }}</strong>
                                        <br>
                                        <small class="text-muted">Role: {{ ucfirst($panelist->pivot->role) }}</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePanelist(this, '{{ $panelist->id }}')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="panelists[{{ $index }}][faculty_id]" value="{{ $panelist->id }}">
                                <input type="hidden" name="panelists[{{ $index }}][role]" value="{{ $panelist->pivot->role }}">
                            </div>
                        @endforeach
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label for="faculty_select" class="form-label">Add Panelist</label>
                        <select id="faculty_select" class="form-select">
                            <option value="">Select Faculty Member</option>
                            @foreach($faculty as $member)
                                @if(!$defenseSchedule->panelists->contains('id', $member->id))
                                    <option value="{{ $member->id }}" data-name="{{ $member->name }}" data-role="{{ $member->roles->first()->name ?? 'N/A' }}">
                                        {{ $member->name }} ({{ ucfirst($member->roles->first()->name ?? 'N/A') }})
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="panelist_role" class="form-label">Role</label>
                        <select id="panelist_role" class="form-select">
                            <option value="member">Member</option>
                            <option value="chair">Chair</option>
                            <option value="adviser">Adviser</option>
                        </select>
                    </div>

                    <button type="button" class="btn btn-success btn-sm w-100" onclick="addPanelist()">
                        <i class="fas fa-plus"></i> Add Panelist
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let panelistCounter = {{ $defenseSchedule->panelists->count() }};
const addedPanelists = new Set(@json($defenseSchedule->panelists->pluck('id')->toArray()));

function addPanelist() {
    const facultySelect = document.getElementById('faculty_select');
    const roleSelect = document.getElementById('panelist_role');
    const container = document.getElementById('panelistsContainer');
    const countBadge = document.getElementById('panelistCount');

    if (!facultySelect.value) {
        alert('Please select a faculty member');
        return;
    }

    const facultyId = facultySelect.value;
    const facultyName = facultySelect.options[facultySelect.selectedIndex].dataset.name;
    const role = roleSelect.value;

    if (addedPanelists.has(facultyId)) {
        alert('This faculty member is already added to the panel');
        return;
    }

    const panelistDiv = document.createElement('div');
    panelistDiv.className = 'panelist-item border rounded p-2 mb-2';
    panelistDiv.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>${facultyName}</strong>
                <br>
                <small class="text-muted">Role: ${role.charAt(0).toUpperCase() + role.slice(1)}</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePanelist(this, '${facultyId}')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <input type="hidden" name="panelists[${panelistCounter}][faculty_id]" value="${facultyId}">
        <input type="hidden" name="panelists[${panelistCounter}][role]" value="${role}">
    `;

    container.appendChild(panelistDiv);
    addedPanelists.add(facultyId);
    panelistCounter++;
    countBadge.textContent = panelistCounter;

    // Reset selects
    facultySelect.value = '';
    roleSelect.value = 'member';
}

function removePanelist(button, facultyId) {
    const panelistDiv = button.closest('.panelist-item');
    panelistDiv.remove();
    addedPanelists.delete(facultyId);
    
    const countBadge = document.getElementById('panelistCount');
    countBadge.textContent = parseInt(countBadge.textContent) - 1;

    // Reindex the hidden inputs
    const hiddenInputs = document.querySelectorAll('#panelistsContainer input[type="hidden"]');
    let newIndex = 0;
    for (let i = 0; i < hiddenInputs.length; i += 2) {
        hiddenInputs[i].name = `panelists[${newIndex}][faculty_id]`;
        hiddenInputs[i + 1].name = `panelists[${newIndex}][role]`;
        newIndex++;
    }
    panelistCounter = newIndex;
}

// Form validation
document.getElementById('defenseForm').addEventListener('submit', function(e) {
    if (panelistCounter === 0) {
        e.preventDefault();
        alert('Please add at least one panelist');
        return false;
    }
});
</script>

<style>
.panelist-item {
    background-color: #f8f9fa;
}
</style>
@endsection
