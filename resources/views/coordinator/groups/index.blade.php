@extends('layouts.coordinator')

@section('title', 'Groups')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 1200px;">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">Groups & Progress Management</h1>
                        <p class="text-muted mb-0">Monitor groups, track progress, and manage capstone projects</p>
                    </div>
                    <div class="d-flex gap-2">
                        <!-- Groups are created by students, not coordinators -->
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Groups</h5>
                        <h3 class="mb-0">{{ $groups->total() }}</h3>
                        <small>active groups</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">With Adviser</h5>
                        <h3 class="mb-0">{{ $groups->where('adviser_id', '!=', null)->count() }}</h3>
                        <small>assigned</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">No Adviser</h5>
                        <h3 class="mb-0">{{ $groups->where('adviser_id', null)->count() }}</h3>
                        <small>unassigned</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Students</h5>
                        <h3 class="mb-0">{{ $groups->sum(function($group) { return $group->members->count(); }) }}</h3>
                        <small>enrolled</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-search me-2"></i>Search & Filter Groups
                        </h5>
                    </div>
                    <div class="card-body">
                        <form class="d-flex" method="GET" action="">
                            <input type="text" name="search" class="form-control me-2" placeholder="Search groups by name or description..." value="{{ request('search') }}">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>



        <!-- Main Content Row -->
        <div class="row mb-4">
            <!-- Groups Table -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>Groups List
                            </h5>
                            <span class="badge bg-primary">{{ $groups->count() }} groups</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($groups->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Group Name</th>
                                            <th>Members</th>
                                            <th>Adviser</th>
                                            <th>Progress</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($groups as $group)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $group->name }}</div>
                                                    <small class="text-muted">{{ Str::limit($group->description, 50) ?? 'No description' }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">{{ $group->members->count() }} members</span>
                                                </td>
                                                <td>
                                                    @if($group->adviser)
                                                        <span class="badge bg-success">{{ $group->adviser->name }}</span>
                                                    @else
                                                        <span class="badge bg-warning">No Adviser</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($group->overall_progress_percentage !== null)
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress me-2" style="width: 60px; height: 20px;">
                                                                <div class="progress-bar {{ $group->overall_progress_percentage >= 60 ? 'bg-success' : ($group->overall_progress_percentage >= 40 ? 'bg-warning' : 'bg-danger') }}" 
                                                                     role="progressbar" 
                                                                     style="width: {{ $group->overall_progress_percentage }}%" 
                                                                     aria-valuenow="{{ $group->overall_progress_percentage }}" 
                                                                     aria-valuemin="0" aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                            <span class="badge {{ $group->overall_progress_percentage >= 60 ? 'bg-success' : ($group->overall_progress_percentage >= 40 ? 'bg-warning' : 'bg-danger') }}">
                                                                {{ $group->overall_progress_percentage }}%
                                                            </span>
                                                        </div>
                                                    @else
                                                        <span class="badge bg-secondary">No Progress</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('coordinator.groups.show', $group->id) }}" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('coordinator.groups.assignAdviser', $group->id) }}" class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-user-plus"></i>
                                                        </a>
                                                        <a href="{{ route('coordinator.groups.milestones', $group->id) }}" class="btn btn-sm btn-outline-success" title="View Milestones">
                                                            <i class="fas fa-flag"></i>
                                                        </a>

                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="d-flex justify-content-center mt-3">
                                {{ $groups->links() }}
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No groups found</h6>
                                <p class="text-muted small">Students will create groups when they register for capstone projects.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar - Group Details -->
            <div class="col-md-4">
                <!-- Group Statistics -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Group Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h4 class="text-primary mb-0">{{ $groups->where('adviser_id', '!=', null)->count() }}</h4>
                                    <small class="text-muted">With Adviser</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h4 class="text-warning mb-0">{{ $groups->where('adviser_id', null)->count() }}</h4>
                                <small class="text-muted">No Adviser</small>
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <h6 class="text-muted">Total Students</h6>
                            <h3 class="text-info">{{ $groups->sum(function($group) { return $group->members->count(); }) }}</h3>
                        </div>
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
                            <a href="{{ route('coordinator.defense.index') }}" class="btn btn-outline-info">
                                <i class="fas fa-calendar me-2"></i>Defense Scheduling
                            </a>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 