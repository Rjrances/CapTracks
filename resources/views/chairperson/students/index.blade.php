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
                    <p class="text-muted mb-0">
                        View and manage students for the active term
                        @if($activeTerm)
                            <span class="badge bg-primary ms-2">{{ $activeTerm->semester }}</span>
                        @else
                            <span class="badge bg-warning ms-2">No Active Term</span>
                        @endif
                    </p>
                </div>
                                 <div class="d-flex gap-2">
                     <a href="{{ route('chairperson.upload-form') }}" class="btn btn-success">
                         <i class="fas fa-upload me-2"></i>Import Students
                     </a>
                 </div>
            </div>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('chairperson.students.index') }}" class="row g-3">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search Students</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Search by name, ID, email, or course...">
                        </div>
                        <div class="col-md-3">
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
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <a href="{{ route('chairperson.students.index') }}" 
                                   class="btn btn-outline-secondary {{ !request('search') && !request('course') ? 'd-none' : '' }}">
                                    <i class="fas fa-times me-2"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                    @if(request('search') || request('course'))
                        <div class="mt-3">
                            <small class="text-muted">
                                Showing {{ $students->total() }} students
                                @if(request('search'))
                                    matching "{{ request('search') }}"
                                @endif
                                @if(request('course'))
                                    in {{ request('course') }}
                                @endif
                                @if($activeTerm)
                                    for {{ $activeTerm->semester }}
                                @endif
                            </small>
                        </div>
                    @endif
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0">Student List</h5>
                    <small class="text-muted">Total: {{ $students->total() }} students</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="deleteSelectedBtn" class="btn btn-danger" style="display: none;">
                        <i class="fas fa-trash me-2"></i>Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                    <a href="{{ route('chairperson.students.export') }}?{{ http_build_query(request()->all()) }}" 
                       class="btn btn-outline-success">
                        <i class="fas fa-download me-2"></i>Export to CSV
                    </a>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 50px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll" style="cursor: pointer;">
                                            <label class="form-check-label text-white" for="selectAll" style="cursor: pointer; font-size: 0.8rem;">
                                                All
                                            </label>
                                        </div>
                                    </th>
                                    <th>
                                        <a href="{{ route('chairperson.students.index', array_merge(request()->query(), ['sort_by' => 'student_id', 'sort_order' => $sortBy === 'student_id' && $sortOrder === 'asc' ? 'desc' : 'asc'])) }}" 
                                           class="text-white text-decoration-none d-flex align-items-center justify-content-between">
                                            Student ID
                                            @if($sortBy === 'student_id')
                                                <i class="fas fa-sort-{{ $sortOrder === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @else
                                                <i class="fas fa-sort ms-1 text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ route('chairperson.students.index', array_merge(request()->query(), ['sort_by' => 'name', 'sort_order' => $sortBy === 'name' && $sortOrder === 'asc' ? 'desc' : 'asc'])) }}" 
                                           class="text-white text-decoration-none d-flex align-items-center justify-content-between">
                                            Name
                                            @if($sortBy === 'name')
                                                <i class="fas fa-sort-{{ $sortOrder === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @else
                                                <i class="fas fa-sort ms-1 text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ route('chairperson.students.index', array_merge(request()->query(), ['sort_by' => 'email', 'sort_order' => $sortBy === 'email' && $sortOrder === 'asc' ? 'desc' : 'asc'])) }}" 
                                           class="text-white text-decoration-none d-flex align-items-center justify-content-between">
                                            Email
                                            @if($sortBy === 'email')
                                                <i class="fas fa-sort-{{ $sortOrder === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @else
                                                <i class="fas fa-sort ms-1 text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ route('chairperson.students.index', array_merge(request()->query(), ['sort_by' => 'course', 'sort_order' => $sortBy === 'course' && $sortOrder === 'asc' ? 'desc' : 'asc'])) }}" 
                                           class="text-white text-decoration-none d-flex align-items-center justify-content-between">
                                            Course
                                            @if($sortBy === 'course')
                                                <i class="fas fa-sort-{{ $sortOrder === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @else
                                                <i class="fas fa-sort ms-1 text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Enrolled Offerings</th>
                                    <th>Group Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $student)
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input student-checkbox" type="checkbox" 
                                                       value="{{ $student->student_id }}" id="student_{{ $student->student_id }}"
                                                       data-student-name="{{ $student->name }}">
                                            </div>
                                        </td>
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
                                             @if($student->offerings->count() > 0)
                                                 @php
                                                     $offering = $student->offerings->first();
                                                 @endphp
                                                 <span class="badge bg-success">{{ $offering->subject_title }}</span>
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
                                                 <a href="{{ route('chairperson.students.edit', $student->student_id) }}" 
                                                    class="btn btn-outline-primary" 
                                                    data-bs-toggle="tooltip" title="Edit Student">
                                                     <i class="fas fa-edit"></i>
                                                 </a>
                                                 <button type="button" class="btn btn-outline-danger" 
                                                         data-bs-toggle="tooltip" title="Delete Student"
                                                         onclick="deleteStudent('{{ $student->student_id }}', '{{ $student->name }}')">
                                                     <i class="fas fa-trash"></i>
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
                                                @if(request('search') || request('course'))
                                                    Try adjusting your search criteria
                                                @elseif($activeTerm)
                                                    No students enrolled for {{ $activeTerm->semester }}
                                                @else
                                                    No active term selected or no students have been imported yet
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
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
.table-dark th a {
    transition: all 0.2s ease;
}
.table-dark th a:hover {
    opacity: 0.8;
    text-decoration: none;
}
.table-dark th a:active {
    transform: scale(0.98);
}
.btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}
.btn-outline-danger:active {
    transform: scale(0.95);
}
.form-check-input {
    cursor: pointer;
    transition: all 0.2s ease;
}
.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
.form-check-input:hover {
    transform: scale(1.1);
}
.form-check-label {
    cursor: pointer;
    user-select: none;
}
#deleteSelectedBtn {
    transition: all 0.2s ease;
}
#deleteSelectedBtn:hover {
    transform: scale(1.02);
}
#deleteSelectedBtn:active {
    transform: scale(0.98);
}
</style>
<form id="deleteStudentForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
<form id="bulkDeleteForm" method="POST" action="{{ route('chairperson.students.bulk-delete') }}" style="display: none;">
    @csrf
    @method('DELETE')
    <input type="hidden" name="student_ids" id="bulkDeleteStudentIds">
