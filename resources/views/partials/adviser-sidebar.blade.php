@php
    $user = auth()->user();
    $userName = $user ? $user->name : 'Adviser';
    $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
@endphp
<div class="sidebar bg-dark text-white" style="width: 280px; min-height: 100vh; position: fixed; left: 0; top: 0; z-index: 1000;">
    <div class="p-3 border-bottom border-secondary">
        <div class="d-flex align-items-center">
            <div style="width:32px; height:32px; margin-right:10px;"></div>
            <a class="navbar-brand fw-bold text-white text-decoration-none" href="{{ route('adviser.dashboard') }}">
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
                <a class="nav-link text-white {{ request()->routeIs('adviser.all-groups') ? 'active bg-primary' : '' }}" 
                   href="{{ route('adviser.all-groups') }}">
                    <i class="fas fa-layer-group me-2"></i>
                    All My Groups
                    </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('adviser.invitations') ? 'active bg-primary' : '' }}" 
                   href="{{ route('adviser.invitations') }}">
                    <i class="fas fa-envelope me-2"></i>
                    Invitations
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('adviser.proposal.*') ? 'active bg-primary' : '' }}" 
                   href="{{ route('adviser.proposal.index') }}">
                    <i class="fas fa-clipboard-check me-2"></i>
                    Proposal Review
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
