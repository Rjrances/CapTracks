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
                                <li><strong>faculty_id</strong> - Required and must be unique</li>
                                <li><strong>first_name</strong> - Required</li>
                                <li><strong>middle_name</strong> - Optional</li>
                                <li><strong>last_name</strong> - Required</li>
                                <li><strong>name_prefix</strong> - Optional (e.g., Dr., Engr.)</li>
                                <li><strong>suffix</strong> - Optional (e.g., Jr., III)</li>
                                <li><strong>email</strong> - Required and must be unique</li>
                                <li><strong>role</strong> - Optional (defaults to teacher)</li>
                                <li><strong>department</strong> - Optional</li>
                                <li><strong>semester</strong> - Required (e.g., 2024-2025 First Semester)</li>
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
                                <li>All faculty members will have a default password: <strong>password123</strong></li>
                                <li>They will be required to change their password on first login</li>
                                <li>Email addresses must be unique</li>
                                <li>Faculty IDs must be provided in the CSV and must be unique</li>
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
