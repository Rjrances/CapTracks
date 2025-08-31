@extends('layouts.coordinator')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 1200px;">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">Welcome, {{ auth()->check() ? auth()->user()->name : 'Coordinator' }}!</h1>
                        <p class="text-muted mb-0">Manage capstone projects, groups, and academic activities</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('coordinator.groups.index') }}" class="btn btn-primary">
                            <i class="fas fa-users me-2"></i>Manage Groups
                        </a>
                        {{-- <a href="{{ route('coordinator.milestones.index') }}" class="btn btn-outline-primary"> --}}
                            <i class="fas fa-flag me-2"></i>Milestones
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

        @if($isTeacherCoordinator && $coordinatedOfferings->count() > 0)
        <!-- My Coordinated Offerings -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chalkboard-teacher me-2"></i>My Coordinated Offerings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($coordinatedOfferings as $offering)
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-0 text-primary">{{ $offering->subject_code }}</h6>
                                            <span class="badge bg-info">{{ $offering->academicTerm->full_name ?? 'N/A' }}</span>
                                        </div>
                                        <p class="card-text text-muted mb-3">{{ $offering->subject_title }}</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-users me-1"></i>
                                                {{ $offering->enrolled_students_count ?? 0 }} students
                                            </small>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('coordinator.groups.index') }}?offering={{ $offering->id }}" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-users me-1"></i>Groups
                                                </a>
                                                <a href="{{ route('coordinator.defense.index') }}?offering={{ $offering->id }}" 
                                                   class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-calendar me-1"></i>Scheduling
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
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
                        @if($activeTerm ?? null)
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <h4 class="mb-0 me-3">{{ $activeTerm->full_name }}</h4>
                                        <span class="badge bg-success fs-6">Active</span>
                                    </div>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Current term for all academic operations and project management
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="text-muted small">Coordinator View</span>
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
                                        Please contact the chairperson to set an active academic term
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="text-muted small">Coordinator View</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Management Overview -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Students</h5>
                        <h3 class="mb-0">{{ $studentCount ?? 0 }}</h3>
                        <small>enrolled students</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Active Groups</h5>
                        <h3 class="mb-0">{{ $groupCount ?? 0 }}</h3>
                        <small>{{ $totalGroupMembers ?? 0 }} total members</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Faculty Members</h5>
                        <h3 class="mb-0">{{ $facultyCount ?? 0 }}</h3>
                        <small>advisers & panelists</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Submissions</h5>
                        <h3 class="mb-0">{{ $submissionCount ?? 0 }}</h3>
                        <small>{{ $pendingSubmissions ?? 0 }} pending review</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Row -->
        <div class="row mb-4">
            <!-- Recent Activities -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-clock me-2"></i>Recent Activities
                            </h5>
                            <a href="{{ route('coordinator.groups.index') }}" class="btn btn-sm btn-outline-primary">
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(isset($recentActivities) && $recentActivities->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Activity</th>
                                            <th>Type</th>
                                            <th>Details</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentActivities as $activity)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $activity->title }}</div>
                                                    <small class="text-muted">{{ $activity->description }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">{{ $activity->type }}</span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">Activity details</small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No recent activities</h6>
                                <p class="text-muted small">Activities will appear here as they occur.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar - Quick Actions & Notifications -->
            <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('coordinator.groups.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create Group
                            </a>

                            <a href="{{ route('coordinator.classlist.index') }}" class="btn btn-outline-success">
                                <i class="fas fa-list me-2"></i>View Class List
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Pending Invitations -->
                <div class="card mb-3">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-envelope me-2"></i>Pending Invitations
                            </h5>
                            <span class="badge bg-warning">{{ isset($pendingInvitations) ? $pendingInvitations->count() : 0 }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(isset($pendingInvitations) && $pendingInvitations->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($pendingInvitations as $invitation)
                                    <div class="list-group-item px-0 border-0">
                                        <div class="d-flex align-items-start">
                                            <div class="me-3">
                                                <i class="fas fa-user-tie text-warning"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">{{ $invitation->faculty->name }}</h6>
                                                <small class="text-muted">{{ $invitation->group->name }}</small>
                                                <br>
                                                <small class="text-muted">{{ $invitation->created_at->diffForHumans() }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                <p class="text-muted small mb-0">No pending invitations</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- System Status -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>System Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="mb-1">Group Assignment Status</h6>
                            <p class="text-muted mb-0">{{ $groupsWithAdviser ?? 0 }} groups have advisers, {{ $groupsWithoutAdviser ?? 0 }} need assignment</p>
                            <div class="progress mt-2" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ ($groupCount ?? 0) > 0 ? (($groupsWithAdviser ?? 0) / ($groupCount ?? 1)) * 100 : 0 }}%"></div>
                            </div>
                            <small class="text-muted">{{ ($groupCount ?? 0) > 0 ? round((($groupsWithAdviser ?? 0) / ($groupCount ?? 1)) * 100) : 0 }}% assigned</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Groups and Submissions -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>Recent Groups
                            </h5>
                            <a href="{{ route('coordinator.groups.index') }}" class="btn btn-sm btn-outline-primary">
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(isset($recentGroups) && $recentGroups->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recentGroups as $group)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ $group->name }}</h6>
                                            <small class="text-muted">{{ $group->members->count() }} members</small>
                                            @if($group->adviser)
                                                <br>
                                                <small class="text-success">
                                                    <i class="fas fa-user-tie me-1"></i>{{ $group->adviser->name }}
                                                </small>
                                            @else
                                                <br>
                                                <small class="text-warning">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>No adviser
                                                </small>
                                            @endif
                                        </div>
                                        <a href="{{ route('coordinator.groups.show', $group) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No groups yet</h6>
                                <p class="text-muted small">Groups will appear here when created.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt me-2"></i>Recent Submissions
                            </h5>
                            <span class="badge bg-primary">{{ isset($recentSubmissions) ? $recentSubmissions->count() : 0 }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(isset($recentSubmissions) && $recentSubmissions->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recentSubmissions as $submission)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ ucfirst($submission->type) }} Submission</h6>
                                            <small class="text-muted">{{ $submission->student->name }}</small>
                                            <br>
                                            <span class="badge bg-{{ $submission->status === 'approved' ? 'success' : ($submission->status === 'rejected' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($submission->status) }}
                                            </span>
                                        </div>
                                        <small class="text-muted">{{ $submission->created_at->diffForHumans() }}</small>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No submissions yet</h6>
                                <p class="text-muted small">Submissions will appear here when students upload files.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>


    </div>
</div>
@endsection
