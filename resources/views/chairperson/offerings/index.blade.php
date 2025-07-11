@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Add Offering</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Offering Form --}}
    <form action="{{ route('chairperson.offerings.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="title" class="form-label">Offering Name</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-success">Create Offering</button>
    </form>

    {{-- Listing offerings --}}
    <hr class="my-4">
    <h3 class="mb-3">Current Offerings</h3>
    @if($offerings->count())
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($offerings as $offering)
                <tr>
                    <td>{{ $offering->title }}</td>
                    <td>{{ $offering->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
        <p>No offerings available yet.</p>
    @endif
</div>
@endsection
