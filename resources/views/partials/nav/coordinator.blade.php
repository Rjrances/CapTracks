@php
    $user = auth()->user();
@endphp

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #182A56;">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">Cap Track</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="{{ route('coordinator.dashboard') }}">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('coordinator.classlist.index') }}">Class List</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('coordinator.milestones.index') }}">Milestones</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('coordinator.defense.scheduling') }}">Defense Scheduling</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('coordinator.groups.index') }}">Groups</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('coordinator.events.index') }}">Events</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('coordinator.notifications') }}">Notifications</a></li>
            </ul>
            <span class="navbar-text me-3">Hi, {{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
            </form>
        </div>
    </div>
</nav>
