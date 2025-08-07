@extends('layouts.coordinator')

@section('title', 'Milestone Templates')

@section('content')
<div class="container mt-5">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1">Milestone Templates</h1>
            <p class="text-muted mb-0">Manage capstone project milestones and their tasks</p>
        </div>
        <a href="{{ route('coordinator.milestones.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Milestone
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Kanban Board -->
    <div class="row g-4 kanban-board">
        @php
            $statuses = [
                'todo' => ['label' => 'To Do', 'color' => 'primary', 'icon' => 'list'],
                'in_progress' => ['label' => 'In Progress', 'color' => 'warning', 'icon' => 'spinner'],
                'done' => ['label' => 'Completed', 'color' => 'success', 'icon' => 'check-circle'],
            ];
        @endphp
        
        @foreach ($statuses as $statusKey => $status)
            <div class="col-md-4">
                <div class="card h-100 kanban-column" data-status="{{ $statusKey }}">
                    <div class="card-header bg-{{ $status['color'] }} text-white">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-{{ $status['icon'] }} me-2"></i>
                            <h5 class="mb-0">{{ $status['label'] }}</h5>
                            <span class="badge bg-light text-dark ms-auto">{{ $milestonesByStatus[$statusKey]->count() }}</span>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="kanban-list" id="kanban-{{ $statusKey }}">
                            @foreach ($milestonesByStatus[$statusKey] as $milestone)
                                <div class="card mb-3 kanban-card" data-id="{{ $milestone->id }}">
                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-2">{{ $milestone->name }}</h6>
                                        @if($milestone->description)
                                            <p class="card-text small text-muted mb-2">{{ Str::limit($milestone->description, 80) }}</p>
                                        @endif
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-secondary">{{ $milestone->tasks->count() }} tasks</span>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('coordinator.milestones.tasks.index', $milestone->id) }}" 
                                                   class="btn btn-outline-primary btn-sm" title="Manage Tasks">
                                                    <i class="fas fa-tasks"></i>
                                                </a>
                                                <a href="{{ route('coordinator.milestones.edit', $milestone->id) }}" 
                                                   class="btn btn-outline-secondary btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteMilestone({{ $milestone->id }}, '{{ $milestone->name }}')" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
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

<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Sortable for each column
    const statuses = ['todo', 'in_progress', 'done'];
    
    statuses.forEach(status => {
        new Sortable(document.getElementById('kanban-' + status), {
            group: 'milestones',
            animation: 150,
            ghostClass: 'kanban-ghost',
            chosenClass: 'kanban-chosen',
            dragClass: 'kanban-drag',
            onStart: function (evt) {
                evt.item.classList.add('shadow-lg', 'scale-105');
            },
            onEnd: function (evt) {
                evt.item.classList.remove('shadow-lg', 'scale-105');
                document.querySelectorAll('.kanban-column').forEach(col => 
                    col.classList.remove('border-primary')
                );
            },
            onMove: function (evt) {
                document.querySelectorAll('.kanban-column').forEach(col => 
                    col.classList.remove('border-primary')
                );
                if (evt.to && evt.to.parentNode.classList.contains('kanban-column')) {
                    evt.to.parentNode.classList.add('border-primary');
                }
                return true;
            },
            onAdd: function (evt) {
                const card = evt.item;
                const milestoneId = card.getAttribute('data-id');
                const newStatus = evt.to.parentNode.getAttribute('data-status');
                
                // Update status via AJAX
                fetch(`/coordinator/milestones/${milestoneId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ status: newStatus })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        showAlert('Failed to update milestone status.', 'danger');
                        location.reload();
                    } else {
                        showAlert('Milestone status updated successfully.', 'success');
                    }
                })
                .catch(() => {
                    showAlert('Error updating milestone status.', 'danger');
                    location.reload();
                });
            }
        });
    });
});

function deleteMilestone(id, name) {
    document.getElementById('milestoneName').textContent = name;
    document.getElementById('deleteForm').action = `/coordinator/milestones/${id}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>

<style>
.kanban-board {
    min-height: 600px;
}

.kanban-column {
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.kanban-column:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.kanban-card {
    cursor: grab;
    transition: all 0.2s ease;
    border: 1px solid #dee2e6;
}

.kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

.kanban-card:active {
    cursor: grabbing;
}

.kanban-ghost {
    opacity: 0.5;
    background: #e3f2fd;
    border: 2px dashed #2196f3;
}

.kanban-chosen {
    box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.2);
    background: #f3f4f6;
}

.kanban-drag {
    opacity: 0.8;
    transform: rotate(5deg);
}

.card-header {
    border-bottom: none;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .kanban-board .col-md-4 {
        margin-bottom: 1rem;
    }
}
</style>
@endsection
