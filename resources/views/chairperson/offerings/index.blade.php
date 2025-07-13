@extends('layouts.chairperson')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Current Offerings</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <a href="{{ route('chairperson.offerings.create') }}" class="btn btn-success mb-3">Add New Offering</a>

    @if($offerings->count())
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Subject Title</th>
                <th>Offer Code</th>
                <th>Teacher Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($offerings as $offering)
                <tr>
                    <td>{{ $offering->subject_title }}</td>
                    <td>{{ $offering->subject_code }}</td>
                    <td>{{ $offering->teacher_name }}</td>
                    <td>
                        <a href="{{ route('chairperson.offerings.edit', $offering->id) }}" class="btn btn-primary btn-sm">Edit</a>
                        <form action="{{ route('chairperson.offerings.delete', $offering->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button onclick="return confirm('Are you sure?')" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
        <p>No offerings available yet.</p>
    @endif
</div>
@endsection
