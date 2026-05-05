@extends('layouts.coordinator')
@section('title')
Assign adviser — {{ $group->name }}
@endsection
@section('content')
<div class="container-fluid">
        <x-coordinator.intro description="Choose a faculty adviser for this group; coordinators for the same offering cannot be assigned.">
            <a href="{{ route('coordinator.groups.show', $group->id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back to group
            </a>
        </x-coordinator.intro>
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('coordinator.groups.index') }}">Groups</a></li>
            <li class="breadcrumb-item"><a href="{{ route('coordinator.groups.show', $group->id) }}">{{ $group->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Assign adviser</li>
        </ol>
    </nav>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title mb-3 h5">Assign adviser</h2>
            <p><strong>Current Adviser:</strong> {{ $group->adviser ? $group->adviser->name : '—' }}</p>
            @if($group->offering)
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Offering:</strong> {{ $group->offering->subject_code }} - {{ $group->offering->subject_title }}
                    <br>
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Teachers who coordinate this offering cannot be assigned as advisers (conflict of interest prevention)
                    </small>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('coordinator.groups.update', $group->id) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="faculty_id" class="form-label">Select Adviser</label>
                    <select class="form-select" id="faculty_id" name="faculty_id" required>
                        <option value="">-- Select Adviser --</option>
                        @foreach($availableFaculty as $faculty)
                            <option value="{{ $faculty->faculty_id }}" {{ $group->faculty_id == $faculty->faculty_id ? 'selected' : '' }}>
                                {{ $faculty->name }} 
                                @if($faculty->hasRole('teacher'))
                                    <span class="badge bg-primary">Teacher</span>
                                @endif
                                @if($faculty->hasRole('adviser'))
                                    <span class="badge bg-success">Adviser</span>
                                @endif
                                @if($faculty->hasRole('panelist'))
                                    <span class="badge bg-info">Panelist</span>
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">
                        Available faculty members who can serve as advisers for this group
                    </div>
                    @error('faculty_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Assign Adviser</button>
                <a href="{{ route('coordinator.groups.show', $group->id) }}" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection 
