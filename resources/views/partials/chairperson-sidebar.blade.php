@php
    $user = auth()->user();
    $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
@endphp
<div class="sidebar bg-dark text-white" style="width: 280px; min-height: 100vh; position: fixed; left: 0; top: 0; z-index: 1000;">
    <div class="p-3 border-bottom border-secondary">
        @include('partials.sidebar-brand', [
            'homeRoute' => route('chairperson.dashboard'),
            'roleLabel' => 'Chairperson',
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
                <a class="nav-link text-white {{ request()->routeIs('chairperson.dashboard') ? 'active bg-primary' : '' }}" 
                   href="{{ route('chairperson.dashboard') }}">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('chairperson.academic-terms.*') ? 'active bg-primary' : '' }}"
                   href="{{ route('chairperson.academic-terms.index') }}">
                    <i class="fas fa-calendar-week me-2"></i>
                    Academic terms
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
