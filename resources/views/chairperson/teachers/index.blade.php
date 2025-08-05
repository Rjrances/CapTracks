@extends('layouts.chairperson')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-chalkboard-teacher me-2"></i>Faculty Management
        </h2>
        <a href="{{ route('chairperson.teachers.create') }}" class="btn btn-success">
            <i class="fas fa-upload me-1"></i>Import Faculty
        </a>
    </div>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Error Message --}}
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($teachers && $teachers->count())
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
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('chairperson.teachers.edit', $teacher->id) }}" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="{{ route('chairperson.teachers.delete', $teacher->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this faculty member?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
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
