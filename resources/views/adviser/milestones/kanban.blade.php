@extends('layouts.adviser')
@section('title', 'Milestone Kanban — ' . ($groupMilestone->milestoneTemplate->name ?? $groupMilestone->title ?? 'Milestone'))
@section('content')
<div class="container-fluid mt-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">
                <i class="fas fa-columns me-2 text-primary"></i>
                {{ $groupMilestone->milestoneTemplate->name ?? $groupMilestone->title ?? 'Milestone' }}
            </h4>
            <p class="text-muted mb-0 small">
                Read-only Kanban view &mdash; Group: <strong>{{ $group->name }}</strong>
            </p>
        </div>
        <a href="{{ route('adviser.groups.details', $group) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Group
        </a>
    </div>

    {{-- Progress Bar --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="d-flex align-items-center gap-3">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-semibold small">Overall Progress</span>
                        <span class="fw-bold {{ $progress >= 80 ? 'text-success' : ($progress >= 50 ? 'text-warning' : 'text-danger') }}">
                            {{ $progress }}% Complete
                        </span>
                    </div>
                    <div class="progress" style="height: 12px; border-radius: 6px;">
                        <div class="progress-bar {{ $progress >= 80 ? 'bg-success' : ($progress >= 50 ? 'bg-warning' : 'bg-danger') }}"
                             role="progressbar"
                             style="width: {{ $progress }}%"
                             aria-valuenow="{{ $progress }}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                        </div>
                    </div>
                </div>
                <div class="text-end" style="min-width: 180px;">
                    <span class="badge bg-secondary me-1">{{ $tasksByStatus['pending']->count() }} Pending</span>
                    <span class="badge bg-warning text-dark me-1">{{ $tasksByStatus['doing']->count() }} In Progress</span>
                    <span class="badge bg-success">{{ $tasksByStatus['done']->count() }} Done</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Kanban Columns --}}
    <div class="row g-3">

        {{-- Pending --}}
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-clock me-2"></i>Pending</span>
                    <span class="badge bg-light text-dark">{{ $tasksByStatus['pending']->count() }}</span>
                </div>
                <div class="card-body" style="min-height: 300px;">
                    @forelse($tasksByStatus['pending'] as $task)
                        @include('adviser.milestones.partials.task-card-readonly', ['task' => $task])
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            <small>No pending tasks</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- In Progress --}}
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-play me-2"></i>In Progress</span>
                    <span class="badge bg-light text-dark">{{ $tasksByStatus['doing']->count() }}</span>
                </div>
                <div class="card-body" style="min-height: 300px;">
                    @forelse($tasksByStatus['doing'] as $task)
                        @include('adviser.milestones.partials.task-card-readonly', ['task' => $task])
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-spinner fa-2x mb-2 d-block"></i>
                            <small>No tasks in progress</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Done --}}
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-check me-2"></i>Completed</span>
                    <span class="badge bg-light text-dark">{{ $tasksByStatus['done']->count() }}</span>
                </div>
                <div class="card-body" style="min-height: 300px;">
                    @forelse($tasksByStatus['done'] as $task)
                        @include('adviser.milestones.partials.task-card-readonly', ['task' => $task])
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                            <small>No completed tasks yet</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
