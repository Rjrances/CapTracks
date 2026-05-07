@extends('layouts.chairperson')
@section('title', 'Add Teacher')
@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-user-plus me-2"></i>Add New Teacher
                    </h4>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    <form action="{{ route('chairperson.teachers.store-manual') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="name_prefix" class="form-label fw-bold">
                                        Prefix
                                    </label>
                                    <input type="text" 
                                           name="name_prefix" 
                                           id="name_prefix" 
                                           class="form-control @error('name_prefix') is-invalid @enderror" 
                                           value="{{ old('name_prefix') }}" 
                                           placeholder="Dr.">
                                    @error('name_prefix')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label fw-bold">
                                        <i class="fas fa-user me-1"></i>First Name *
                                    </label>
                                    <input type="text" 
                                           name="first_name" 
                                           id="first_name" 
                                           class="form-control @error('first_name') is-invalid @enderror" 
                                           value="{{ old('first_name') }}" 
                                           placeholder="Enter first name"
                                           required>
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="middle_name" class="form-label fw-bold">Middle Name</label>
                                    <input type="text" 
                                           name="middle_name" 
                                           id="middle_name" 
                                           class="form-control @error('middle_name') is-invalid @enderror" 
                                           value="{{ old('middle_name') }}" 
                                           placeholder="Optional">
                                    @error('middle_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label fw-bold">Last Name *</label>
                                    <input type="text" 
                                           name="last_name" 
                                           id="last_name" 
                                           class="form-control @error('last_name') is-invalid @enderror" 
                                           value="{{ old('last_name') }}" 
                                           placeholder="Enter last name"
                                           required>
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="suffix" class="form-label fw-bold">Suffix</label>
                                    <input type="text" 
                                           name="suffix" 
                                           id="suffix" 
                                           class="form-control @error('suffix') is-invalid @enderror" 
                                           value="{{ old('suffix') }}" 
                                           placeholder="Jr., III">
                                    @error('suffix')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-10">
                                <div class="mb-3">
                                    <label for="email" class="form-label fw-bold">
                                        <i class="fas fa-envelope me-1"></i>Email Address *
                                    </label>
                                    <input type="email" 
                                           name="email" 
                                           id="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           value="{{ old('email') }}" 
                                           placeholder="email@university.edu"
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="faculty_id" class="form-label fw-bold">
                                        <i class="fas fa-id-card me-1"></i>Faculty ID *
                                    </label>
                                    <input type="text" 
                                           name="faculty_id" 
                                           id="faculty_id" 
                                           class="form-control @error('faculty_id') is-invalid @enderror" 
                                           value="{{ old('faculty_id') }}" 
                                           placeholder="e.g., 10001, 10002, 10003"
                                           required>
                                    @error('faculty_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Enter a unique faculty ID (e.g., 10001, 10002, etc.)
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role_display" class="form-label fw-bold">
                                        <i class="fas fa-user-tag me-1"></i>Role
                                    </label>
                                    <input type="text" id="role_display" class="form-control" value="Teacher" readonly>
                                    <div class="form-text">Role is automatically assigned as teacher for manual adding.</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="department" class="form-label fw-bold">
                                        <i class="fas fa-building me-1"></i>Department
                                    </label>
                                    <input type="text" 
                                           name="department" 
                                           id="department" 
                                           class="form-control @error('department') is-invalid @enderror" 
                                           value="{{ old('department') }}" 
                                           placeholder="e.g., Computer Science">
                                    @error('department')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle me-1"></i>Important Notes
                            </h6>
                            <ul class="mb-0">
                                <li>Default password will be: <strong>password123</strong></li>
                                <li>Teacher will be required to change password on first login</li>
                                <li>Email and Faculty ID must be unique</li>
                                <li>Faculty ID format: 10001, 10002, 10003, etc.</li>
                                <li>All fields marked with * are required</li>
                            </ul>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Add Teacher
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
