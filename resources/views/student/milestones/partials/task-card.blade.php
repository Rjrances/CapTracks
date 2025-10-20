<div class="card task-card" data-task-id="{{ $task->id }}" draggable="true">
    <div class="card-body p-3">
        <div class="task-card-header">
            <div class="task-card-content">
                <h6 class="mb-2 {{ $task->status === 'done' ? 'text-decoration-line-through text-muted' : '' }}">
                    {{ $task->milestoneTask->name ?? 'Task' }}
                </h6>
                <p class="text-muted mb-2 small">
                    {{ Str::limit($task->milestoneTask->description ?? 'No description', 100) }}
                </p>
                <div class="mb-2">
                    @if($task->is_assigned_to_me)
                        <span class="badge bg-info status-badge">
                            <i class="fas fa-user me-1"></i>Assigned to you
                        </span>
                    @elseif($task->assigned_to)
                        <span class="badge bg-secondary status-badge">
                            <i class="fas fa-user me-1"></i>{{ $task->assignedStudent->name ?? 'Assigned' }}
                        </span>
                    @else
                        <span class="badge bg-light text-dark status-badge">
                            <i class="fas fa-user-slash me-1"></i>Unassigned
                        </span>
                    @endif
                </div>
                @if($task->notes)
                    <div class="task-notes">
                        <small><strong>Notes:</strong> {{ Str::limit($task->notes, 80) }}</small>
                    </div>
                @endif
                <div class="task-meta">
                    @if($task->deadline)
                        <div class="task-deadline {{ $task->is_overdue ? 'overdue' : '' }}">
                            <i class="fas fa-calendar me-1"></i>
                            {{ $task->deadline ? \Carbon\Carbon::parse($task->deadline)->format('M d, Y') : 'TBA' }}
                            @if($task->is_overdue)
                                <span class="text-danger ms-1">(Overdue)</span>
                            @endif
                        </div>
                    @endif
                    @if($task->completed_at)
                        <div class="task-meta">
                            <small class="text-success">
                                <i class="fas fa-check-circle me-1"></i>
                                Completed {{ $task->completed_at ? \Carbon\Carbon::parse($task->completed_at)->format('M d, Y') : 'Recently' }}
                            </small>
                        </div>
                    @endif
                </div>
                <div class="task-card-actions">
                    @if($isGroupLeader)
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignTaskModal{{ $task->id }}" title="{{ $task->assigned_to ? 'Reassign Task' : 'Assign Task' }}">
                            <i class="fas fa-{{ $task->assigned_to ? 'user-edit' : 'user-plus' }}"></i>
                        </button>
                        @if($task->assigned_to)
                            <form action="{{ route('student.milestones.unassign-task', $task->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Unassign this task?')" title="Unassign Task">
                                    <i class="fas fa-user-minus"></i>
                                </button>
                            </form>
                        @endif
                    @endif
                    @if($task->assigned_to === null || $task->assigned_to == $student->student_id)
                        <a href="{{ route('student.task-submission.create', $task->id) }}" class="btn btn-sm btn-success" title="Submit Task">
                            <i class="fas fa-upload"></i>
                        </a>
                    @endif
                    @if($task->submissions->count() > 0)
                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#submissionsModal{{ $task->id }}" title="View Submissions">
                            <i class="fas fa-eye"></i>
                            <span class="badge bg-primary ms-1">{{ $task->submissions->count() }}</span>
                        </button>
                    @endif
                </div>
            </div>
            <div class="ms-2">
                <span class="badge bg-{{ $task->status_badge_class }} status-badge">
                    @if($task->status === 'done')
                        <i class="fas fa-check me-1"></i>Done
                    @elseif($task->status === 'doing')
                        <i class="fas fa-play me-1"></i>Doing
                    @else
                        <i class="fas fa-clock me-1"></i>Pending
                    @endif
                </span>
            </div>
        </div>
        <div class="mt-3 d-flex gap-1">
            <button type="button" 
                    class="btn btn-sm {{ $task->status === 'pending' ? 'btn-secondary' : 'btn-outline-secondary' }}"
                    onclick="changeTaskStatus({{ $task->id }}, 'pending')"
                    title="Mark as Pending">
                <i class="fas fa-clock"></i>
            </button>
            <button type="button" 
                    class="btn btn-sm {{ $task->status === 'doing' ? 'btn-warning' : 'btn-outline-warning' }}"
                    onclick="changeTaskStatus({{ $task->id }}, 'doing')"
                    title="Mark as In Progress">
                <i class="fas fa-play"></i>
            </button>
            <button type="button" 
                    class="btn btn-sm {{ $task->status === 'done' ? 'btn-success' : 'btn-outline-success' }}"
                    onclick="changeTaskStatus({{ $task->id }}, 'done')"
                    title="Mark as Done">
                <i class="fas fa-check"></i>
            </button>
        </div>
    </div>
</div>
<script>
function changeTaskStatus(taskId, newStatus) {
    fetch(`{{ url('/student/milestones/tasks') }}/${taskId}/move`, {
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
            showAlert('Task status updated successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('Failed to update task status: ' + (data.message || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating task status. Please try again.', 'danger');
    });
}
</script>
