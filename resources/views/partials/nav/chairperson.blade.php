@php
    $user = auth()->user();
@endphp

<nav class="navbar navbar-expand-lg px-4" style="background-color: #182A56;">
    <div class="d-flex align-items-center">
        <div style="width:32px; height:32px; margin-right:10px;"></div> <!-- Empty logo space -->
        <a class="navbar-brand fw-bold text-white" href="{{ route('chairperson.dashboard') }}">CapTrack</a>
    </div>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link text-white" href="{{ route('chairperson.offerings.index') }}">Offerings</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="{{ route('chairperson.teachers.index') }}">Teachers</a></li>
            
                            <li class="nav-item"><a class="nav-link text-white" href="{{ route('chairperson.roles.index') }}">Roles</a></li>


            <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-light btn-sm">Logout</button>
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
