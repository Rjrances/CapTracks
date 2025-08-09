@extends('layouts.chairperson')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">
                <i class="fas fa-chalkboard-teacher me-2"></i>Faculty Management
            </h2>
            @if($activeTerm)
                <p class="text-muted mb-0">
                    <i class="fas fa-calendar-alt me-1"></i>
                    @if($showAllTerms)
                        Showing faculty assigned to offerings in: <strong>All Terms</strong>
                        <span class="badge bg-info ms-2">All Terms</span>
                    @else
                        Showing faculty assigned to offerings in: <strong>{{ $activeTerm->full_name }}</strong>
                        <span class="badge bg-success ms-2">Active Term</span>
                    @endif
                </p>
            @else
                <p class="text-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    No active academic term set. Please set an active term to view faculty assignments.
                </p>
            @endif
        </div>
        <div class="d-flex gap-2">
            @if($activeTerm)
                @if($showAllTerms)
                    <a href="{{ route('chairperson.teachers.index') }}" class="btn btn-outline-success">
                        <i class="fas fa-calendar-check"></i> Show Active Term Only
                    </a>
                @else
                    <a href="{{ route('chairperson.teachers.index', ['show_all' => true]) }}" class="btn btn-outline-info">
                        <i class="fas fa-calendar-alt"></i> Show All Terms
                    </a>
                @endif
            @endif
            <a href="{{ route('chairperson.teachers.create-manual') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Add Teacher
            </a>
            <a href="{{ route('chairperson.teachers.create') }}" class="btn btn-success">
                <i class="fas fa-upload me-1"></i>Import Faculty
            </a>
        </div>
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
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($teachers as $teacher)
                    <tr>
                        <td><strong>{{ $teacher->school_id }}</strong></td>
                        <td>{{ $teacher->name }}</td>
                        <td>{{ $teacher->email }}</td>
                        <td><span class="badge bg-{{ $teacher->role == 'adviser' ? 'primary' : 'info' }}">{{ ucfirst($teacher->role) }}</span></td>
                        <td>{{ $teacher->department ?? 'N/A' }}</td>
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
