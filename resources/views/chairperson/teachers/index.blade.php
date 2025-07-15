@extends('layouts.chairperson')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Teachers List</h2>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Add New Teacher --}}
    <a href="{{ route('chairperson.teachers.create') }}" class="btn btn-success mb-3">Add New Teacher</a>

    @if ($teachers->count())
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($teachers as $teacher)
                    <tr>
                        <td>{{ $teacher->name }}</td>
                        <td>{{ $teacher->email }}</td>
                        <td>{{ ucfirst($teacher->role) }}</td>
                        <td>
                            <a href="{{ route('chairperson.teachers.edit', $teacher->id) }}" class="btn btn-primary btn-sm">Edit</a>
                            <form action="{{ route('chairperson.teachers.update', $teacher->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to update?');">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                            </form>
                            {{-- You may add a delete button here if you have delete functionality --}}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No teachers found.</p>
    @endif
</div>
@endsection
