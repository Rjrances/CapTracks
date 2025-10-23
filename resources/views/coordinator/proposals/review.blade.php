@extends('layouts.coordinator')

@section('title', 'Review Proposal')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">
                    <i class="fas fa-gavel me-2"></i>Review Proposal
                </h2>
                <a href="{{ route('coordinator.proposals.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Proposals
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt me-2"></i>Proposal Details
                            </h5>
                        </div>
                        <div class="card-body">
                            @php
                                $student = $proposal->getStudentData();
                            @endphp
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Student:</strong>
                                    <p class="mb-0">{{ $student ? $student->name : 'Unknown' }}</p>
                                    <small class="text-muted">{{ $student ? $student->student_id : 'N/A' }}</small>
                                </div>
                                <div class="col-md-6">
                                    <strong>Group:</strong>
                                    <p class="mb-0">{{ $studentGroup->name ?? 'No Group' }}</p>
                                    <small class="text-muted">{{ $offering->subject_code ?? 'N/A' }}</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <strong>Proposal Title:</strong>
                                <p class="mb-0">{{ $proposal->title ?? 'Untitled Proposal' }}</p>
                            </div>

                            @if($proposal->description)
                                <div class="mb-3">
                                    <strong>Description:</strong>
                                    <p class="mb-0">{{ $proposal->description }}</p>
                                </div>
                            @endif

                            @if($proposal->file_path)
                                <div class="mb-3">
                                    <strong>Attached File:</strong>
                                    <div class="mt-2">
                                        <a href="{{ Storage::url($proposal->file_path) }}" 
                                           target="_blank" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-download me-1"></i>Download File
                                        </a>
                                    </div>
                                </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Submitted:</strong>
                                    <p class="mb-0">{{ $proposal->submitted_at ? $proposal->submitted_at->format('M d, Y H:i') : 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Current Status:</strong>
                                    <p class="mb-0">
                                        @switch($proposal->status)
                                            @case('pending')
                                                <span class="badge bg-warning">Pending Review</span>
                                                @break
                                            @case('approved')
                                                <span class="badge bg-success">Approved</span>
                                                @break
                                            @case('rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($proposal->status) }}</span>
                                        @endswitch
                                    </p>
                                </div>
                            </div>

                            @if($proposal->teacher_comment)
                                <div class="mb-3">
                                    <strong>Previous Comment:</strong>
                                    <div class="alert alert-info">
                                        <i class="fas fa-comment me-2"></i>{{ $proposal->teacher_comment }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-gavel me-2"></i>Review Decision
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('coordinator.proposals.update', $proposal->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label fw-semibold">Decision</label>
                                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                        <option value="approved" {{ old('status', 'approved') === 'approved' ? 'selected' : '' }}>
                                            Approve Proposal
                                        </option>
                                        <option value="rejected" {{ old('status') === 'rejected' ? 'selected' : '' }}>
                                            Reject Proposal
                                        </option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="teacher_comment" class="form-label fw-semibold">
                                        Comments <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="teacher_comment" 
                                              id="teacher_comment" 
                                              class="form-control @error('teacher_comment') is-invalid @enderror" 
                                              rows="4" 
                                              placeholder="Provide detailed feedback for the student..."
                                              required>{{ old('teacher_comment', $proposal->teacher_comment) }}</textarea>
                                    @error('teacher_comment')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        Minimum 10 characters. This feedback will be sent to the student.
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check me-1"></i>Submit Review
                                    </button>
                                    <a href="{{ route('coordinator.proposals.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Review Guidelines
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0 small">
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Check proposal completeness
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Verify technical feasibility
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Ensure alignment with course objectives
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Provide constructive feedback
                                </li>
                                <li class="mb-0">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Be specific about required changes
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const commentTextarea = document.getElementById('teacher_comment');
    
    statusSelect.addEventListener('change', function() {
        const submitButton = document.querySelector('button[type="submit"]');
        if (this.value === 'approved') {
            submitButton.innerHTML = '<i class="fas fa-check me-1"></i>Approve Proposal';
            submitButton.className = 'btn btn-success';
        } else if (this.value === 'rejected') {
            submitButton.innerHTML = '<i class="fas fa-times me-1"></i>Reject Proposal';
            submitButton.className = 'btn btn-danger';
        } else {
            submitButton.innerHTML = '<i class="fas fa-check me-1"></i>Submit Review';
            submitButton.className = 'btn btn-success';
        }
    });
    
    commentTextarea.addEventListener('input', function() {
        const length = this.value.length;
        const minLength = 10;
        const submitButton = document.querySelector('button[type="submit"]');
        
        if (length < minLength) {
            submitButton.disabled = true;
            submitButton.title = `Minimum ${minLength} characters required`;
        } else {
            submitButton.disabled = false;
            submitButton.title = '';
        }
    });
    
    commentTextarea.dispatchEvent(new Event('input'));
});
</script>
@endsection
