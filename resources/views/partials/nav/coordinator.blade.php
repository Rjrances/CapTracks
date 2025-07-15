@php
    $user = auth()->user();
@endphp

<nav class="navbar navbar-expand-lg px-4" style="background-color: #182A56;">
    <div class="d-flex align-items-center">
        <div style="width:32px; height:32px; margin-right:10px;"></div> <!-- Empty logo space -->
        <a class="navbar-brand fw-bold text-white" href="{{ route('coordinator.dashboard') }}">CapTrack</a>
    </div>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavCoordinator"
        aria-controls="navbarNavCoordinator" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNavCoordinator">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link text-white" href="{{ route('coordinator.dashboard') }}">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="{{ route('coordinator.classlist.index') }}">Class List</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="{{ route('coordinator.milestones.index') }}">Milestones</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="{{ route('coordinator.defense.scheduling') }}">Defense Scheduling</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="{{ route('coordinator.groups.index') }}">Groups</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="{{ route('coordinator.events.index') }}">Events</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="{{ route('coordinator.notifications') }}">Notifications</a></li>
            <li class="nav-item d-flex align-items-center text-white px-3"><span>Hi, {{ $user->name }}</span></li>
            <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                </form>
            </li>
        </ul>
    </div>
</nav>
<style>
.navbar-nav .nav-link.text-white:hover {
    text-decoration: underline;
    color: #fff;
}
</style>
