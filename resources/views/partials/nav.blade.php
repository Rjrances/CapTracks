<nav class="navbar navbar-expand-lg" style="background-color: #182A56;">
    <div class="container d-flex align-items-center">
        <div style="width:32px; height:32px; margin-right:10px;"></div> <!-- Empty logo space -->
        <a class="navbar-brand fw-bold text-white" href="#">CapTrack</a>
        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link text-white" href="{{ route('logout') }}">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<style>
.navbar-nav .nav-link.text-white:hover {
    text-decoration: underline;
    color: #fff;
}
</style>
