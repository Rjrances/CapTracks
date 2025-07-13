@extends('layouts.chairperson')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Edit Offering</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('chairperson.offerings.update', $offering->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="title" class="form-label">Offering Name</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $offering->title) }}" required>

        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ $offering->description }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Update Offering</button>
    </form>
</div>
@endsection
