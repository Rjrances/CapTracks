@extends('layouts.student')
@section('title', 'Edit Proposal')
@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">Edit Project Proposal</h4>
                            <p class="text-muted mb-0">Update your capstone project proposal</p>
                        </div>
                        <a href="{{ route('student.proposal') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Proposals
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($proposal->status === 'rejected')
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Proposal Revision Required</strong><br>
                            Your proposal was rejected. Please review the feedback and make necessary changes.
                        </div>
                    @endif
                    
                    <form action="{{ route('student.proposal.update', $proposal->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Project Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title', $proposal->title) }}" 
                                           placeholder="Enter your project title" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Choose a clear, descriptive title for your project</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="objectives" class="form-label">Project Objectives <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('objectives') is-invalid @enderror" 
                                              id="objectives" name="objectives" rows="4" 
                                              placeholder="Describe the main objectives and goals of your project" required>{{ old('objectives', $proposal->objectives) }}</textarea>
                                    @error('objectives')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Clearly state what you aim to achieve with this project (minimum 100 characters)</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="methodology" class="form-label">Methodology & Approach <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('methodology') is-invalid @enderror" 
                                              id="methodology" name="methodology" rows="4" 
                                              placeholder="Explain your research methodology, tools, and approach" required>{{ old('methodology', $proposal->methodology) }}</textarea>
                                    @error('methodology')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Describe how you plan to implement your project (minimum 100 characters)</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="timeline" class="form-label">Project Timeline <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('timeline') is-invalid @enderror" 
                                              id="timeline" name="timeline" rows="3" 
                                              placeholder="Outline your project timeline and key milestones" required>{{ old('timeline', $proposal->timeline) }}</textarea>
                                    @error('timeline')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Provide a realistic timeline for your project completion (minimum 50 characters)</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="expected_outcomes" class="form-label">Expected Outcomes <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('expected_outcomes') is-invalid @enderror" 
                                              id="expected_outcomes" name="expected_outcomes" rows="3" 
                                              placeholder="Describe the expected results and deliverables" required>{{ old('expected_outcomes', $proposal->expected_outcomes) }}</textarea>
                                    @error('expected_outcomes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">What will be the final deliverables and outcomes? (minimum 50 characters)</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="file" class="form-label">Supporting Document <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control @error('file') is-invalid @enderror" 
                                           id="file" name="file" accept=".pdf,.doc,.docx">
                                    @error('file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Upload a new proposal document (PDF, DOC, or DOCX, max 10MB)
                                        @if($proposal->file_path)
                                            <br><strong>Current file:</strong> {{ basename($proposal->file_path) }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Proposal Status</label>
                                    <div class="form-control-plaintext">
                                        <span class="badge 
                                            @if($proposal->status === 'pending') bg-warning
                                            @elseif($proposal->status === 'approved') bg-success
                                            @elseif($proposal->status === 'rejected') bg-danger
                                            @else bg-secondary
                                            @endif">
                                            {{ ucfirst($proposal->status) }}
                                        </span>
                                        @if($proposal->teacher_comment)
                                            <br><small class="text-muted mt-2 d-block">
                                                <strong>Feedback:</strong> {{ $proposal->teacher_comment }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('student.proposal') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Proposal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Editing Guidelines
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>What You Can Edit:</h6>
                            <ul class="small">
                                <li>Project title and objectives</li>
                                <li>Methodology and approach</li>
                                <li>Timeline and milestones</li>
                                <li>Expected outcomes</li>
                                <li>Supporting documents</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Important Notes:</h6>
                            <ul class="small">
                                <li>Changes will reset approval status to pending</li>
                                <li>Your adviser will need to review again</li>
                                <li>Keep all information current and accurate</li>
                                <li>Upload new file only if needed</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
