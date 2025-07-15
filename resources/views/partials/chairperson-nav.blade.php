<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="#">Chairperson</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#chairNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="chairNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('chairperson.dashboard') }}">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('chairperson.manage-roles') }}">Manage Roles</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('chairperson.offerings.index') }}">Offerings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('chairperson.schedules.index') }}">Schedules</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('chairperson.teachers.index') }}">Teachers</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
