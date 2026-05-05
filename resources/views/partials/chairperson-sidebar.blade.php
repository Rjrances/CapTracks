@php
    $user = auth()->user();
    $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
@endphp
<div class="sidebar bg-dark text-white" style="width: 280px; min-height: 100vh; position: fixed; left: 0; top: 0; z-index: 1000;">
    <div class="p-3 border-bottom border-secondary">
        <div class="d-flex align-items-center">
            <div class="ct-badge me-2">
                <span>CT</span>
            </div>
            <a class="navbar-brand fw-bold text-white text-decoration-none mb-0" href="{{ route('chairperson.dashboard') }}">
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
        <a href="{{ route('chairperson.academic-terms.index') }}" class="btn btn-sm btn-outline-light mt-2 w-100">
            <i class="fas fa-cog"></i> Manage Terms
        </a>
    </div>
    <nav class="p-3">
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('chairperson.dashboard') ? 'active bg-primary' : '' }}" 
                   href="{{ route('chairperson.dashboard') }}">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('chairperson.offerings.*') ? 'active bg-primary' : '' }}" 
                   href="{{ route('chairperson.offerings.index') }}">
                    <i class="fas fa-book me-2"></i>
                    Offerings
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('chairperson.teachers.*') ? 'active bg-primary' : '' }}" 
                   href="{{ route('chairperson.teachers.index') }}">
                    <i class="fas fa-chalkboard-teacher me-2"></i>
                    Teachers
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('chairperson.students.*') ? 'active bg-primary' : '' }}" 
                   href="{{ route('chairperson.students.index') }}">
                    <i class="fas fa-users me-2"></i>
                    Students
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('chairperson.roles.*') ? 'active bg-primary' : '' }}" 
                   href="{{ route('chairperson.roles.index') }}">
                    <i class="fas fa-user-tag me-2"></i>
                    Roles
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('chairperson.calendar') ? 'active bg-primary' : '' }}" 
                   href="{{ route('chairperson.calendar') }}">
                    <i class="fas fa-calendar me-2"></i>
                    Calendar
                </a>
            </li>
        </ul>
    </nav>
    <div class="mt-auto p-3 border-top border-secondary">
        <div class="d-flex align-items-center">
            <div class="small">
                <div class="text-muted">{{ $user ? $user->name : 'User' }}</div>
                <div class="text-muted">Chairperson</div>
            </div>
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
