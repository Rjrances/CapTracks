@extends('layouts.student')

@section('title', 'Milestone Details')

@section('content')
<div class="container mt-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1">{{ $groupMilestone->milestoneTemplate->name }}</h1>
            <p class="text-muted mb-0">Milestone details and task progress</p>
        </div>
        <a href="{{ route('student.milestones') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Milestones
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Milestone Information -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-flag me-2"></i>Milestone Information
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h6 class="fw-semibold">Description</h6>
                    <p class="text-muted">{{ $groupMilestone->milestoneTemplate->description ?? 'No description provided' }}</p>
                    
                    @if($groupMilestone->notes)
                        <h6 class="fw-semibold mt-3">Notes</h6>
                        <p class="text-muted">{{ $groupMilestone->notes }}</p>
                    @endif
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <h4 class="mb-0 {{ $progress >= 80 ? 'text-success' : ($progress >= 50 ? 'text-warning' : 'text-danger') }}">
                            {{ $progress }}%
                        </h4>
                        <small class="text-muted">Complete</small>
                        <div class="progress mt-2" style="height: 20px;">
                            <div class="progress-bar {{ $progress >= 80 ? 'bg-success' : ($progress >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                 role="progressbar" 
                                 style="width: {{ $progress }}%" 
                                 aria-valuenow="{{ $progress }}" 
                                 aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks List -->
    <div class="card">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-tasks me-2"></i>Tasks
                </h5>
                <span class="badge bg-primary">{{ $tasks->count() }} tasks</span>
            </div>
        </div>
        <div class="card-body p-0">
            @if($tasks->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($tasks as $task)
                    <div class="list-group-item">
                        <div class="d-flex align-items-start">
                            <div class="form-check me-3 mt-1">
                                <input class="form-check-input task-checkbox" 
                                       type="checkbox" 
                                       form="updateTasksForm"
                                       name="completed_tasks[]"
                                       value="{{ $task->id }}"
                                       {{ $task->is_completed ? 'checked' : '' }}
                                       {{ $task->assigned_to && !$task->is_assigned_to_me ? 'disabled' : '' }}>
                            </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1 {{ $task->is_completed ? 'text-decoration-line-through text-muted' : '' }}">
                                        {{ $task->milestoneTask->name ?? 'Task' }}
                                        @if($task->is_assigned_to_me)
                                            <span class="badge bg-info ms-2">Assigned to you</span>
                                        @elseif($task->assigned_to)
                                            <span class="badge bg-secondary ms-2">Assigned to another member</span>
                                        @else
                                            <span class="badge bg-light text-dark ms-2">Unassigned</span>
                                        @endif
                                        
                                        @if($isGroupLeader)
                                            <div class="mt-2">
                                                @if($task->assigned_to)
                                                    <form action="{{ route('student.milestones.unassign-task', $task->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Unassign this task?')">
                                                            <i class="fas fa-user-minus me-1"></i>Unassign
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignTaskModal{{ $task->id }}">
                                                        <i class="fas fa-user-plus me-1"></i>Assign Task
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </h6>
                                        <p class="text-muted mb-2">{{ $task->milestoneTask->description ?? 'No description' }}</p>
                                        
                                        @if($task->notes)
                                            <div class="alert alert-info py-2 px-3 mb-2">
                                                <small><strong>Notes:</strong> {{ $task->notes }}</small>
                                            </div>
                                        @endif
                                        
                                        @if($task->deadline)
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Deadline: {{ \Carbon\Carbon::parse($task->deadline)->format('M d, Y') }}
                                                @if($task->is_overdue)
                                                    <span class="text-danger ms-2">(Overdue)</span>
                                                @endif
                                            </small>
                                        @endif
                                    </div>
                                    <div class="ms-3 text-end">
                                        @if($task->is_completed)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Completed
                                            </span>
                                            @if($task->completed_at)
                                                <br><small class="text-muted">{{ \Carbon\Carbon::parse($task->completed_at)->format('M d, Y') }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock me-1"></i>Pending
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <!-- Separate form for updating task completion status -->
                <form action="{{ route('student.milestones.update-tasks', $groupMilestone->id) }}" method="POST" id="updateTasksForm">
                    @csrf
                    @method('PATCH')
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Check the tasks you want to mark as completed</small>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Progress
                            </button>
                        </div>
                    </div>
                </form>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">No tasks found</h6>
                    <p class="text-muted small">Tasks will appear here when they are added to this milestone.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Group Information -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0">
                <i class="fas fa-users me-2"></i>Group Information
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-semibold">Group Details</h6>
                    <p><strong>Name:</strong> {{ $group->name }}</p>
                    <p><strong>Adviser:</strong> 
                        @if($group->adviser)
                            <span class="badge bg-success">{{ $group->adviser->name }}</span>
                        @else
                            <span class="badge bg-warning">No adviser assigned</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-semibold">Milestone Status</h6>
                    @php
                        $statusClass = match($groupMilestone->status) {
                            'completed' => 'success',
                            'almost_done' => 'warning',
                            'in_progress' => 'info',
                            default => 'secondary'
                        };
                        $statusText = ucfirst(str_replace('_', ' ', $groupMilestone->status));
                    @endphp
                    <span class="badge bg-{{ $statusClass }} fs-6">{{ $statusText }}</span>
                    
                    @if($groupMilestone->target_date)
                        <br><small class="text-muted mt-2 d-block">
                            <i class="fas fa-calendar me-1"></i>
                            Target Date: {{ \Carbon\Carbon::parse($groupMilestone->target_date)->format('M d, Y') }}
                        </small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Assignment Modals -->
@if($isGroupLeader)
    @foreach($tasks as $task)
        @if(!$task->assigned_to)
        <div class="modal fade" id="assignTaskModal{{ $task->id }}" tabindex="-1" aria-labelledby="assignTaskModalLabel{{ $task->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignTaskModalLabel{{ $task->id }}">Assign Task</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <!-- Debug: {{ route('student.milestones.assign-task', $task->id) }} -->
                    <form action="{{ route('student.milestones.assign-task', $task->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="modal-body">
                            <h6>{{ $task->milestoneTask->name }}</h6>
                            <p class="text-muted">{{ $task->milestoneTask->description }}</p>
                            
                            <div class="mb-3">
                                <label for="assigned_to_{{ $task->id }}" class="form-label">Assign to:</label>
                                <select class="form-select" id="assigned_to_{{ $task->id }}" name="assigned_to" required>
                                    <option value="">Select a group member</option>
                                    @foreach($group->members as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->pivot->role }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Assign Task</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    @endforeach
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add form validation
    const form = document.querySelector('form');
    const saveButton = document.querySelector('button[type="submit"]');
    
    form.addEventListener('submit', function(e) {
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
    });
});
</script>
@endpush
@endsection
