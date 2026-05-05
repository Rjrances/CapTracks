@extends('layouts.chairperson')
@section('title', 'Edit Teacher')
@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-user-edit me-2"></i>Edit Teacher
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
                    <form action="{{ route('chairperson.teachers.update', $teacher->faculty_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-id-card me-1"></i>School ID
                            </label>
                            <input type="text" 
                                   class="form-control bg-light" 
                                   value="{{ $teacher->faculty_id }}" 
                                   readonly>
                            <small class="text-muted">School ID cannot be changed</small>
                        </div>
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
                                           value="{{ old('name', $teacher->name) }}" 
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
                                           value="{{ old('email', $teacher->email) }}" 
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
                                    <label for="department" class="form-label fw-bold">
                                        <i class="fas fa-building me-1"></i>Department
                                    </label>
                                    <input type="text" 
                                           name="department" 
                                           id="department" 
                                           class="form-control @error('department') is-invalid @enderror" 
                                           value="{{ old('department', $teacher->department) }}" 
                                           placeholder="e.g., Computer Science">
                                    @error('department')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label fw-bold">
                                        <i class="fas fa-key me-1"></i>New Password
                                    </label>
                                    <input type="password" 
                                           name="password" 
                                           id="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           placeholder="Leave blank to keep current password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Leave blank to keep current password</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-info-circle me-1"></i>Current Role
                                    </label>
                                    <input type="text" 
                                           class="form-control bg-light" 
                                           value="{{ ucfirst($teacher->primary_role ?? 'N/A') }}" 
                                           readonly>
                                    <small class="text-muted">Coordinator access is managed on the Teachers page.</small>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Teacher
                            </button>
                            @if($teacher->hasRole('coordinator'))
                                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#removeCoordinatorModal">
                                    <i class="fas fa-user-minus me-1"></i>Remove Coordinator Access
                                </button>
                            @endif
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

@if($teacher->hasRole('coordinator'))
<div class="modal fade" id="removeCoordinatorModal" tabindex="-1" aria-labelledby="removeCoordinatorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="removeCoordinatorModalLabel">
                    <i class="fas fa-user-minus text-warning me-2"></i>Remove Coordinator Access
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Are you sure you want to remove coordinator access for:</p>
                <p class="fw-bold mb-0">{{ $teacher->name }} ({{ $teacher->faculty_id }})</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('chairperson.teachers.remove-coordinator', $teacher->faculty_id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-user-minus me-1"></i>Confirm Remove
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
