@extends('layouts.adviser')
@section('title', 'Review Proposal')
@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">Review Project Proposal</h4>
                            <p class="text-muted mb-0">Provide feedback and approve or reject this proposal</p>
                        </div>
                        <a href="{{ route('adviser.proposal.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Proposals
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Student Information</h6>
                            <p class="mb-1"><strong>Name:</strong> {{ $proposal->student->name ?? 'Unknown' }}</p>
                            <p class="mb-1"><strong>Email:</strong> {{ $proposal->student->email ?? 'N/A' }}</p>
                            <p class="mb-0"><strong>Group:</strong> {{ $studentGroup->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Submission Details</h6>
                            <p class="mb-1"><strong>Submitted:</strong> {{ $proposal->submitted_at ? \Carbon\Carbon::parse($proposal->submitted_at)->format('M d, Y H:i') : 'N/A' }}</p>
                            <p class="mb-1"><strong>Current Status:</strong> 
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
                            <p class="mb-0"><strong>Document:</strong> 
                                <a href="{{ asset('storage/' . $proposal->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download me-1"></i>Download
                                </a>
                            </p>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6>Proposal Content</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Project Title</label>
                                        <div class="form-control-plaintext">{{ $proposal->title ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Project Type</label>
                                        <div class="form-control-plaintext">{{ ucfirst($proposal->type) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Project Objectives</label>
                                <div class="form-control-plaintext" style="min-height: 80px;">{{ $proposal->objectives ?? 'N/A' }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Methodology & Approach</label>
                                <div class="form-control-plaintext" style="min-height: 80px;">{{ $proposal->methodology ?? 'N/A' }}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Project Timeline</label>
                                        <div class="form-control-plaintext" style="min-height: 60px;">{{ $proposal->timeline ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Expected Outcomes</label>
                                        <div class="form-control-plaintext" style="min-height: 60px;">{{ $proposal->expected_outcomes ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($proposal->status === 'pending')
                        <form action="{{ route('adviser.proposal.update', $proposal->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6>Review Decision</h6>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Decision <span class="text-danger">*</span></label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="status" id="status_approved" value="approved" required>
                                                <label class="form-check-label text-success fw-bold" for="status_approved">
                                                    <i class="fas fa-check-circle me-2"></i>Approve
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="status" id="status_rejected" value="rejected" required>
                                                <label class="form-check-label text-danger fw-bold" for="status_rejected">
                                                    <i class="fas fa-times-circle me-2"></i>Reject
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="teacher_comment" class="form-label fw-bold">Feedback & Comments <span class="text-danger">*</span></label>
                                        <textarea class="form-control @error('teacher_comment') is-invalid @enderror" 
                                                  id="teacher_comment" name="teacher_comment" rows="6" 
                                                  placeholder="Provide detailed feedback on the proposal. Include suggestions for improvement if rejecting, or confirmation of approval if accepting." required>{{ old('teacher_comment', $proposal->teacher_comment ?? '') }}</textarea>
                                        @error('teacher_comment')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Provide constructive feedback to help the student improve their proposal (minimum 10 characters)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('adviser.proposal.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Review
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6>Review Decision</h6>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Decision</label>
                                    <div>
                                        @if($proposal->status === 'approved')
                                            <span class="badge bg-success fs-6">Approved</span>
                                        @elseif($proposal->status === 'rejected')
                                            <span class="badge bg-danger fs-6">Rejected</span>
                                        @endif
                                    </div>
                                </div>
                                @if($proposal->teacher_comment)
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Feedback & Comments</label>
                                        <div class="form-control-plaintext" style="min-height: 80px;">{{ $proposal->teacher_comment }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('adviser.proposal.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Proposals
                            </a>
                            @if($proposal->status === 'rejected')
                                <a href="{{ route('adviser.proposal.edit', $proposal->id) }}" class="btn btn-warning">
                                    <i class="fas fa-edit me-2"></i>Update Review
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>Review Guidelines
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Approval Criteria:</h6>
                            <ul class="small">
                                <li>Clear and realistic project objectives</li>
                                <li>Feasible methodology and approach</li>
                                <li>Realistic timeline and milestones</li>
                                <li>Well-defined expected outcomes</li>
                                <li>Appropriate scope for capstone project</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Rejection Reasons:</h6>
                            <ul class="small">
                                <li>Vague or unrealistic objectives</li>
                                <li>Insufficient methodology detail</li>
                                <li>Unrealistic timeline expectations</li>
                                <li>Scope too broad or too narrow</li>
                                <li>Missing critical project elements</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
