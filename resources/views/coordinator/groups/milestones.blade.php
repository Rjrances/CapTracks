@extends('layouts.coordinator')
@section('title')
{{ $group->name }} — Milestones
@endsection
@section('content')
<div class="container-fluid">
        <x-coordinator.intro description="Task-level progress is read-only here. Use Unassign only if an assignment was added by mistake.">
            <a href="{{ route('coordinator.groups.show', $group->id) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to group
            </a>
            <a href="{{ route('coordinator.groups.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-users me-2"></i>All groups
            </a>
        </x-coordinator.intro>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
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
                            <span class="badge bg-danger">No adviser assigned</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-semibold">Members ({{ $group->members->count() }})</h6>
                    @foreach($group->members as $member)
                        <span class="badge bg-secondary me-1 mb-1">{{ $member->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-chart-line me-2"></i>Overall Progress
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <h6>Average milestone completion</h6>
                        @php
                            $overallProgress = $group->groupMilestones->count() > 0
                                ? (int) round($group->groupMilestones->avg('progress_percentage'))
                                : 0;
                        @endphp
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar {{ $overallProgress >= 60 ? 'bg-success' : ($overallProgress >= 40 ? 'bg-warning' : 'bg-danger') }}"
                                 role="progressbar"
                                 style="width: {{ $overallProgress }}%"
                                 aria-valuenow="{{ $overallProgress }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                                {{ $overallProgress }}%
                            </div>
                        </div>
                        <small class="text-muted">Mean of each assigned milestone’s progress</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <h4 class="mb-0 {{ $overallProgress >= 60 ? 'text-success' : ($overallProgress >= 40 ? 'text-warning' : 'text-danger') }}">
                            {{ $overallProgress }}%
                        </h4>
                        <small class="text-muted">Overall</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">
                <i class="fas fa-flag me-2"></i>Assigned milestones
            </h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary">{{ $group->groupMilestones->count() }} assigned</span>
                <a href="{{ route('coordinator.milestones.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-layer-group me-1"></i>Browse templates
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            @if($group->groupMilestones->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 coordinator-milestones-table">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 280px;">Milestone &amp; tasks</th>
                                <th style="width: 140px;">Progress</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 130px;">Target date</th>
                                <th style="width: 160px;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group->groupMilestones as $groupMilestone)
                                @php
                                    $taskRows = $groupMilestone->groupTasks->sortBy(fn ($t) => $t->milestoneTask?->order ?? 0);
                                    $totalTasks = $groupMilestone->totalTasksCount();
                                    $doneTasks = $groupMilestone->completedTasksCount();
                                    $coordStatus = $groupMilestone->coordinatorDisplayStatus();
                                    $hasTaskList = $taskRows->isNotEmpty();
                                @endphp
                                {{-- Main row: summary metrics stay top-aligned and do not stretch when tasks expand --}}
                                <tr class="align-top coordinator-milestone-main-row">
                                    <td class="{{ $hasTaskList ? 'border-bottom-0 pb-0' : '' }}">
                                        <div class="fw-semibold">{{ $groupMilestone->milestoneTemplate->name }}</div>
                                        @if($groupMilestone->notes)
                                            <div class="small text-muted">{{ Str::limit($groupMilestone->notes, 80) }}</div>
                                        @endif
                                        <div class="small text-muted mt-2">
                                            @if($totalTasks > 0)
                                                Tasks completed: <strong>{{ $doneTasks }}</strong> / {{ $totalTasks }}
                                            @else
                                                No tasks on this milestone template
                                            @endif
                                        </div>
                                        @if($hasTaskList)
                                            <button class="btn btn-link btn-sm px-0 py-0 mt-2 text-decoration-none coordinator-task-toggle"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#milestone-tasks-{{ $groupMilestone->id }}"
                                                    aria-expanded="false"
                                                    aria-controls="milestone-tasks-{{ $groupMilestone->id }}">
                                                <span class="coordinator-chevron d-inline-block me-1"><i class="fas fa-chevron-down small"></i></span>
                                                View task list
                                            </button>
                                        @endif
                                    </td>
                                    <td class="{{ $hasTaskList ? 'border-bottom-0' : '' }}">
                                        <div class="progress coordinator-progress-cell" style="height: 22px;">
                                            <div class="progress-bar {{ $groupMilestone->progress_percentage >= 80 ? 'bg-success' : ($groupMilestone->progress_percentage >= 40 ? 'bg-warning' : 'bg-danger') }}"
                                                 role="progressbar"
                                                 style="width: {{ max(0, min(100, $groupMilestone->progress_percentage)) }}%"
                                                 aria-valuenow="{{ $groupMilestone->progress_percentage }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100">
                                                {{ $groupMilestone->progress_percentage }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="{{ $hasTaskList ? 'border-bottom-0' : '' }}">
                                        <span class="badge bg-{{ $coordStatus['class'] }}">{{ $coordStatus['label'] }}</span>
                                    </td>
                                    <td class="{{ $hasTaskList ? 'border-bottom-0' : '' }}">
                                        @if($groupMilestone->target_date)
                                            <span class="text-{{ $groupMilestone->is_overdue ? 'danger' : 'primary' }}">
                                                {{ \Carbon\Carbon::parse($groupMilestone->target_date)->format('M d, Y') }}
                                            </span>
                                            @if($groupMilestone->is_overdue)
                                                <br><span class="small text-danger">Overdue</span>
                                            @endif
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </td>
                                    <td class="text-end {{ $hasTaskList ? 'border-bottom-0' : '' }}">
                                        <form action="{{ route('coordinator.milestones.removeFromGroup', [$group, $groupMilestone]) }}"
                                              method="post"
                                              class="d-inline"
                                              onsubmit="return confirm('Unassign this milestone from the group? Task progress for it will be removed.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    title="Remove this assignment from the group only">
                                                <i class="fas fa-unlink me-1"></i>Unassign
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @if($hasTaskList)
                                    <tr class="align-top coordinator-milestone-expand-row border-bottom">
                                        <td colspan="5" class="pt-0 px-3 pb-3 border-top-0">
                                            <div class="collapse coordinator-task-collapse" id="milestone-tasks-{{ $groupMilestone->id }}">
                                                <div class="rounded border bg-light px-3 py-2">
                                                    <ul class="list-group list-group-flush small mb-0">
                                                        @foreach($taskRows as $gt)
                                                            @php
                                                                $isDone = $gt->status === 'done' || $gt->is_completed;
                                                                $isDoing = $gt->status === 'doing';
                                                                $label = $isDone ? 'Done' : ($isDoing ? 'In progress' : 'Pending');
                                                                $badgeClass = $isDone ? 'success' : ($isDoing ? 'warning text-dark' : 'secondary');
                                                                $name = $gt->milestoneTask?->name ?? 'Task';
                                                            @endphp
                                                            <li class="list-group-item d-flex justify-content-between align-items-center gap-2 py-2 px-0 bg-transparent border-bottom border-opacity-25">
                                                                <span>{{ $name }}</span>
                                                                <span class="badge bg-{{ $badgeClass }} flex-shrink-0">{{ $label }}</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5 px-3">
                    <i class="fas fa-flag fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">No milestones assigned yet</h6>
                    <p class="text-muted small mb-0">Assign milestones from <a href="{{ route('coordinator.milestones.index') }}">Milestone Templates</a>.</p>
                </div>
            @endif
        </div>
    </div>
    <div class="card mt-4 border-warning">
        <div class="card-header bg-warning text-dark">
            <h6 class="mb-0">
                <i class="fas fa-bolt me-2"></i>Quick actions
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-6">
                    <a href="{{ route('coordinator.groups.show', $group->id) }}" class="btn btn-outline-primary w-100">
                        <i class="fas fa-users me-2"></i>View group details
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('coordinator.groups.assignAdviser', $group->id) }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-user-tie me-2"></i>Manage adviser
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('styles')
<style>
    .coordinator-milestones-table td {
        vertical-align: top;
    }
    .coordinator-milestones-table .coordinator-progress-cell {
        min-width: 120px;
    }
    .coordinator-task-toggle .coordinator-chevron {
        transition: transform 0.2s ease;
        vertical-align: middle;
    }
    .coordinator-task-toggle[aria-expanded="true"] .coordinator-chevron {
        transform: rotate(-180deg);
    }
</style>
@endpush
