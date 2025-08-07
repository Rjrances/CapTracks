@extends('layouts.coordinator')

@section('title', 'Edit Task')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Edit Task</h1>
                    <p class="text-muted mb-0">Update task details for: {{ $milestone->name }}</p>
                </div>
                <a href="{{ route('coordinator.milestones.tasks.index', $milestone->id) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Tasks
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

                    <form action="{{ route('coordinator.milestones.tasks.update', [$milestone->id, $task->id]) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="name" class="form-label fw-semibold">
                                <i class="fas fa-tasks me-2"></i>Task Name
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $task->name) }}" 
                                   class="form-control form-control-lg" 
                                   placeholder="e.g., Research Topic, Write Proposal, Submit Documents"
                                   required>
                            <div class="form-text">
                                Choose a clear, descriptive name for this task.
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
                                      placeholder="Describe what this task involves, requirements, and expected outcomes...">{{ old('description', $task->description) }}</textarea>
                            <div class="form-text">
                                Provide detailed instructions to help students complete this task.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="order" class="form-label fw-semibold">
                                <i class="fas fa-sort-numeric-up me-2"></i>Display Order
                            </label>
                            <input type="number" 
                                   id="order" 
                                   name="order" 
                                   value="{{ old('order', $task->order) }}" 
                                   class="form-control" 
                                   min="1" 
                                   style="max-width: 150px;">
                            <div class="form-text">
                                Lower numbers appear first in the task list.
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('coordinator.milestones.tasks.index', $milestone->id) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Task Info Card -->
            <div class="card mt-4 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Task Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-semibold">Milestone:</h6>
                            <p class="text-muted">{{ $milestone->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-semibold">Current Order:</h6>
                            <span class="badge bg-secondary">{{ $task->order }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
