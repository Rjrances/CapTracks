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

                        <!-- Sample Data -->
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="fas fa-lightbulb me-1"></i>Sample Data
                            </h6>
                            <p class="mb-2">Example of how your Excel should look:</p>
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
                                            <td>Dr. John Smith</td>
                                            <td>john.smith@university.edu</td>
                                            <td>FAC001</td>
                                            <td>adviser</td>
                                            <td>Computer Science</td>
                                        </tr>
                                        <tr>
                                            <td>Prof. Sarah Johnson</td>
                                            <td>sarah.johnson@university.edu</td>
                                            <td>FAC002</td>
                                            <td>adviser</td>
                                            <td>Information Technology</td>
                                        </tr>
                                        <tr>
                                            <td>Dr. Michael Brown</td>
                                            <td>michael.brown@university.edu</td>
                                            <td>FAC003</td>
                                            <td>panelist</td>
                                            <td>Software Engineering</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
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
                                <li>Role must be either "adviser" or "panelist"</li>
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
