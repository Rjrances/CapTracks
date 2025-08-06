@extends('layouts.adviser')

@section('title', 'Task Management')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Task Management</h2>
        <a href="{{ route('adviser.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Task Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Tasks</h5>
                    <h3 class="mb-0">{{ $tasks->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Completed</h5>
                    <h3 class="mb-0">{{ $tasks->where('is_completed', true)->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">In Progress</h5>
                    <h3 class="mb-0">{{ $tasks->where('is_completed', false)->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Groups</h5>
                    <h3 class="mb-0">{{ $groups->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Tasks</h5>
        </div>
        <div class="card-body">
            @if($tasks->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Milestone</th>
                                <th>Group</th>
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
                                    <td>{{ $task->milestoneTemplate->name ?? 'N/A' }}</td>
                                    <td>
                                        @foreach($groups as $group)
                                            @if($group->id == ($task->assigned_to ?? 0))
                                                {{ $group->name }}
                                                @break
                                            @endif
                                        @endforeach
                                        <span class="text-muted">Not assigned</span>
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
                    <h5 class="text-muted">No tasks found</h5>
                    <p class="text-muted">Tasks will appear here when they are assigned to your groups.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Group Quick Access -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Quick Access - Group Tasks</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($groups as $group)
                    <div class="col-md-4 mb-3">
                        <div class="card border">
                            <div class="card-body text-center">
                                <h6 class="card-title">{{ $group->name }}</h6>
                                <p class="text-muted small">{{ $group->members->count() }} members</p>
                                <a href="{{ route('adviser.groups.tasks', $group) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-tasks me-2"></i>View Tasks
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection 