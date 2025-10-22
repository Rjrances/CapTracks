@extends('layouts.chairperson')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">
                <i class="fas fa-chalkboard-teacher me-2"></i>Faculty Management
            </h2>
            <p class="text-muted mb-0">
                <i class="fas fa-users me-1"></i>
                @if($activeTerm)
                    Showing faculty members for {{ $activeTerm->semester }}
                    <span class="badge bg-info ms-2">Active Term</span>
                @else
                    Showing all faculty members in the system
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
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
                    <th>
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'faculty_id', 'direction' => request('sort') == 'faculty_id' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                           class="text-white text-decoration-none">
                            ID Number
                            @if(request('sort') == 'faculty_id')
                                @if(request('direction') == 'asc')
                                    <i class="fas fa-sort-up"></i>
                                @else
                                    <i class="fas fa-sort-down"></i>
                                @endif
                            @else
                                <i class="fas fa-sort text-muted"></i>
                            @endif
                        </a>
                    </th>
                    <th>
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => request('sort') == 'name' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                           class="text-white text-decoration-none">
                            Name
                            @if(request('sort') == 'name')
                                @if(request('direction') == 'asc')
                                    <i class="fas fa-sort-up"></i>
                                @else
                                    <i class="fas fa-sort-down"></i>
                                @endif
                            @else
                                <i class="fas fa-sort text-muted"></i>
                            @endif
                        </a>
                    </th>
                    <th>
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'email', 'direction' => request('sort') == 'email' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                           class="text-white text-decoration-none">
                            Email
                            @if(request('sort') == 'email')
                                @if(request('direction') == 'asc')
                                    <i class="fas fa-sort-up"></i>
                                @else
                                    <i class="fas fa-sort-down"></i>
                                @endif
                            @else
                                <i class="fas fa-sort text-muted"></i>
                            @endif
                        </a>
                    </th>
                    <th>
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'role', 'direction' => request('sort') == 'role' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                           class="text-white text-decoration-none">
                            Role
                            @if(request('sort') == 'role')
                                @if(request('direction') == 'asc')
                                    <i class="fas fa-sort-up"></i>
                                @else
                                    <i class="fas fa-sort-down"></i>
                                @endif
                            @else
                                <i class="fas fa-sort text-muted"></i>
                            @endif
                        </a>
                    </th>
                    <th>
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'department', 'direction' => request('sort') == 'department' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                           class="text-white text-decoration-none">
                            Department
                            @if(request('sort') == 'department')
                                @if(request('direction') == 'asc')
                                    <i class="fas fa-sort-up"></i>
                                @else
                                    <i class="fas fa-sort-down"></i>
                                @endif
                            @else
                                <i class="fas fa-sort text-muted"></i>
                            @endif
                        </a>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($teachers as $teacher)
                    <tr>
                        <td><strong>{{ $teacher->faculty_id }}</strong></td>
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
                                $allRoles = $teacher->all_roles;
                            @endphp
                            @if(count($allRoles) > 1)
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($allRoles as $role)
                                        @php
                                            $badgeColor = $roleColors[$role] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $badgeColor }}">{{ ucfirst($role) }}</span>
                                    @endforeach
                                </div>
                            @else
                                @php
                                    $role = $allRoles[0] ?? 'N/A';
                                    $badgeColor = $roleColors[$role] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $badgeColor }}">{{ ucfirst($role) }}</span>
                            @endif
                        </td>
                        <td>{{ $teacher->department ?? 'N/A' }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('chairperson.teachers.edit', $teacher->faculty_id) }}" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="{{ route('chairperson.teachers.delete', $teacher->faculty_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this faculty member?');">
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
        
        {{-- Pagination --}}
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted">
                Showing {{ $teachers->firstItem() }} to {{ $teachers->lastItem() }} of {{ $teachers->total() }} faculty members
            </div>
            <div>
                <nav aria-label="Teacher pagination">
                    {{ $teachers->appends(request()->query())->links('pagination::bootstrap-5') }}
                </nav>
            </div>
        </div>
    @else
        <div class="text-center py-5">
            <i class="fas fa-users fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No Faculty Members Found</h4>
            <p class="text-muted">
                @if($activeTerm)
                    No faculty members found for the active term: <strong>{{ $activeTerm->semester }}</strong>
                @else
                    No faculty members found in the system.
                @endif
            </p>
            <a href="{{ route('chairperson.teachers.create') }}" class="btn btn-primary">
                <i class="fas fa-upload me-1"></i>Import Faculty
            </a>
        </div>
    @endif
</div>

<style>
.pagination {
    margin-bottom: 0;
}
.pagination .page-link {
    color: #495057;
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
}
.pagination .page-link:hover {
    color: #0056b3;
    background-color: #e9ecef;
    border-color: #dee2e6;
}
.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}
.pagination .page-item.disabled .page-link {
    color: #6c757d;
    pointer-events: none;
    background-color: #fff;
    border-color: #dee2e6;
}
.pagination .page-item:not(:first-child) .page-link {
    margin-left: -1px;
}
</style>
@endsection
