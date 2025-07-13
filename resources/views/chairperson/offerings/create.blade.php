@extends('layouts.chairperson')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Add Offering</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Offering Form --}}
    <form action="{{ route('chairperson.offerings.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="title" class="form-label">Offering Name</label>
            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>

        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-success">Create Offering</button>
    </form>
</div>
@endsection
