@extends('layouts.adviser')
@section('title', 'Review Submission')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Review Submission</h2>
            <p class="text-muted mb-0">Review and provide feedback on student submission</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('adviser.project.edit', $submission->id) }}" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i>Edit Review
            </a>
            <a href="{{ route('adviser.project.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Projects
            </a>
        </div>
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
                        <i class="fas fa-file-alt me-2"></i>Submission Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Student:</strong>
                            <p>{{ $submission->student->name ?? 'Unknown' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Email:</strong>
                            <p>{{ $submission->student->email ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Submission Type:</strong>
                            <p>
                                <span class="badge bg-secondary text-capitalize">{{ $submission->type }}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong>
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
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Submitted At:</strong>
                            <p>{{ $submission->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->format('F d, Y \a\t g:i A') : 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>File:</strong>
                            <p>
                                <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                    <i class="fas fa-download me-1"></i>Download File
                                </a>
                            </p>
                        </div>
                    </div>
                    @if($submission->teacher_comment)
                        <div class="mb-3">
                            <strong>Your Feedback:</strong>
                            <div class="alert alert-info">
                                {{ $submission->teacher_comment }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>Student Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Student ID:</strong>
                            <p>{{ $submission->student->student_id ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Course:</strong>
                            <p>{{ $submission->student->course ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Year Level:</strong>
                            <p>{{ $submission->student->year ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Semester:</strong>
                            <p>{{ $submission->student->semester ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-check me-2"></i>Review Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($submission->status === 'pending')
                            <button class="btn btn-success" onclick="approveSubmission()">
                                <i class="fas fa-check me-2"></i>Approve Submission
                            </button>
                            <button class="btn btn-danger" onclick="rejectSubmission()">
                                <i class="fas fa-times me-2"></i>Reject Submission
                            </button>
                        @else
                            <div class="alert alert-info">
                                <strong>Status:</strong> {{ ucfirst($submission->status) }}
                                <br>
                                <small>This submission has already been reviewed.</small>
                            </div>
                        @endif
                        <a href="{{ route('adviser.project.edit', $submission->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Add/Edit Feedback
                        </a>
                    </div>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Quick Stats
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary">{{ $submission->student->submissions->count() }}</h4>
                            <small class="text-muted">Total Submissions</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success">{{ $submission->student->submissions->where('status', 'approved')->count() }}</h4>
                            <small class="text-muted">Approved</small>
                        </div>
                    </div>
                </div>
            </div>
            @php
                $otherSubmissions = $submission->student->submissions->where('id', '!=', $submission->id)->take(3);
            @endphp
            @if($otherSubmissions->count() > 0)
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Other Submissions
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach($otherSubmissions as $otherSubmission)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <small class="fw-semibold">{{ ucfirst($otherSubmission->type) }}</small>
                                    <br>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($otherSubmission->submitted_at)->format('M d, Y') }}</small>
                                </div>
                                <div>
                                    @if($otherSubmission->status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($otherSubmission->status === 'approved')
                                        <span class="badge bg-success">Approved</span>
                                    @elseif($otherSubmission->status === 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
<script>
function approveSubmission() {
    if (confirm('Are you sure you want to approve this submission?')) {
        window.location.href = "{{ route('adviser.project.edit', $submission->id) }}?action=approve";
    }
}
function rejectSubmission() {
    if (confirm('Are you sure you want to reject this submission?')) {
        window.location.href = "{{ route('adviser.project.edit', $submission->id) }}?action=reject";
    }
}
</script>
@endsection 
