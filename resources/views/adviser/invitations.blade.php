@extends('layouts.adviser')
@section('title', 'Invitations')
@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
                            <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">
                        <i class="fas fa-envelope-open-text me-3 text-primary"></i>
                        Invitations
                    </h1>
                    <p class="text-muted mb-0">Manage your teacher invitations and group assignments</p>
                </div>
        </div>
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
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white border-0">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-envelope me-2"></i>
                            <h5 class="mb-0 fw-bold">All Invitations</h5>
                            <span class="badge bg-light text-primary ms-auto">{{ $invitations->count() }} invitation(s)</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($invitations->count() > 0)
                            @foreach($invitations as $invitation)
                                <div class="invitation-item p-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="status-icon-wrapper">
                                                <span class="status-icon {{ $invitation->status === 'pending' ? 'pending' : ($invitation->status === 'accepted' ? 'accepted' : 'declined') }}">
                                                    <i class="fas fa-{{ $invitation->status === 'pending' ? 'envelope' : ($invitation->status === 'accepted' ? 'check-circle' : 'times-circle') }}"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="invitation-content">
                                                <h6 class="fw-bold text-dark mb-2">
                                                    <i class="fas fa-users me-2 text-primary"></i>
                                                    {{ $invitation->group->name }}
                                                </h6>
                                                <div class="invitation-meta mb-3">
                                                    <span class="badge bg-info me-2">
                                                        <i class="fas fa-users me-1"></i>
                                                        {{ $invitation->group->members->count() }} members
                                                    </span>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ $invitation->created_at->diffForHumans() }}
                                                    </span>
                                                </div>
                                                @if($invitation->message)
                                                    <div class="invitation-message mb-3">
                                                        <div class="alert alert-light border-start border-primary border-3">
                                                            <i class="fas fa-comment me-2 text-primary"></i>
                                                            <strong>Message:</strong> "{{ Str::limit($invitation->message, 100) }}"
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($invitation->status === 'pending')
                                                    <div class="invitation-actions">
                                                        <form action="{{ route('adviser.invitations.respond', $invitation) }}" method="POST" class="d-inline me-2">
                                                            @csrf
                                                            <input type="hidden" name="status" value="accepted">
                                                            <button type="submit" class="btn btn-success btn-sm px-3">
                                                                <i class="fas fa-check me-1"></i> Accept Invitation
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('adviser.invitations.respond', $invitation) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="status" value="declined">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm px-3">
                                                                <i class="fas fa-times me-1"></i> Decline
                                                            </button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <div class="invitation-status">
                                                        <span class="badge bg-{{ $invitation->status === 'accepted' ? 'success' : 'danger' }} fs-6 px-3 py-2">
                                                            <i class="fas fa-{{ $invitation->status === 'accepted' ? 'check-circle' : 'times-circle' }} me-1"></i>
                                                            {{ ucfirst($invitation->status) }}
                                                        </span>
                                                        @if($invitation->response_message)
                                                            <div class="mt-2">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-reply me-1"></i>
                                                                    <strong>Your response:</strong> "{{ Str::limit($invitation->response_message, 50) }}"
                                                                </small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                    @if($invitations->hasPages())
                        <div class="d-flex justify-content-center p-4 border-top">
                            {{ $invitations->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="empty-state">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No Invitations</h5>
                            <p class="text-muted mb-0">You don't have any invitations at the moment.</p>
                        </div>
                    </div>
                @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.status-icon-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
}
.status-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    transition: all 0.3s ease;
}
.status-icon.pending {
    background: linear-gradient(135deg, #ffc107, #ff9800);
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
}
.status-icon.accepted {
    background: linear-gradient(135deg, #28a745, #20c997);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}
.status-icon.declined {
    background: linear-gradient(135deg, #dc3545, #e74c3c);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}
.invitation-item {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}
.invitation-item:hover {
    background-color: #f8f9fa;
    border-left-color: #007bff;
    transform: translateX(5px);
}
.invitation-meta .badge {
    font-size: 0.8rem;
    padding: 0.5rem 0.75rem;
}
.invitation-message .alert {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border: none;
    border-radius: 8px;
}
.invitation-actions .btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}
.invitation-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.invitation-status .badge {
    border-radius: 6px;
    font-weight: 500;
}
.empty-state {
    padding: 2rem;
}
.empty-state i {
    opacity: 0.6;
}
.card {
    border-radius: 12px;
    overflow: hidden;
}
.card-header {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
}
.invitation-item:last-child {
    border-bottom: none;
}
</style>
@endsection 
