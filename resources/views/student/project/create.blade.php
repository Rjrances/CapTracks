@extends('layouts.student')
@section('title', 'Upload Project Submission')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Quick File Uploads</h2>
            <p class="text-muted mb-0">Upload supplementary project documents quickly and easily</p>
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
    <div class="alert alert-info mb-4">
        <div class="d-flex align-items-start">
            <i class="fas fa-info-circle fa-2x me-3 mt-1"></i>
            <div>
                <h5 class="alert-heading mb-2">Quick File Uploads</h5>
                <p class="mb-2">This section is for uploading supplementary project documents quickly. For formal project proposals, please use the <strong>Project Proposals</strong> section instead.</p>
                <div class="d-flex gap-3">
                    <a href="{{ route('student.proposal') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-file-contract me-1"></i>Go to Project Proposals
                    </a>
                    <a href="{{ route('student.milestones') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-tasks me-1"></i>View Milestone Tasks
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-flag-checkered fa-2x text-success mb-3"></i>
                    <h5 class="card-title">Final Report</h5>
                    <p class="card-text small">Complete project documentation and final report</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="fas fa-paperclip fa-2x text-info mb-3"></i>
                    <h5 class="card-title">Additional Files</h5>
                    <p class="card-text small">Presentations, demos, or supplementary materials</p>
                </div>
            </div>
        </div>
    </div>
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
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> This is for supplementary documents only. For formal project proposals, milestone tasks, or other structured submissions, please use the appropriate sections in the navigation menu.
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
    <div class="mt-4">
        <h6 class="text-muted mb-3">Quick Links</h6>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('student.milestones') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-tasks me-1"></i>Milestone Tasks
            </a>
            <a href="{{ route('student.proposal') }}" class="btn btn-outline-info btn-sm">
                <i class="fas fa-file-contract me-1"></i>Project Proposals
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
