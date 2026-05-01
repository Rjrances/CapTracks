@extends('layouts.student')

@section('title', 'Milestone Checklist')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Must Have Checklist</h4>
            <small class="text-muted">{{ $group->name }}</small>
        </div>
    </div>

    @forelse($templates as $template)
        <div class="card mb-3">
            <div class="card-header">
                <strong>{{ $template->name }}</strong>
            </div>
            <div class="card-body">
                @if($template->tasks->isEmpty())
                    <p class="text-muted mb-0">No tasks for this milestone.</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($template->tasks as $task)
                            @php
                                $groupTask = $groupTaskStatus->get($task->id);
                                $isDone = $groupTask && $groupTask->status === 'done';
                            @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $task->name }}</span>
                                <span class="badge {{ $isDone ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $isDone ? 'Done' : 'Pending' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @empty
        <div class="alert alert-info">No milestone templates available.</div>
    @endforelse
</div>
@endsection
