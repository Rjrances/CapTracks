@extends('layouts.adviser')

@section('title', 'Invitations')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 900px;">
        <div class="mb-4" style="margin-bottom: 1.2rem !important;">
            <h1 class="fw-bold mb-1 text-center" style="font-size:2.5rem; margin-bottom:0.1rem;">Invitations</h1>
            <div class="text-muted text-center" style="font-size:1.1rem; margin-bottom:0;">Manage your teacher invitations</div>
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

        <!-- Invitations List -->
        <div class="mb-4">
            <div class="fw-bold mb-2" style="font-size:1.2rem;">
                <i class="fas fa-envelope me-2"></i>All Invitations
            </div>
            <div class="bg-light rounded-3 p-3">
                @if($invitations->count() > 0)
                    @foreach($invitations as $invitation)
                        <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                            <div class="me-3 flex-shrink-0">
                                <span class="d-inline-flex align-items-center justify-content-center 
                                    {{ $invitation->status === 'pending' ? 'bg-warning' : ($invitation->status === 'accepted' ? 'bg-success' : 'bg-danger') }} 
                                    border rounded-circle" style="width:36px; height:36px;">
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
                                <div class="d-flex gap-2 align-items-center">
                                    @if($invitation->status === 'pending')
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
                                    @else
                                        <span class="badge bg-{{ $invitation->status === 'accepted' ? 'success' : 'danger' }}">
                                            {{ ucfirst($invitation->status) }}
                                        </span>
                                        @if($invitation->response_message)
                                            <small class="text-muted">
                                                <i class="fas fa-reply me-1"></i>
                                                "{{ Str::limit($invitation->response_message, 50) }}"
                                            </small>
                                        @endif
                                    @endif
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ $invitation->created_at->diffForHumans() }}
                                    @if($invitation->responded_at)
                                        • Responded {{ $invitation->responded_at->diffForHumans() }}
                                    @endif
                                </small>
                            </div>
                        </div>
                    @endforeach

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $invitations->links() }}
                    </div>
                @else
                    <div class="text-muted text-center">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                        No invitations found
                    </div>
                @endif
            </div>
        </div>

        <!-- Back to Dashboard -->
        <div class="text-center">
            <a href="{{ route('adviser.dashboard') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection 