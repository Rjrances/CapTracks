@extends('layouts.student')
@section('title', 'Edit Group')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-edit me-2"></i>Edit Group
        </h2>
        <a href="{{ route('student.group') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Group
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
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Basic Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('student.group.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label for="name" class="form-label fw-bold">Group Name</label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name', $group->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label fw-bold">Description</label>
                                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" 
                                          rows="4" placeholder="Describe your group's project or purpose...">{{ old('description', $group->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Information
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>Members ({{ $group->members->count() }}/3)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="fw-bold mb-2">Current Members:</h6>
                            @foreach($group->members as $member)
                                <div class="d-flex justify-content-between align-items-center p-2 border rounded mb-2">
                                    <div>
                                        <strong>{{ $member->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $member->email }}</small>
                                        <span class="badge bg-{{ $member->pivot->role === 'leader' ? 'primary' : 'secondary' }} ms-2">
                                            {{ ucfirst($member->pivot->role) }}
                                        </span>
                                    </div>
                                    @if($member->pivot->role !== 'leader')
                                        <form action="{{ route('student.group.remove-member', $member->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to remove {{ $member->name }} from the group?')">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if($group->members->count() < 3)
                            <div class="border-top pt-3">
                                <h6 class="fw-bold mb-2">Add New Member:</h6>
                                <form action="{{ route('student.group.add-member') }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <select name="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
                                            <option value="">Select a student...</option>
                                            @foreach(\App\Models\Student::whereNotIn('id', $group->members->pluck('id'))->get() as $student)
                                                <option value="{{ $student->student_id }}" {{ old('student_id') == $student->student_id ? 'selected' : '' }}>
                                                    {{ $student->name }} ({{ $student->student_id }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('student_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-user-plus me-1"></i>Add Member
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                This group has reached the maximum of 3 members.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Adviser Management
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                @if($group->adviser)
                                    <div class="text-center p-3 border rounded">
                                        <i class="fas fa-user-tie fa-2x text-success mb-2"></i>
                                        <h6 class="fw-bold">{{ $group->adviser->name }}</h6>
                                        <p class="text-muted">{{ $group->adviser->email }}</p>
                                        <span class="badge bg-success">Assigned Adviser</span>
                                    </div>
                                @else
                                    <div class="text-center p-3 border rounded">
                                        <i class="fas fa-user-tie fa-2x text-muted mb-2"></i>
                                        <p class="text-muted mb-2">No adviser assigned</p>
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#inviteAdviserModal">
                                            <i class="fas fa-envelope me-1"></i>Invite Adviser
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                @if($group->adviserInvitations->where('status', 'pending')->count() > 0)
                                    <h6 class="fw-bold mb-2">Pending Invitations:</h6>
                                    @foreach($group->adviserInvitations->where('status', 'pending') as $invitation)
                                        <div class="border rounded p-2 mb-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>{{ $invitation->faculty->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ $invitation->created_at->diffForHumans() }}
                                                    </small>
                                                </div>
                                                <span class="badge bg-warning">Pending</span>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center p-3">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">No pending invitations</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Group Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                    <h4 class="fw-bold">{{ $group->members->count() }}</h4>
                                    <p class="text-muted mb-0">Members</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <i class="fas fa-user-tie fa-2x text-success mb-2"></i>
                                    <h4 class="fw-bold">{{ $group->adviser ? '1' : '0' }}</h4>
                                    <p class="text-muted mb-0">Adviser</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <i class="fas fa-envelope fa-2x text-warning mb-2"></i>
                                    <h4 class="fw-bold">{{ $group->adviserInvitations->where('status', 'pending')->count() }}</h4>
                                    <p class="text-muted mb-0">Pending Invitations</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                                    <h4 class="fw-bold">{{ $group->created_at->format('M Y') }}</h4>
                                    <p class="text-muted mb-0">Created</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center">
            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
            <h4>No Group Found</h4>
            <p class="text-muted">You are not a member of any group yet.</p>
            <a href="{{ route('student.group.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Create Group
            </a>
        </div>
    @endif
</div>
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
                        <label for="faculty_id" class="form-label fw-bold">Select Faculty Member</label>
                        <select name="faculty_id" id="faculty_id" class="form-select @error('faculty_id') is-invalid @enderror" required>
                            <option value="">Choose a faculty member...</option>
                            @foreach(\App\Models\User::whereIn('role', ['adviser', 'panelist', 'teacher'])->get() as $faculty)
                                <option value="{{ $faculty->id }}" {{ old('faculty_id') == $faculty->id ? 'selected' : '' }}>
                                    {{ $faculty->name }} 
                                    <span class="text-muted">({{ ucfirst($faculty->roles->first()->name ?? 'N/A') }})</span>
                                </option>
                            @endforeach
                        </select>
                        @error('faculty_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label fw-bold">Message (Optional)</label>
                        <textarea name="message" id="message" class="form-control @error('message') is-invalid @enderror" 
                                  rows="3" placeholder="Add a personal message to your invitation...">{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Send Invitation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection 
