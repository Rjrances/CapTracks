@extends('layouts.adviser')

@section('title', 'Group Tasks - ' . $group->name)

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Tasks for {{ $group->name }}</h2>
            <p class="text-muted mb-0">{{ $group->members->count() }} members</p>
        </div>
        <div>
            <a href="{{ route('adviser.tasks.index') }}" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>All Tasks
            </a>
            <a href="{{ route('adviser.groups.details', $group) }}" class="btn btn-outline-primary">
                <i class="fas fa-users me-2"></i>Group Details
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Group Progress Overview -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Group Progress Overview</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center">
                    <h4 class="text-primary">{{ $tasks->count() }}</h4>
                    <p class="text-muted mb-0">Total Tasks</p>
                </div>
                <div class="col-md-3 text-center">
                    <h4 class="text-success">{{ $tasks->where('is_completed', true)->count() }}</h4>
                    <p class="text-muted mb-0">Completed</p>
                </div>
                <div class="col-md-3 text-center">
                    <h4 class="text-warning">{{ $tasks->where('is_completed', false)->count() }}</h4>
                    <p class="text-muted mb-0">Pending</p>
                </div>
                <div class="col-md-3 text-center">
                    <h4 class="text-info">{{ $tasks->count() > 0 ? round(($tasks->where('is_completed', true)->count() / $tasks->count()) * 100) : 0 }}%</h4>
                    <p class="text-muted mb-0">Completion Rate</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Tasks</h5>
        </div>
        <div class="card-body">
            @if($tasks->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Milestone</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasks as $task)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $task->name }}</div>
                                        <small class="text-muted">{{ $task->description }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $task->milestoneTemplate->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        @if($task->is_completed)
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar {{ $task->is_completed ? 'bg-success' : 'bg-warning' }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $task->is_completed ? 100 : 0 }}%" 
                                                 aria-valuenow="{{ $task->is_completed ? 100 : 0 }}" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                {{ $task->is_completed ? '100%' : '0%' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <form action="{{ route('adviser.tasks.update', $task) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_completed" value="{{ $task->is_completed ? 0 : 1 }}">
                                            <button type="submit" class="btn btn-sm {{ $task->is_completed ? 'btn-warning' : 'btn-success' }}">
                                                <i class="fas fa-{{ $task->is_completed ? 'undo' : 'check' }}"></i>
                                                {{ $task->is_completed ? 'Mark Incomplete' : 'Mark Complete' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No tasks found for this group</h5>
                    <p class="text-muted">Tasks will appear here when they are assigned to this group.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Group Members -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Group Members</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($group->members as $member)
                    <div class="col-md-4 mb-3">
                        <div class="card border">
                            <div class="card-body text-center">
                                <div class="mb-2">
                                    <span class="d-inline-flex align-items-center justify-content-center bg-primary border rounded-circle" style="width:40px; height:40px;">
                                        <i class="fas fa-user text-white"></i>
                                    </span>
                                </div>
                                <h6 class="card-title mb-1">{{ $member->name }}</h6>
                                <p class="text-muted small mb-2">{{ $member->email }}</p>
                                <small class="text-muted">Member since {{ $member->pivot->created_at->format('M Y') }}</small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection 