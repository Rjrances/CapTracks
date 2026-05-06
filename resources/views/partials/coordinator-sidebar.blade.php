@php
    $user = auth()->user();
    $userName = $user ? $user->name : 'Coordinator';
    $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
    $coordinatorOfferingIds = $user
        ? $user->offerings()
            ->when($activeTerm, function ($query) use ($activeTerm) {
                return $query->where('academic_term_id', $activeTerm->id);
            })
            ->pluck('id')
            ->toArray()
        : [];
    // Same scope as Coordinator\DefenseScheduleController@defenseRequestsIndex
    $pendingDefenseRequestsCount = ($user && count($coordinatorOfferingIds) > 0)
        ? \App\Models\DefenseRequest::where('status', 'pending')
            ->whereHas('group', function ($q) use ($coordinatorOfferingIds) {
                $q->whereIn('offering_id', $coordinatorOfferingIds);
            })
            ->count()
        : 0;
@endphp
<div class="sidebar bg-dark text-white" style="width: 280px; min-height: 100vh; position: fixed; left: 0; top: 0; z-index: 1000;">
    <div class="p-3 border-bottom border-secondary">
        <div class="d-flex align-items-center">
            <div class="ct-badge me-2">
                <span>CT</span>
            </div>
            <a class="navbar-brand fw-bold text-white text-decoration-none mb-0" href="{{ route('coordinator.dashboard') }}">
                CapTrack
            </a>
        </div>
    </div>
    <div class="px-3 py-2 border-bottom border-secondary text-center">
        @if($activeTerm)
            <div class="d-flex align-items-center justify-content-center gap-2">
                <span class="badge bg-success">Active</span>
                <span class="small text-white-50">{{ $activeTerm->semester }}</span>
            </div>
        @else
            <div class="text-warning small text-center">
                <i class="fas fa-exclamation-triangle me-1"></i>No active term
            </div>
        @endif
    </div>
    <nav class="p-3">
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('coordinator.dashboard') ? 'active bg-primary' : '' }}" 
                   href="{{ route('coordinator.dashboard') }}">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('coordinator.groups.*') ? 'active bg-primary' : '' }}" 
                   href="{{ route('coordinator.groups.index') }}">
                    <i class="fas fa-users me-2"></i>
                    Groups
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('coordinator.classlist.*') ? 'active bg-primary' : '' }}" 
                   href="{{ route('coordinator.classlist.index') }}">
                    <i class="fas fa-list me-2"></i>
                    Class List
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('coordinator.faculty-matrix') ? 'active bg-primary' : '' }}"
                   href="{{ route('coordinator.faculty-matrix') }}">
                    <i class="fas fa-table me-2"></i>
                    Faculty Matrix
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('coordinator.proposals.*') ? 'active bg-primary' : '' }}" 
                   href="{{ route('coordinator.proposals.index') }}">
                    <i class="fas fa-file-alt me-2"></i>
                    Proposal Review
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white d-flex align-items-center justify-content-between gap-2 {{ request()->routeIs('coordinator.defense.*') || request()->routeIs('coordinator.defense-requests.*') ? 'active bg-primary' : '' }}"
                   href="{{ route('coordinator.defense.index') }}">
                    <span class="d-flex align-items-center text-truncate">
                        <i class="fas fa-gavel me-2 flex-shrink-0"></i>
                        Defense Management
                    </span>
                    @if($pendingDefenseRequestsCount > 0)
                        <span class="sidebar-invitation-badge flex-shrink-0">{{ $pendingDefenseRequestsCount > 99 ? '99+' : $pendingDefenseRequestsCount }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('coordinator.milestones.*') ? 'active bg-primary' : '' }}" 
                   href="{{ route('coordinator.milestones.index') }}">
                    <i class="fas fa-flag me-2"></i>
                    Milestone Templates
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('coordinator.calendar') ? 'active bg-primary' : '' }}" 
                   href="{{ route('coordinator.calendar') }}">
                    <i class="fas fa-calendar me-2"></i>
                    Calendar
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('coordinator.activity-log') ? 'active bg-primary' : '' }}"
                   href="{{ route('coordinator.activity-log') }}">
                    <i class="fas fa-history me-2"></i>
                    Activity Log
                </a>
            </li>
        </ul>
    </nav>
    <div class="mt-auto p-3 border-top border-secondary">
        @php
            $hasAdviserRole = $user && (
                \App\Models\AdviserInvitation::where('faculty_id', $user->id)->where('status', 'pending')->exists()
                || \App\Models\Group::where('faculty_id', $user->faculty_id)->exists()
            );
        @endphp
        @if($hasAdviserRole)
            <a href="{{ route('adviser.dashboard') }}" class="btn btn-outline-info btn-sm w-100 mb-3">
                <i class="fas fa-exchange-alt me-2"></i>Switch to Adviser View
            </a>
        @endif
        <div class="small">
            <div class="text-muted">{{ $userName }}</div>
            <div class="text-muted">Coordinator</div>
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
.sidebar-invitation-badge {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    min-width: 1.35rem;
    height: 1.35rem;
    padding: 0 0.4rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    border-radius: 9999px;
    color: #fff;
    background: linear-gradient(145deg, #dc3545 0%, #b02a37 100%);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.35);
}
.sidebar .nav-link.active .sidebar-invitation-badge {
    background: rgba(255, 255, 255, 0.22);
    color: #fff;
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.15);
}
</style>

