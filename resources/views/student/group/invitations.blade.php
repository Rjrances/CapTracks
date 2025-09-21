@extends('layouts.student')
@section('title', 'Group Invitations')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Group Invitations</h2>
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
    
    @if($invitations->count() > 0)
        <div class="row">
            @foreach($invitations as $invitation)
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-users me-2"></i>{{ $invitation->group->name }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="fw-bold">Group Details:</h6>
                                <p class="mb-1"><strong>Description:</strong> {{ $invitation->group->description ?: 'No description provided.' }}</p>
                                @if($invitation->group->offering)
                                    <p class="mb-1"><strong>Subject:</strong> {{ $invitation->group->offering->offer_code }} - {{ $invitation->group->offering->subject_title }}</p>
                                @else
                                    <p class="mb-1"><strong>Subject:</strong> <span class="text-muted">No subject assigned</span></p>
                                @endif
                                <p class="mb-0"><strong>Current Members:</strong> {{ $invitation->group->members->count() }}/3</p>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="fw-bold">Invitation Details:</h6>
                                <p class="mb-1"><strong>Invited by:</strong> {{ $invitation->invitedBy->name }}</p>
                                <p class="mb-1"><strong>Sent:</strong> {{ $invitation->created_at->format('M d, Y \a\t g:i A') }}</p>
                                @if($invitation->message)
                                    <p class="mb-0"><strong>Message:</strong> "{{ $invitation->message }}"</p>
                                @endif
                            </div>
                            
                            <div class="d-flex gap-2">
                                <form action="{{ route('student.group.accept-invitation', $invitation->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Accept this group invitation?')">
                                        <i class="fas fa-check me-1"></i>Accept
                                    </button>
                                </form>
                                
                                <form action="{{ route('student.group.decline-invitation', $invitation->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Decline this group invitation?')">
                                        <i class="fas fa-times me-1"></i>Decline
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h4>No Pending Invitations</h4>
            <p class="text-muted">You don't have any pending group invitations at the moment.</p>
            <a href="{{ route('student.group') }}" class="btn btn-primary">
                <i class="fas fa-users me-1"></i>View My Group
            </a>
        </div>
    @endif
</div>
@endsection
