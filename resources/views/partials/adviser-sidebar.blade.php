@php
    $user = auth()->user();
    $userName = $user ? $user->name : 'Adviser';
    $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
    $pendingAdviserInvitations = $user
        ? \App\Models\AdviserInvitation::where('faculty_id', $user->id)->where('status', 'pending')->count()
        : 0;
    $pendingPanelInvitations = $user
        ? \App\Models\DefensePanel::where('faculty_id', $user->id)->where('status', 'pending')->count()
        : 0;
    $isPanelSubmissionContext = request()->routeIs('adviser.project.show') && request()->query('context') === 'panel';
@endphp
<div class="sidebar bg-dark text-white" style="width: 280px; min-height: 100vh; position: fixed; left: 0; top: 0; z-index: 1000;">
    <div class="p-3 border-bottom border-secondary">
        <div class="d-flex align-items-center">
            <div class="ct-badge me-2">
                <span>CT</span>
            </div>
            <a class="navbar-brand fw-bold text-white text-decoration-none mb-0" href="{{ route('adviser.dashboard') }}">
                CapTrack
            </a>
        </div>
    </div>
    <div class="p-3 border-bottom border-secondary">
        <h6 class="text-muted mb-2">Current Term</h6>
        @if($activeTerm)
            <div class="d-flex align-items-center">
                <span class="badge bg-success me-2">Active</span>
                <span class="small">{{ $activeTerm->semester }}</span>
            </div>
        @else
            <div class="text-warning small">
                <i class="fas fa-exclamation-triangle"></i> No active term
            </div>
        @endif
    </div>
    <nav class="p-3">
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('adviser.dashboard') ? 'active bg-primary' : '' }}" 
                   href="{{ route('adviser.dashboard') }}">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('adviser.groups') ? 'active bg-primary' : '' }}"
                   href="{{ route('adviser.groups') }}">
                    <i class="fas fa-user-tie me-2"></i>
                    Adviser Groups
                    </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('adviser.panel-groups') || request()->routeIs('adviser.panel-submissions') || $isPanelSubmissionContext ? 'active bg-primary' : '' }}"
                   href="{{ route('adviser.panel-groups') }}">
                    <i class="fas fa-gavel me-2"></i>
                    Panel Groups
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('adviser.invitations') ? 'active bg-primary' : '' }}" 
                   href="{{ route('adviser.invitations') }}">
                    <i class="fas fa-envelope me-2"></i>
                    Adviser Invitations
                    @if($pendingAdviserInvitations > 0)
                        <span class="badge bg-warning text-dark ms-2">{{ $pendingAdviserInvitations }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('adviser.panel-invitations') ? 'active bg-primary' : '' }}"
                   href="{{ route('adviser.panel-invitations') }}">
                    <i class="fas fa-gavel me-2"></i>
                    Panel Invitations
                    @if($pendingPanelInvitations > 0)
                        <span class="badge bg-warning text-dark ms-2">{{ $pendingPanelInvitations }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('adviser.calendar') ? 'active bg-primary' : '' }}" 
                   href="{{ route('adviser.calendar') }}">
                    <i class="fas fa-calendar me-2"></i>
                    Calendar
                </a>
            </li>
        </ul>
    </nav>
    <div class="mt-auto p-3 border-top border-secondary">
        <div class="d-flex align-items-center justify-content-between">
            <div class="small">
                <div class="text-muted">{{ $userName }}</div>
                <div class="text-muted">Teacher</div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
    </div>
</div>
<style>
.ct-badge {
    width: 34px;
    height: 34px;
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: white;
    letter-spacing: 0.5px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.4);
}
.sidebar .nav-link {
    border-radius: 6px;
    transition: all 0.3s ease;
}
.sidebar .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
    text-decoration: none;
}
.sidebar .nav-link.active {
    background-color: #0d6efd !important;
    color: white !important;
}
.sidebar {
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
}
</style>
