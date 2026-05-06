@php
    $pendingGroupInvitationsCount = 0;
    if (\Illuminate\Support\Facades\Auth::guard('student')->check()) {
        $studentAccount = \Illuminate\Support\Facades\Auth::guard('student')->user();
        $student = $studentAccount->student;
        $studentName = $student->name ?? 'Student';
        if ($student) {
            $pendingGroupInvitationsCount = \App\Models\GroupInvitation::where('student_id', $student->student_id)
                ->where('status', 'pending')
                ->count();
        }
    } else {
        $studentName = 'Guest';
    }
    $activeTerm = \App\Models\AcademicTerm::where('is_active', true)->first();
@endphp
<div class="sidebar bg-dark text-white" style="width: 280px; min-height: 100vh; position: fixed; left: 0; top: 0; z-index: 1000;">
    <div class="p-3 border-bottom border-secondary">
        <div class="d-flex align-items-center">
            <div class="ct-badge me-2">
                <span>CT</span>
            </div>
            <a class="navbar-brand fw-bold text-white text-decoration-none mb-0" href="{{ route('student.dashboard') }}">
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
                }
            @endphp
            
            @if(!$studentHasGroup)
            <li class="nav-item mb-2">
                <a class="nav-link text-white d-flex align-items-center justify-content-between gap-2 {{ request()->routeIs('student.group.invitations') || request()->is('student/group/invitations') ? 'active bg-primary' : '' }}"
                   href="{{ route('student.group.invitations') }}">
                    <span class="d-flex align-items-center text-truncate">
                        <i class="fas fa-envelope me-2 flex-shrink-0"></i>
                        Group Invitations
                    </span>
                    @if($pendingGroupInvitationsCount > 0)
                        <span class="sidebar-invitation-badge flex-shrink-0">{{ $pendingGroupInvitationsCount > 99 ? '99+' : $pendingGroupInvitationsCount }}</span>
                    @endif
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
