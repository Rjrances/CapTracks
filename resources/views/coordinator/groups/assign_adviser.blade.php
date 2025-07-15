@extends('layouts.coordinator')

@section('title', 'Assign Adviser')

@section('content')
<div class="container mt-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('coordinator.groups.index') }}">Groups</a></li>
            <li class="breadcrumb-item"><a href="{{ route('coordinator.groups.show', $group->id) }}">{{ $group->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Assign Adviser</li>
        </ol>
    </nav>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title mb-3">Assign Adviser to: {{ $group->name }}</h2>
            <p><strong>Current Adviser:</strong> {{ $group->adviser ? $group->adviser->name : '—' }}</p>
            <form method="POST" action="#">
                @csrf
                <div class="mb-3">
                    <label for="adviser_id" class="form-label">Select Adviser</label>
                    <select class="form-select" id="adviser_id" name="adviser_id" required>
                        <option value="">-- Select Adviser --</option>
                        <option value="#">(Adviser list coming soon)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Assign Adviser</button>
                <a href="{{ route('coordinator.groups.show', $group->id) }}" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection 