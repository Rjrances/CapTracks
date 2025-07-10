<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chairperson Dashboard - CapTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
        <a class="navbar-brand" href="{{ route('chairperson.dashboard') }}">CapTrack</a>
        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="{{ route('chairperson.offerings') }}">Offerings</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('chairperson.teachers') }}">Teachers</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('chairperson.schedules') }}">Schedules</a></li>
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-outline-light btn-sm ms-2">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>

    <main class="py-4 container">
        @yield('content')
    </main>
</body>
</html>
