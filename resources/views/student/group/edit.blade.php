@extends('layouts.app')

@section('title', 'Edit Group')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="bg-white rounded-4 shadow-sm pt-4 px-5 pb-5 w-100" style="max-width: 500px;">
        <h2 class="fw-bold mb-3">Edit Group</h2>
        <form action="{{ route('student.group.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="name" class="form-label">Group Name</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ $group->name ?? '' }}" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control">{{ $group->description ?? '' }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('student.group') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection 