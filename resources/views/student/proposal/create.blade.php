@extends('layouts.student')

@section('title', 'Submit Proposal')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">Submit Project Proposal</h4>
                            <p class="text-muted mb-0">Complete this form to submit your capstone project proposal</p>
                        </div>
                        <a href="{{ route('student.proposal') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Proposals
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($existingProposal && $existingProposal->status === 'rejected')
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Proposal Revision Required</strong><br>
                            Your previous proposal was rejected. Please review the feedback and submit a revised version.
                        </div>
                    @endif

                    <form action="{{ route('student.proposal.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Project Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                           id="title" name="title" value="{{ old('title', $existingProposal->title ?? '') }}" 
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
                                              placeholder="Describe the main objectives and goals of your project" required>{{ old('objectives', $existingProposal->objectives ?? '') }}</textarea>
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
                                              placeholder="Explain your research methodology, tools, and approach" required>{{ old('methodology', $existingProposal->methodology ?? '') }}</textarea>
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
                                              placeholder="Outline your project timeline and key milestones" required>{{ old('timeline', $existingProposal->timeline ?? '') }}</textarea>
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
                                              placeholder="Describe the expected results and deliverables" required>{{ old('expected_outcomes', $existingProposal->expected_outcomes ?? '') }}</textarea>
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
                                           id="file" name="file" accept=".pdf,.doc,.docx" required>
                                    @error('file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Upload your proposal document (PDF, DOC, or DOCX, max 10MB)</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="group_info" class="form-label">Group Information</label>
                                    <div class="form-control-plaintext">
                                        <strong>Group:</strong> {{ $group->name }}<br>
                                        <strong>Members:</strong> {{ $group->members->count() }}<br>
                                        @if($group->adviser)
                                            <strong>Adviser:</strong> {{ $group->adviser->name }}
                                        @else
                                            <span class="text-warning">No adviser assigned yet</span>
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
                                <i class="fas fa-paper-plane me-2"></i>Submit Proposal
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>Proposal Guidelines
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>What to Include:</h6>
                            <ul class="small">
                                <li>Clear project scope and objectives</li>
                                <li>Detailed methodology and approach</li>
                                <li>Realistic timeline with milestones</li>
                                <li>Expected deliverables and outcomes</li>
                                <li>Supporting research and references</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Tips for Success:</h6>
                            <ul class="small">
                                <li>Be specific and detailed</li>
                                <li>Ensure feasibility within timeline</li>
                                <li>Include relevant technical details</li>
                                <li>Proofread before submission</li>
                                <li>Follow department guidelines</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
