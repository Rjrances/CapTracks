@php
    if (\Illuminate\Support\Facades\Auth::check()) {
        $user = \Illuminate\Support\Facades\Auth::user();
        $studentName = $user->name ?? 'User';
    } elseif (session('is_student') && session('student_id')) {
        $student = \App\Models\Student::find(session('student_id'));
        $studentName = $student->name ?? 'Student';
    } else {
        $studentName = 'Guest';
    }
    $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
@endphp
<div class="sidebar bg-dark text-white" style="width: 280px; min-height: 100vh; position: fixed; left: 0; top: 0; z-index: 1000;">
    <div class="p-3 border-bottom border-secondary">
        <div class="d-flex align-items-center">
            <div style="width:32px; height:32px; margin-right:10px;"></div>
            <a class="navbar-brand fw-bold text-white text-decoration-none" href="{{ route('student.dashboard') }}">
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
                <a class="nav-link text-white {{ request()->routeIs('student.dashboard') || request()->is('student/dashboard') ? 'active bg-primary' : '' }}" 
                   href="{{ route('student.dashboard') }}">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('student.group*') || request()->is('student/group*') ? 'active bg-primary' : '' }}" 
                   href="{{ route('student.group') }}">
                    <i class="fas fa-users me-2"></i>
                    My Group
                </a>
            </li>
            @php
                // Check if student has a group
                $studentHasGroup = false;
                if (Auth::guard('student')->check()) {
                    $studentAccount = Auth::guard('student')->user();
                    $student = $studentAccount->student;
                    $studentHasGroup = $student && $student->groups()->exists();
                } elseif (session('is_student') && session('student_id')) {
                    $student = \App\Models\Student::find(session('student_id'));
                    $studentHasGroup = $student && $student->groups()->exists();
                }
            @endphp
            
            @if(!$studentHasGroup)
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('student.group.invitations') || request()->is('student/group/invitations') ? 'active bg-primary' : '' }}" 
                   href="{{ route('student.group.invitations') }}">
                    <i class="fas fa-envelope me-2"></i>
                    Group Invitations
                </a>
            </li>
            @endif
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('student.project*') || request()->is('student/project*') ? 'active bg-primary' : '' }}" 
                   href="{{ route('student.project') }}">
                    <i class="fas fa-upload me-2"></i>
                    Quick File Uploads
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('student.proposal*') || request()->is('student/proposal*') ? 'active bg-primary' : '' }}" 
                   href="{{ route('student.proposal') }}">
                    <i class="fas fa-file-contract me-2"></i>
                    Project Proposals
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('student.milestones*') || request()->is('student/milestones*') ? 'active bg-primary' : '' }}" 
                   href="{{ route('student.milestones') }}">
                    <i class="fas fa-flag me-2"></i>
                    Milestones
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('student.defense-requests*') || request()->is('student/defense-requests*') ? 'active bg-primary' : '' }}" 
                   href="{{ route('student.defense-requests.index') }}">
                    <i class="fas fa-gavel me-2"></i>
                    Defense Requests
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('student.calendar') || request()->is('student/calendar') ? 'active bg-primary' : '' }}" 
                   href="{{ route('student.calendar') }}">
                    <i class="fas fa-calendar me-2"></i>
                    Calendar
                </a>
            </li>
        </ul>
    </nav>
    <div class="p-3 border-top border-secondary">
        <div class="small text-muted">
            <div>Current Route: {{ request()->route()->getName() ?? 'Unknown' }}</div>
            <div>URL: {{ request()->url() }}</div>
        </div>
    </div>
    <div class="mt-auto p-3 border-top border-secondary">
        <div class="d-flex align-items-center justify-content-between">
            <div class="small">
                <div class="text-muted">{{ $studentName }}</div>
                <div class="text-muted">Student</div>
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
