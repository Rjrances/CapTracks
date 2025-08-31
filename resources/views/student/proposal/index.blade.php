@extends('layouts.student')

@section('title', 'Proposal & Endorsement')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Proposal & Endorsement</h2>
            <p class="text-muted mb-0">Submit and track your project proposal for approval</p>
        </div>
        <div>
            @if(!$existingProposal || $existingProposal->status === 'rejected')
                <a href="{{ route('student.proposal.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Submit Proposal
                </a>
            @elseif($existingProposal->status === 'pending')
                <a href="{{ route('student.proposal.edit', $existingProposal->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit me-2"></i>Edit Proposal
                </a>
            @endif
        </div>
    </div>

    <!-- Status Overview -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Proposal Status
                    </h5>
                </div>
                <div class="card-body">
                    @if($existingProposal)
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="mb-1">{{ $existingProposal->title ?? 'Project Proposal' }}</h6>
                                <p class="text-muted mb-2">Submitted: {{ $existingProposal->submitted_at ? \Carbon\Carbon::parse($existingProposal->submitted_at)->format('M d, Y H:i') : 'N/A' }}</p>
                                
                                @switch($proposalStatus['status'])
                                    @case('pending')
                                        <span class="badge bg-warning fs-6">Under Review</span>
                                        @break
                                    @case('approved')
                                        <span class="badge bg-success fs-6">Approved!</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge bg-danger fs-6">Needs Revision</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary fs-6">Unknown Status</span>
                                @endswitch
                            </div>
                            <div class="col-md-4 text-end">
                                @if($existingProposal->status === 'approved')
                                    <a href="{{ route('student.defense-requests.create') }}" class="btn btn-success">
                                        <i class="fas fa-gavel me-2"></i>Request Proposal Defense
                                    </a>
                                @elseif($existingProposal->status === 'rejected')
                                    <a href="{{ route('student.proposal.edit', $existingProposal->id) }}" class="btn btn-warning">
                                        <i class="fas fa-edit me-2"></i>Revise Proposal
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if($existingProposal->teacher_comment)
                            <div class="mt-3 p-3 bg-light rounded">
                                <h6 class="mb-2">Adviser Feedback:</h6>
                                <p class="mb-0">{{ $existingProposal->teacher_comment }}</p>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No Proposal Submitted</h6>
                            <p class="text-muted small">You need to submit a project proposal before proceeding with your capstone project.</p>
                            <a href="{{ route('student.proposal.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Submit Your First Proposal
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Next Steps Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-route me-2"></i>Next Steps to 60% Defense
                    </h5>
                </div>
                <div class="card-body">
                    @if($existingProposal)
                        @if($existingProposal->status === 'approved')
                            <div class="mb-3">
                                <h6 class="text-success mb-2">
                                    <i class="fas fa-check-circle me-2"></i>Step 1: Proposal Approved ✅
                                </h6>
                                <small class="text-muted">Your project proposal has been approved by your adviser.</small>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-primary mb-2">
                                    <i class="fas fa-rocket me-2"></i>Step 2: Request 60% Defense
                                </h6>
                                <small class="text-muted">Submit a formal request for your 60% defense.</small>
                                <div class="mt-2">
                                    <a href="{{ route('student.defense-requests.create') }}" class="btn btn-success btn-sm">
                                        <i class="fas fa-gavel me-1"></i>Request Defense
                                    </a>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-warning mb-2">
                                    <i class="fas fa-clock me-2"></i>Step 3: Prepare for Defense
                                </h6>
                                <small class="text-muted">Work on your progress report, demo, and presentation.</small>
                            </div>
                            
                            <div class="alert alert-success">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>Pro Tip:</strong><br>
                                <small>Start preparing your defense materials while waiting for approval. This will save you time!</small>
                            </div>
                        @elseif($existingProposal->status === 'pending')
                            <div class="mb-3">
                                <h6 class="text-warning mb-2">
                                    <i class="fas fa-clock me-2"></i>Step 1: Awaiting Review
                                </h6>
                                <small class="text-muted">Your proposal is currently under review by your adviser.</small>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-info mb-2">
                                    <i class="fas fa-edit me-2"></i>Step 2: Prepare for Feedback
                                </h6>
                                <small class="text-muted">Be ready to make revisions based on adviser feedback.</small>
                            </div>
                        @elseif($existingProposal->status === 'rejected')
                            <div class="mb-3">
                                <h6 class="text-danger mb-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Step 1: Revise Proposal
                                </h6>
                                <small class="text-muted">Address the feedback and resubmit your proposal.</small>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-info mb-2">
                                    <i class="fas fa-comments me-2"></i>Step 2: Review Feedback
                                </h6>
                                <small class="text-muted">Carefully review your adviser's comments for improvement.</small>
                            </div>
                        @endif
                    @else
                        <div class="mb-3">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-plus me-2"></i>Step 1: Submit Proposal
                            </h6>
                            <small class="text-muted">Start by submitting your project proposal.</small>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="text-info mb-2">
                                <i class="fas fa-users me-2"></i>Step 2: Get Adviser Approval
                            </h6>
                            <small class="text-muted">Your adviser will review and provide feedback.</small>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="text-success mb-2">
                                <i class="fas fa-gavel me-2"></i>Step 3: Request Defense
                            </h6>
                            <small class="text-muted">Once approved, request your 60% defense.</small>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Requirements Card -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list-check me-2"></i>Requirements
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <small>Project title and description</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <small>Clear objectives and scope</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <small>Methodology and approach</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <small>Timeline and milestones</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <small>Expected outcomes</small>
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-check text-success me-2"></i>
                            <small>Supporting documentation</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Group Information -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Group Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Group: {{ $group->name }}</h6>
                            <p class="text-muted mb-2">{{ $group->description ?? 'No description available' }}</p>
                            <small class="text-muted">Members: {{ $group->members->count() }}</small>
                        </div>
                        <div class="col-md-6">
                            @if($group->adviser)
                                <h6>Adviser: {{ $group->adviser->name }}</h6>
                                <p class="text-muted mb-0">{{ $group->adviser->email }}</p>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>No Adviser Assigned</strong><br>
                                    <small>Your group needs an adviser to review proposals.</small>
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
