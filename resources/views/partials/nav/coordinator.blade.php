@php
    $user = auth()->user();
@endphp

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
    <a class="navbar-brand" href="{{ route('coordinator.dashboard') }}">
        CapTrack
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavCoordinator"
        aria-controls="navbarNavCoordinator" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNavCoordinator">
        <ul class="navbar-nav">

            {{-- Navigation Links --}}
            <li class="nav-item">
                <a class="nav-link" href="{{ route('coordinator.dashboard') }}">
                    Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('coordinator.classlist.index') }}">
                    Class List
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('coordinator.milestones.index') }}">
                    Milestones
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('coordinator.defense.scheduling') }}">
                    Defense Scheduling
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('coordinator.groups.index') }}">
                    Groups
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('coordinator.notifications') }}">
                    Notifications
                </a>
            </li>

            {{-- User Greeting --}}
            <li class="nav-item d-flex align-items-center text-white px-3">
                <span>Hi, {{ $user->name }}</span>
            </li>

            {{-- Logout --}}
            <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">
                        Logout
                    </button>
                </form>
            </li>

        </ul>
    </div>
</nav>
