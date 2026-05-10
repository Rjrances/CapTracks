@extends('layouts.coordinator')
@section('title', 'Create Defense Schedule')
@section('content')
@php
    $oldPm = old('panel_members');
    if (is_array($oldPm) && count($oldPm) >= 2) {
        $invitedSlots = [];
        foreach ($oldPm as $i => $row) {
            $invitedSlots[] = [
                'role' => $row['role'] ?? ($i === 0 ? 'chair' : ($i === 1 ? 'member' : 'panelist')),
                'selected_id' => (string) ($row['faculty_id'] ?? ''),
            ];
        }
        $invitedSlots[0]['role'] = 'chair';
        $invitedSlots[1]['role'] = 'member';
    } else {
        $invitedSlots = [
            ['role' => 'chair', 'selected_id' => ''],
            ['role' => 'member', 'selected_id' => ''],
        ];
    }
@endphp
<div class="container-fluid">
        <x-coordinator.intro description="Set date, room, and invited panel for a defense in one of your coordinated offerings.">
            <a href="{{ route('coordinator.defense.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Defense management
            </a>
        </x-coordinator.intro>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> You can only create defense schedules for groups that belong to your coordinated offerings (capstone offer codes).
                The academic term is automatically set to the current active term.
                <br><small class="text-muted">Select <strong>Chair</strong> and <strong>Member</strong>. The adviser and offering coordinator are added automatically. You may add up to {{ $optionalPanelistCapacity }} optional <strong>Panelist</strong> ({{ $optionalPanelistCapacity === 1 ? 'slot' : 'slots' }}) when needed.</small>
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
                            <div class="alert alert-info mb-3">
                                <strong>Note:</strong> The group's adviser and offering coordinator are included automatically.
                                Choose <strong>Chair</strong> and <strong>Member</strong> from eligible faculty for the selected group.
                                Optional panelists can be added when you need more than two invited faculty beyond adviser/coordinator.
                            </div>
                            @php
                                $chairSel = $invitedSlots[0]['selected_id'] ?? '';
                                $memberSel = $invitedSlots[1]['selected_id'] ?? '';
                            @endphp
                            <div class="chair-row mb-3" data-slot-type="chair">
                                <div class="row align-items-end g-2">
                                    <div class="col-md-3">
                                        <span class="badge bg-primary">Chair</span>
                                        <span class="text-danger">*</span>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="hidden" name="panel_members[0][role]" value="chair">
                                        <select name="panel_members[0][faculty_id]" id="panel_chair_select" class="form-select invited-panel-select @error('panel_members.0.faculty_id') is-invalid @enderror" required>
                                            <option value="">Select faculty</option>
                                        </select>
                                        @error('panel_members.0.faculty_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="member-row mb-3" data-slot-type="member">
                                <div class="row align-items-end g-2">
                                    <div class="col-md-3">
                                        <span class="badge bg-secondary">Member</span>
                                        <span class="text-danger">*</span>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="hidden" name="panel_members[1][role]" value="member">
                                        <select name="panel_members[1][faculty_id]" id="panel_member_select" class="form-select invited-panel-select @error('panel_members.1.faculty_id') is-invalid @enderror" required>
                                            <option value="">Select faculty</option>
                                        </select>
                                        @error('panel_members.1.faculty_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div id="optional-panelist-rows">
                                @foreach($invitedSlots as $idx => $slot)
                                    @if($idx >= 2)
                                        <div class="panel-member-row mb-2 optional-panelist-row">
                                            <div class="row align-items-end g-2">
                                                <div class="col-md-3">
                                                    <span class="badge bg-info text-dark">Panelist</span>
                                                    <span class="text-muted small">(optional)</span>
                                                </div>
                                                <div class="col-md-7">
                                                    <input type="hidden" name="panel_members[{{ $idx }}][role]" value="panelist">
                                                    <select name="panel_members[{{ $idx }}][faculty_id]" class="form-select invited-panel-select" required>
                                                        <option value="">Select faculty</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2 text-end">
                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-panelist-btn" title="Remove">&times;</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            @if($optionalPanelistCapacity > 0)
                                <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-panelist-btn">
                                    <i class="fas fa-plus me-1"></i>Add optional panelist
                                </button>
                            @endif
                            @error('panel_members')
                                <span class="text-danger d-block">{{ $message }}</span>
                            @enderror
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
    const OPTIONAL_CAPACITY = {{ (int) $optionalPanelistCapacity }};
    const PREFILL_GROUP_ID = @json($prefillGroupId ? (string) $prefillGroupId : '');
    const INITIAL_CHAIR = @json((string) ($chairSel ?? ''));
    const INITIAL_MEMBER = @json((string) ($memberSel ?? ''));
    const INITIAL_OPTIONAL_IDS = @json(collect($invitedSlots)->slice(2)->pluck('selected_id')->values()->all());
    const APP_TIMEZONE_OFFSET = @json(now()->timezone(config('app.timezone'))->format('P'));
    const panelFacultyByGroupId = @json($panelFacultyByGroupId);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    let latestDoubleBookingRequestId = 0;

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
        const parts = String(value).split(':');
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

    function syncPanelDropdowns() {
        const selects = Array.from(document.querySelectorAll('.invited-panel-select'));
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

    function fillSelectOptions(selectEl, groupId, selectedId) {
        const list = panelFacultyByGroupId[groupId] || [];
        const prev = selectedId != null ? String(selectedId) : selectEl.value;
        selectEl.innerHTML = '<option value="">Select faculty</option>';
        list.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f.id;
            opt.textContent = f.name;
            selectEl.appendChild(opt);
        });
        if (prev && list.some(item => String(item.id) === prev)) {
            selectEl.value = prev;
        }
    }

    function refillAllPanelSelects(preserveSelections) {
        const gid = document.getElementById('group_id')?.value || '';
        const prevChair = preserveSelections ? (document.getElementById('panel_chair_select')?.value || '') : '';
        const prevMember = preserveSelections ? (document.getElementById('panel_member_select')?.value || '') : '';
        fillSelectOptions(document.getElementById('panel_chair_select'), gid, preserveSelections ? prevChair : '');
        fillSelectOptions(document.getElementById('panel_member_select'), gid, preserveSelections ? prevMember : '');
        document.querySelectorAll('#optional-panelist-rows .invited-panel-select').forEach(sel => {
            const keep = preserveSelections ? sel.value : '';
            fillSelectOptions(sel, gid, keep);
        });
        syncPanelDropdowns();
    }

    function optionalPanelistDomCount() {
        return document.querySelectorAll('#optional-panelist-rows .optional-panelist-row').length;
    }

    function updateAddPanelistButton() {
        const btn = document.getElementById('add-panelist-btn');
        if (!btn) {
            return;
        }
        btn.disabled = OPTIONAL_CAPACITY <= 0 || optionalPanelistDomCount() >= OPTIONAL_CAPACITY;
    }

    function renumberPanelMemberFields() {
        let idx = 0;
        const chairH = document.querySelector('.chair-row input[type="hidden"]');
        const chairS = document.getElementById('panel_chair_select');
        if (chairH && chairS) {
            chairH.setAttribute('name', 'panel_members[' + idx + '][role]');
            chairS.setAttribute('name', 'panel_members[' + idx + '][faculty_id]');
            idx++;
        }
        const memberH = document.querySelector('.member-row input[type="hidden"]');
        const memberS = document.getElementById('panel_member_select');
        if (memberH && memberS) {
            memberH.setAttribute('name', 'panel_members[' + idx + '][role]');
            memberS.setAttribute('name', 'panel_members[' + idx + '][faculty_id]');
            idx++;
        }
        document.querySelectorAll('#optional-panelist-rows .optional-panelist-row').forEach(row => {
            const hid = row.querySelector('input[type="hidden"][data-role-hidden]') || row.querySelector('input[type="hidden"]');
            const sel = row.querySelector('.invited-panel-select');
            if (hid && sel) {
                hid.setAttribute('name', 'panel_members[' + idx + '][role]');
                sel.setAttribute('name', 'panel_members[' + idx + '][faculty_id]');
                idx++;
            }
        });
    }

    function addOptionalPanelistRow(prefillId) {
        if (optionalPanelistDomCount() >= OPTIONAL_CAPACITY) {
            return;
        }
        const gid = document.getElementById('group_id')?.value || '';
        const wrap = document.getElementById('optional-panelist-rows');
        const row = document.createElement('div');
        row.className = 'panel-member-row mb-2 optional-panelist-row';
        row.innerHTML = `
            <div class="row align-items-end g-2">
                <div class="col-md-3">
                    <span class="badge bg-info text-dark">Panelist</span>
                    <span class="text-muted small">(optional)</span>
                </div>
                <div class="col-md-7">
                    <input type="hidden" value="panelist" data-role-hidden="1">
                    <select class="form-select invited-panel-select" required>
                        <option value="">Select faculty</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-panelist-btn" title="Remove">&times;</button>
                </div>
            </div>`;
        wrap.appendChild(row);
        const hid = row.querySelector('input[type="hidden"]');
        const sel = row.querySelector('select');
        fillSelectOptions(sel, gid, prefillId || '');
        hid.addEventListener('change', () => {});
        sel.addEventListener('change', syncPanelDropdowns);
        row.querySelector('.remove-panelist-btn').addEventListener('click', function () {
            row.remove();
            renumberPanelMemberFields();
            syncPanelDropdowns();
            updateAddPanelistButton();
        });
        renumberPanelMemberFields();
        syncPanelDropdowns();
        updateAddPanelistButton();
    }

    function checkDoubleBookingRoom() {
        const groupId = document.getElementById('group_id')?.value;
        const date = document.getElementById('date')?.value;
        const startTime = document.getElementById('start_time')?.value;
        const endTime = document.getElementById('end_time')?.value;
        const room = document.getElementById('room')?.value;

        if (!groupId || !date || !startTime || !endTime || !room) {
            document.getElementById('doubleBookingWarning')?.classList.add('d-none');
            return;
        }
        if (!isEndTimeAfterStartOnSameDay()) {
            document.getElementById('doubleBookingWarning')?.classList.add('d-none');
            return;
        }

        const requestId = ++latestDoubleBookingRequestId;
        fetch('{{ route("coordinator.defense.available-faculty") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                group_id: groupId,
                date: date,
                start_time: startTime.length >= 8 ? startTime.slice(0, 5) : startTime,
                end_time: endTime.length >= 8 ? endTime.slice(0, 5) : endTime,
                room: room
            })
        })
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                if (requestId !== latestDoubleBookingRequestId) {
                    return;
                }
                const warn = document.getElementById('doubleBookingWarning');
                if (!response.ok) {
                    warn?.classList.add('d-none');
                    return;
                }
                if (data.conflict && data.message) {
                    document.getElementById('warningMessage').textContent = data.message;
                    warn?.classList.remove('d-none');
                } else {
                    warn?.classList.add('d-none');
                }
            })
            .catch(() => {
                if (requestId === latestDoubleBookingRequestId) {
                    document.getElementById('doubleBookingWarning')?.classList.add('d-none');
                }
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('group_id')?.addEventListener('change', function () {
            refillAllPanelSelects(false);
            document.getElementById('optional-panelist-rows').innerHTML = '';
            renumberPanelMemberFields();
            updateAddPanelistButton();
            checkDoubleBookingRoom();
        });
        ['date', 'start_time', 'end_time'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => {
                updatePastStartWarning();
                updateTimeOrderWarning();
                checkDoubleBookingRoom();
            });
            document.getElementById(id)?.addEventListener('input', () => {
                updatePastStartWarning();
                updateTimeOrderWarning();
            });
        });
        document.getElementById('room')?.addEventListener('input', checkDoubleBookingRoom);

        document.getElementById('panel_chair_select')?.addEventListener('change', syncPanelDropdowns);
        document.getElementById('panel_member_select')?.addEventListener('change', syncPanelDropdowns);

        document.getElementById('add-panelist-btn')?.addEventListener('click', () => addOptionalPanelistRow(''));

        document.querySelectorAll('#optional-panelist-rows .remove-panelist-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                btn.closest('.optional-panelist-row')?.remove();
                renumberPanelMemberFields();
                syncPanelDropdowns();
                updateAddPanelistButton();
            });
        });
        document.querySelectorAll('#optional-panelist-rows .invited-panel-select').forEach(sel => {
            sel.addEventListener('change', syncPanelDropdowns);
        });

        const startGroup = document.getElementById('group_id')?.value || PREFILL_GROUP_ID;
        if (startGroup) {
            if (!document.getElementById('group_id').value && PREFILL_GROUP_ID) {
                document.getElementById('group_id').value = PREFILL_GROUP_ID;
            }
            fillSelectOptions(document.getElementById('panel_chair_select'), startGroup, INITIAL_CHAIR);
            fillSelectOptions(document.getElementById('panel_member_select'), startGroup, INITIAL_MEMBER);
            document.querySelectorAll('#optional-panelist-rows .optional-panelist-row').forEach((row, i) => {
                const sel = row.querySelector('.invited-panel-select');
                const pid = INITIAL_OPTIONAL_IDS[i] || '';
                if (sel) {
                    fillSelectOptions(sel, startGroup, pid);
                }
            });
        }
        renumberPanelMemberFields();
        syncPanelDropdowns();
        updateAddPanelistButton();

        updateTimeOrderWarning();
        updatePastStartWarning();
        checkDoubleBookingRoom();

        document.getElementById('defenseForm')?.addEventListener('submit', function (e) {
            const dateVal = document.getElementById('date')?.value;
            const startTime = document.getElementById('start_time')?.value;
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
