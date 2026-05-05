@extends('layouts.coordinator')
@section('title', 'Edit group')
@section('content')
<div class="container-fluid mb-3">
        <x-coordinator.intro description="Update the display name or description for this capstone group.">
            <a href="{{ route('coordinator.groups.show', $group->id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back to group
            </a>
        </x-coordinator.intro>
</div>
<div class="d-flex justify-content-center align-items-start" style="min-height: 80vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm p-5 w-100" style="max-width: 600px;">
        <h2 class="fw-bold mb-4 h4">Edit details</h2>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <form method="POST" action="{{ route('coordinator.groups.update', $group->id) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="name" class="form-label">Group Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $group->name) }}" required>
                @error('name')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $group->description) }}</textarea>
                @error('description')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Group</button>
                <a href="{{ route('coordinator.groups.show', $group->id) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection 
