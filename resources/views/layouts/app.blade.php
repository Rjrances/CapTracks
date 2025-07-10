<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CapTrack Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
    <a class="navbar-brand" href="#">CapTrack</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
        <ul class="navbar-nav align-items-center">
            @if (Auth::check() && Auth::user()->role === 'chairperson')
                <li class="nav-item">
                    <a class="nav-link" href="/chairperson/offerings">Offerings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/chairperson/teachers">Teachers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/chairperson/schedules">Schedules</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/manage-roles">Manage Roles</a>
                </li>
            @endif
            <li class="nav-item ms-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-light btn-sm" type="submit">Logout</button>
                </form>
            </li>
        </ul>
    </div>
</nav>


    <main class="py-4 container">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
