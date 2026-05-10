@extends('layouts.chairperson')
@section('title', 'Chairperson Dashboard')
@section('content')
<div class="chairperson-dashboard-page container-fluid mt-4">
    <div class="mb-4">
        <p class="text-muted mb-0">Welcome back, {{ auth()->check() ? auth()->user()->name : 'Chairperson' }}! Oversee capstone projects and academic operations</p>
    </div>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Current Academic Term Context
                    </h5>
                </div>
                <div class="card-body">
                    @if($activeTerm)
                        <div>
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <h4 class="mb-0">{{ $activeTerm->semester }}</h4>
                                    <span class="badge bg-success fs-6">Active</span>
                                </div>
                                <a href="{{ route('chairperson.academic-terms.index') }}" class="btn btn-outline-primary btn-sm flex-shrink-0">
                                    <i class="fas fa-exchange-alt me-1"></i>
                                    Switch or activate term
                                </a>
                            </div>
                            <p class="text-muted mb-0">
                                <i class="fas fa-info-circle me-1"></i>
                                Current term for all academic operations and scheduling
                            </p>
                        </div>
                    @else
                        <div>
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <h4 class="mb-0 text-warning">No Active Term</h4>
                                    <span class="badge bg-warning fs-6">Inactive</span>
                                </div>
                                <a href="{{ route('chairperson.academic-terms.index') }}" class="btn btn-warning btn-sm flex-shrink-0">
                                    <i class="fas fa-calendar-check me-1"></i>
                                    Activate a term
                                </a>
                            </div>
                            <p class="text-muted mb-0">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Please set an active academic term to continue operations
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @if($activeTerm)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient-warning text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Defense Statistics ({{ $activeTerm->semester }})
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="border-end">
                                <h3 class="text-primary mb-1">{{ $stats['totalDefenses'] ?? 0 }}</h3>
                                <p class="text-muted mb-0">Total Defenses</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border-end">
                                <h3 class="text-success mb-1">{{ $stats['completedDefenses'] ?? 0 }}</h3>
                                <p class="text-muted mb-0">Completed</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div>
                                <h3 class="text-warning mb-1">{{ $stats['pendingReviews'] ?? 0 }}</h3>
                                <p class="text-muted mb-0">Pending</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-project-diagram me-2"></i>Active Projects
                    </h5>
                </div>
                <div class="card-body text-center">
                    <h2 class="text-primary mb-1">{{ $stats['activeProjects'] ?? 0 }}</h2>
                    <p class="text-muted mb-0">Capstone Projects</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-gradient-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Faculty Members
                    </h5>
                </div>
                <div class="card-body text-center">
                    <h2 class="text-success mb-1">{{ $stats['facultyCount'] ?? 0 }}</h2>
                    <p class="text-muted mb-0">Active Faculty</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-gradient-warning text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-gavel me-2"></i>Pending Defenses
                    </h5>
                </div>
                <div class="card-body text-center">
                    <h2 class="text-warning mb-1">{{ $stats['pendingReviews'] ?? 0 }}</h2>
                    <p class="text-muted mb-0">Scheduled</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-gradient-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-book me-2"></i>Course Offerings
                    </h5>
                </div>
                <div class="card-body text-center">
                    <h2 class="text-info mb-1">{{ $stats['offeringsCount'] ?? 0 }}</h2>
                    <p class="text-muted mb-0">Active Sections</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Scoped to this page only — unscoped rules were leaking and shifting global UI vs other chairperson routes */
.chairperson-dashboard-page .bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.chairperson-dashboard-page .bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}

.chairperson-dashboard-page .bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
}

.chairperson-dashboard-page .bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
}

.chairperson-dashboard-page .card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.2s ease-in-out;
}

.chairperson-dashboard-page .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.chairperson-dashboard-page .card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.chairperson-dashboard-page .btn-outline-primary:hover,
.chairperson-dashboard-page .btn-outline-secondary:hover,
.chairperson-dashboard-page .btn-outline-warning:hover,
.chairperson-dashboard-page .btn-outline-info:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.chairperson-dashboard-page .table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.chairperson-dashboard-page .badge {
    font-size: 0.75em;
}

.chairperson-dashboard-page .alert {
    border: none;
    border-radius: 0.5rem;
}

.chairperson-dashboard-page .list-group-item {
    border: none;
    padding: 0.75rem 0;
}

.chairperson-dashboard-page .list-group-item:not(:last-child) {
    border-bottom: 1px solid #f8f9fa;
}

.chairperson-dashboard-page .avatar-sm {
    width: 40px;
    height: 40px;
    font-size: 16px;
}

.chairperson-dashboard-page .text-primary { color: #007bff !important; }
.chairperson-dashboard-page .text-success { color: #28a745 !important; }
.chairperson-dashboard-page .text-warning { color: #ffc107 !important; }
.chairperson-dashboard-page .text-info { color: #17a2b8 !important; }
</style>
@endsection