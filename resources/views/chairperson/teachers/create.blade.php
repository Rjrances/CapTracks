@extends('layouts.chairperson')
@section('title', 'Add Teachers')
@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-upload me-2"></i>Import Teachers/Faculty
                    </h4>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    <form action="{{ route('chairperson.teachers.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label for="file" class="form-label fw-bold">
                                <i class="fas fa-file-csv me-1"></i>Select CSV File
                            </label>
                            <input type="file" name="file" id="file" class="form-control" accept=".csv" required>
                            <div class="form-text">
                                Upload a CSV file containing faculty information.
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle me-1"></i>CSV Format Required
                            </h6>
                            <p class="mb-2">Your CSV file should have these columns:</p>
                            <ul class="mb-0">
                                <li><strong>faculty_id</strong> - Required (existing IDs are updated on re-import)</li>
                                <li><strong>first_name</strong> - Required</li>
                                <li><strong>middle_name</strong> - Optional</li>
                                <li><strong>last_name</strong> - Required</li>
                                <li><strong>name_prefix</strong> - Optional (e.g., Dr., Engr.)</li>
                                <li><strong>suffix</strong> - Optional (e.g., Jr., III)</li>
                                <li><strong>email</strong> - Required (used to match existing faculty when re-importing)</li>
                                <li><strong>role</strong> - Optional (defaults to teacher)</li>
                                <li><strong>department</strong> - Optional</li>
                                <li><strong>school_year</strong> - e.g. <code>2025-2026</code> (required when semester is <code>1st</code>, <code>2nd</code>, or <code>summer</code>)</li>
                                <li><strong>semester</strong> - <code>1st</code>, <code>2nd</code>, or <code>summer</code> with <code>school_year</code>, or the full term text matching Academic Terms (e.g. <code>2024-2025 First Semester</code>)</li>
                            </ul>
                            <hr class="my-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-download me-2"></i>
                                <a href="/faculty_import_template.csv" class="btn btn-sm btn-outline-info" download>
                                    Download CSV Template
                                </a>
                                <small class="text-muted ms-2">Use this template to ensure correct format</small>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <strong>Legacy support:</strong> A single <code>name</code> column is still accepted, but split name columns are recommended.
                                </small>
                            </div>
                        </div>
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-1"></i>Important Notes
                            </h6>
                            <ul class="mb-0">
                                <li>New faculty accounts use the default password <strong>password123</strong> until they change it on first login</li>
                                <li><strong>New vs existing:</strong> If you import <strong>10</strong> rows and <strong>9</strong> already exist (matched by <code>faculty_id</code> or email), those <strong>9</strong> are only <strong>updated</strong>—no duplicate accounts. Only the <strong>1</strong> new row creates a <strong>new</strong> faculty login.</li>
                                <li>Re-importing updates existing faculty when IDs or emails match</li>
                                <li>Faculty IDs must be provided in the CSV</li>
                            </ul>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-upload me-1"></i>Import Faculty
                            </button>
                            <a href="{{ route('chairperson.teachers.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Teachers
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
