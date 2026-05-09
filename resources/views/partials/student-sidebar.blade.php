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
        @include('partials.sidebar-brand', [
            'homeRoute' => route('student.dashboard'),
            'roleLabel' => 'Student',
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
        @php
            $isMyGroupActive = request()->routeIs('student.group')
                || request()->routeIs('student.group.index')
                || request()->routeIs('student.group.create')
                || request()->routeIs('student.group.edit');
            $isGroupInvitationsActive = request()->routeIs('student.group.invitations');
        @endphp
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ request()->routeIs('student.dashboard') || request()->is('student/dashboard') ? 'active bg-primary' : '' }}" 
                   href="{{ route('student.dashboard') }}">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white {{ $isMyGroupActive ? 'active bg-primary' : '' }}" 
                   href="{{ route('student.group') }}">
                    <i class="fas fa-users me-2"></i>
                    My Group
                </a>
            </li>
            @php
                
                $studentHasGroup = false;
                if (Auth::guard('student')->check()) {
                    $studentAccount = Auth::guard('student')->user();
                    $student = $studentAccount->student;
                    $studentHasGroup = $student && $student->groups()->exists();
                }
            @endphp
            
            @if(!$studentHasGroup)
            <li class="nav-item mb-2">
                <a class="nav-link text-white d-flex align-items-center justify-content-between gap-2 {{ $isGroupInvitationsActive ? 'active bg-primary' : '' }}"
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
@include('partials.dark-sidebar-shared-styles')
