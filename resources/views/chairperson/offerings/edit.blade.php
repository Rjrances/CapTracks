@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Edit Offering</h2>

    <form action="{{ route('offerings.update', $offering->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="name" class="form-label">Offering Name</label>
            <input type="text" name="name" class="form-control" value="{{ $offering->name }}" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ $offering->description }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Update Offering</button>
    </form>
</div>
@endsection
