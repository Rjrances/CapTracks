@extends('layouts.chairperson')
@section('content')
<style>
.btn-loading {
    display: inline-block;
}
.btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
.form-text {
    font-size: 0.875rem;
    color: #6c757d;
}
.alert {
    border-radius: 0.5rem;
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
.alert-success {
    background-color: #d1e7dd;
    color: #0f5132;
    border-left: 4px solid #198754;
}
.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}
.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid #0dcaf0;
}
.alert-warning {
    background-color: #fff3cd;
    color: #664d03;
    border-left: 4px solid #ffc107;
}
</style>
<div class="container mt-5">
    <h2 class="mb-4 text-center">Import Student List</h2>
    @if(request('offering_id'))
        @php
            $offering = \App\Models\Offering::find(request('offering_id'));
        @endphp
        @if($offering)
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Importing students for:</strong> {{ $offering->subject_title }} ({{ $offering->subject_code }})
                <br>
                <small class="text-muted">Students will be imported and automatically enrolled in this offering. Note: Students can only be enrolled in one offering at a time.</small>
            </div>
        @endif
    @endif
    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    {{-- Error Messages --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    {{-- Form Card --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('chairperson.upload-students') }}" method="POST" enctype="multipart/form-data" id="importForm">
                @csrf
                @if(request('offering_id'))
                    <input type="hidden" name="offering_id" value="{{ request('offering_id') }}">
                @endif
                <div class="mb-3">
                    <label for="file" class="form-label">Select CSV File</label>
                    <input type="file" name="file" class="form-control" required accept=".csv" id="fileInput">
                    <div class="form-text">Maximum file size: 10MB. Supported format: .csv</div>
                </div>
                <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                    <span class="btn-text">
                        <i class="fas fa-upload me-2"></i>Upload & Import
                    </span>
                    <span class="btn-loading d-none">
                        <i class="fas fa-spinner fa-spin me-2"></i>Importing...
                    </span>
                </button>
            </form>
            {{-- Format Information --}}
            <div class="mt-3">
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-1"></i>Excel Format Required
                    </h6>
                    <p class="mb-2">Your Excel file should have these columns:</p>
                    <ul class="mb-0">
                        <li><strong>student_id</strong> - Student ID number (must be exactly 10 digits)</li>
                        <li><strong>name</strong> - Full name</li>
                        <li><strong>email</strong> - Email address (must be unique)</li>
                        <li><strong>semester</strong> - Current semester (format: "2024-2025 First Semester", "2024-2025 Second Semester", "2024-2025 Summer")</li>
                        <li><strong>course</strong> - Course/Program (BS Computer Science, BS Information Technology, BS Entertainment and Multimedia Computing)</li>
                        <li><strong>offer_code</strong> - Offering code for automatic enrollment (e.g., "11000", "11001", "11002")</li>
                    </ul>
                    <hr class="my-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-download me-2"></i>
                        <a href="/student_import_template_final.csv" class="btn btn-sm btn-outline-info" download>
                            Download CSV Template
                        </a>
                        <small class="text-muted ms-2">Use this template to ensure correct format</small>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-magic me-1"></i>
                            <strong>Automatic Enrollment:</strong> Students will be automatically enrolled in their specified offering based on the offer_code.
                        </small>
                    </div>
                </div>
                @if(request('offering_id') && $offering ?? null)
                    <div class="alert alert-success">
                        <h6 class="alert-heading">
                            <i class="fas fa-check-circle me-1"></i>Automatic Enrollment
                        </h6>
                        <p class="mb-2">After importing students:</p>
                        <ol class="mb-0">
                            <li>Students will be added to the system</li>
                            <li>Students will be automatically enrolled in <strong>{{ $offering->subject_title }}</strong></li>
                            <li>No manual enrollment needed!</li>
                        </ol>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('importForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    const fileInput = document.getElementById('fileInput');
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        const maxSize = 10 * 1024 * 1024; 
        if (file && file.size > maxSize) {
            alert('File size exceeds 10MB limit. Please choose a smaller file.');
            this.value = '';
            return;
        }
    });
    form.addEventListener('submit', function() {
        if (!fileInput.files[0]) {
            alert('Please select a file to upload.');
            return false;
        }
        submitBtn.disabled = true;
        btnText.classList.add('d-none');
        btnLoading.classList.remove('d-none');
        setTimeout(() => {
            submitBtn.disabled = false;
        }, 100);
    });
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.classList.contains('alert-success') || alert.classList.contains('alert-danger')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 8000);
    });
});
</script>
@endsection
