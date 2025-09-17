@extends('layouts.coordinator')
@section('title', 'Edit Milestone Template - Coordinator Dashboard')
@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 800px;">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">Edit Milestone Template</h1>
                        <p class="text-muted mb-0">Update milestone template details and manage tasks</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('coordinator.milestones.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Templates
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-edit me-2"></i>Template Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('coordinator.milestones.update', $milestone->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-4">
                                <label for="name" class="form-label">
                                    Template Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $milestone->name) }}" 
                                       placeholder="e.g., 60% Defense Milestone Template"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Enter a descriptive name for this milestone template</div>
                            </div>
                            <div class="mb-4">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" 
                                          name="description" 
                                          rows="4" 
                                          placeholder="Describe what this milestone template covers and its purpose">{{ old('description', $milestone->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Provide a detailed description of this milestone template</div>
                            </div>
                            <div class="mb-4">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" 
                                        name="status">
                                    <option value="active" {{ old('status', $milestone->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status', $milestone->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="draft" {{ old('status', $milestone->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Set the status for this template</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Template
                                </button>
                                <a href="{{ route('coordinator.milestones.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-tasks me-2"></i>Template Tasks
                            </h5>
                            <a href="{{ route('coordinator.milestones.tasks.index', $milestone->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus me-1"></i>Manage Tasks
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($milestone->tasks->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Task Name</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($milestone->tasks->sortBy('order') as $task)
                                            <tr>
                                                <td>
                                                    <span class="badge bg-secondary">{{ $task->order }}</span>
                                                </td>
                                                <td>
                                                    <strong>{{ $task->name }}</strong>
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ Str::limit($task->description, 100) }}</small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-tasks fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No tasks defined for this template yet.</p>
                                <a href="{{ route('coordinator.milestones.tasks.index', $milestone->id) }}" class="btn btn-sm btn-primary mt-2">
                                    <i class="fas fa-plus me-1"></i>Add Tasks
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-info-circle me-2"></i>Managing Milestone Templates
                        </h6>
                        <p class="card-text mb-2">
                            <strong>Template Details:</strong> Update the name, description, and status of your milestone template.
                        </p>
                        <p class="card-text mb-2">
                            <strong>Tasks:</strong> Click "Manage Tasks" to add, edit, or reorder the specific tasks that make up this milestone template.
                        </p>
                        <p class="card-text mb-0">
                            <strong>Status:</strong> Only "Active" templates will be available for groups to use when creating their milestones.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    $(document).ready(function() {
        $('#description').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
</script>
@endpush
