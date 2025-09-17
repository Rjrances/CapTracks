<nav class="navbar navbar-expand-lg" style="background-color: #182A56;">
    <div class="container d-flex align-items-center">
        <div style="width:32px; height:32px; margin-right:10px;"></div> 
        <a class="navbar-brand fw-bold text-white" href="#">CapTrack</a>
        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="nav-link btn btn-link text-white p-0" style="text-decoration: none;">Logout</button>
                    </form>
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
