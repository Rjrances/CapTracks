@php
    $user = auth()->user();
    $userName = $user ? $user->name : 'Adviser';
    $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
    $pendingAdviserInvitations = $user
        ? \App\Models\AdviserInvitation::where('faculty_id', $user->id)->where('status', 'pending')->count()
        : 0;
    
    $pendingPanelInvitations = $user
        ? \App\Models\DefensePanel::where('faculty_id', $user->id)
            ->whereIn('role', ['chair', 'member'])
            ->where('status', 'pending')
            ->count()
        : 0;
    $isPanelSubmissionContext = request()->routeIs('adviser.project.show') && request()->query('context') === 'panel';

    $sidebarRoleLabel = 'Teacher';
    if ($user) {
        $pr = $user->primary_role;
        $sidebarRoleLabel = match ($pr) {
            'chairperson' => 'Chairperson',
            'coordinator' => 'Coordinator',
            'adviser' => 'Adviser',
            'panelist' => 'Panelist',
            'teacher' => 'Teacher',
            default => $pr ? ucfirst((string) $pr) : 'Teacher',
        };
    }
@endphp
<div class="sidebar bg-dark text-white" style="width: 280px; min-height: 100vh; position: fixed; left: 0; top: 0; z-index: 1000;">
    <div class="p-3 border-bottom border-secondary">
        @include('partials.sidebar-brand', [
            'homeRoute' => route('adviser.dashboard'),
            'roleLabel' => $sidebarRoleLabel,
        ])
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
                <a class="nav-link text-white d-flex align-items-center justify-content-between gap-2 {{ request()->routeIs('adviser.invitations') ? 'active bg-primary' : '' }}"
                   href="{{ route('adviser.invitations') }}">
                    <span class="d-flex align-items-center text-truncate">
                        <i class="fas fa-envelope me-2 flex-shrink-0"></i>
                        Adviser Invitations
                    </span>
                    @if($pendingAdviserInvitations > 0)
                        <span class="sidebar-invitation-badge flex-shrink-0">{{ $pendingAdviserInvitations > 99 ? '99+' : $pendingAdviserInvitations }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white d-flex align-items-center justify-content-between gap-2 {{ request()->routeIs('adviser.panel-invitations') ? 'active bg-primary' : '' }}"
                   href="{{ route('adviser.panel-invitations') }}">
                    <span class="d-flex align-items-center text-truncate">
                        <i class="fas fa-gavel me-2 flex-shrink-0"></i>
                        Panel Invitations
                    </span>
                    @if($pendingPanelInvitations > 0)
                        <span class="sidebar-invitation-badge flex-shrink-0">{{ $pendingPanelInvitations > 99 ? '99+' : $pendingPanelInvitations }}</span>
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
        @if($user && $user->primary_role === 'coordinator')
            <a href="{{ route('coordinator.dashboard') }}" class="btn btn-outline-warning btn-sm w-100 mb-3">
                <i class="fas fa-exchange-alt me-2"></i>Switch to Coordinator View
            </a>
        @endif
        <div class="small">
            <div class="text-muted">{{ $userName }}</div>
            <div class="text-muted">Teacher</div>
        </div>
    </div>
</div>
@include('partials.dark-sidebar-shared-styles')
