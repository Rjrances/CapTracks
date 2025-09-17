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
                        Showing <strong>All Faculty & Staff</strong> (all users except students)
                        <span class="badge bg-success ms-2">All Faculty & Staff</span>
                    @else
                        Showing faculty assigned to offerings in: <strong>Active Term Only</strong> (use "Show All Faculty" to see all faculty & staff)
                        <span class="badge bg-info ms-2">Active Term Only</span>
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
                    <a href="{{ route('chairperson.teachers.index') }}" class="btn btn-outline-info">
                        <i class="fas fa-calendar-alt"></i> Show Active Term Only
                    </a>
                @else
                    <a href="{{ route('chairperson.teachers.index', ['show_all' => true]) }}" class="btn btn-outline-success">
                        <i class="fas fa-users"></i> Show All Faculty & Staff
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
                        <td>
                            @php
                                $roleColors = [
                                    'adviser' => 'primary',
                                    'coordinator' => 'success', 
                                    'teacher' => 'info',
                                    'panelist' => 'warning',
                                    'chairperson' => 'danger',
                                    'admin' => 'dark'
                                ];
                                $badgeColor = $roleColors[$teacher->role] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $badgeColor }}">{{ ucfirst($teacher->role ?? 'N/A') }}</span>
                            @if($teacher->effective_roles_string !== ucfirst($teacher->role))
                                <br><small class="text-muted">Effective: {{ $teacher->effective_roles_string }}</small>
                            @endif
                        </td>
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
        @if($showAllTerms)
            <div class="mt-3">
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-1"></i>All Faculty & Staff View
                    </h6>
                    <p class="mb-0 small">
                        This view shows all users in the system except students. 
                        You can see faculty, staff, administrators, and other roles for complete system management.
                    </p>
                </div>
            </div>
        @endif
    @else
        <p>No teachers found.</p>
    @endif
</div>
@endsection
