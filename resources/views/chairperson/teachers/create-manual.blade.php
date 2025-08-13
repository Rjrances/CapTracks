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
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

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
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-bold">
                                        <i class="fas fa-user me-1"></i>Full Name *
                                    </label>
                                    <input type="text" 
                                           name="name" 
                                           id="name" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name') }}" 
                                           placeholder="Enter full name"
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
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
                                    <label for="school_id" class="form-label fw-bold">
                                        <i class="fas fa-id-card me-1"></i>School ID *
                                    </label>
                                    <input type="text" 
                                           name="school_id" 
                                           id="school_id" 
                                           class="form-control @error('school_id') is-invalid @enderror" 
                                           value="{{ old('school_id') }}" 
                                           placeholder="e.g., FAC001"
                                           required>
                                    @error('school_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label fw-bold">
                                        <i class="fas fa-user-tag me-1"></i>Role *
                                    </label>
                                    <select name="role" 
                                            id="role" 
                                            class="form-select @error('role') is-invalid @enderror" 
                                            required>
                                        <option value="">Select Role</option>
                                        <option value="adviser" {{ old('role') == 'adviser' ? 'selected' : '' }}>Adviser</option>
                                        <option value="panelist" {{ old('role') == 'panelist' ? 'selected' : '' }}>Panelist</option>
                                    </select>
                                    @error('role')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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

                        <!-- Important Notes -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle me-1"></i>Important Notes
                            </h6>
                            <ul class="mb-0">
                                <li>Default password will be: <strong>password123</strong></li>
                                <li>Teacher will be required to change password on first login</li>
                                <li>Email and School ID must be unique</li>
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
