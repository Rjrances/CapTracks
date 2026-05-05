@extends('layouts.coordinator')
@section('title', 'Edit Milestone Template')
@section('content')
<div class="container-fluid mb-3">
        <x-coordinator.intro description="Adjust template details, tasks, or ordering for new assignments going forward.">
            <a href="{{ route('coordinator.milestones.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to templates
            </a>
        </x-coordinator.intro>
</div>
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 800px;">
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
                            <div class="mb-4">
                                <label for="sequence_order" class="form-label">Sequence step</label>
                                <input type="number"
                                       min="1"
                                       max="255"
                                       class="form-control @error('sequence_order') is-invalid @enderror"
                                       id="sequence_order"
                                       name="sequence_order"
                                       value="{{ old('sequence_order', $milestone->sequence_order) }}"
                                       placeholder="e.g. 1 = Proposal, 2 = 60%, 3 = 100%">
                                @error('sequence_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Leave blank if not part of the main sequence. Use 1, 2, 3 for Proposal → 60% → 100%.</div>
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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-tasks me-2"></i>Tasks
                            <span class="badge bg-info ms-2">{{ $milestone->tasks->count() }}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($milestone->tasks->count() > 0)
                            <ul class="list-group list-group-flush mb-3">
                                @foreach($milestone->tasks as $task)
                                    <li class="list-group-item px-0">
                                        <form action="{{ route('coordinator.milestones.tasks.update', [$milestone->id, $task->id]) }}"
                                              method="POST"
                                              class="d-flex align-items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <i class="fas fa-grip-vertical text-muted"></i>
                                            <input type="text"
                                                   name="name"
                                                   value="{{ $task->name }}"
                                                   class="form-control form-control-sm"
                                                   required>
                                            <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">
                                                <i class="fas fa-save me-1"></i>Save
                                            </button>
                                            <form action="{{ route('coordinator.milestones.tasks.destroy', [$milestone->id, $task->id]) }}"
                                                  method="POST"
                                                  class="d-inline m-0"
                                                  onsubmit="return confirm('Delete this task?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted text-center py-2">No tasks yet. Add one below.</p>
                        @endif

                        {{-- Add new task --}}
                        <form action="{{ route('coordinator.milestones.tasks.store', $milestone->id) }}"
                              method="POST"
                              class="d-flex gap-2 mt-2">
                            @csrf
                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   placeholder="New task name (e.g. Chapter 1 - Introduction)"
                                   required>
                            <button type="submit" class="btn btn-success text-nowrap">
                                <i class="fas fa-plus me-1"></i>Add Task
                            </button>
                        </form>
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