</form>
<script>
function deleteStudent(studentId, studentName) {
    if (confirm(`Are you sure you want to delete student "${studentName}"?\n\nThis action cannot be undone and will remove the student from all offerings and groups.`)) {
        const form = document.getElementById('deleteStudentForm');
        form.action = `/chairperson/students/${studentId}`;
        form.submit();
    }
}
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    initializeBulkSelection();
});
function initializeBulkSelection() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const studentCheckboxes = document.querySelectorAll('.student-checkbox');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        studentCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        updateBulkDeleteButton();
    });
    studentCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllState();
            updateBulkDeleteButton();
        });
    });
    function updateSelectAllState() {
        const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
        const totalCount = studentCheckboxes.length;
        if (checkedCount === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (checkedCount === totalCount) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
            selectAllCheckbox.checked = false;
        }
    }
    function updateBulkDeleteButton() {
        const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
        if (checkedCount > 0) {
            deleteSelectedBtn.style.display = 'inline-block';
            selectedCountSpan.textContent = checkedCount;
        } else {
            deleteSelectedBtn.style.display = 'none';
        }
    }
    deleteSelectedBtn.addEventListener('click', function() {
        const checkedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
        const studentIds = Array.from(checkedCheckboxes).map(cb => cb.value);
        const studentNames = Array.from(checkedCheckboxes).map(cb => cb.dataset.studentName);
        if (studentIds.length === 0) {
            alert('Please select at least one student to delete.');
            return;
        }
        const confirmMessage = `Are you sure you want to delete ${studentIds.length} selected student(s)?\n\n` +
                             `Students to be deleted:\n${studentNames.join('\n')}\n\n` +
                             `This action cannot be undone and will remove the students from all offerings and groups.`;
        if (confirm(confirmMessage)) {
            const form = document.getElementById('bulkDeleteForm');
            const input = document.getElementById('bulkDeleteStudentIds');
            if (form && input) {
                input.value = JSON.stringify(studentIds);
                form.submit();
            } else {
                alert('Error: Form not found. Please refresh the page and try again.');
            }
        }
    });
}
</script>
@endsection
