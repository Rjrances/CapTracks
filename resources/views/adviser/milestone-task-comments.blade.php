@extends('layouts.adviser')
@section('title', 'Task discussion')
@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="{{ route('adviser.groups.details', $group) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Back to group
        </a>
    </div>
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-comments me-2"></i>
                Task discussion — {{ $groupMilestoneTask->milestoneTask->name ?? 'Task' }}
            </h5>
            <small class="text-muted">{{ $group->name }} · {{ $groupMilestoneTask->groupMilestone->milestoneTemplate->name ?? 'Milestone' }}</small>
        </div>
        <div class="card-body">
            @include('partials.task-comments-thread', [
                'comments' => $groupMilestoneTask->taskComments,
                'formAction' => route('adviser.groups.milestone-task-comments.store', [$group, $groupMilestoneTask]),
            ])
        </div>
    </div>
</div>
@endsection
