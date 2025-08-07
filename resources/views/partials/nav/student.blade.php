@php
    // Get user info from either Auth or session
    if (auth()->check()) {
        $user = auth()->user();
        $userName = $user->name;
    } else {
        $userName = session('student_name');
    }
@endphp

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #182A56;">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="/student/dashboard">CapTrack</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/student/dashboard">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="/student/project">Project</a></li>
                <li class="nav-item"><a class="nav-link" href="/student/group">Group</a></li>
                <li class="nav-item"><a class="nav-link" href="/student/proposal">Proposal & Endorsement</a></li>
                <li class="nav-item"><a class="nav-link" href="/student/milestones">Milestones</a></li>
            </ul>
            <span class="navbar-text me-3">Hi, {{ $userName }}</span>
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
            </form>
        </div>
    </div>
</nav>
