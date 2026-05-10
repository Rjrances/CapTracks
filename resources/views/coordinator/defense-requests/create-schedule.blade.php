@extends('layouts.coordinator')
@section('title', 'Schedule from request')
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
    $groupId = $defenseRequest->group_id;
    $facultyList = $panelFacultyByGroupId[$groupId] ?? [];
    $chairSel = $invitedSlots[0]['selected_id'] ?? '';
    $memberSel = $invitedSlots[1]['selected_id'] ?? '';
@endphp
<div class="container-fluid">
        <x-coordinator.intro description="Approve this student request by picking date, room, start time, and invited panel. The defense window is two hours (same as Create Defense).">
            <a href="{{ route('coordinator.defense.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Defense management
            </a>
        </x-coordinator.intro>
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
                    <form action="{{ route('coordinator.defense-requests.store-schedule', $defenseRequest) }}" method="POST" id="scheduleFromRequestForm">
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
                                <label for="scheduled_time" class="form-label">Start time *</label>
                                <input type="time" name="scheduled_time" id="scheduled_time"
                                       class="form-control @error('scheduled_time') is-invalid @enderror"
                                       value="{{ old('scheduled_time', $defenseRequest->preferred_time?->format('H:i')) }}" required>
                                @error('scheduled_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">End time is two hours after start (same as Create Defense from scratch).</small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="room" class="form-label">Room *</label>
                                <input type="text" name="room" id="room"
                                       class="form-control @error('room') is-invalid @enderror"
                                       value="{{ old('room') }}" placeholder="e.g., Room 101, Computer Lab, Conference Room A" required>
                                @error('room')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="coordinator_notes" class="form-label">Notes (optional)</label>
                                <textarea name="coordinator_notes" id="coordinator_notes"
                                          class="form-control @error('coordinator_notes') is-invalid @enderror"
                                          rows="2" placeholder="Additional notes for this request…">{{ old('coordinator_notes') }}</textarea>
                                @error('coordinator_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="milestone_override_reason" class="form-label">Milestone override reason (required only if milestone is incomplete)</label>
                            <textarea name="milestone_override_reason" id="milestone_override_reason"
                                      class="form-control @error('milestone_override_reason') is-invalid @enderror"
                                      rows="2"
                                      placeholder="Explain why this defense must proceed even if required milestone is not completed.">{{ old('milestone_override_reason') }}</textarea>
                            @error('milestone_override_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Student requests are blocked on incomplete milestones. Coordinator may override with documented reason.</small>
                        </div>
                        <div id="doubleBookingWarning" class="alert alert-warning d-none" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span id="warningMessage"></span>
                        </div>
                        <div id="pastStartWarning" class="alert alert-danger d-none" role="alert">
                            <i class="fas fa-clock me-2"></i>
                            <span id="pastStartMessage"></span>
                        </div>
                        <hr class="my-4">
                        <h6 class="mb-3">
                            <i class="fas fa-users me-2"></i>Panel members
                        </h6>
                        <div class="alert alert-info mb-3">
                            <strong>Note:</strong> The group's adviser and offering coordinator are included automatically.
                            Choose <strong>Chair</strong> and <strong>Member</strong>, and add additional members if needed (same rules as Create Defense).
                        </div>
                        <div class="chair-row mb-3">
                            <div class="row align-items-end g-2">
                                <div class="col-md-3"><span class="badge bg-primary">Chair</span> <span class="text-danger">*</span></div>
                                <div class="col-md-9">
                                    <input type="hidden" name="panel_members[0][role]" value="chair">
                                    <select name="panel_members[0][faculty_id]" id="req_panel_chair" class="form-select invited-panel-select" required>
                                        <option value="">Select faculty</option>
                                        @foreach($facultyList as $f)
                                            <option value="{{ $f['id'] }}" {{ (string) $chairSel === (string) $f['id'] ? 'selected' : '' }}>{{ $f['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="member-row mb-3">
                            <div class="row align-items-end g-2">
                                <div class="col-md-3"><span class="badge bg-secondary">Member</span> <span class="text-danger">*</span></div>
                                <div class="col-md-9">
                                    <input type="hidden" name="panel_members[1][role]" value="member">
                                    <select name="panel_members[1][faculty_id]" id="req_panel_member" class="form-select invited-panel-select" required>
                                        <option value="">Select faculty</option>
                                        @foreach($facultyList as $f)
                                            <option value="{{ $f['id'] }}" {{ (string) $memberSel === (string) $f['id'] ? 'selected' : '' }}>{{ $f['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div id="optional-panelist-rows">
                            @foreach($invitedSlots as $idx => $slot)
                                @if($idx >= 2)
                                    <div class="panel-member-row mb-2 optional-panelist-row">
                                        <div class="row align-items-end g-2">
                                            <div class="col-md-3">
                                                <span class="badge bg-info text-dark">Member</span>
                                                <span class="text-muted small">(optional)</span>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="hidden" name="panel_members[{{ $idx }}][role]" value="panelist">
                                                <select name="panel_members[{{ $idx }}][faculty_id]" class="form-select invited-panel-select" required>
                                                    <option value="">Select faculty</option>
                                                    @foreach($facultyList as $f)
                                                        <option value="{{ $f['id'] }}" {{ (string) ($slot['selected_id'] ?? '') === (string) $f['id'] ? 'selected' : '' }}>{{ $f['name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-panelist-btn">&times;</button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        @if($optionalPanelistCapacity > 0)
                            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="req-add-panelist-btn">
                                <i class="fas fa-plus me-1"></i>Add panel member
                            </button>
                        @endif
                        @error('panel_members')
                            <span class="text-danger d-block mb-2">{{ $message }}</span>
                        @enderror
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('coordinator.defense.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-calendar-check me-1"></i>Schedule defense
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
(function () {
    const GROUP_ID = {{ (int) $defenseRequest->group_id }};
    const OPTIONAL_CAPACITY = {{ (int) $optionalPanelistCapacity }};
    const APP_TIMEZONE_OFFSET = @json(now()->timezone(config('app.timezone'))->format('P'));
    const facultyList = @json($facultyList);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    let latestRoomCheckId = 0;

    function defenseStartInstant(dateStr, timeStr) {
        if (!dateStr || !timeStr) return null;
        const normalized = timeStr.length === 5 ? timeStr + ':00' : timeStr;
        return new Date(dateStr + 'T' + normalized + APP_TIMEZONE_OFFSET);
    }

    function updatePastStartWarning() {
        const box = document.getElementById('pastStartWarning');
        const msgEl = document.getElementById('pastStartMessage');
        const dateEl = document.getElementById('scheduled_date');
        const startEl = document.getElementById('scheduled_time');
        if (!box || !msgEl || !dateEl || !startEl) return;
        const inst = defenseStartInstant(dateEl.value, startEl.value);
        if (!inst || isNaN(inst.getTime())) {
            box.classList.add('d-none');
            startEl.classList.remove('is-invalid');
            return;
        }
        if (inst.getTime() <= Date.now()) {
            msgEl.textContent = 'Start date and time must be later than right now.';
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
                if (!option.value) return;
                const takenElsewhere = values.some(v => v === option.value && v !== myVal);
                option.hidden = takenElsewhere;
                option.disabled = takenElsewhere;
            });
        });
    }

    function refillSelect(sel, selectedId) {
        const prev = selectedId != null ? String(selectedId) : sel.value;
        sel.innerHTML = '<option value="">Select faculty</option>';
        facultyList.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f.id;
            opt.textContent = f.name;
            sel.appendChild(opt);
        });
        if (prev && facultyList.some(x => String(x.id) === prev)) sel.value = prev;
    }

    function optionalPanelistDomCount() {
        return document.querySelectorAll('#optional-panelist-rows .optional-panelist-row').length;
    }

    function updateAddBtn() {
        const btn = document.getElementById('req-add-panelist-btn');
        if (!btn) return;
        btn.disabled = OPTIONAL_CAPACITY <= 0 || optionalPanelistDomCount() >= OPTIONAL_CAPACITY;
    }

    function renumberPanelMemberFields() {
        let idx = 0;
        document.querySelector('.chair-row input[type="hidden"]')?.setAttribute('name', 'panel_members[' + idx + '][role]');
        document.getElementById('req_panel_chair')?.setAttribute('name', 'panel_members[' + idx + '][faculty_id]');
        idx++;
        document.querySelector('.member-row input[type="hidden"]')?.setAttribute('name', 'panel_members[' + idx + '][role]');
        document.getElementById('req_panel_member')?.setAttribute('name', 'panel_members[' + idx + '][faculty_id]');
        idx++;
        document.querySelectorAll('#optional-panelist-rows .optional-panelist-row').forEach(row => {
            row.querySelector('input[type="hidden"]')?.setAttribute('name', 'panel_members[' + idx + '][role]');
            row.querySelector('.invited-panel-select')?.setAttribute('name', 'panel_members[' + idx + '][faculty_id]');
            idx++;
        });
    }

    function addOptionalRow() {
        if (optionalPanelistDomCount() >= OPTIONAL_CAPACITY) return;
        const wrap = document.getElementById('optional-panelist-rows');
        const row = document.createElement('div');
        row.className = 'panel-member-row mb-2 optional-panelist-row';
        row.innerHTML = `
            <div class="row align-items-end g-2">
                <div class="col-md-3"><span class="badge bg-info text-dark">Member</span> <span class="text-muted small">(optional)</span></div>
                <div class="col-md-7">
                    <input type="hidden" value="panelist" data-role-hidden="1">
                    <select class="form-select invited-panel-select" required><option value="">Select faculty</option></select>
                </div>
                <div class="col-md-2 text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-panelist-btn">&times;</button></div>
            </div>`;
        wrap.appendChild(row);
        refillSelect(row.querySelector('.invited-panel-select'), '');
        row.querySelector('.invited-panel-select').addEventListener('change', syncPanelDropdowns);
        row.querySelector('.remove-panelist-btn').addEventListener('click', () => {
            row.remove();
            renumberPanelMemberFields();
            syncPanelDropdowns();
            updateAddBtn();
        });
        renumberPanelMemberFields();
        syncPanelDropdowns();
        updateAddBtn();
    }

    function checkRoomConflict() {
        const date = document.getElementById('scheduled_date')?.value;
        const startTime = document.getElementById('scheduled_time')?.value;
        const room = document.getElementById('room')?.value;
        if (!date || !startTime || !room) {
            document.getElementById('doubleBookingWarning')?.classList.add('d-none');
            return;
        }
        const rid = ++latestRoomCheckId;
        fetch('{{ route("coordinator.defense.available-faculty") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({
                group_id: GROUP_ID,
                date: date,
                start_time: startTime.length >= 8 ? startTime.slice(0, 5) : startTime,
                duration_hours: 2,
                room: room
            })
        }).then(async res => {
            const data = await res.json().catch(() => ({}));
            if (rid !== latestRoomCheckId) return;
            const w = document.getElementById('doubleBookingWarning');
            if (res.ok && data.conflict && data.message) {
                document.getElementById('warningMessage').textContent = data.message;
                w?.classList.remove('d-none');
            } else {
                w?.classList.add('d-none');
            }
        }).catch(() => {});
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.invited-panel-select').forEach(el => el.addEventListener('change', syncPanelDropdowns));
        document.querySelectorAll('#optional-panelist-rows .remove-panelist-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.closest('.optional-panelist-row')?.remove();
                renumberPanelMemberFields();
                syncPanelDropdowns();
                updateAddBtn();
            });
        });
        document.getElementById('req-add-panelist-btn')?.addEventListener('click', addOptionalRow);

        ['scheduled_date', 'scheduled_time'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => {
                updatePastStartWarning();
                checkRoomConflict();
            });
            document.getElementById(id)?.addEventListener('input', updatePastStartWarning);
        });
        document.getElementById('room')?.addEventListener('input', checkRoomConflict);

        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const dateInput = document.getElementById('scheduled_date');
        if (dateInput) dateInput.min = tomorrow.toISOString().split('T')[0];

        renumberPanelMemberFields();
        syncPanelDropdowns();
        updateAddBtn();
        updatePastStartWarning();
        checkRoomConflict();

        document.getElementById('scheduleFromRequestForm')?.addEventListener('submit', function (e) {
            const inst = defenseStartInstant(document.getElementById('scheduled_date')?.value, document.getElementById('scheduled_time')?.value);
            if (inst && !isNaN(inst.getTime()) && inst.getTime() <= Date.now()) {
                e.preventDefault();
                updatePastStartWarning();
                alert('Defense start must be in the future.');
                return false;
            }
        });
    });
})();
</script>
@endpush
