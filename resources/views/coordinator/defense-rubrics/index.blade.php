@extends('layouts.coordinator')
@section('title', 'Defense Rubrics')

@section('content')
<div class="container-fluid">
    <x-coordinator.intro description="Configure scoring rubrics per defense stage. Only one active rubric is used per stage.">
        <a href="{{ route('coordinator.defense-rubrics.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>New Rubric
        </a>
    </x-coordinator.intro>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Stage</th>
                            <th>Criteria</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr>
                                <td>{{ $template->name }}</td>
                                <td>{{ strtoupper($template->stage === 'proposal' ? 'proposal' : ($template->stage.'%')) }}</td>
                                <td>{{ $template->criteria->count() }}</td>
                                <td>
                                    @if($template->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Draft</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('coordinator.defense-rubrics.edit', $template) }}" class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('coordinator.defense-rubrics.destroy', $template) }}" onsubmit="return confirm('Delete this rubric? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" {{ $template->is_active ? 'disabled' : '' }}>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No defense rubrics yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

