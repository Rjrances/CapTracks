@extends('layouts.chairperson')

@section('content')
<div class="container">
    <h2>Edit Teacher</h2>

    <form action="{{ route('chairperson.teachers.update', $teacher->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="{{ $teacher->name }}" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="{{ $teacher->email }}" required>
        </div>

        <div class="mb-3">
            <label>Role</label>
            <select name="role" class="form-control" required>
                <option value="adviser" {{ $teacher->role === 'adviser' ? 'selected' : '' }}>Adviser</option>
                <option value="panelist" {{ $teacher->role === 'panelist' ? 'selected' : '' }}>Panelist</option>
            </select>
        </div>

        <div class="mb-3">
            <label>New Password (optional)</label>
            <input type="password" name="password" class="form-control">
        </div>

        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
