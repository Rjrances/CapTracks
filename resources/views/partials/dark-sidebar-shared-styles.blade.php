<style>
/* Uniform dark sidebar layout across Chairperson, Coordinator, Adviser, Student */
.sidebar-role-badge {
    font-size: 0.65rem;
    letter-spacing: 0.02em;
    line-height: 1.2;
}
.sidebar > .border-bottom:first-of-type {
    min-height: 3.25rem;
    display: flex;
    align-items: center;
}
.sidebar .nav.flex-column {
    --sidebar-nav-min-height: 2.75rem;
}
.sidebar .nav-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-height: var(--sidebar-nav-min-height);
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    transition: background-color 0.2s ease, color 0.2s ease;
    /* Same label size when active vs inactive (Bootstrap defaults differ) */
    font-size: 0.9375rem;
    font-weight: 500;
    line-height: 1.35;
}
.sidebar .nav-link:hover,
.sidebar .nav-link:focus,
.sidebar .nav-link:focus-visible {
    font-size: 0.9375rem;
    font-weight: 500;
}
.sidebar .nav-link.active,
.sidebar .nav-link.bg-primary {
    font-size: 0.9375rem !important;
    font-weight: 500 !important;
}
.sidebar .nav-link.d-flex {
    gap: 0.5rem;
}
.sidebar .navbar-brand {
    padding-top: 0;
    padding-bottom: 0;
    line-height: 1.2;
}
.sidebar .nav-link > i:first-child {
    width: 1.25rem;
    flex-shrink: 0;
    text-align: center;
    font-size: 1em;
    line-height: 1;
}
.sidebar .nav-link .text-truncate i {
    font-size: 1em;
    line-height: 1;
}
.sidebar .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
    text-decoration: none;
}
.sidebar .nav-link.active,
.sidebar .nav-link.bg-primary {
    background-color: #0d6efd !important;
    color: white !important;
}
.ct-badge {
    width: 34px;
    height: 34px;
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: white;
    letter-spacing: 0.5px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.4);
}
.sidebar {
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}
.sidebar-invitation-badge {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    min-width: 1.35rem;
    height: 1.35rem;
    padding: 0 0.4rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    border-radius: 9999px;
    color: #fff;
    background: linear-gradient(145deg, #dc3545 0%, #b02a37 100%);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.35);
}
.sidebar .nav-link.active .sidebar-invitation-badge {
    background: rgba(255, 255, 255, 0.22);
    color: #fff;
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.15);
}
</style>
