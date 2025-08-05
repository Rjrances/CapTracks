@extends('layouts.adviser')

@section('title', 'Adviser Dashboard')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 900px;">
        <div class="mb-4" style="margin-bottom: 1.2rem !important;">
            <h1 class="fw-bold mb-1 text-center" style="font-size:2.5rem; margin-bottom:0.1rem;">Adviser Dashboard</h1>
            <div class="text-muted text-center" style="font-size:1.1rem; margin-bottom:0;">Manage your groups, invitations, and student projects</div>
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

        <!-- Quick Actions -->
        <div class="mb-4">
            <div class="fw-semibold mb-2" style="font-size:1.2rem;">Quick Actions</div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('adviser.invitations') }}" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm border">
                    <i class="fas fa-envelope me-2"></i>View Invitations
                </a>
                <a href="{{ route('adviser.groups') }}" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm border">
                    <i class="fas fa-users me-2"></i>My Groups
                </a>
                <a href="{{ route('adviser.project.index') }}" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm border">
                    <i class="fas fa-file-alt me-2"></i>Project Reviews
                </a>
            </div>
        </div>

        <!-- Pending Invitations -->
        <div class="mb-4">
            <div class="fw-bold mb-2" style="font-size:1.2rem;">
                <i class="fas fa-envelope me-2"></i>Pending Invitations
                @if($pendingInvitations->count() > 0)
                    <span class="badge bg-danger ms-2">{{ $pendingInvitations->count() }}</span>
                @endif
            </div>
            <div class="bg-light rounded-3 p-3">
                @if($pendingInvitations->count() > 0)
                    @foreach($pendingInvitations as $invitation)
                        <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                            <div class="me-3 flex-shrink-0">
                                <span class="d-inline-flex align-items-center justify-content-center bg-warning border rounded-circle" style="width:36px; height:36px;">
                                    <i class="fas fa-envelope text-white"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $invitation->group->name }}</div>
                                <div class="text-muted small mb-2">
                                    <i class="fas fa-users me-1"></i>
                                    {{ $invitation->group->members->count() }} members
                                </div>
                                @if($invitation->message)
                                    <div class="text-muted small mb-2">
                                        <i class="fas fa-comment me-1"></i>
                                        "{{ Str::limit($invitation->message, 100) }}"
                                    </div>
                                @endif
                                <div class="d-flex gap-2">
                                    <form action="{{ route('adviser.invitations.respond', $invitation) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="accepted">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Accept
                                        </button>
                                    </form>
                                    <form action="{{ route('adviser.invitations.respond', $invitation) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="declined">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times"></i> Decline
                                        </button>
                                    </form>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ $invitation->created_at->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                    @endforeach
                    <div class="text-center">
                        <a href="{{ route('adviser.invitations') }}" class="btn btn-outline-primary btn-sm">
                            View All Invitations
                        </a>
                    </div>
                @else
                    <div class="text-muted text-center">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                        No pending invitations
                    </div>
                @endif
            </div>
        </div>

        <!-- My Groups -->
        <div class="mb-4">
            <div class="fw-bold mb-2" style="font-size:1.2rem;">
                <i class="fas fa-users me-2"></i>My Groups
                @if($adviserGroups->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $adviserGroups->count() }}</span>
                @endif
            </div>
            <div class="bg-light rounded-3 p-3">
                @if($adviserGroups->count() > 0)
                    @foreach($adviserGroups as $group)
                        <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                            <div class="me-3 flex-shrink-0">
                                <span class="d-inline-flex align-items-center justify-content-center bg-primary border rounded-circle" style="width:36px; height:36px;">
                                    <i class="fas fa-users text-white"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $group->name }}</div>
                                <div class="text-muted small mb-2">
                                    <i class="fas fa-users me-1"></i>
                                    {{ $group->members->count() }} members
                                </div>
                                @if($group->description)
                                    <div class="text-muted small mb-2">
                                        {{ Str::limit($group->description, 100) }}
                                    </div>
                                @endif
                                <a href="{{ route('adviser.groups.details', $group) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    @endforeach
                    <div class="text-center">
                        <a href="{{ route('adviser.groups') }}" class="btn btn-outline-primary btn-sm">
                            View All Groups
                        </a>
                    </div>
                @else
                    <div class="text-muted text-center">
                        <i class="fas fa-users fa-2x mb-2"></i><br>
                        No groups assigned yet
                    </div>
                @endif
            </div>
        </div>

        <!-- Notifications -->
        <div class="mb-2">
            <div class="fw-bold mb-2" style="font-size:1.2rem;">
                <i class="fas fa-bell me-2"></i>Recent Notifications
            </div>
            <div class="bg-light rounded-3 p-3">
                @if($notifications->count() > 0)
                    @foreach($notifications as $notification)
                        <div class="d-flex align-items-start mb-3">
                            <div class="me-3 flex-shrink-0">
                                <span class="d-inline-flex align-items-center justify-content-center bg-info border rounded-circle" style="width:36px; height:36px;">
                                    <i class="fas fa-bell text-white"></i>
                                </span>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $notification->title }}</div>
                                <div class="text-muted small">{{ $notification->description }}</div>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ $notification->created_at->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-muted text-center">
                        <i class="fas fa-bell fa-2x mb-2"></i><br>
                        No notifications
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection 