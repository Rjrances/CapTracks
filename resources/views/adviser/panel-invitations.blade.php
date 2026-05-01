@extends('layouts.adviser')
@section('title', 'Panel Invitations')
@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div>
                <h1 class="h2 mb-1 text-dark fw-bold">
                    <i class="fas fa-gavel me-3 text-primary"></i>
                    Panel Invitations
                </h1>
                <p class="text-muted mb-0">Manage your defense panel assignments</p>
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

    {{-- Pending Panel Invitations --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white border-0">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-clock me-2"></i>
                        <h5 class="mb-0 fw-bold">Pending Panel Assignments</h5>
                        <span class="badge bg-light text-primary ms-auto">{{ $pendingPanels->count() }} pending</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($pendingPanels->count() > 0)
                        @foreach($pendingPanels as $panel)
                            <div class="invitation-item p-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="status-icon-wrapper">
                                            <span class="status-icon pending">
                                                <i class="fas fa-gavel"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="invitation-content">
                                            <h6 class="fw-bold text-dark mb-2">
                                                <i class="fas fa-users me-2 text-primary"></i>
                                                {{ $panel->defenseSchedule->group->name ?? 'Unknown Group' }}
                                            </h6>
                                            <div class="invitation-meta mb-3">
                                                <span class="badge bg-info me-2">
                                                    <i class="fas fa-shield-alt me-1"></i>
                                                    {{ $panel->role_label }}
                                                </span>
                                                <span class="badge bg-warning text-dark me-2">
                                                    <i class="fas fa-layer-group me-1"></i>
                                                    {{ $panel->defenseSchedule->stage_label ?? 'Unknown Stage' }}
                                                </span>
                                                <span class="badge bg-secondary me-2">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    {{ $panel->defenseSchedule->start_at ? $panel->defenseSchedule->start_at->format('M d, Y h:i A') : 'TBD' }}
                                                </span>
                                                @if($panel->defenseSchedule->room)
                                                    <span class="badge bg-dark">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        {{ $panel->defenseSchedule->room }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="invitation-actions">
                                                <form action="{{ route('adviser.panel-invitations.respond', $panel) }}" method="POST" class="d-inline me-2">
                                                    @csrf
                                                    <input type="hidden" name="response" value="accept">
                                                    <button type="submit" class="btn btn-success btn-sm px-3">
                                                        <i class="fas fa-check me-1"></i> Accept
                                                    </button>
                                                </form>
                                                <form action="{{ route('adviser.panel-invitations.respond', $panel) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="response" value="decline">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm px-3">
                                                        <i class="fas fa-times me-1"></i> Decline
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-check-circle fa-4x text-success mb-3" style="opacity: 0.6;"></i>
                                <h5 class="text-muted">No Pending Panel Assignments</h5>
                                <p class="text-muted mb-0">You're all caught up! No panel invitations require your response.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Response History --}}
    @if($respondedPanels->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white border-0">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-history me-2"></i>
                        <h5 class="mb-0 fw-bold">Response History</h5>
                        <span class="badge bg-light text-secondary ms-auto">{{ $respondedPanels->count() }} responses</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @foreach($respondedPanels as $panel)
                        <div class="invitation-item p-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="status-icon-wrapper">
                                        <span class="status-icon {{ $panel->status }}">
                                            <i class="fas fa-{{ $panel->status === 'accepted' ? 'check-circle' : 'times-circle' }}"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="invitation-content">
                                        <h6 class="fw-bold text-dark mb-2">
                                            <i class="fas fa-users me-2 text-primary"></i>
                                            {{ $panel->defenseSchedule->group->name ?? 'Unknown Group' }}
                                        </h6>
                                        <div class="invitation-meta mb-2">
                                            <span class="badge bg-info me-2">
                                                <i class="fas fa-shield-alt me-1"></i>
                                                {{ $panel->role_label }}
                                            </span>
                                            <span class="badge bg-warning text-dark me-2">
                                                <i class="fas fa-layer-group me-1"></i>
                                                {{ $panel->defenseSchedule->stage_label ?? 'Unknown Stage' }}
                                            </span>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-calendar me-1"></i>
                                                {{ $panel->defenseSchedule->start_at ? $panel->defenseSchedule->start_at->format('M d, Y h:i A') : 'TBD' }}
                                            </span>
                                        </div>
                                        <div class="invitation-status">
                                            <span class="badge bg-{{ $panel->status === 'accepted' ? 'success' : 'danger' }} px-3 py-2">
                                                <i class="fas fa-{{ $panel->status === 'accepted' ? 'check-circle' : 'times-circle' }} me-1"></i>
                                                {{ ucfirst($panel->status) }}
                                            </span>
                                            @if($panel->responded_at)
                                                <small class="text-muted ms-2">
                                                    <i class="fas fa-clock me-1"></i>
                                                    {{ $panel->responded_at->diffForHumans() }}
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
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
.card {
    border-radius: 12px;
    overflow: hidden;
}
.card-header {
    background: linear-gradient(135deg, #007bff, #0056b3) !important;
}
.card-header.bg-secondary {
    background: linear-gradient(135deg, #6c757d, #495057) !important;
}
</style>
@endsection
