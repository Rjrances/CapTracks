@extends('layouts.chairperson')
@section('title', 'Add Students to Offering')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Add Students to Offering
                    </h2>
                    <p class="text-muted mb-0">
                        Adding students to: <strong>{{ $offering->subject_title }} ({{ $offering->subject_code }})</strong>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('chairperson.offerings.show', $offering->id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Offering
                    </a>
                </div>
            </div>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            <div class="alert alert-info">
                <h6 class="alert-heading">
                    <i class="fas fa-info-circle me-1"></i>Single Enrollment System
                </h6>
                <p class="mb-0">
                    Students can only be enrolled in one offering at a time. Adding students to this offering will automatically remove them from any other offerings they may be enrolled in.
                </p>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0">Available Students</h5>
                    <small class="text-muted">Total: {{ $unenrolledStudents->count() }} unenrolled students</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="addSelectedBtn" class="btn btn-primary" style="display: none;">
                        <i class="fas fa-user-plus me-2"></i>Add Selected (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    @if($unenrolledStudents->count() > 0)
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
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Course</th>
                                        <th>Semester</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($unenrolledStudents as $student)
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input student-checkbox" type="checkbox" 
                                                           value="{{ $student->id }}" id="student_{{ $student->id }}"
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
                                                <span class="badge bg-info">{{ $student->semester }}</span>
                                            </td>
                                            <td>
                                                <form action="{{ route('chairperson.offerings.enroll-student', $offering->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-success" 
                                                            onclick="return confirm('Add {{ $student->name }} to {{ $offering->subject_code }}?')">
                                                        <i class="fas fa-plus me-1"></i>Add
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No unenrolled students found</h6>
                            <p class="text-muted small">All students are currently enrolled in offerings.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<form id="bulkAddForm" method="POST" action="{{ route('chairperson.offerings.enroll-multiple-students', $offering->id) }}" style="display: none;">
    @csrf
    <input type="hidden" name="student_ids" id="bulkAddStudentIds">
</form>
<script>
const offeringSubjectCode = "{{ $offering->subject_code }}";
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing bulk selection...');
    initializeBulkSelection();
});
function initializeBulkSelection() {
    console.log('Initializing bulk selection...');
    const selectAllCheckbox = document.getElementById('selectAll');
    const studentCheckboxes = document.querySelectorAll('.student-checkbox');
    const addSelectedBtn = document.getElementById('addSelectedBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    console.log('Elements found:', {
        selectAll: selectAllCheckbox,
        studentCheckboxes: studentCheckboxes.length,
        addSelectedBtn: addSelectedBtn,
        selectedCountSpan: selectedCountSpan
    });
    if (!selectAllCheckbox || !addSelectedBtn) {
        console.error('Required elements not found');
        return;
    }
    selectAllCheckbox.addEventListener('change', function() {
        console.log('Select all changed to:', this.checked);
        const isChecked = this.checked;
        studentCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        updateBulkAddButton();
    });
    studentCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            console.log('Individual checkbox changed:', this.value, this.checked);
            updateSelectAllState();
            updateBulkAddButton();
        });
    });
    function updateSelectAllState() {
        const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
        const totalCount = studentCheckboxes.length;
        console.log('Updating select all state:', checkedCount, 'of', totalCount);
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
    function updateBulkAddButton() {
        const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
        console.log('Updating bulk add button:', checkedCount, 'checked');
        if (checkedCount > 0) {
            addSelectedBtn.style.display = 'inline-block';
            selectedCountSpan.textContent = checkedCount;
        } else {
            addSelectedBtn.style.display = 'none';
        }
    }
    addSelectedBtn.addEventListener('click', function() {
        console.log('Bulk add button clicked');
        const checkedCheckboxes = document.querySelectorAll('.student-checkbox:checked');
        const studentIds = Array.from(checkedCheckboxes).map(cb => cb.value);
        const studentNames = Array.from(checkedCheckboxes).map(cb => cb.dataset.studentName);
        console.log('Selected students:', studentIds, studentNames);
        if (studentIds.length === 0) {
            alert('Please select at least one student to add.');
            return;
        }
        const confirmMessage = `Are you sure you want to add ${studentIds.length} selected student(s) to ${offeringSubjectCode}?\n\n` +
                             `Students to be added:\n${studentNames.join('\n')}\n\n` +
                             `Note: This will remove them from any other offerings they may be enrolled in.`;
        if (confirm(confirmMessage)) {
            const form = document.getElementById('bulkAddForm');
            const input = document.getElementById('bulkAddStudentIds');
            if (form && input) {
                input.value = JSON.stringify(studentIds);
                console.log('Submitting form with:', input.value);
                form.submit();
            } else {
                alert('Error: Form not found. Please refresh the page and try again.');
            }
        }
    });
    updateBulkAddButton();
    console.log('Bulk selection initialized successfully');
}
</script>
@endsection
