@extends('layouts.coordinator')
@section('title', 'Edit Group')
@section('content')
<div class="d-flex justify-content-center align-items-start" style="min-height: 80vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm p-5 w-100" style="max-width: 600px;">
        <div class="mb-4" style="margin-bottom: 1.2rem !important;">
            <h1 class="fw-bold mb-1" style="font-size:2rem; margin-bottom:0.1rem;">Edit Group</h1>
            <div class="text-muted" style="font-size:1.1rem; margin-bottom:0;">Update group information</div>
        </div>
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
