@extends('layouts.chairperson')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </h2>
            <p class="text-muted mb-0">Welcome back, {{ auth()->check() ? auth()->user()->name : 'Chairperson' }}! Oversee capstone projects and academic operations</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('chairperson.offerings.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-book me-2"></i>Manage Offerings
            </a>
            <a href="{{ route('chairperson.roles.index') }}" class="btn btn-outline-warning">
                <i class="fas fa-user-tag me-2"></i>Manage Roles
            </a>
        </div>
    </div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
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
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center mb-2">
                                    <h4 class="mb-0 me-3">{{ $activeTerm->full_name }}</h4>
                                    <span class="badge bg-success fs-6">Active</span>
                                </div>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Current term for all academic operations and scheduling
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="{{ route('chairperson.academic-terms.index') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-cog me-1"></i> Manage Terms
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center mb-2">
                                    <h4 class="mb-0 me-3 text-warning">No Active Term</h4>
                                    <span class="badge bg-warning fs-6">Inactive</span>
                                </div>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Please set an active academic term to continue operations
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="{{ route('chairperson.academic-terms.index') }}" class="btn btn-warning">
                                    <i class="fas fa-plus me-1"></i> Set Active Term
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- Statistics Cards -->
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
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-gavel me-2"></i>Upcoming Defense Schedules
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($upcomingDefenses->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0">
                                            <i class="fas fa-users me-2"></i>Group
                                        </th>
                                        <th class="border-0">
                                            <i class="fas fa-layer-group me-2"></i>Stage
                                        </th>
                                        <th class="border-0">
                                            <i class="fas fa-calendar-alt me-2"></i>Date & Time
                                        </th>
                                        <th class="border-0">
                                            <i class="fas fa-door-open me-2"></i>Room
                                        </th>
                                        <th class="border-0">
                                            <i class="fas fa-info-circle me-2"></i>Status
                                        </th>
                                        <th class="border-0">
                                            <i class="fas fa-tools me-2"></i>Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingDefenses as $defense)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $defense->group->name ?? 'N/A' }}</div>
                                                <small class="text-muted">
                                                    {{ $defense->group->members->count() ?? 0 }} members
                                                    @if($defense->group->adviser)
                                                        • {{ $defense->group->adviser->name }}
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $defense->defense_type == '60_percent' ? 'warning' : ($defense->defense_type == '100_percent' ? 'danger' : 'info') }}">
                                                    {{ $defense->defense_type_label }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $defense->start_at ? $defense->start_at->format('M d, Y') : 'TBA' }}</div>
                                                <small class="text-muted">
                                                    {{ $defense->start_at ? $defense->start_at->format('h:i A') : 'TBA' }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $defense->room }}</span>
                                            </td>
                                            <td>
                                                @if($defense->status == 'scheduled')
                                                    <span class="badge bg-primary">Scheduled</span>
                                                @elseif($defense->status == 'completed')
                                                    <span class="badge bg-success">Completed</span>
                                                @else
                                                    <span class="badge bg-secondary">Cancelled</span>
                                                @endif
                                            </td>
                                            <td>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-gavel fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No upcoming defense schedules</h6>
                            <p class="text-muted small">Defense schedules for the next 30 days will appear here.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3 shadow-sm border-0">
                <div class="card-header bg-gradient-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-bell me-2"></i>Latest Notifications
                        </h5>
                        <span class="badge bg-light text-dark">{{ $notifications->count() }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($notifications->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($notifications as $notification)
                                <div class="list-group-item px-0 border-0">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <i class="fas fa-{{ $notification->icon ?? 'info-circle' }} text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $notification->title }}</h6>
                                            <p class="text-muted small mb-1">{{ $notification->message ?? '' }}</p>
                                            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-bell fa-2x text-muted mb-2"></i>
                            <p class="text-muted small mb-0">No notifications</p>
                        </div>
                    @endif
                </div>
            </div>
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('chairperson.offerings.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-book me-2"></i>Manage Offerings
                        </a>
                        <a href="{{ route('chairperson.teachers.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-chalkboard-teacher me-2"></i>View Teachers
                        </a>
                        <a href="{{ route('chairperson.roles.index') }}" class="btn btn-outline-warning">
                            <i class="fas fa-user-tag me-2"></i>Manage Roles
                        </a>
                        <a href="{{ route('chairperson.students.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-user-graduate me-2"></i>View Students
                        </a>
                    </div>
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
                        <i class="fas fa-chart-bar me-2"></i>Defense Statistics ({{ $activeTerm->full_name }})
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
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.btn-outline-primary:hover,
.btn-outline-secondary:hover,
.btn-outline-warning:hover,
.btn-outline-info:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.badge {
    font-size: 0.75em;
}

.alert {
    border: none;
    border-radius: 0.5rem;
}

.list-group-item {
    border: none;
    padding: 0.75rem 0;
}

.list-group-item:not(:last-child) {
    border-bottom: 1px solid #f8f9fa;
}

.avatar-sm {
    width: 40px;
    height: 40px;
    font-size: 16px;
}

.text-primary { color: #007bff !important; }
.text-success { color: #28a745 !important; }
.text-warning { color: #ffc107 !important; }
.text-info { color: #17a2b8 !important; }
</style>
@endsection


