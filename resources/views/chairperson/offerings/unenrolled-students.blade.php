@extends('layouts.chairperson')
@section('title', 'Add Students to Offering')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
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
                    <button type="button" id="addSelectedBtn" class="btn btn-primary" style="display: none;"
                            data-bs-toggle="modal" data-bs-target="#bulkConfirmModal">
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
                                                <span class="badge bg-info">{{ $student->semester }}</span>
                                            </td>
                                            <td>
                                                {{-- Single add — opens modal instead of browser confirm --}}
                                                <button type="button" class="btn btn-sm btn-outline-success single-add-btn"
                                                        data-student-id="{{ $student->student_id }}"
                                                        data-student-name="{{ $student->name }}"
                                                        data-offering-code="{{ $offering->subject_code }}"
                                                        data-action="{{ route('chairperson.offerings.enroll-student', $offering->id) }}"
                                                        data-bs-toggle="modal" data-bs-target="#singleConfirmModal">
                                                    <i class="fas fa-plus me-1"></i>Add
                                                </button>
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

{{-- Hidden bulk form --}}
<form id="bulkAddForm" method="POST" action="{{ route('chairperson.offerings.enroll-multiple-students', $offering->id) }}" style="display: none;">
    @csrf
    <input type="hidden" name="student_ids" id="bulkAddStudentIds">
</form>

{{-- Hidden single-add form --}}
<form id="singleAddForm" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="student_id" id="singleAddStudentId">
</form>

{{-- ─── Single Add Confirm Modal ─────────────────────────────── --}}
<div class="modal fade" id="singleConfirmModal" tabindex="-1" aria-labelledby="singleConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="singleConfirmModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Confirm Enrollment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-1">You are about to enroll:</p>
                <p class="fw-bold fs-5 mb-1" id="singleStudentName">—</p>
                <p class="text-muted mb-3">into <strong id="singleOfferingCode">—</strong></p>
                <div class="alert alert-warning mb-0 py-2">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <small>This will automatically remove them from any other offering they are currently enrolled in.</small>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-success" id="singleConfirmBtn">
                    <i class="fas fa-check me-1"></i>Yes, Enroll
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ─── Bulk Add Confirm Modal ───────────────────────────────── --}}
<div class="modal fade" id="bulkConfirmModal" tabindex="-1" aria-labelledby="bulkConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="bulkConfirmModalLabel">
                    <i class="fas fa-users me-2"></i>Confirm Bulk Enrollment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-3">
                    You are about to enroll <strong><span id="bulkCount">0</span> student(s)</strong>
                    into <strong>{{ $offering->subject_code }}</strong>.
                </p>
                <div class="border rounded p-3 bg-light" style="max-height: 260px; overflow-y: auto;">
                    <p class="text-muted small mb-2 fw-semibold">Students to be enrolled:</p>
                    <ul class="list-unstyled mb-0" id="bulkStudentList"></ul>
                </div>
                <div class="alert alert-warning mb-0 mt-3 py-2">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <small>Students already enrolled in another offering will be automatically moved to this one.</small>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="bulkConfirmBtn">
                    <i class="fas fa-user-plus me-1"></i>Yes, Enroll All
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── Single Add Modal ─────────────────────────── */
    document.querySelectorAll('.single-add-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('singleStudentName').textContent = this.dataset.studentName;
            document.getElementById('singleOfferingCode').textContent = this.dataset.offeringCode;
            document.getElementById('singleAddStudentId').value      = this.dataset.studentId;
            document.getElementById('singleAddForm').action          = this.dataset.action;
        });
    });

    document.getElementById('singleConfirmBtn').addEventListener('click', function () {
        document.getElementById('singleAddForm').submit();
    });

    /* ── Bulk Add Modal ───────────────────────────── */
    const selectAllCheckbox = document.getElementById('selectAll');
    const studentCheckboxes = document.querySelectorAll('.student-checkbox');
    const addSelectedBtn    = document.getElementById('addSelectedBtn');
    const selectedCountSpan = document.getElementById('selectedCount');

    selectAllCheckbox.addEventListener('change', function () {
        studentCheckboxes.forEach(cb => cb.checked = this.checked);
        updateBulkAddButton();
    });

    studentCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            updateSelectAllState();
            updateBulkAddButton();
        });
    });

    function updateSelectAllState() {
        const checked = document.querySelectorAll('.student-checkbox:checked').length;
        const total   = studentCheckboxes.length;
        selectAllCheckbox.indeterminate = checked > 0 && checked < total;
        selectAllCheckbox.checked       = checked === total;
    }

    function updateBulkAddButton() {
        const count = document.querySelectorAll('.student-checkbox:checked').length;
        addSelectedBtn.style.display = count > 0 ? 'inline-block' : 'none';
        selectedCountSpan.textContent = count;
    }

    /* Populate bulk modal content before it shows */
    document.getElementById('bulkConfirmModal').addEventListener('show.bs.modal', function () {
        const checked      = document.querySelectorAll('.student-checkbox:checked');
        const studentIds   = Array.from(checked).map(cb => cb.value);
        const studentNames = Array.from(checked).map(cb => cb.dataset.studentName);

        document.getElementById('bulkCount').textContent = studentIds.length;

        const list = document.getElementById('bulkStudentList');
        list.innerHTML = studentNames.map(name =>
            `<li class="py-1 border-bottom"><i class="fas fa-user text-primary me-2 small"></i>${name}</li>`
        ).join('');

        document.getElementById('bulkAddStudentIds').value = JSON.stringify(studentIds);
    });

    document.getElementById('bulkConfirmBtn').addEventListener('click', function () {
        document.getElementById('bulkAddForm').submit();
    });

    updateBulkAddButton();
});
</script>
@endsection
