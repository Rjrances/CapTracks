@extends('layouts.student')

@section('title', 'Upload Project Submission')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Upload Project Submission</h2>
            <p class="text-muted mb-0">Submit important project documents outside of milestone tasks</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('student.milestones') }}" class="btn btn-outline-info">
                <i class="fas fa-tasks me-2"></i>View Milestones
            </a>
            <a href="{{ route('student.project') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Submissions
            </a>
        </div>
    </div>

    <!-- Information Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="fas fa-file-alt fa-2x text-primary mb-3"></i>
                    <h5 class="card-title">Project Proposal</h5>
                    <p class="card-text small">Initial project proposal and concept document</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-flag-checkered fa-2x text-success mb-3"></i>
                    <h5 class="card-title">Final Report</h5>
                    <p class="card-text small">Complete project documentation and final report</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="fas fa-paperclip fa-2x text-info mb-3"></i>
                    <h5 class="card-title">Additional Files</h5>
                    <p class="card-text small">Presentations, demos, or supplementary materials</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Submission Form -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-upload me-2"></i>Upload Document
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('student.project.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="type" class="form-label">
                                <i class="fas fa-tag me-1"></i>Submission Type
                            </label>
                            <select name="type" id="type" class="form-select" required>
                                <option value="">Select submission type...</option>
                                <option value="proposal">📋 Project Proposal</option>
                                <option value="final">🏁 Final Report</option>
                                <option value="other">📎 Additional Files</option>
                            </select>
                            <div class="form-text">Choose the type of document you're submitting</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="file" class="form-label">
                                <i class="fas fa-file me-1"></i>Document File
                            </label>
                            <input type="file" name="file" id="file" class="form-control" required accept=".pdf,.doc,.docx,.zip,.pptx,.ppt">
                            <div class="form-text">Supported formats: PDF, DOC, DOCX, ZIP, PPT, PPTX (Max: 10MB)</div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">
                        <i class="fas fa-align-left me-1"></i>Description (Optional)
                    </label>
                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="Brief description of what this document contains..."></textarea>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> For milestone-specific tasks, please use the 
                    <a href="{{ route('student.milestones') }}" class="alert-link">Milestones section</a> 
                    instead. This form is for general project documents.
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-2"></i>Upload Document
                    </button>
                    <a href="{{ route('student.project') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="mt-4">
        <h6 class="text-muted mb-3">Quick Links</h6>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('student.milestones') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-tasks me-1"></i>Milestone Tasks
            </a>
            <a href="{{ route('student.proposal') }}" class="btn btn-outline-info btn-sm">
                <i class="fas fa-file-alt me-1"></i>Proposal & Endorsement
            </a>
            <a href="{{ route('student.defense-requests.index') }}" class="btn btn-outline-warning btn-sm">
                <i class="fas fa-gavel me-1"></i>Defense Requests
            </a>
        </div>
    </div>
</div>

@push('styles')
<style>
.card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
}

.form-select:focus, .form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.alert-link {
    font-weight: 600;
}

.btn {
    border-radius: 6px;
}

@media (max-width: 768px) {
    .d-flex.gap-2 {
        flex-direction: column;
    }
    
    .d-flex.gap-2 .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>
@endpush
@endsection 