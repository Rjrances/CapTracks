@php
    $user = auth()->user();
@endphp

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
    <a class="navbar-brand" href="{{ route('chairperson.dashboard') }}">CapTrack</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" href="{{ route('chairperson.offerings') }}">Offerings</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('chairperson.teachers') }}">Teachers</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('chairperson.schedules') }}">Schedules</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ url('/chairperson/manage-roles') }}">Manage Roles</a></li>

            <li class="nav-item d-flex align-items-center text-white px-2">
                Hi, {{ $user->name }}
            </li>
            <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-light btn-sm">Logout</button>
                </form>
            </li>
        </ul>
    </div>
</nav>
