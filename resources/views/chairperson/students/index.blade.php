@extends('layouts.chairperson')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-users me-2"></i>Student Management
                    </h2>
                    <p class="text-muted mb-0">View and manage all students in the system</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('chairperson.upload-form') }}" class="btn btn-success">
                        <i class="fas fa-upload me-2"></i>Import Students
                    </a>
                    <a href="{{ route('chairperson.offerings.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Offerings
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Search and Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('chairperson.students.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Students</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Search by name, ID, email, or course...">
                        </div>
                        <div class="col-md-2">
                            <label for="course" class="form-label">Course</label>
                            <select class="form-select" id="course" name="course">
                                <option value="">All Courses</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course }}" {{ request('course') == $course ? 'selected' : '' }}>
                                        {{ $course }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="semester" class="form-label">Semester</label>
                            <select class="form-select" id="semester" name="semester">
                                <option value="">All Semesters</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester }}" {{ request('semester') == $semester ? 'selected' : '' }}>
                                        {{ $semester }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <a href="{{ route('chairperson.students.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    @if(request('search') || request('course') || request('semester'))
                        <div class="mt-3">
                            <small class="text-muted">
                                Showing {{ $students->total() }} students
                                @if(request('search'))
                                    matching "{{ request('search') }}"
                                @endif
                                @if(request('course'))
                                    in {{ request('course') }}
                                @endif
                                @if(request('semester'))
                                    in {{ request('semester') }}
                                @endif
                            </small>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Export Button -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0">Student List</h5>
                    <small class="text-muted">Total: {{ $students->total() }} students</small>
                </div>
                <div>
                    <a href="{{ route('chairperson.students.export') }}?{{ http_build_query(request()->all()) }}" 
                       class="btn btn-outline-success">
                        <i class="fas fa-download me-2"></i>Export to CSV
                    </a>
                </div>
            </div>

            <!-- Students Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Course</th>
                                    <th>Semester</th>
                                    <th>Enrolled Offerings</th>
                                    <th>Group Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $student)
                                    <tr>
                                        <td>
                                            <strong>{{ $student->student_id }}</strong>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $student->name }}</div>
                                        </td>
                                        <td>
                                            <small>{{ $student->email }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">{{ $student->course }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $student->semester }}</span>
                                        </td>
                                        <td>
                                            @if($student->offerings->count() > 0)
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($student->offerings->take(2) as $offering)
                                                        <span class="badge bg-success">{{ $offering->subject_code }}</span>
                                                    @endforeach
                                                    @if($student->offerings->count() > 2)
                                                        <span class="badge bg-secondary">+{{ $student->offerings->count() - 2 }} more</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="badge bg-warning">Not Enrolled</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($student->groups->count() > 0)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-users me-1"></i>{{ $student->groups->first()->name }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">No Group</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-info" 
                                                        data-bs-toggle="tooltip" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-primary" 
                                                        data-bs-toggle="tooltip" title="Edit Student">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">No students found</h6>
                                            <p class="text-muted small">
                                                @if(request('search') || request('course') || request('semester'))
                                                    Try adjusting your search criteria
                                                @else
                                                    No students have been imported yet
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($students->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            <nav aria-label="Student pagination">
                                {{ $students->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </nav>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom pagination styling */
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

/* Ensure proper spacing */
.pagination .page-item:not(:first-child) .page-link {
    margin-left: -1px;
}
</style>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection
