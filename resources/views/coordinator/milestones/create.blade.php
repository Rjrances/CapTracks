@extends('layouts.coordinator')

@section('title', 'Create Milestone Template')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Create Milestone Template</h1>
                    <p class="text-muted mb-0">Add a new milestone for capstone projects</p>
                </div>
                <a href="{{ route('coordinator.milestones.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Milestones
                </a>
            </div>

            <!-- Form Card -->
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div>
                                    <strong>Please fix the following errors:</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('coordinator.milestones.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="name" class="form-label fw-semibold">
                                <i class="fas fa-flag me-2"></i>Milestone Name
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   class="form-control form-control-lg" 
                                   placeholder="e.g., Project Proposal, Implementation Phase, Final Defense"
                                   required 
                                   autofocus>
                            <div class="form-text">
                                Choose a clear, descriptive name for this milestone.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold">
                                <i class="fas fa-align-left me-2"></i>Description
                            </label>
                            <textarea id="description" 
                                      name="description" 
                                      class="form-control" 
                                      rows="4" 
                                      placeholder="Describe what this milestone involves, its objectives, and expected deliverables...">{{ old('description') }}</textarea>
                            <div class="form-text">
                                Provide a detailed description to help students understand what's expected.
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('coordinator.milestones.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Milestone
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Help Card -->
            <div class="card mt-4 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>Tips for Creating Milestones
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Use clear, action-oriented names</li>
                        <li>Include specific deliverables and requirements</li>
                        <li>Consider the logical progression of the project</li>
                        <li>Set realistic timelines and expectations</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
