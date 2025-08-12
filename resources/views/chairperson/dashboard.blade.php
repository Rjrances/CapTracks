@extends('layouts.chairperson')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 1200px;">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Welcome, {{ auth()->check() ? auth()->user()->name : 'Chairperson' }}!</h1>
                    <p class="text-muted mb-0">Oversee capstone projects and academic operations</p>
                </div>
                <div class="d-flex gap-2">

                    <a href="{{ route('chairperson.offerings.index') }}" class="btn btn-outline-primary">
                        <i class="fas fa-book me-2"></i>Manage Offerings
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Current Academic Term Context -->
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

    <!-- Department Overview Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Active Projects</h5>
                    <h3 class="mb-0">{{ $stats['activeProjects'] ?? 0 }}</h3>
                    <small>capstone projects</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Faculty Members</h5>
                    <h3 class="mb-0">{{ $stats['facultyCount'] ?? 0 }}</h3>
                    <small>active advisers</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Pending Defenses</h5>
                    <h3 class="mb-0">{{ $stats['pendingReviews'] ?? 0 }}</h3>
                    <small>scheduled</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Course Offerings</h5>
                    <h3 class="mb-0">{{ $stats['offeringsCount'] ?? 0 }}</h3>
                    <small>active sections</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row mb-4">
        <!-- Upcoming Defense Schedules -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-gavel me-2"></i>Upcoming Defense Schedules
                        </h5>

                    </div>
                </div>
                <div class="card-body">
                    @if($upcomingDefenses->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Group</th>
                                        <th>Stage</th>
                                        <th>Date & Time</th>
                                        <th>Room</th>
                                        <th>Status</th>
                                        <th>Actions</th>
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
                                                <div class="fw-semibold">{{ $defense->scheduled_date->format('M d, Y') }}</div>
                                                <small class="text-muted">
                                                    {{ \Carbon\Carbon::parse($defense->scheduled_time)->format('h:i A') }}
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

        <!-- Sidebar - Notifications & Quick Actions -->
        <div class="col-md-4">
            <!-- Latest Notifications -->
            <div class="card mb-3">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-bell me-2"></i>Latest Notifications
                        </h5>
                        <span class="badge bg-primary">{{ $notifications->count() }}</span>
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

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
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
                            <i class="fas fa-user-tag me-2"></i>Roles
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Defense Statistics Row -->
    @if($activeTerm)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
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

    <!-- Upcoming Events -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar me-2"></i>Upcoming Events
                    </h5>
                </div>
                <div class="card-body">
                    @if($events->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Event</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($events as $event)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $event->title }}</div>
                                                <small class="text-muted">{{ $event->description ?? 'No description available' }}</small>
                                            </td>
                                            <td>
                                                <span class="text-primary">
                                                    {{ $event->date ? \Carbon\Carbon::parse($event->date)->format('M d, Y') : 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $event->time ? \Carbon\Carbon::parse($event->time)->format('h:i A') : 'N/A' }}
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $event->type == 'meeting' ? 'primary' : ($event->type == 'deadline' ? 'warning' : 'info') }}">
                                                    {{ ucfirst($event->type ?? 'event') }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('events.show', $event->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No upcoming events</h6>
                            <p class="text-muted small">Events will appear here when they are scheduled.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
