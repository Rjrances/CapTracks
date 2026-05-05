@extends('layouts.adviser')
@section('title', 'Adviser Invitations')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0">Invitations to become the assigned adviser of a student group</p>
        </div>
        <a href="{{ route('adviser.groups') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Adviser Groups
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

    @php
        $pendingCount = $invitations->where('status', 'pending')->count();
        $acceptedCount = $invitations->where('status', 'accepted')->count();
        $declinedCount = $invitations->where('status', 'declined')->count();
    @endphp

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Pending Invitations</h5>
                    <h3 class="mb-0">{{ $pendingCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Accepted</h5>
                    <h3 class="mb-0">{{ $acceptedCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Declined</h5>
                    <h3 class="mb-0">{{ $declinedCount }}</h3>
                </div>
            </div>
        </div>
    </div>

    @if($invitations->count() > 0)
        @foreach($invitations as $invitation)
            <div class="card mb-3">
                <div class="card-header {{ $invitation->status === 'pending' ? 'bg-primary' : ($invitation->status === 'accepted' ? 'bg-success' : 'bg-secondary') }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>{{ $invitation->group->name }}
                                <span class="badge bg-light text-dark ms-2">{{ ucfirst($invitation->status) }}</span>
                            </h5>
                            <small class="text-white-50">{{ $invitation->group->members->count() }} members</small>
                        </div>
                        <small>{{ $invitation->created_at->diffForHumans() }}</small>
                    </div>
                </div>
                <div class="card-body">
                    @if($invitation->message)
                        <p class="mb-2"><strong>Message:</strong> {{ $invitation->message }}</p>
                    @endif

                    @if($invitation->status === 'pending')
                        <div class="d-flex gap-2">
                            <form action="{{ route('adviser.invitations.respond', $invitation) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="accepted">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-check me-1"></i>Accept
                                </button>
                            </form>
                            <form action="{{ route('adviser.invitations.respond', $invitation) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="declined">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-times me-1"></i>Decline
                                </button>
                            </form>
                        </div>
                    @elseif($invitation->response_message)
                        <small class="text-muted"><strong>Your response:</strong> {{ $invitation->response_message }}</small>
                    @endif
                </div>
            </div>
        @endforeach

        @if($invitations->hasPages())
            <div class="d-flex justify-content-center">
                {{ $invitations->links() }}
            </div>
        @endif
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No adviser invitations</h5>
                <p class="text-muted mb-0">You don’t have adviser invitations right now.</p>
            </div>
        </div>
    @endif
</div>
@endsection 
