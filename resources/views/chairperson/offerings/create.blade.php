@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Add Offering</h2>

    <form action="{{ route('offerings.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Offering Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-success">Create Offering</button>
    </form>
</div>
@endsection
