@extends('layouts.student')
@section('title', 'Milestone Details')
@section('content')
<div class="container-fluid mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1">{{ $groupMilestone->title ?? $groupMilestone->milestoneTemplate->name }}</h1>
            <p class="text-muted mb-0">Kanban board for milestone tasks</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-info" onclick="recomputeProgress()">
                <i class="fas fa-sync-alt me-2"></i>Recompute Progress
            </button>
            <a href="{{ route('student.project') }}" class="btn btn-outline-primary">
                <i class="fas fa-file-upload me-2"></i>View Project Submissions
            </a>
            <a href="{{ route('student.milestones') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Milestones
            </a>
        </div>
    </div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-0">
                        <i class="fas fa-flag me-2"></i>Milestone Information
                    </h5>
                    <small>{{ $groupMilestone->description ?? $groupMilestone->milestoneTemplate->description ?? 'No description provided' }}</small>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex align-items-center justify-content-end">
                        <div class="me-3">
                            <h4 class="mb-0 {{ $progress >= 80 ? 'text-success' : ($progress >= 50 ? 'text-warning' : 'text-danger') }}">
                                {{ $progress }}%
                            </h4>
                            <small class="text-white-50">Complete</small>
                        </div>
                        <div class="progress flex-grow-1" style="height: 25px; max-width: 200px;">
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
    <div class="row">
        <div class="col-md-4">
            <div class="card kanban-column" data-status="pending">
                <div class="card-header bg-secondary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Pending
                        </h6>
                        <span class="badge bg-light text-dark">{{ $tasks['pending']->count() }}</span>
                    </div>
                </div>
                <div class="card-body kanban-column-body" style="min-height: 400px;">
                    @foreach($tasks['pending'] as $task)
                        @include('student.milestones.partials.task-card', ['task' => $task, 'isGroupLeader' => $isGroupLeader, 'student' => $student])
                    @endforeach
                    @if($tasks['pending']->count() === 0)
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p class="mb-0">No pending tasks</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card kanban-column" data-status="doing">
                <div class="card-header bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-play me-2"></i>In Progress
                        </h6>
                        <span class="badge bg-light text-dark">{{ $tasks['doing']->count() }}</span>
                    </div>
                </div>
                <div class="card-body kanban-column-body" style="min-height: 400px;">
                    @foreach($tasks['doing'] as $task)
                        @include('student.milestones.partials.task-card', ['task' => $task, 'isGroupLeader' => $isGroupLeader, 'student' => $student])
                    @endforeach
                    @if($tasks['doing']->count() === 0)
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-2x mb-2"></i>
                            <p class="mb-0">No tasks in progress</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card kanban-column" data-status="done">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-check me-2"></i>Completed
                        </h6>
                        <span class="badge bg-light text-dark">{{ $tasks['done']->count() }}</span>
                    </div>
                </div>
                <div class="card-body kanban-column-body" style="min-height: 400px;">
                    @foreach($tasks['done'] as $task)
                        @include('student.milestones.partials.task-card', ['task' => $task, 'isGroupLeader' => $isGroupLeader, 'student' => $student])
                    @endforeach
                    @if($tasks['done']->count() === 0)
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <p class="mb-0">No completed tasks</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@if($isGroupLeader)
    @php
        $allTasks = collect($tasks['pending'])->concat($tasks['doing'])->concat($tasks['done']);
    @endphp
    @foreach($allTasks as $task)
        <div class="modal fade" id="assignTaskModal{{ $task->id }}" tabindex="-1" aria-labelledby="assignTaskModalLabel{{ $task->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignTaskModalLabel{{ $task->id }}">
                            {{ $task->assigned_to ? 'Reassign Task' : 'Assign Task' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('student.milestones.assign-task', $task->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="modal-body">
                            <h6>{{ $task->milestoneTask->name }}</h6>
                            <p class="text-muted">{{ $task->milestoneTask->description }}</p>
                            @if($task->assigned_to)
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Currently assigned to: <strong>{{ $task->assignedStudent->name ?? 'Unknown' }}</strong>
                                </div>
                            @endif
                            <div class="mb-3">
                                <label for="assigned_to_{{ $task->id }}" class="form-label">
                                    {{ $task->assigned_to ? 'Reassign to:' : 'Assign to:' }}
                                </label>
                                <select class="form-select" id="assigned_to_{{ $task->id }}" name="assigned_to" required>
                                    <option value="">Select a group member</option>
                                    @foreach($group->members as $member)
                                        <option value="{{ $member->student_id }}" 
                                                {{ $task->assigned_to == $member->student_id ? 'selected' : '' }}>
                                            {{ $member->name }} ({{ $member->pivot->role }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                {{ $task->assigned_to ? 'Reassign Task' : 'Assign Task' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif
@push('styles')
<style>
.kanban-column {
    height: 100%;
}
.kanban-column-body {
    overflow-y: auto;
    max-height: 600px;
}
.task-card {
    cursor: grab;
    transition: all 0.2s ease;
    border: 1px solid #dee2e6;
    margin-bottom: 1rem;
}
.task-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.task-card.dragging {
    opacity: 0.5;
    cursor: grabbing;
}
.kanban-column.drag-over {
    background-color: #f8f9fa;
    border: 2px dashed #007bff;
}
.task-card-header {
    display: flex;
    justify-content-between;
    align-items: flex-start;
}
.task-card-content {
    flex-grow: 1;
}
.task-card-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}
.status-badge {
    font-size: 0.75rem;
}
.progress-sm {
    height: 8px;
}
.task-meta {
    font-size: 0.875rem;
    color: #6c757d;
}
.task-assignee {
    font-size: 0.875rem;
    font-weight: 500;
}
.task-deadline {
    font-size: 0.75rem;
}
.task-deadline.overdue {
    color: #dc3545;
}
.task-notes {
    background-color: #f8f9fa;
    border-left: 3px solid #007bff;
    padding: 0.5rem;
    margin: 0.5rem 0;
    font-size: 0.875rem;
}
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const columns = document.querySelectorAll('.kanban-column-body');
    columns.forEach(column => {
        new Sortable(column, {
            group: 'tasks',
            animation: 150,
            ghostClass: 'dragging',
            onEnd: function(evt) {
                const taskId = evt.item.dataset.taskId;
                const newStatus = evt.to.closest('.kanban-column').dataset.status;
                moveTask(taskId, newStatus);
            }
        });
    });
    columns.forEach(column => {
        column.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.closest('.kanban-column').classList.add('drag-over');
        });
        column.addEventListener('dragleave', function(e) {
            this.closest('.kanban-column').classList.remove('drag-over');
        });
        column.addEventListener('drop', function(e) {
            this.closest('.kanban-column').classList.remove('drag-over');
        });
    });
});
function moveTask(taskId, newStatus) {
    fetch(`/student/milestones/tasks/${taskId}/move`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('Task moved successfully!', 'success');
            updateProgressBars();
        } else {
            showAlert('Failed to move task: ' + data.message, 'danger');
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(() => {
        showAlert('Error moving task. Please try again.', 'danger');
        setTimeout(() => location.reload(), 1000);
    });
}
function updateProgressBars() {
    fetch(`/student/milestones/{{ $groupMilestone->id }}/recompute-progress`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const progressBar = document.querySelector('.progress-bar');
            const progressText = document.querySelector('.h4');
            if (progressBar && progressText) {
                progressBar.style.width = data.progress + '%';
                progressBar.setAttribute('aria-valuenow', data.progress);
                progressText.textContent = data.progress + '%';
                const progressContainer = progressBar.closest('.progress');
                progressBar.className = 'progress-bar';
                if (data.progress >= 80) {
                    progressBar.classList.add('bg-success');
                    progressText.className = 'h4 mb-0 text-success';
                } else if (data.progress >= 50) {
                    progressBar.classList.add('bg-warning');
                    progressText.className = 'h4 mb-0 text-warning';
                } else {
                    progressBar.classList.add('bg-danger');
                    progressText.className = 'h4 mb-0 text-danger';
                }
            }
        }
    });
}
function recomputeProgress() {
    fetch(`/student/milestones/{{ $groupMilestone->id }}/recompute-progress`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('Progress recomputed successfully!', 'success');
            updateProgressBars();
        } else {
            showAlert('Failed to recompute progress: ' + data.message, 'danger');
        }
    })
    .catch(() => {
        showAlert('Error recomputing progress. Please try again.', 'danger');
    });
}
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endpush
@endsection
