@extends('layouts.coordinator')
@section('title', 'Milestones - Coordinator Dashboard')
@section('content')
<div class="container-fluid">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-4 pb-5">
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
        @if($activeTerm)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-calendar me-2"></i>
                        Showing milestone assignments for: <strong>{{ $activeTerm->semester }}</strong>
                    </div>
                </div>
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
            <div class="col-12">
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
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 20%;">Group</th>
                                            <th style="width: 20%;">Members</th>
                                            <th style="width: 15%;">Adviser</th>
                                            <th style="width: 25%;">Assigned Milestones</th>
                                            <th style="width: 10%;">Progress</th>
                                            <th style="width: 10%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($groups as $group)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-truncate" style="max-width: 200px;" title="{{ $group->name }}">
                                                        {{ $group->name }}
                                                    </div>
                                                    <small class="text-muted d-block text-truncate" style="max-width: 200px;" title="{{ $group->description }}">
                                                        {{ $group->description ?? 'No description' }}
                                                    </small>
                                                </td>
                                                <td>
                                                    @if($group->members->count() > 0)
                                                        <div class="d-flex flex-column gap-1">
                                                            @foreach($group->members->take(2) as $member)
                                                                <span class="text-truncate" style="max-width: 150px;" title="{{ $member->name }}">
                                                                    {{ $member->name }}
                                                                </span>
                                                            @endforeach
                                                            @if($group->members->count() > 2)
                                                                <small class="text-muted">+{{ $group->members->count() - 2 }} more</small>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-muted">No members</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($group->adviser)
                                                        <span class="badge bg-primary text-truncate d-inline-block" style="max-width: 150px;" title="{{ $group->adviser->name }}">
                                                            {{ $group->adviser->name }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning">No adviser</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($group->milestones->count() > 0)
                                                        <div class="d-flex flex-column gap-1">
                                                            @foreach($group->milestones as $milestone)
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span class="badge bg-info text-truncate" style="max-width: 180px;" title="{{ $milestone->template ? $milestone->template->name : 'Unknown Template' }}">
                                                                        {{ $milestone->template ? $milestone->template->name : 'Unknown' }}
                                                                    </span>
                                                                    <small class="text-muted text-nowrap">
                                                                        Due: {{ $milestone->target_date ? \Carbon\Carbon::parse($milestone->target_date)->format('M d') : 'Not set' }}
                                                                    </small>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-muted">No milestones assigned</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($group->overall_progress_percentage !== null)
                                                        <div class="progress" style="height: 20px; min-width: 60px;">
                                                            <div class="progress-bar" role="progressbar" 
                                                                 style="width: {{ $group->overall_progress_percentage }}%"
                                                                 aria-valuenow="{{ $group->overall_progress_percentage }}" 
                                                                 aria-valuemin="0" aria-valuemax="100">
                                                                <small>{{ $group->overall_progress_percentage }}%</small>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('coordinator.groups.milestones', $group->id) }}" 
                                                       class="btn btn-sm btn-outline-primary text-nowrap">
                                                        <i class="fas fa-eye"></i> Details
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
        </div>
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Milestone Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="p-3">
                                    <h2 class="text-primary mb-1">{{ $milestoneTemplates->count() }}</h2>
                                    <p class="text-muted mb-0">Total Templates</p>
                                </div>
                            </div>
                            <div class="col-md-4 border-start border-end">
                                <div class="p-3">
                                    <h2 class="text-success mb-1">{{ $groups->filter(function($group) { return $group->milestones->count() > 0; })->count() }}</h2>
                                    <p class="text-muted mb-0">Groups with Milestones</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3">
                                    <h2 class="text-info mb-1">{{ $groups->sum(function($group) { return $group->milestones->count(); }) }}</h2>
                                    <p class="text-muted mb-0">Total Milestone Assignments</p>
                                </div>
                            </div>
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
