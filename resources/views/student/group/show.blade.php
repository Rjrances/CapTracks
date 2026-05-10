@extends('layouts.student')
@section('title', 'Group Details')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('student.dashboard') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Return to Dashboard
        </a>
    </div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($group)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>{{ $group->name }}
                </h4>
            </div>
            <div class="card-body">
                @if($group->offering)
                    <div class="alert alert-success mb-3">
                        <i class="fas fa-book me-2"></i>
                        <strong>Capstone Subject:</strong> {{ $group->offering->offer_code }} - {{ $group->offering->subject_code }} - {{ $group->offering->subject_title }}
                        <br><i class="fas fa-chalkboard-teacher me-2"></i>
                        <strong>Coordinator:</strong> {{ $group->offering->coordinator_name }}
                    </div>
                @endif
                <div class="row">
                    <div class="col-md-8">
                        <p class="text-muted mb-3">{{ $group->description ?: 'No description provided.' }}</p>
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-users me-1"></i>Group Members
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group->members as $member)
                                        <tr>
                                            <td>{{ $member->name }}</td>
                                            <td>{{ $member->email }}</td>
                                            <td>
                                                <span class="badge bg-{{ $member->pivot->role === 'leader' ? 'primary' : 'secondary' }}">
                                                    {{ ucfirst($member->pivot->role) }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $currentStudent = Auth::guard('student')->check() ? Auth::guard('student')->user()->student : null;
                                                    $isCurrentStudentLeader = $currentStudent && $group->members()
                                                        ->where('group_members.student_id', $currentStudent->student_id)
                                                        ->where('group_members.role', 'leader')
                                                        ->exists();
                                                @endphp
                                                @if($member->pivot->role !== 'leader' && $isCurrentStudentLeader)
                                                    <form action="{{ route('student.group.remove-member', $member->student_id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Remove this member?')">
                                                            <i class="fas fa-user-minus"></i>
                                                        </button>
                                                    </form>
                                                @elseif($member->pivot->role !== 'leader')
                                                    <span class="text-muted small">Only leader can remove</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($group->members->count() < 3)
                            <div class="mt-4">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-user-plus me-1"></i>Invite Member
                                </h6>
                                @php
                                    $pendingMemberInvitationsCount = $group->groupInvitations()->where('status', 'pending')->count();
                                    $remainingSlots = max(0, 3 - $group->members->count() - $pendingMemberInvitationsCount);
                                @endphp
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i>
                                    You can invite {{ $remainingSlots }} more member(s) to reach the maximum of 3 members.
                                    <br><strong>Note:</strong> Only students enrolled in the same offering can be invited to your group.
                                    @if($pendingMemberInvitationsCount > 0)
                                        <br><strong>Pending invitations:</strong> {{ $pendingMemberInvitationsCount }}
                                    @endif
                                </div>
                                <form action="{{ route('student.group.invite-member') }}" method="POST" class="row g-3">
                                    @csrf
                                    <div class="col-md-6">
                                        <input type="text" id="student_search" class="form-control" placeholder="Search for student name..." onkeyup="filterStudents()">
                                        <select name="student_ids[]" id="student_select_1" class="form-select mt-2" {{ $remainingSlots <= 0 ? 'disabled' : '' }} required>
                                            <option value="">Select student...</option>
                                            
                                            @foreach($availableStudents as $student)
                                                <option value="{{ $student->student_id }}" data-name="{{ strtolower($student->name) }}">
                                                    {{ $student->name }} ({{ $student->student_id }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <select name="student_ids[]" id="student_select_2" class="form-select mt-2" {{ $remainingSlots < 2 ? 'disabled' : '' }}>
                                            <option value="">Select student (optional)...</option>
                                            @foreach($availableStudents as $student)
                                                <option value="{{ $student->student_id }}" data-name="{{ strtolower($student->name) }}">
                                                    {{ $student->name }} ({{ $student->student_id }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @if($availableStudents->isEmpty())
                                            <div class="form-text text-info">
                                                <i class="fas fa-info-circle me-1"></i>
                                                No other students available with the same offer code{{ $group->offering ? ' (' . $group->offering->offer_code . ')' : '' }}.
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <textarea name="message" class="form-control" rows="3" placeholder="Optional message for the invitation..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-success" {{ $remainingSlots <= 0 ? 'disabled' : '' }}>
                                            <i class="fas fa-paper-plane"></i> Send Invitation
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="mt-4">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    This group has reached the maximum of 3 members.
                                </div>
                            </div>
                        @endif
                        
                        @if($group->groupInvitations()->where('status', 'pending')->count() > 0)
                            <div class="mt-4">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-clock me-1"></i>Pending Invitations
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Message</th>
                                                <th>Sent</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($group->groupInvitations()->where('status', 'pending')->with('student')->get() as $invitation)
                                                <tr>
                                                    <td>{{ $invitation->student->name }} ({{ $invitation->student->student_id }})</td>
                                                    <td>{{ $invitation->message ?: 'No message' }}</td>
                                                    <td>{{ $invitation->created_at->diffForHumans() }}</td>
                                                    <td>
                                                        <form action="{{ route('student.group.cancel-invitation', $invitation->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this invitation?')">
                                                                <i class="fas fa-times"></i> Cancel
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chalkboard-teacher me-1"></i>Adviser
                                </h6>
                            </div>
                            <div class="card-body">
                                @if($group->adviser)
                                    <div class="text-center">
                                        <i class="fas fa-user-tie fa-2x text-primary mb-2"></i>
                                        <h6>{{ $group->adviser->name }}</h6>
                                        <p class="text-muted small">{{ $group->adviser->email }}</p>
                                        <span class="badge bg-success">Assigned</span>
                                    </div>
                                @else
                                    @php
                                        $pendingAdviserInvitationsCount = $group->adviserInvitations->where('status', 'pending')->count();
                                    @endphp
                                    <div class="text-center">
                                        <i class="fas fa-user-tie fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">No adviser assigned</p>
                                        @if($pendingAdviserInvitationsCount === 0)
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#inviteAdviserModal">
                                                <i class="fas fa-envelope"></i> Invite Adviser
                                            </button>
                                        @else
                                            <p class="text-muted small mb-0">
                                                You have a pending adviser invitation. Cancel it below if you want to invite a different faculty member.
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        @if($group->adviserInvitations->where('status', 'pending')->count() > 0)
                            <div class="card border-0 bg-warning mt-3 shadow-sm">
                                <div class="card-header bg-warning border-bottom py-3" style="border-color: rgba(0, 0, 0, 0.08) !important;">
                                    <h6 class="card-title mb-0 fw-bold">
                                        <i class="fas fa-clock me-1"></i>Pending Invitations
                                    </h6>
                                </div>
                                <div class="card-body bg-warning px-3 py-0">
                                    @foreach($group->adviserInvitations->where('status', 'pending') as $invitation)
                                        <div class="d-flex align-items-start justify-content-between gap-2 py-3 {{ !$loop->last ? 'border-bottom' : '' }}" style="{{ !$loop->last ? 'border-color: rgba(0,0,0,0.08) !important;' : '' }}">
                                            <div class="min-w-0">
                                                <h6 class="mb-1 fw-bold">{{ $invitation->faculty->name }}</h6>
                                                <small class="text-dark">
                                                    <i class="fas fa-clock me-1"></i>{{ $invitation->created_at->diffForHumans() }}
                                                </small>
                                            </div>
                                            <div class="flex-shrink-0 pt-0">
                                                <form action="{{ route('student.group.cancel-adviser-invitation', $invitation) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this adviser invitation?')">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @if($group->adviser_id)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-graduation-cap me-2"></i>Defense Readiness
                </h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center mb-3">
                    <div class="col-md-8">
                        <h6 class="mb-1">Proposal Defense</h6>
                        <small class="text-muted">
                            @if($group->defenseRequests->where('defense_type', 'proposal')->where('status', 'scheduled')->first())
                                 Scheduled - Ready to proceed
                            @elseif($group->defenseRequests->where('defense_type', 'proposal')->where('status', 'pending')->first())
                                 Request pending - Waiting for coordinator response
                            @else
                                 Ready to request - All requirements met
                            @endif
                        </small>
                    </div>
                    <div class="col-md-4 text-end">
                        @if($group->defenseRequests->where('defense_type', 'proposal')->where('status', 'scheduled')->first())
                            <button class="btn btn-success btn-sm" disabled>
                                <i class="fas fa-check"></i> Scheduled
                            </button>
                        @elseif($group->defenseRequests->where('defense_type', 'proposal')->where('status', 'pending')->first())
                            <button class="btn btn-warning btn-sm" disabled>
                                <i class="fas fa-clock"></i> Pending
                            </button>
                        @else
                            <button class="btn btn-primary btn-sm" onclick="requestDefense('proposal')">
                                <i class="fas fa-rocket"></i> Request Defense
                            </button>
                        @endif
                    </div>
                </div>
                <div class="row align-items-center mb-3">
                    <div class="col-md-8">
                        <h6 class="mb-1">60% Progress Defense</h6>
                        <small class="text-muted">
                            @if($group->defenseRequests->where('defense_type', '60_percent')->where('status', 'scheduled')->first())
                                 Scheduled - Ready to proceed
                            @elseif($group->defenseRequests->where('defense_type', '60_percent')->where('status', 'pending')->first())
                                 Request pending - Waiting for coordinator response
                            @elseif($group->overall_progress_percentage < 60)
                                 Not ready - Progress: {{ $group->overall_progress_percentage }}%
                            @else
                                 Ready to request - Progress: {{ $group->overall_progress_percentage }}%
                            @endif
                        </small>
                    </div>
                    <div class="col-md-4 text-end">
                        @if($group->defenseRequests->where('defense_type', '60_percent')->where('status', 'scheduled')->first())
                            <button class="btn btn-success btn-sm" disabled>
                                <i class="fas fa-check"></i> Scheduled
                            </button>
                        @elseif($group->defenseRequests->where('defense_type', '60_percent')->where('status', 'pending')->first())
                            <button class="btn btn-warning btn-sm" disabled>
                                <i class="fas fa-clock"></i> Pending
                            </button>
                        @elseif($group->overall_progress_percentage < 60)
                            <button class="btn btn-secondary btn-sm" disabled>
                                <i class="fas fa-clock"></i> Not Ready
                            </button>
                        @else
                            <button class="btn btn-primary btn-sm" onclick="requestDefense('60_percent')">
                                <i class="fas fa-rocket"></i> Request Defense
                            </button>
                        @endif
                    </div>
                </div>
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="mb-1">100% Final Defense</h6>
                        <small class="text-muted">
                            @if($group->defenseRequests->where('defense_type', '100_percent')->where('status', 'scheduled')->first())
                                 Scheduled - Ready to proceed
                            @elseif($group->defenseRequests->where('defense_type', '100_percent')->where('status', 'pending')->first())
                                 Request pending - Waiting for coordinator response
                            @elseif($group->overall_progress_percentage < 100)
                                 Not ready - Progress: {{ $group->overall_progress_percentage }}%
                            @else
                                 Ready to request - Progress: {{ $group->overall_progress_percentage }}%
                            @endif
                        </small>
                    </div>
                    <div class="col-md-4 text-end">
                        @if($group->defenseRequests->where('defense_type', '100_percent')->where('status', 'scheduled')->first())
                            <button class="btn btn-success btn-sm" disabled>
                                <i class="fas fa-check"></i> Scheduled
                            </button>
                        @elseif($group->defenseRequests->where('defense_type', '100_percent')->where('status', 'pending')->first())
                            <button class="btn btn-warning btn-sm" disabled>
                                <i class="fas fa-clock"></i> Pending
                            </button>
                        @elseif($group->overall_progress_percentage < 100)
                            <button class="btn btn-secondary btn-sm" disabled>
                                <i class="fas fa-clock"></i> Not Ready
                            </button>
                        @else
                            <button class="btn btn-primary btn-sm" onclick="requestDefense('100_percent')">
                                <i class="fas fa-rocket"></i> Request Defense
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @if($group->defenseRequests->count() > 0)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Defense Request Status
                </h5>
            </div>
            <div class="card-body">
                @foreach($group->defenseRequests as $request)
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">{{ $request->defense_type_label }}</h6>
                            <small class="text-muted">
                                Requested: {{ $request->requested_at->format('M d, Y') }}
                                @if($request->student_message)
                                    - Message: "{{ Str::limit($request->student_message, 50) }}"
                                @endif
                            </small>
                        </div>
                        <div>
                            @if($request->status === 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @elseif($request->status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($request->status === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                                @if($request->coordinator_notes)
                                    <small class="text-muted d-block">Reason: {{ Str::limit($request->coordinator_notes, 50) }}</small>
                                @endif
                            @elseif($request->status === 'scheduled')
                                <span class="badge bg-primary">Scheduled</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @endif
        <div class="d-flex gap-2">
            <a href="{{ route('student.group.edit') }}" class="btn btn-outline-primary">
                <i class="fas fa-edit"></i> Edit Group
            </a>
        </div>
    @else
        <div class="text-center">
            <i class="fas fa-users fa-3x text-muted mb-3"></i>
            <h4>No Group Found</h4>
            <p class="text-muted">You are not a member of any group yet.</p>
            <a href="{{ route('student.group.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Group
            </a>
        </div>
    @endif
</div>
@if($group && !$group->adviser && $group->adviserInvitations->where('status', 'pending')->count() === 0)
<div class="modal fade" id="inviteAdviserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-envelope me-2"></i>Invite Adviser
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('student.group.invite-adviser') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="faculty_id" class="form-label">Select Faculty Member</label>
                        <select name="faculty_id" id="faculty_id" class="form-select" required>
                            <option value="">Choose a faculty member...</option>
                            @foreach($availableFaculty as $faculty)
                                <option value="{{ $faculty->id }}">
                                    {{ $faculty->name }}{{ $faculty->department ? ' (' . $faculty->department . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message (Optional)</label>
                        <textarea name="message" id="message" class="form-control" rows="3" placeholder="Add a personal message to your invitation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Invitation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
<div class="modal fade" id="defenseRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-graduation-cap me-2"></i>Request Defense
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Defense Type</label>
                    <div class="form-control-plaintext" id="defense_type_display"></div>
                </div>
                <div class="mb-3">
                    <p class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        You'll be redirected to our new Defense Request system to complete your request.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="{{ route('student.defense-requests.create') }}" class="btn btn-primary" id="defense_request_redirect">
                    <i class="fas fa-external-link-alt me-2"></i>Go to Defense Requests
                </a>
            </div>
        </div>
    </div>
</div>
<script>
function requestDefense(defenseType) {
    const defenseTypeLabels = {
        'proposal': 'Proposal Defense',
        '60_percent': '60% Progress Defense',
        '100_percent': '100% Final Defense'
    };
    document.getElementById('defense_type_display').textContent = defenseTypeLabels[defenseType];
    
    const redirectLink = document.getElementById('defense_request_redirect');
    redirectLink.href = "{{ route('student.defense-requests.create') }}?defense_type=" + defenseType;
    
    const modal = new bootstrap.Modal(document.getElementById('defenseRequestModal'));
    modal.show();
}

const originalInviteOptions = [];

function initializeInviteOptions() {
    const first = document.getElementById('student_select_1');
    if (!first) return;
    originalInviteOptions.length = 0;

    const options = first.querySelectorAll('option');
    options.forEach((option) => {
        if (!option.value) return;
        originalInviteOptions.push({
            value: option.value,
            text: option.textContent,
            name: (option.getAttribute('data-name') || '').toLowerCase(),
        });
    });
}

function renderInviteSelectOptions() {
    const first = document.getElementById('student_select_1');
    const second = document.getElementById('student_select_2');
    const searchInput = document.getElementById('student_search');
    if (!first || !second) return;

    const searchTerm = (searchInput?.value || '').toLowerCase();
    const selectedFirst = first.value;
    const selectedSecond = second.value;

    first.innerHTML = '<option value="">Select student...</option>';
    second.innerHTML = '<option value="">Select student (optional)...</option>';

    originalInviteOptions.forEach((student) => {
        const matchesSearch = !searchTerm || student.name.includes(searchTerm);
        const hiddenInFirst = selectedSecond && student.value === selectedSecond && student.value !== selectedFirst;
        const hiddenInSecond = selectedFirst && student.value === selectedFirst && student.value !== selectedSecond;

        if (!hiddenInFirst && (matchesSearch || student.value === selectedFirst)) {
            const firstOption = document.createElement('option');
            firstOption.value = student.value;
            firstOption.textContent = student.text;
            firstOption.setAttribute('data-name', student.name);
            if (student.value === selectedFirst) {
                firstOption.selected = true;
            }
            first.appendChild(firstOption);
        }

        if (!hiddenInSecond && (matchesSearch || student.value === selectedSecond)) {
            const secondOption = document.createElement('option');
            secondOption.value = student.value;
            secondOption.textContent = student.text;
            secondOption.setAttribute('data-name', student.name);
            if (student.value === selectedSecond) {
                secondOption.selected = true;
            }
            second.appendChild(secondOption);
        }
    });

    if (selectedSecond && selectedSecond === selectedFirst) {
        second.value = '';
    }
}

function filterStudents() {
    renderInviteSelectOptions();
}

document.addEventListener('DOMContentLoaded', function () {
    const first = document.getElementById('student_select_1');
    const second = document.getElementById('student_select_2');
    const searchInput = document.getElementById('student_search');

    initializeInviteOptions();
    renderInviteSelectOptions();

    if (first) {
        first.addEventListener('change', renderInviteSelectOptions);
    }
    if (second) {
        second.addEventListener('change', renderInviteSelectOptions);
    }
    if (searchInput) {
        searchInput.addEventListener('input', renderInviteSelectOptions);
    }
});
</script>
@endsection 
