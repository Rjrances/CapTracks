@extends('layouts.coordinator')

@section('title', 'Edit Milestone Template')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Edit Milestone Template</h1>
                    <p class="text-muted mb-0">Update milestone details and requirements</p>
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

                    <form action="{{ route('coordinator.milestones.update', $milestone->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="name" class="form-label fw-semibold">
                                <i class="fas fa-flag me-2"></i>Milestone Name
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $milestone->name) }}" 
                                   class="form-control form-control-lg" 
                                   placeholder="e.g., Project Proposal, Implementation Phase, Final Defense"
                                   required>
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
                                      placeholder="Describe what this milestone involves, its objectives, and expected deliverables...">{{ old('description', $milestone->description) }}</textarea>
                            <div class="form-text">
                                Provide a detailed description to help students understand what's expected.
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('coordinator.milestones.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Milestone
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="card mt-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('coordinator.milestones.tasks.index', $milestone->id) }}" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Manage Tasks ({{ $milestone->tasks->count() }})
                        </a>
                        <button type="button" class="btn btn-outline-danger" onclick="deleteMilestone({{ $milestone->id }}, '{{ $milestone->name }}')">
                            <i class="fas fa-trash me-2"></i>Delete Milestone
                        </button>
                    </div>
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
                <h5 class="modal-title">Delete Milestone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<span id="milestoneName"></span>"?</p>
                <p class="text-danger small">This action cannot be undone and will also delete all associated tasks.</p>
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
function deleteMilestone(id, name) {
    document.getElementById('milestoneName').textContent = name;
    document.getElementById('deleteForm').action = `/coordinator/milestones/${id}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endsection
