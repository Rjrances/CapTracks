@extends('layouts.adviser')
@section('title', 'Panel Invitations')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0">Invitations for chair/member defense panel assignments</p>
        </div>
        <a href="{{ route('adviser.panel-groups') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Panel Groups
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

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Pending</h5>
                    <h3 class="mb-0">{{ $pendingPanels->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Accepted</h5>
                    <h3 class="mb-0">{{ $respondedPanels->where('status', 'accepted')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Declined</h5>
                    <h3 class="mb-0">{{ $respondedPanels->where('status', 'declined')->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <h5 class="mb-3">Pending Panel Assignments</h5>
    @if($pendingPanels->count() > 0)
        @foreach($pendingPanels as $panel)
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">{{ $panel->defenseSchedule->group->name ?? 'Unknown Group' }}</h5>
                            <small class="text-white-50">{{ $panel->role_label }} · {{ $panel->defenseSchedule->stage_label ?? 'Unknown Stage' }}</small>
                        </div>
                        <span class="badge bg-light text-dark">Pending</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Schedule:</strong> {{ $panel->defenseSchedule->start_at ? $panel->defenseSchedule->start_at->format('M d, Y h:i A') : 'TBD' }}</p>
                    <p class="mb-3"><strong>Room:</strong> {{ $panel->defenseSchedule->room ?? 'TBD' }}</p>
                    <div class="d-flex gap-2">
                        <form action="{{ route('adviser.panel-invitations.respond', $panel) }}" method="POST">
                            @csrf
                            <input type="hidden" name="response" value="accept">
                            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Accept</button>
                        </form>
                        <form action="{{ route('adviser.panel-invitations.respond', $panel) }}" method="POST">
                            @csrf
                            <input type="hidden" name="response" value="decline">
                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-times me-1"></i>Decline</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="card mb-4">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No pending panel assignments</h5>
                <p class="text-muted mb-0">You’re all caught up.</p>
            </div>
        </div>
    @endif

    @if($respondedPanels->count() > 0)
        <h5 class="mb-3">Response History</h5>
        @foreach($respondedPanels as $panel)
            <div class="card mb-3">
                <div class="card-header {{ $panel->status === 'accepted' ? 'bg-success' : 'bg-secondary' }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">{{ $panel->defenseSchedule->group->name ?? 'Unknown Group' }}</h5>
                            <small class="text-white-50">{{ $panel->role_label }} · {{ $panel->defenseSchedule->stage_label ?? 'Unknown Stage' }}</small>
                        </div>
                        <span class="badge bg-light text-dark">{{ ucfirst($panel->status) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Schedule:</strong> {{ $panel->defenseSchedule->start_at ? $panel->defenseSchedule->start_at->format('M d, Y h:i A') : 'TBD' }}</p>
                    @if($panel->responded_at)
                        <p class="mb-2"><small class="text-muted">Responded {{ $panel->responded_at->diffForHumans() }}</small></p>
                    @endif
                    @if($panel->status === 'accepted')
                        <a href="{{ route('adviser.rating-sheets.show', $panel->defenseSchedule) }}" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-clipboard-check me-1"></i>Open Rating Sheet
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
