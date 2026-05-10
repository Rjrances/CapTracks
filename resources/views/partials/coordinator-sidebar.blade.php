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
        @include('partials.sidebar-brand', [
            'homeRoute' => route('coordinator.dashboard'),
            'roleLabel' => 'Coordinator',
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
            @php
                $classMenuOpen = request()->routeIs('coordinator.classlist.*') || request()->routeIs('coordinator.final-grades');
            @endphp
            <li class="nav-item mb-2">
                <a class="nav-link text-white d-flex align-items-center justify-content-between {{ $classMenuOpen ? 'active bg-primary' : '' }}"
                   data-bs-toggle="collapse"
                   href="#coordinatorClassMenu"
                   role="button"
                   aria-expanded="{{ $classMenuOpen ? 'true' : 'false' }}"
                   aria-controls="coordinatorClassMenu">
                    <span>
                        <i class="fas fa-list me-2"></i>
                        Students
                    </span>
                    <i class="fas fa-chevron-down small sidebar-collapse-chevron"></i>
                </a>
                <div class="collapse {{ $classMenuOpen ? 'show' : '' }}" id="coordinatorClassMenu">
                    <ul class="nav flex-column mt-1 ms-4">
                        <li class="nav-item mb-1">
                            <a class="nav-link py-1 px-2 text-white {{ request()->routeIs('coordinator.classlist.*') ? 'active bg-primary' : '' }}"
                               href="{{ route('coordinator.classlist.index') }}">
                                Class List
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-1 px-2 text-white {{ request()->routeIs('coordinator.final-grades') ? 'active bg-primary' : '' }}"
                               href="{{ route('coordinator.final-grades') }}">
                                Grades
                            </a>
                        </li>
                    </ul>
                </div>
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
    .sidebar-collapse-chevron {
        transition: transform 0.2s ease;
    }

    .sidebar .nav-link[aria-expanded="true"] .sidebar-collapse-chevron {
        transform: rotate(180deg);
    }
</style>
@include('partials.dark-sidebar-shared-styles')

