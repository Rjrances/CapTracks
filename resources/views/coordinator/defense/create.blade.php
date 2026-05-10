@extends('layouts.coordinator')
@section('title', 'Create Defense Schedule')
@section('content')
<div class="container-fluid">
        <x-coordinator.intro description="Set date, room, and panel for a defense in one of your coordinated offerings.">
            <a href="{{ route('coordinator.defense.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Defense management
            </a>
        </x-coordinator.intro>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> You can only create defense schedules for groups that belong to your coordinated offerings (capstone offer codes). 
                The academic term is automatically set to the current active term.
                <br><small class="text-muted">Invited faculty (Chair, Member, and additional Panelists—{{ $panelSlotCount }} slots by configuration) are assigned automatically (no manual selection). The adviser and subject coordinator are excluded from those slots and are added to the panel automatically.</small>
            </div>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(! empty($prefillGroupUnavailable))
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This group cannot be selected for a new schedule right now (for example, it may already have a defense on record). Choose another group or open Defense management to review pending schedules.
                </div>
            @endif
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
                        @php
                            $selectedGroupId = old('group_id', $prefillGroupId ?? null);
                            $selectedStage = old('stage', $prefillStage ?? null);
                        @endphp
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="group_id" class="form-label">Group <span class="text-danger">*</span></label>
                                @if($groups->count() > 0)
                                    <select name="group_id" id="group_id" class="form-select @error('group_id') is-invalid @enderror" required>
                                        <option value="">Select a group</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group->id }}" {{ (string) $selectedGroupId === (string) $group->id ? 'selected' : '' }}>
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
                                        @if(($groupAvailability['total_scoped_groups'] ?? 0) > 0 && ($groupAvailability['scheduled_groups'] ?? 0) > 0)
                                            <strong>No Groups Available for New Schedule:</strong>
                                            All your scoped groups for the active term already have defense schedules.
                                        @else
                                            <strong>No Groups Available:</strong> You don't have any groups assigned to your offerings yet.
                                            Please contact the chairperson to assign groups to your offerings.
                                        @endif
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
                                    <option value="proposal" {{ $selectedStage === 'proposal' ? 'selected' : '' }}>Proposal Defense</option>
                                    <option value="60" {{ $selectedStage === '60' ? 'selected' : '' }}>60% Defense</option>
                                    <option value="100" {{ $selectedStage === '100' ? 'selected' : '' }}>100% Defense</option>
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
                        <div class="mb-3">
                            <label for="milestone_override_reason" class="form-label">Milestone Override Reason (required only if milestone is incomplete)</label>
                            <textarea
                                name="milestone_override_reason"
                                id="milestone_override_reason"
                                rows="2"
                                class="form-control @error('milestone_override_reason') is-invalid @enderror"
                                placeholder="Explain why this defense must proceed even if required milestone is not completed.">{{ old('milestone_override_reason') }}</textarea>
                            @error('milestone_override_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Students are blocked from requesting when milestone is incomplete. Coordinators may override with documented reason.</small>
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
                        <div id="pastStartWarning" class="alert alert-danger d-none" role="alert">
                            <i class="fas fa-clock me-2"></i>
                            <span id="pastStartMessage"></span>
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
                                <label>Panel (invited faculty)</label>
                                <div class="alert alert-info mb-3">
                                    <strong>Note:</strong> The group's adviser and offering coordinator are automatically included in the panel.
                                    Chair, Member, and additional Panelists are assigned by the system from available faculty (availability, no double-booking, workload balancing). You cannot change them on this screen—the same rules apply when you submit.
                                </div>
                                <div id="panel-auto-assignment-hint" class="alert alert-warning mb-3">
                                    Fill in Group, Date, Start Time, End Time, and Room to preview the auto-assigned panel ({{ $panelSlotCount }} slots).
                                </div>
                                <div id="panel-insufficient-faculty" class="alert alert-danger d-none mb-3" role="alert">
                                    <i class="fas fa-user-slash me-2"></i>
                                    Fewer than {{ $panelSlotCount }} faculty are available for the invited panel slots. Adjust date, time, or room and check again.
                                </div>
                                @php
                                    $slotLabels = [];
                                    for ($i = 0; $i < $panelSlotCount; $i++) {
                                        $slotLabels[] = $i === 0 ? 'Chair' : ($i === 1 ? 'Member' : 'Panelist');
                                    }
                                @endphp
                                <div id="panel-members-container">
                                    @for($i = 0; $i < $panelSlotCount; $i++)
                                    <div class="panel-member-row mb-3">
                                        <div class="row align-items-center g-2">
                                            <div class="col-sm-3 col-md-2">
                                                <span class="badge {{ $i === 0 ? 'bg-primary' : ($i === 1 ? 'bg-secondary' : 'bg-info text-dark') }}">{{ $slotLabels[$i] }}</span>
                                            </div>
                                            <div class="col-sm-9 col-md-10">
                                                <div id="panel-display-{{ $i }}" class="border rounded px-3 py-2 bg-light text-muted">—</div>
                                            </div>
                                        </div>
                                    </div>
                                    @endfor
                                </div>
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
<script>
(function () {
    const PANEL_SLOT_COUNT = {{ (int) $panelSlotCount }};
    const APP_TIMEZONE_OFFSET = @json(now()->timezone(config('app.timezone'))->format('P'));

    function defenseStartInstant(dateStr, timeStr) {
        if (!dateStr || !timeStr) {
            return null;
        }
        const normalized = timeStr.length === 5 ? timeStr + ':00' : timeStr;

        return new Date(dateStr + 'T' + normalized + APP_TIMEZONE_OFFSET);
    }

    function timeInputToMinutes(value) {
        if (value == null || value === '') {
            return null;
        }
        const s = String(value);
        const parts = s.split(':');
        const h = parseInt(parts[0], 10);
        const m = parseInt(parts[1] != null ? parts[1] : '0', 10);
        if (Number.isNaN(h) || Number.isNaN(m)) {
            return null;
        }
        return h * 60 + m;
    }

    function isEndTimeAfterStartOnSameDay() {
        const startM = timeInputToMinutes(document.getElementById('start_time')?.value);
        const endM = timeInputToMinutes(document.getElementById('end_time')?.value);
        if (startM === null || endM === null) {
            return true;
        }
        return endM > startM;
    }

    function updateTimeOrderWarning() {
        const box = document.getElementById('timeOrderWarning');
        const startEl = document.getElementById('start_time');
        const endEl = document.getElementById('end_time');
        if (!box || !startEl || !endEl) {
            return;
        }
        if (!isEndTimeAfterStartOnSameDay()) {
            box.classList.remove('d-none');
            endEl.classList.add('is-invalid');
        } else {
            box.classList.add('d-none');
            endEl.classList.remove('is-invalid');
        }
    }

    function updatePastStartWarning() {
        const box = document.getElementById('pastStartWarning');
        const msgEl = document.getElementById('pastStartMessage');
        const dateEl = document.getElementById('date');
        const startEl = document.getElementById('start_time');
        if (!box || !msgEl || !dateEl || !startEl) {
            return;
        }
        const inst = defenseStartInstant(dateEl.value, startEl.value);
        if (!inst || isNaN(inst.getTime())) {
            box.classList.add('d-none');
            startEl.classList.remove('is-invalid');
            return;
        }
        if (inst.getTime() <= Date.now()) {
            msgEl.textContent = 'Start date and time must be later than right now. You cannot schedule a defense in the past.';
            box.classList.remove('d-none');
            startEl.classList.add('is-invalid');
        } else {
            box.classList.add('d-none');
            startEl.classList.remove('is-invalid');
        }
    }

document.addEventListener('DOMContentLoaded', function() {
    let currentAvailableFaculty = [];
    let currentAutoAssignedFacultyIds = [];
    let latestDoubleBookingRequestId = 0;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const panelFacultyByGroupId = @json($panelFacultyByGroupId);
    const groupSelectEl = document.getElementById('group_id');
    if (groupSelectEl) {
        groupSelectEl.addEventListener('change', function() {
            const groupId = this.value;
            if (groupId && hasCompleteSchedulingInputs()) {
                loadFacultyForGroup(groupId);
            } else {
                clearPanelDisplay();
            }
            togglePanelAssignmentState();
        });
    }
    document.getElementById('date').addEventListener('change', function () {
        togglePanelAssignmentState();
        updatePastStartWarning();
    });
    document.getElementById('start_time').addEventListener('change', function () {
        togglePanelAssignmentState();
        updatePastStartWarning();
        updateTimeOrderWarning();
    });
    document.getElementById('start_time').addEventListener('input', function () {
        updatePastStartWarning();
        updateTimeOrderWarning();
    });
    document.getElementById('end_time').addEventListener('change', function () {
        togglePanelAssignmentState();
        updateTimeOrderWarning();
    });
    document.getElementById('end_time').addEventListener('input', updateTimeOrderWarning);
    document.getElementById('room').addEventListener('input', togglePanelAssignmentState);
    loadInitialFaculty();
    togglePanelAssignmentState();
    updateTimeOrderWarning();
    updatePastStartWarning();

    function hasCompleteSchedulingInputs() {
        return !!(
            document.getElementById('group_id').value &&
            document.getElementById('date').value &&
            document.getElementById('start_time').value &&
            document.getElementById('end_time').value &&
            document.getElementById('room').value
        );
    }

    function togglePanelAssignmentState() {
        const enabled = hasCompleteSchedulingInputs();
        const hint = document.getElementById('panel-auto-assignment-hint');

        if (hint) {
            hint.classList.toggle('d-none', enabled);
        }

        if (enabled) {
            const groupId = document.getElementById('group_id').value;
            if (groupId) {
                loadFacultyForGroup(groupId);
            }
        } else {
            clearPanelDisplay();
        }
    }

    function loadInitialFaculty() {
        const groupSelect = document.getElementById('group_id');
        const gid = groupSelect ? groupSelect.value : '';
        if (gid && hasCompleteSchedulingInputs()) {
            loadFacultyForGroup(gid);
        } else {
            clearPanelDisplay();
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
            currentAutoAssignedFacultyIds = [];
            clearPanelDisplay();
            return Promise.resolve(base);
        }

        if (!isEndTimeAfterStartOnSameDay()) {
            updateTimeOrderWarning();
            document.getElementById('doubleBookingWarning')?.classList.add('d-none');
            currentAvailableFaculty = base;
            currentAutoAssignedFacultyIds = [];
            clearPanelDisplay();
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
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            const pastBox = document.getElementById('pastStartWarning');
            const pastMsg = document.getElementById('pastStartMessage');
            const orderBox = document.getElementById('timeOrderWarning');
            const orderMsg = document.getElementById('timeOrderMessage');
            if (!response.ok) {
                document.getElementById('doubleBookingWarning')?.classList.add('d-none');
                pastBox?.classList.add('d-none');
                orderBox?.classList.add('d-none');
                if (data.invalid_time_window && orderMsg && orderBox && data.message) {
                    orderMsg.textContent = data.message;
                    orderBox.classList.remove('d-none');
                } else if (pastBox && pastMsg && data.message) {
                    pastMsg.textContent = data.message;
                    pastBox.classList.remove('d-none');
                }
                currentAvailableFaculty = base;
                currentAutoAssignedFacultyIds = [];
                clearPanelDisplay();
                return base;
            }
            if (pastBox) {
                pastBox.classList.add('d-none');
            }
            if (orderBox) {
                orderBox.classList.add('d-none');
            }
            updateTimeOrderWarning();
            updatePastStartWarning();
            const list = data.availableFaculty || base;
            currentAvailableFaculty = list;
            currentAutoAssignedFacultyIds = (data.autoAssignedFacultyIds || list.slice(0, PANEL_SLOT_COUNT).map(member => String(member.id))).map(String);
            renderAutoAssignedPanel(list, currentAutoAssignedFacultyIds);
            return list;
        })
        .catch(error => {
            console.error('Error loading faculty:', error);
            currentAvailableFaculty = base;
            currentAutoAssignedFacultyIds = base.slice(0, PANEL_SLOT_COUNT).map(member => String(member.id));
            renderAutoAssignedPanel(base, currentAutoAssignedFacultyIds);
            return base;
        });
    }
    function renderAutoAssignedPanel(faculty, autoIds) {
        const insufficientEl = document.getElementById('panel-insufficient-faculty');
        const list = faculty || [];
        const ids = (autoIds && autoIds.length) ? autoIds.map(String) : list.slice(0, PANEL_SLOT_COUNT).map(m => String(m.id));

        function labelForUserId(id) {
            if (id == null || id === '') {
                return null;
            }
            const m = list.find(f => String(f.id) === String(id));
            if (!m) {
                return null;
            }
            return `${m.name} (${m.faculty_id != null ? m.faculty_id : 'N/A'})`;
        }

        for (let i = 0; i < PANEL_SLOT_COUNT; i++) {
            const el = document.getElementById('panel-display-' + i);
            if (!el) {
                continue;
            }
            const label = ids[i] ? labelForUserId(ids[i]) : null;
            el.textContent = label || '—';
            el.classList.toggle('text-muted', !label);
        }

        if (insufficientEl) {
            const insufficient = list.length < PANEL_SLOT_COUNT;
            insufficientEl.classList.toggle('d-none', !insufficient);
        }
    }
    function clearPanelDisplay() {
        const insufficientEl = document.getElementById('panel-insufficient-faculty');
        for (let i = 0; i < PANEL_SLOT_COUNT; i++) {
            const el = document.getElementById('panel-display-' + i);
            if (el) {
                el.textContent = '—';
                el.classList.add('text-muted');
            }
        }
        if (insufficientEl) {
            insufficientEl.classList.add('d-none');
        }
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

        if (!isEndTimeAfterStartOnSameDay()) {
            updateTimeOrderWarning();
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
            .then(async response => {
                const data = await response.json().catch(() => ({}));
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

                const pastBox = document.getElementById('pastStartWarning');
                const pastMsg = document.getElementById('pastStartMessage');
                const orderBox = document.getElementById('timeOrderWarning');
                const orderMsg = document.getElementById('timeOrderMessage');
                if (!response.ok) {
                    document.getElementById('doubleBookingWarning')?.classList.add('d-none');
                    pastBox?.classList.add('d-none');
                    orderBox?.classList.add('d-none');
                    if (data.invalid_time_window && orderMsg && orderBox && data.message) {
                        orderMsg.textContent = data.message;
                        orderBox.classList.remove('d-none');
                    } else if (pastBox && pastMsg && data.message) {
                        pastMsg.textContent = data.message;
                        pastBox.classList.remove('d-none');
                    }
                    currentAvailableFaculty = [];
                    currentAutoAssignedFacultyIds = [];
                    clearPanelDisplay();
                    return;
                }
                if (pastBox) {
                    pastBox.classList.add('d-none');
                }
                if (orderBox) {
                    orderBox.classList.add('d-none');
                }
                updateTimeOrderWarning();
                updatePastStartWarning();

                currentAvailableFaculty = data.availableFaculty || [];
                currentAutoAssignedFacultyIds = (data.autoAssignedFacultyIds || []).map(String);
                renderAutoAssignedPanel(currentAvailableFaculty, currentAutoAssignedFacultyIds);
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
        const dateVal = document.getElementById('date').value;
        const startTime = document.getElementById('start_time').value;
        if (!isEndTimeAfterStartOnSameDay()) {
            e.preventDefault();
            updateTimeOrderWarning();
            document.getElementById('timeOrderWarning')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            alert('End time must be after start time on the same day.');
            return false;
        }
        const startInst = defenseStartInstant(dateVal, startTime);
        if (startInst && !isNaN(startInst.getTime()) && startInst.getTime() <= Date.now()) {
            e.preventDefault();
            updatePastStartWarning();
            document.getElementById('pastStartWarning')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            alert('Defense start must be in the future. Choose a later time or another day.');
            return false;
        }
    });
});
})();
</script>
@endsection
