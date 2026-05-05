@extends('layouts.coordinator')
@section('title', 'Create group')
@section('content')
<div class="container-fluid mb-3">
        <x-coordinator.intro description="Register a new capstone group linked to your coordinated offerings.">
            <a href="{{ route('coordinator.groups.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back to groups
            </a>
        </x-coordinator.intro>
</div>
<div class="d-flex justify-content-center align-items-start" style="min-height: 80vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm p-5 w-100" style="max-width: 600px;">
        <h2 class="fw-bold mb-4 h4">New group</h2>
        <form method="POST" action="{{ route('coordinator.groups.store') }}">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Group Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Create Group</button>
            <a href="{{ route('coordinator.groups.index') }}" class="btn btn-secondary ms-2">Cancel</a>
        </form>
    </div>
</div>
@endsection 
