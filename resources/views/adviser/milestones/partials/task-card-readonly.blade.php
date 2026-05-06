<div class="card mb-3 border shadow-sm">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <h6 class="mb-1 {{ $task->status === 'done' ? 'text-decoration-line-through text-muted' : '' }}">
                    {{ $task->milestoneTask->name ?? 'Task' }}
                </h6>
                @if($task->milestoneTask->description ?? null)
                    <p class="text-muted mb-2 small">
                        {{ Str::limit($task->milestoneTask->description, 90) }}
                    </p>
                @endif

                {{-- Assigned Student --}}
                <div class="mb-2">
                    @if($task->assignedStudent)
                        <span class="badge bg-info text-dark small">
                            <i class="fas fa-user me-1"></i>{{ $task->assignedStudent->name }}
                        </span>
                    @else
                        <span class="badge bg-light text-muted small">
                            <i class="fas fa-user-slash me-1"></i>Unassigned
                        </span>
                    @endif
                </div>

                {{-- Deadline --}}
                @if($task->deadline)
                    <div class="small {{ $task->deadline && now()->isAfter($task->deadline) && $task->status !== 'done' ? 'text-danger' : 'text-muted' }}">
                        <i class="fas fa-calendar me-1"></i>
                        {{ \Carbon\Carbon::parse($task->deadline)->format('M d, Y') }}
                        @if($task->deadline && now()->isAfter($task->deadline) && $task->status !== 'done')
                            <span class="ms-1 fw-bold">(Overdue)</span>
                        @endif
                    </div>
                @endif

                {{-- Comments count --}}
                @if(($task->task_comments_count ?? 0) > 0)
                    <div class="small text-muted mt-1">
                        <i class="fas fa-comments me-1"></i>{{ $task->task_comments_count }} comment(s)
                        <a href="{{ route('adviser.groups.milestone-task-comments', [$task->groupMilestone->group_id ?? 0, $task->id]) }}"
                           class="ms-2 small text-decoration-none">View discussion →</a>
                    </div>
                @endif
            </div>

            {{-- Status Badge --}}
            <div class="ms-2">
                @if($task->status === 'done')
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Done</span>
                @elseif($task->status === 'doing')
                    <span class="badge bg-warning text-dark"><i class="fas fa-play me-1"></i>Doing</span>
                @else
                    <span class="badge bg-secondary"><i class="fas fa-clock me-1"></i>Pending</span>
                @endif
            </div>
        </div>
    </div>
</div>
