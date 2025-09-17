@extends('layouts.coordinator')
@section('title', 'Milestones - Coordinator Dashboard')
@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 1200px;">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">Milestones Overview</h1>
                        <p class="text-muted mb-0">View milestone templates and group assignments</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('coordinator.milestones.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create New Template
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
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Milestone Templates
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($milestoneTemplates->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Template Name</th>
                                            <th>Description</th>
                                            <th>Tasks</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($milestoneTemplates as $template)
                                            <tr>
                                                <td>
                                                    <strong>{{ $template->name }}</strong>
                                                </td>
                                                <td>{{ Str::limit($template->description, 100) }}</td>
                                                <td>
                                                    <span class="badge bg-info">{{ $template->tasks->count() }} tasks</span>
                                                </td>
                                                <td>
                                                    @if($template->status === 'active')
                                                        <span class="badge bg-success">Active</span>
                                                    @elseif($template->status === 'todo')
                                                        <span class="badge bg-warning">Todo</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ ucfirst($template->status) }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $template->created_at->format('M d, Y') }}</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="{{ route('coordinator.milestones.edit', $template->id) }}" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           title="Edit Template">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="{{ route('coordinator.milestones.tasks.index', $template->id) }}" 
                                                           class="btn btn-sm btn-outline-info" 
                                                           title="Manage Tasks">
                                                            <i class="fas fa-tasks"></i>
                                                        </a>
                                                        <form action="{{ route('coordinator.milestones.destroy', $template->id) }}" 
                                                              method="POST" 
                                                              class="d-inline"
                                                              onsubmit="return confirm('Are you sure you want to delete this template? This action cannot be undone.')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                    class="btn btn-sm btn-outline-danger" 
                                                                    title="Delete Template">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-flag fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No milestone templates available.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>Group Milestone Assignments
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
                                            <th>Group</th>
                                            <th>Members</th>
                                            <th>Adviser</th>
                                            <th>Assigned Milestones</th>
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
                                                    @if($group->members->count() > 0)
                                                        @foreach($group->members->take(3) as $member)
                                                            <span class="badge bg-light text-dark me-1">
                                                                {{ $member->name }}
                                                            </span>
                                                        @endforeach
                                                        @if($group->members->count() > 3)
                                                            <span class="badge bg-secondary">+{{ $group->members->count() - 3 }} more</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">No members</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($group->adviser)
                                                        <span class="badge bg-primary">{{ $group->adviser->name }}</span>
                                                    @else
                                                        <span class="badge bg-warning">No adviser</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($group->milestones->count() > 0)
                                                        @foreach($group->milestones as $milestone)
                                                            <div class="mb-1">
                                                                <span class="badge bg-info me-1">
                                                                    {{ $milestone->template ? $milestone->template->name : 'Unknown Template' }}
                                                                </span>
                                                                <small class="text-muted">
                                                                    Due: {{ $milestone->target_date ? \Carbon\Carbon::parse($milestone->target_date)->format('M d, Y') : 'Not set' }}
                                                                </small>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <span class="text-muted">No milestones assigned</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($group->overall_progress_percentage !== null)
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar" role="progressbar" 
                                                                 style="width: {{ $group->overall_progress_percentage }}%"
                                                                 aria-valuenow="{{ $group->overall_progress_percentage }}" 
                                                                 aria-valuemin="0" aria-valuemax="100">
                                                                {{ $group->overall_progress_percentage }}%
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">No progress data</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('coordinator.groups.milestones', $group->id) }}" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye me-1"></i>View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No groups available.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Milestone Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h4 class="text-primary mb-0">{{ $milestoneTemplates->count() }}</h4>
                                    <small class="text-muted">Templates</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success mb-0">{{ $groups->filter(function($group) { return $group->milestones->count() > 0; })->count() }}</h4>
                                <small class="text-muted">Groups with Milestones</small>
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <h6 class="text-muted">Total Milestone Assignments</h6>
                            <h3 class="text-info">{{ $groups->sum(function($group) { return $group->milestones->count(); }) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    $(document).ready(function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush
