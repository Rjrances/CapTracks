<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="{{ route('coordinator.dashboard') }}">CapTrack - Coordinator</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#coordinatorNav" aria-controls="coordinatorNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="coordinatorNav">
            <ul class="navbar-nav ms-auto">

                {{-- Dashboard --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('coordinator/dashboard') ? 'active' : '' }}" href="{{ route('coordinator.dashboard') }}">Dashboard</a>
                </li>

                {{-- Class List --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('coordinator/classlist*') ? 'active' : '' }}" href="{{ route('coordinator.classlist.index') }}">Class List</a>
                </li>

                {{-- Milestones -- REMOVED for coordinators
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('coordinator.milestones.index') }}">Milestones</a>
                </li>
                --}}

                {{-- Scheduling --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('coordinator/defense*') ? 'active' : '' }}" href="{{ route('coordinator.defense.index') }}">Defense Schedules</a>
                </li>

                {{-- Group Management --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('coordinator/groups*') ? 'active' : '' }}" href="{{ route('coordinator.groups.index') }}">Groups</a>
                </li>

                {{-- Notifications --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('coordinator/notifications*') ? 'active' : '' }}" href="{{ route('coordinator.notifications') }}">Notifications</a>
                </li>

                {{-- Account / Profile --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('coordinator/profile*') ? 'active' : '' }}" href="{{ route('coordinator.profile') }}">Profile</a>
                </li>

            </ul>
        </div>
    </div>
</nav>
