@extends('layouts.coordinator')

@section('title', 'Milestone Tasks')

@section('content')
<div class="container mt-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1">Tasks for: {{ $milestone->name }}</h1>
            <p class="text-muted mb-0">Manage tasks and requirements for this milestone</p>
        </div>
        <a href="{{ route('coordinator.milestones.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Milestones
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Create Task Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-plus me-2"></i>Add New Task
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('coordinator.milestones.tasks.store', $milestone->id) }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label fw-semibold">
                            <i class="fas fa-tasks me-2"></i>Task Name
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-control" 
                               placeholder="e.g., Research Topic, Write Proposal, Submit Documents"
                               required>
                    </div>
                    <div class="col-md-2">
                        <label for="order" class="form-label fw-semibold">
                            <i class="fas fa-sort-numeric-up me-2"></i>Order
                        </label>
                        <input type="number" 
                               id="order" 
                               name="order" 
                               value="{{ $tasks->count() + 1 }}" 
                               class="form-control" 
                               min="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add Task
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold">
                            <i class="fas fa-align-left me-2"></i>Description
                        </label>
                        <textarea id="description" 
                                  name="description" 
                                  class="form-control" 
                                  rows="3" 
                                  placeholder="Describe what this task involves, requirements, and expected outcomes..."></textarea>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tasks List -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Task List
                </h5>
                <span class="badge bg-primary">{{ $tasks->count() }} tasks</span>
            </div>
        </div>
        <div class="card-body p-0">
            @if ($tasks->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">No tasks found</h6>
                    <p class="text-muted small">Add tasks to help students understand what needs to be completed.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Task Name</th>
                                <th>Description</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tasks->sortBy('order') as $task)
                            <tr>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $task->order }}</span>
                                </td>
                                <td>
                                    <strong>{{ $task->name }}</strong>
                                </td>
                                <td>
                                    @if($task->description)
                                        <span class="text-muted">{{ Str::limit($task->description, 100) }}</span>
                                    @else
                                        <span class="text-muted fst-italic">No description</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('coordinator.milestones.tasks.edit', [$milestone->id, $task->id]) }}" 
                                           class="btn btn-outline-primary" title="Edit Task">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-danger" 
                                                onclick="deleteTask({{ $task->id }}, '{{ $task->name }}')" 
                                                title="Delete Task">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Milestone Info Card -->
    <div class="card mt-4 border-info">
        <div class="card-header bg-info text-white">
            <h6 class="mb-0">
                <i class="fas fa-info-circle me-2"></i>Milestone Information
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-semibold">Description:</h6>
                    <p class="text-muted">{{ $milestone->description ?: 'No description provided' }}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-semibold">Status:</h6>
                    <span class="badge bg-{{ $milestone->status === 'done' ? 'success' : ($milestone->status === 'in_progress' ? 'warning' : 'primary') }}">
                        {{ ucfirst(str_replace('_', ' ', $milestone->status)) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<span id="taskName"></span>"?</p>
                <p class="text-danger small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteTask(id, name) {
    document.getElementById('taskName').textContent = name;
    document.getElementById('deleteForm').action = `/coordinator/milestones/{{ $milestone->id }}/tasks/${id}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endsection
