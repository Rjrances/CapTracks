@extends('layouts.adviser')

@section('title', 'View Proposal')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">View Project Proposal</h4>
                            <p class="text-muted mb-0">Review proposal details and content</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('adviser.proposal.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Proposals
                            </a>
                            @if($proposal->status === 'pending')
                                <a href="{{ route('adviser.proposal.edit', $proposal->id) }}" class="btn btn-warning">
                                    <i class="fas fa-edit me-2"></i>Review & Provide Feedback
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Proposal Details -->
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

                    <!-- Proposal Content -->
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

                    <!-- Review Status -->
                    @if($proposal->status !== 'pending')
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
                    @endif

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('adviser.proposal.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Proposals
                        </a>
                        <div class="d-flex gap-2">
                            @if($proposal->status === 'pending')
                                <a href="{{ route('adviser.proposal.edit', $proposal->id) }}" class="btn btn-warning">
                                    <i class="fas fa-edit me-2"></i>Review & Provide Feedback
                                </a>
                            @elseif($proposal->status === 'rejected')
                                <a href="{{ route('adviser.proposal.edit', $proposal->id) }}" class="btn btn-warning">
                                    <i class="fas fa-edit me-2"></i>Update Review
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Group Information -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Group Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Group: {{ $studentGroup->name }}</h6>
                            <p class="text-muted mb-2">{{ $studentGroup->description ?? 'No description available' }}</p>
                            <small class="text-muted">Members: {{ $studentGroup->members->count() }}</small>
                        </div>
                        <div class="col-md-6">
                            @if($studentGroup->adviser)
                                <h6>Adviser: {{ $studentGroup->adviser->name }}</h6>
                                <p class="text-muted mb-0">{{ $studentGroup->adviser->email }}</p>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>No Adviser Assigned</strong><br>
                                    <small>This group needs an adviser to review proposals.</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
