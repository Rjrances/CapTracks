@extends('layouts.student')

@section('title', 'My Milestones')

@section('content')
<div class="container mt-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1">My Milestones</h1>
            <p class="text-muted mb-0">Track your capstone project progress with Kanban boards</p>
        </div>
        <a href="{{ route('student.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (!$group)
        <!-- No Group Message -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">{{ $message ?? 'No Group Found' }}</h5>
                <p class="text-muted">You need to be part of a group to view milestones.</p>
                <a href="{{ route('student.group') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Join or Create Group
                </a>
            </div>
        </div>
    @else
        <!-- Group Information -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>Group Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-semibold">Group Details</h6>
                        <p><strong>Name:</strong> {{ $group->name }}</p>
                        <p><strong>Description:</strong> {{ $group->description ?? 'No description provided' }}</p>
                        <p><strong>Adviser:</strong> 
                            @if($group->adviser)
                                <span class="badge bg-success">{{ $group->adviser->name }}</span>
                            @else
                                <span class="badge bg-warning">No adviser assigned</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-semibold">Members ({{ $group->members->count() }})</h6>
                        @foreach($group->members as $member)
                            <span class="badge bg-secondary me-1 mb-1">
                                {{ $member->name }}
                                @if($member->id === $student->id)
                                    <i class="fas fa-user me-1"></i>(You)
                                @endif
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Adviser & Defense Information -->
        <div class="row mb-4">
            <!-- Adviser Information -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Adviser Information
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($group->adviser)
                            <div class="text-center">
                                <i class="fas fa-user-tie fa-3x text-primary mb-3"></i>
                                <h5 class="mb-2">{{ $group->adviser->name }}</h5>
                                <p class="text-muted mb-2">{{ $group->adviser->email }}</p>
                                <span class="badge bg-success fs-6">Assigned</span>
                            </div>
                        @elseif($group->adviserInvitations->where('status', 'pending')->count() > 0)
                            <div class="text-center">
                                <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                <h5 class="mb-2">Pending Invitations</h5>
                                <p class="text-muted mb-2">{{ $group->adviserInvitations->where('status', 'pending')->count() }} invitation(s) sent</p>
                                <span class="badge bg-warning fs-6">Awaiting Response</span>
                                
                                <div class="mt-3">
                                    @foreach($group->adviserInvitations->where('status', 'pending') as $invitation)
                                        <div class="border rounded p-2 mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                {{ $invitation->faculty->name }}
                                                <br>
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $invitation->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-center">
                                <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                                <h5 class="mb-2">No Adviser Assigned</h5>
                                <p class="text-muted mb-3">You need an adviser to proceed with your project</p>
                                <a href="{{ route('student.group') }}" class="btn btn-primary">
                                    <i class="fas fa-envelope me-2"></i>Invite Adviser
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Defense Schedule Information -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>Defense Schedule
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($group->defenseSchedules->where('status', 'scheduled')->count() > 0)
                            <div class="text-center mb-3">
                                <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                                <h5 class="mb-2">Scheduled Defenses</h5>
                            </div>
                            @foreach($group->defenseSchedules->where('status', 'scheduled') as $defense)
                                <div class="border rounded p-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ ucfirst(str_replace('_', ' ', $defense->defense_type)) }} Defense</h6>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                {{ $defense->scheduled_date->format('M d, Y') }}
                                                <br>
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $defense->scheduled_time }}
                                            </small>
                                        </div>
                                        <span class="badge bg-success">Scheduled</span>
                                    </div>
                                </div>
                            @endforeach
                        @elseif($group->defenseRequests->where('status', 'pending')->count() > 0)
                            <div class="text-center mb-3">
                                <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                <h5 class="mb-2">Pending Requests</h5>
                            </div>
                            @foreach($group->defenseRequests->where('status', 'pending') as $request)
                                <div class="border rounded p-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">{{ ucfirst(str_replace('_', ' ', $request->defense_type)) }} Defense</h6>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                Requested {{ $request->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                        <span class="badge bg-warning">Pending</span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center">
                                <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                                <h5 class="mb-2">No Defense Scheduled</h5>
                                <p class="text-muted mb-3">Defense schedules will appear here when scheduled</p>
                                @if($group->adviser)
                                    <a href="{{ route('student.group') }}" class="btn btn-warning">
                                        <i class="fas fa-rocket me-2"></i>Request Defense
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Overall Progress -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>Overall Project Progress
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <h6>Project Completion</h6>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar {{ $overallProgress >= 60 ? 'bg-success' : ($overallProgress >= 40 ? 'bg-warning' : 'bg-danger') }}" 
                                     role="progressbar" 
                                     style="width: {{ $overallProgress }}%" 
                                     aria-valuenow="{{ $overallProgress }}" 
                                     aria-valuemin="0" aria-valuemax="100">
                                    {{ $overallProgress }}%
                                </div>
                            </div>
                            <small class="text-muted">Overall project completion percentage</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h4 class="mb-0 {{ $overallProgress >= 60 ? 'text-success' : ($overallProgress >= 40 ? 'text-warning' : 'text-danger') }}">
                                {{ $overallProgress }}%
                            </h4>
                            <small class="text-muted">Complete</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Milestones Overview -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-flag me-2"></i>Milestone Progress
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if($groupMilestones->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($groupMilestones as $groupMilestone)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $groupMilestone->milestoneTemplate->name }}</h6>
                                            <p class="text-muted mb-2">{{ Str::limit($groupMilestone->milestoneTemplate->description ?? '', 100) }}</p>
                                            <div class="progress" style="height: 15px;">
                                                <div class="progress-bar {{ $groupMilestone->progress_percentage >= 80 ? 'bg-success' : ($groupMilestone->progress_percentage >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                                     role="progressbar" 
                                                     style="width: {{ $groupMilestone->progress_percentage }}%" 
                                                     aria-valuenow="{{ $groupMilestone->progress_percentage }}" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                            <small class="text-muted">{{ $groupMilestone->progress_percentage }}% complete</small>
                                        </div>
                                        <div class="ms-3">
                                            @php
                                                $statusClass = match($groupMilestone->status) {
                                                    'completed' => 'success',
                                                    'almost_done' => 'warning',
                                                    'in_progress' => 'info',
                                                    default => 'secondary'
                                                };
                                                $statusText = ucfirst(str_replace('_', ' ', $groupMilestone->status));
                                            @endphp
                                            <span class="badge bg-{{ $statusClass }}">{{ $statusText }}</span>
                                            <br>
                                            <a href="{{ route('student.milestones.show', $groupMilestone->id) }}" class="btn btn-sm btn-outline-primary mt-2">
                                                <i class="fas fa-columns me-1"></i>Kanban Board
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-flag fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No milestones assigned yet</h6>
                                <p class="text-muted small">Milestones will appear here when they are assigned to your group.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- My Tasks Summary -->
                <div class="card mb-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-tasks me-2"></i>My Tasks Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($studentTasks->count() > 0)
                            @php
                                $pendingTasks = $studentTasks->where('status', 'pending')->count();
                                $doingTasks = $studentTasks->where('status', 'doing')->count();
                                $doneTasks = $studentTasks->where('status', 'done')->count();
                            @endphp
                            
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border-end">
                                        <h4 class="text-secondary mb-0">{{ $pendingTasks }}</h4>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border-end">
                                        <h4 class="text-warning mb-0">{{ $doingTasks }}</h4>
                                        <small class="text-muted">In Progress</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <h4 class="text-success mb-0">{{ $doneTasks }}</h4>
                                    <small class="text-muted">Done</small>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="list-group list-group-flush">
                                @foreach($studentTasks->take(3) as $task)
                                <div class="list-group-item px-0">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-{{ $task->status_badge_class }} me-2">{{ ucfirst($task->status) }}</span>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ Str::limit($task->milestoneTask->name ?? 'Task', 30) }}</h6>
                                            <small class="text-muted">{{ Str::limit($task->milestoneTask->description ?? '', 40) }}</small>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            @if($studentTasks->count() > 3)
                                <div class="text-center mt-2">
                                    <small class="text-muted">+{{ $studentTasks->count() - 3 }} more tasks</small>
                                </div>
                            @endif
                        @else
                            <p class="text-muted small mb-0">No tasks assigned to you yet.</p>
                        @endif
                    </div>
                </div>

                <!-- Recent Submissions -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-file-alt me-2"></i>Recent Submissions
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($recentSubmissions->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recentSubmissions->take(3) as $submission)
                                <div class="list-group-item px-0">
                                    <h6 class="mb-1">{{ ucfirst($submission->type) }}</h6>
                                    <small class="text-muted">{{ $submission->created_at->diffForHumans() }}</small>
                                    <br>
                                    <span class="badge bg-{{ $submission->status === 'approved' ? 'success' : ($submission->status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($submission->status) }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted small mb-0">No submissions yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanban Preview -->
        @if($groupMilestones->count() > 0)
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-columns me-2"></i>Kanban Board Preview
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="bg-secondary text-white rounded p-3 mb-2">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h6>Pending</h6>
                                <h4>{{ $studentTasks->where('status', 'pending')->count() }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="bg-warning text-dark rounded p-3 mb-2">
                                <i class="fas fa-play fa-2x mb-2"></i>
                                <h6>In Progress</h6>
                                <h4>{{ $studentTasks->where('status', 'doing')->count() }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="bg-success text-white rounded p-3 mb-2">
                                <i class="fas fa-check fa-2x mb-2"></i>
                                <h6>Completed</h6>
                                <h4>{{ $studentTasks->where('status', 'done')->count() }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <p class="text-muted mb-0">Click on any milestone above to access the full Kanban board with drag & drop functionality!</p>
                </div>
            </div>
        </div>
        @endif
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle task completion checkboxes
    const taskCheckboxes = document.querySelectorAll('.task-checkbox');
    
    taskCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const taskId = this.dataset.taskId;
            const isCompleted = this.checked;
            
            // Send AJAX request to update task
            fetch(`/student/milestones/tasks/${taskId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ is_completed: isCompleted })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert('Task updated successfully!', 'success');
                    // Reload page to update progress
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('Failed to update task: ' + data.message, 'danger');
                    // Revert checkbox
                    this.checked = !isCompleted;
                }
            })
            .catch(() => {
                showAlert('Error updating task. Please try again.', 'danger');
                // Revert checkbox
                this.checked = !isCompleted;
            });
        });
    });
});

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endpush
@endsection
