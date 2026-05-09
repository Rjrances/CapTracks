{{--
    Brand row for dark sidebars: CT badge + CapTrack link + optional role pill.
    @param string $homeRoute
    @param string|null $roleLabel  e.g. Student, Coordinator, Chairperson
--}}
<div class="d-flex align-items-center flex-wrap">
    <div class="ct-badge me-2">
        <span>CT</span>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap min-w-0">
        <a class="navbar-brand fw-bold text-white text-decoration-none mb-0" href="{{ $homeRoute }}">
            CapTrack
        </a>
        @if(!empty($roleLabel))
            <span class="badge rounded-pill bg-white bg-opacity-10 text-white border border-white border-opacity-25 fw-semibold px-2 py-1 sidebar-role-badge">{{ $roleLabel }}</span>
        @endif
    </div>
</div>
