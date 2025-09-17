@extends('layouts.adviser')
@section('title', 'Review Submission')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Review Submission</h2>
            <p class="text-muted mb-0">Provide feedback and update submission status</p>
        </div>
        <a href="{{ route('adviser.project.show', $submission->id) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Review
        </a>
    </div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Review Form
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('adviser.project.update', $submission->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <strong>Student:</strong>
                                <p>{{ $submission->student->name ?? 'Unknown' }}</p>
                            </div>
                            <div class="col-md-6">
                                <strong>Submission Type:</strong>
                                <p>
                                    <span class="badge bg-secondary text-capitalize">{{ $submission->type }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="status" class="form-label fw-bold">Submission Status</label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="pending" {{ $submission->status === 'pending' ? 'selected' : '' }}>Pending Review</option>
                                <option value="approved" {{ $submission->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ $submission->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                            <div class="form-text">Select the appropriate status for this submission.</div>
                        </div>
                        <div class="mb-4">
                            <label for="teacher_comment" class="form-label fw-bold">Feedback/Comment</label>
                            <textarea name="teacher_comment" id="teacher_comment" rows="6" class="form-control" placeholder="Provide detailed feedback for the student...">{{ $submission->teacher_comment }}</textarea>
                            <div class="form-text">Provide constructive feedback to help the student improve their work.</div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Review
                            </button>
                            <a href="{{ route('adviser.project.show', $submission->id) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Submission Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Current Status:</strong>
                        <p>
                            @if($submission->status === 'pending')
                                <span class="badge bg-warning">Pending Review</span>
                            @elseif($submission->status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($submission->status === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($submission->status) }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="mb-3">
                        <strong>Submitted:</strong>
                        <p>{{ $submission->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->format('M d, Y g:i A') : 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <strong>File:</strong>
                        <p>
                            <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download me-1"></i>Download
                            </a>
                        </p>
                    </div>
                    @if($submission->teacher_comment)
                        <div class="mb-3">
                            <strong>Current Feedback:</strong>
                            <div class="alert alert-info">
                                {{ $submission->teacher_comment }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success" onclick="setStatus('approved')">
                            <i class="fas fa-check me-2"></i>Quick Approve
                        </button>
                        <button type="button" class="btn btn-danger" onclick="setStatus('rejected')">
                            <i class="fas fa-times me-2"></i>Quick Reject
                        </button>
                        <button type="button" class="btn btn-warning" onclick="setStatus('pending')">
                            <i class="fas fa-clock me-2"></i>Mark Pending
                        </button>
                    </div>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard me-2"></i>Feedback Templates
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="insertTemplate('good')">
                            Good Work Template
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="insertTemplate('needs_improvement')">
                            Needs Improvement
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="insertTemplate('major_revision')">
                            Major Revision Needed
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action');
    if (action === 'approve') {
        setStatus('approved');
    } else if (action === 'reject') {
        setStatus('rejected');
    }
});
function setStatus(status) {
    document.getElementById('status').value = status;
    if (status === 'rejected') {
        document.getElementById('teacher_comment').focus();
    }
}
function insertTemplate(type) {
    const commentField = document.getElementById('teacher_comment');
    let template = '';
    switch(type) {
        case 'good':
            template = 'Excellent work! Your submission demonstrates a good understanding of the requirements. The content is well-structured and clearly presented. Keep up the good work!';
            break;
        case 'needs_improvement':
            template = 'Good effort, but there are some areas that need improvement:\n\n- Please review the formatting requirements\n- Consider adding more details to strengthen your arguments\n- Ensure all citations are properly formatted\n\nPlease revise and resubmit.';
            break;
        case 'major_revision':
            template = 'This submission requires major revisions before it can be approved:\n\n- The content does not meet the basic requirements\n- Significant improvements needed in structure and clarity\n- Please consult the guidelines and resubmit\n\nPlease address these issues and submit a revised version.';
            break;
    }
    commentField.value = template;
    commentField.focus();
}
</script>
@endsection 
