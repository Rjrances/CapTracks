@extends('layouts.app')

@section('title', 'My Group')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">My Group</h2>
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
        <!-- Group Information -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>{{ $group->name }}
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <p class="text-muted mb-3">{{ $group->description ?: 'No description provided.' }}</p>
                        
                        <!-- Group Members -->
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
                                                @if($member->pivot->role !== 'leader')
                                                    <form action="{{ route('student.group.remove-member', $member->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Remove this member?')">
                                                            <i class="fas fa-user-minus"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Add Member Form -->
                        @if($group->members->count() < 3)
                            <div class="mt-4">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-user-plus me-1"></i>Add Member
                                </h6>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i>
                                    You can add {{ 3 - $group->members->count() }} more member(s) to reach the maximum of 3 members.
                                </div>
                                <form action="{{ route('student.group.add-member') }}" method="POST" class="row g-3">
                                    @csrf
                                    <div class="col-md-8">
                                        <select name="student_id" class="form-select" required>
                                            <option value="">Select a student...</option>
                                            @foreach(\App\Models\Student::whereNotIn('id', $group->members->pluck('id'))->get() as $student)
                                                <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->student_id }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-plus"></i> Add Member
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
                    </div>

                    <div class="col-md-4">
                        <!-- Adviser Information -->
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
                                    <div class="text-center">
                                        <i class="fas fa-user-tie fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">No adviser assigned</p>
                                        
                                        <!-- Invite Adviser Form -->
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#inviteAdviserModal">
                                            <i class="fas fa-envelope"></i> Invite Adviser
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Pending Invitations -->
                        @if($group->adviserInvitations->where('status', 'pending')->count() > 0)
                            <div class="card bg-warning mt-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-clock me-1"></i>Pending Invitations
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @foreach($group->adviserInvitations->where('status', 'pending') as $invitation)
                                        <div class="border-bottom pb-2 mb-2">
                                            <h6 class="mb-1">{{ $invitation->faculty->name }}</h6>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $invitation->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Group Actions -->
        <div class="d-flex gap-2">
            <a href="{{ route('student.group.edit') }}" class="btn btn-outline-primary">
                <i class="fas fa-edit"></i> Edit Group
            </a>
            <a href="{{ route('student.group.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Groups
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

<!-- Invite Adviser Modal -->
@if($group && !$group->adviser)
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
                                    {{ $faculty->name }} 
                                    <span class="text-muted">({{ ucfirst($faculty->role) }}{{ $faculty->course ? ' - ' . $faculty->course : '' }})</span>
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
@endsection 