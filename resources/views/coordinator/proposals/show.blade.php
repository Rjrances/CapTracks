@extends('layouts.coordinator')

@section('title', 'View Proposal')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">
                    <i class="fas fa-eye me-2"></i>View Proposal
                </h2>
                <div class="d-flex gap-2">
                    <a href="{{ route('coordinator.proposals.edit', $proposal->id) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-1"></i>Review Proposal
                    </a>
                    <a href="{{ route('coordinator.proposals.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Proposals
                    </a>
                </div>
            </div>

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
                            
                            <div class="row mb-4">
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

                            <div class="mb-4">
                                <strong>Proposal Title:</strong>
                                <h4 class="text-primary">{{ $proposal->title ?? 'Untitled Proposal' }}</h4>
                            </div>

                            @if($proposal->description)
                                <div class="mb-4">
                                    <strong>Description:</strong>
                                    <div class="border rounded p-3 bg-light">
                                        {{ $proposal->description }}
                                    </div>
                                </div>
                            @endif

                            @if($proposal->file_path)
                                <div class="mb-4">
                                    <strong>Attached File:</strong>
                                    <div class="mt-2">
                                        <a href="{{ Storage::url($proposal->file_path) }}" 
                                           target="_blank" 
                                           class="btn btn-primary">
                                            <i class="fas fa-download me-1"></i>Download File
                                        </a>
                                        <small class="text-muted ms-2">
                                            {{ basename($proposal->file_path) }}
                                        </small>
                                    </div>
                                </div>
                            @endif

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <strong>Submitted:</strong>
                                    <p class="mb-0">{{ $proposal->submitted_at ? $proposal->submitted_at->format('M d, Y H:i') : 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Status:</strong>
                                    <p class="mb-0">
                                        @switch($proposal->status)
                                            @case('pending')
                                                <span class="badge bg-warning fs-6">Pending Review</span>
                                                @break
                                            @case('approved')
                                                <span class="badge bg-success fs-6">Approved</span>
                                                @break
                                            @case('rejected')
                                                <span class="badge bg-danger fs-6">Rejected</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary fs-6">{{ ucfirst($proposal->status) }}</span>
                                        @endswitch
                                    </p>
                                </div>
                            </div>

                            @if($proposal->teacher_comment)
                                <div class="mb-4">
                                    <strong>Review Comments:</strong>
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
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Proposal Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Offering:</strong>
                                <p class="mb-0">{{ $offering->subject_code }} - {{ $offering->subject_title }}</p>
                                <small class="text-muted">{{ $offering->offer_code }}</small>
                            </div>

                            <div class="mb-3">
                                <strong>Academic Term:</strong>
                                <p class="mb-0">{{ $offering->academicTerm->semester ?? 'N/A' }}</p>
                            </div>

                            @if($studentGroup)
                                <div class="mb-3">
                                    <strong>Group Members:</strong>
                                    <ul class="list-unstyled mb-0">
                                        @foreach($studentGroup->members as $member)
                                            <li class="mb-1">
                                                <i class="fas fa-user me-1"></i>
                                                {{ $member->name }}
                                                @if($member->pivot->role === 'leader')
                                                    <span class="badge bg-primary ms-1">Leader</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="mb-3">
                                <strong>Proposal ID:</strong>
                                <p class="mb-0 text-muted">#{{ $proposal->id }}</p>
                            </div>

                            @if($proposal->created_at)
                                <div class="mb-3">
                                    <strong>Created:</strong>
                                    <p class="mb-0 text-muted">{{ $proposal->created_at->format('M d, Y H:i') }}</p>
                                </div>
                            @endif

                            @if($proposal->updated_at && $proposal->updated_at != $proposal->created_at)
                                <div class="mb-3">
                                    <strong>Last Updated:</strong>
                                    <p class="mb-0 text-muted">{{ $proposal->updated_at->format('M d, Y H:i') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($proposal->status === 'pending')
                        <div class="card mt-3">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>Action Required
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">This proposal is waiting for your review and decision.</p>
                                <a href="{{ route('coordinator.proposals.edit', $proposal->id) }}" 
                                   class="btn btn-warning w-100">
                                    <i class="fas fa-edit me-1"></i>Review Now
                                </a>
                            </div>
                        </div>
                    @elseif($proposal->status === 'approved')
                        <div class="card mt-3">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-check-circle me-2"></i>Approved
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">This proposal has been approved and the student has been notified.</p>
                                <a href="{{ route('coordinator.proposals.edit', $proposal->id) }}" 
                                   class="btn btn-outline-success w-100">
                                    <i class="fas fa-edit me-1"></i>Update Review
                                </a>
                            </div>
                        </div>
                    @elseif($proposal->status === 'rejected')
                        <div class="card mt-3">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-times-circle me-2"></i>Rejected
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">This proposal has been rejected and the student has been notified.</p>
                                <a href="{{ route('coordinator.proposals.edit', $proposal->id) }}" 
                                   class="btn btn-outline-danger w-100">
                                    <i class="fas fa-edit me-1"></i>Update Review
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
