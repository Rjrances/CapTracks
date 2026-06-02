<div class="card task-card" data-task-id="{{ $task->id }}" draggable="true">
    <div class="card-body p-3">
        <div class="task-card-header">
            <div class="task-card-content">
                <h6 class="mb-2 {{ $task->status === 'done' ? 'text-decoration-line-through text-muted' : '' }}">
                    {{ $task->task_label }}
                </h6>
                <p class="text-muted mb-2 small">
                    {{ Str::limit($task->task_body ?? 'No description', 100) }}
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
                    <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#taskCommentsModal{{ $task->id }}" title="Task discussion">
                        <i class="fas fa-comments"></i>
                        @if(($task->task_comments_count ?? 0) > 0)
                            <span class="badge bg-secondary ms-1">{{ $task->task_comments_count }}</span>
                        @endif
                    </button>
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

                </div>
            </div>
            <div class="ms-2">
                <span class="badge bg-{{ $task->status_badge_class }} {{ $task->status === 'doing' ? 'text-dark' : '' }} status-badge">
                    @if($task->status === 'done')
                        <i class="fas fa-check me-1"></i>Done
                    @elseif($task->status === 'doing')
                        <i class="fas fa-play me-1"></i>In Progress
                    @else
                        <i class="fas fa-clock me-1"></i>Pending
                    @endif
                </span>
            </div>
        </div>
    </div>
</div>
