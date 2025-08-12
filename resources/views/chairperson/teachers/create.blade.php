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

                    <form action="{{ route('chairperson.teachers.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="file" class="form-label fw-bold">
                                <i class="fas fa-file-excel me-1"></i>Select Excel File
                            </label>
                            <input type="file" name="file" id="file" class="form-control" accept=".xlsx,.xls" required>
                            <div class="form-text">
                                Upload an Excel file (.xlsx or .xls) containing faculty information.
                            </div>
                        </div>

                        <!-- Excel Template Information -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle me-1"></i>Excel File Format
                            </h6>
                            <p class="mb-2">Your Excel file should have the following columns:</p>
                            <ul class="mb-0">
                                <li><strong>name</strong></li>
                                <li><strong>email</strong></li>
                                <li><strong>school_id</strong></li>
                                <li><strong>role</strong></li>
                                <li><strong>course</strong></li>
                            </ul>
                        </div>

                        <!-- Excel Format Guide -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle me-1"></i>Excel Format Guide
                            </h6>
                            <p class="mb-2">Your Excel file should have these columns:</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>name</th>
                                            <th>email</th>
                                            <th>school_id</th>
                                            <th>role</th>
                                            <th>course</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Faculty Name</td>
                                            <td>email@university.edu</td>
                                            <td>12345</td>
                                            <td>teacher</td>
                                            <td>Department</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="mb-0 mt-2">
                                <strong>Notes:</strong><br>
                                • The school_id must be exactly 5 digits (e.g., 12345, 67890)<br>
                                • The role column is optional - if not specified, it will default to "teacher"<br>
                            </p>
                        </div>

                        <!-- Important Notes -->
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-1"></i>Important Notes
                            </h6>
                            <ul class="mb-0">
                                <li>All faculty members will have a default password: <strong>password123</strong></li>
                                <li>They will be required to change their password on first login</li>
                                <li>Email addresses and School IDs must be unique</li>
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
