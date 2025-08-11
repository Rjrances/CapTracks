@extends('layouts.chairperson')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Schedule Defense</h4>
                    @if($activeTerm)
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            New defense schedules will be assigned to the current active term: <strong>{{ $activeTerm->full_name }}</strong>
                        </small>
                    @endif
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

                    <form action="{{ route('chairperson.scheduling.store') }}" method="POST" id="defenseForm">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="group_id" class="form-label">Group</label>
                                    <select name="group_id" id="group_id" class="form-select @error('group_id') is-invalid @enderror" required>
                                        <option value="">Select Group</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
                                                {{ $group->name ?? 'N/A' }}
                                                @if($group->adviser)
                                                    (Adviser: {{ $group->adviser->name }})
                                                @else
                                                    (No adviser)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('group_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="stage" class="form-label">Defense Stage</label>
                                    <select name="stage" id="stage" class="form-select @error('stage') is-invalid @enderror" required>
                                        <option value="">Select Stage</option>
                                        <option value="60" {{ old('stage') == '60' ? 'selected' : '' }}>60% Defense</option>
                                        <option value="100" {{ old('stage') == '100' ? 'selected' : '' }}>100% Defense</option>
                                    </select>
                                    @error('stage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="academic_term_id" class="form-label">Academic Term</label>
                                    <select name="academic_term_id" id="academic_term_id" class="form-select @error('academic_term_id') is-invalid @enderror" required>
                                        <option value="">Select Academic Term</option>
                                        @foreach($academicTerms as $term)
                                            <option value="{{ $term->id }}" 
                                                {{ old('academic_term_id', $activeTerm ? $activeTerm->id : '') == $term->id ? 'selected' : '' }}>
                                                {{ $term->full_name }}
                                                @if($term->is_active)
                                                    <span class="text-success">(Active)</span>
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('academic_term_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="room" class="form-label">Room</label>
                                    <input type="text" name="room" id="room" 
                                           class="form-control @error('room') is-invalid @enderror" 
                                           value="{{ old('room') }}" required>
                                    @error('room')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_at" class="form-label">Start Date & Time</label>
                                    <input type="datetime-local" name="start_at" id="start_at" 
                                           class="form-control @error('start_at') is-invalid @enderror" 
                                           value="{{ old('start_at') }}" required>
                                    @error('start_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_at" class="form-label">End Date & Time</label>
                                    <input type="datetime-local" name="end_at" id="end_at" 
                                           class="form-control @error('end_at') is-invalid @enderror" 
                                           value="{{ old('end_at') }}" required>
                                    @error('end_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea name="remarks" id="remarks" rows="3" 
                                      class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks') }}</textarea>
                            @error('remarks')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('chairperson.scheduling.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Schedule Defense
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
                        <span class="badge bg-primary ms-2" id="panelistCount">0</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div id="panelistsContainer">
                        <!-- Panelists will be added here dynamically -->
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label for="faculty_select" class="form-label">Add Panelist</label>
                        <select id="faculty_select" class="form-select">
                            <option value="">Select Faculty Member</option>
                            @foreach($faculty as $member)
                                <option value="{{ $member->id }}" data-name="{{ $member->name }}" data-role="{{ $member->roles->first()->name ?? 'N/A' }}">
                                    {{ $member->name }} ({{ ucfirst($member->roles->first()->name ?? 'N/A') }})
                                </option>
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
let panelistCounter = 0;
const addedPanelists = new Set();

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
