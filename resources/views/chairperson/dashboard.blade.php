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
                    <a href="{{ route('chairperson.offerings.index') }}" class="btn btn-primary">
                        <i class="fas fa-book me-2"></i>Manage Offerings
                    </a>
                    <a href="{{ route('chairperson.teachers.index') }}" class="btn btn-outline-primary">
                        <i class="fas fa-chalkboard-teacher me-2"></i>View Teachers
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

    <!-- Department Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Active Projects</h5>
                    <h3 class="mb-0">{{ $activeProjects ?? 0 }}</h3>
                    <small>capstone projects</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Faculty Members</h5>
                    <h3 class="mb-0">{{ $facultyCount ?? 0 }}</h3>
                    <small>active advisers</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Pending Reviews</h5>
                    <h3 class="mb-0">{{ $pendingReviews ?? 0 }}</h3>
                    <small>awaiting approval</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Course Offerings</h5>
                    <h3 class="mb-0">{{ $offeringsCount ?? 0 }}</h3>
                    <small>active sections</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Academic Period -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Current Academic Period
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">{{ $academicPeriod['name'] ?? 'Current Academic Period' }}</h6>
                            <p class="text-muted mb-0">{{ $academicPeriod['description'] ?? 'No description available' }}</p>
                        </div>
                        <div class="text-end">
                            <div class="progress mb-2" style="width: 150px; height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ $academicPeriod['progress'] ?? 0 }}%"></div>
                            </div>
                            <small class="text-muted">{{ $academicPeriod['progress'] ?? 0 }}% complete</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>Recent Activities
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($recentActivities) && $recentActivities->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentActivities as $activity)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $activity->title }}</h6>
                                        <small class="text-muted">{{ $activity->description }}</small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-{{ $activity->type == 'approval' ? 'success' : ($activity->type == 'review' ? 'warning' : 'info') }}">
                                            {{ ucfirst($activity->type) }}
                                        </span>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No recent activities</h6>
                            <p class="text-muted small">Activities will appear here as they occur.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-4">
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
                        <a href="{{ route('chairperson.schedules.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-calendar me-2"></i>View Schedules
                        </a>
                        <a href="{{ route('chairperson.upload-form') }}" class="btn btn-outline-success">
                            <i class="fas fa-upload me-2"></i>Import Students
                        </a>
                        <a href="{{ url('/chairperson/manage-roles') }}" class="btn btn-outline-warning">
                            <i class="fas fa-user-cog me-2"></i>Manage Roles
                        </a>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bell me-2"></i>Notifications
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($notifications) && $notifications->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($notifications as $notification)
                                <div class="list-group-item px-0">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <i class="fas fa-{{ $notification->icon ?? 'info-circle' }} text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $notification->title }}</h6>
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
        </div>
    </div>

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
                    @if(isset($events) && $events->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
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
</div>
@endsection
