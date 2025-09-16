@extends('layouts.student')

@section('title', 'Edit Milestone')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit me-2"></i>Edit Milestone
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('student.milestones') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Milestones
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('student.milestones.update', $groupMilestone->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-4">
                                    <label for="title" class="form-label">
                                        <i class="fas fa-tag me-2"></i>Milestone Title
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('title') is-invalid @enderror" 
                                           id="title" 
                                           name="title" 
                                           value="{{ old('title', $groupMilestone->title) }}" 
                                           placeholder="Enter milestone title..."
                                           required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="description" class="form-label">
                                        <i class="fas fa-align-left me-2"></i>Description
                                    </label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="4" 
                                              placeholder="Enter milestone description...">{{ old('description', $groupMilestone->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="due_date" class="form-label">
                                        <i class="fas fa-calendar me-2"></i>Due Date
                                    </label>
                                    <input type="date" 
                                           class="form-control @error('due_date') is-invalid @enderror" 
                                           id="due_date" 
                                           name="due_date" 
                                           value="{{ old('due_date', $groupMilestone->due_date ? $groupMilestone->due_date->format('Y-m-d') : '') }}"
                                           min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-info-circle me-2"></i>Milestone Info
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <strong>Template:</strong><br>
                                            <span class="text-muted">{{ $groupMilestone->milestoneTemplate->title }}</span>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Progress:</strong><br>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: {{ $groupMilestone->progress_percentage }}%"
                                                     aria-valuenow="{{ $groupMilestone->progress_percentage }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    {{ $groupMilestone->progress_percentage }}%
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Tasks:</strong><br>
                                            <span class="text-muted">{{ $groupMilestone->groupTasks ? $groupMilestone->groupTasks->count() : 0 }} tasks</span>
                                        </div>
                                        <div class="mb-0">
                                            <strong>Created:</strong><br>
                                            <span class="text-muted">{{ $groupMilestone->created_at->format('M d, Y') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-lightbulb me-2"></i>Tips
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                You can only edit the title, description, and due date
                                            </li>
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                Tasks are managed in the milestone view
                                            </li>
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                Progress is calculated automatically
                                            </li>
                                            <li class="mb-0">
                                                <i class="fas fa-check text-success me-2"></i>
                                                Changes will be visible to all group members
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('student.milestones') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Milestone
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
