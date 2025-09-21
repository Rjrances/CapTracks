@extends('layouts.coordinator')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Academic Terms</h2>
                <a href="{{ route('coordinator.dashboard') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>School Year</th>
                                    <th>Semester</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($academicTerms as $term)
                                    <tr class="{{ $term->is_archived ? 'table-secondary' : '' }}">
                                        <td>{{ $term->school_year }}</td>
                                        <td>
                                            @php
                                                // Extract semester part from full string (e.g., "2024-2025 First Semester" -> "First Semester")
                                                $semesterDisplay = $term->semester;
                                                if (strpos($semesterDisplay, 'First Semester') !== false) {
                                                    $semesterDisplay = 'First Semester';
                                                } elseif (strpos($semesterDisplay, 'Second Semester') !== false) {
                                                    $semesterDisplay = 'Second Semester';
                                                } elseif (strpos($semesterDisplay, 'Summer') !== false) {
                                                    $semesterDisplay = 'Summer';
                                                }
                                            @endphp
                                            {{ $semesterDisplay }}
                                        </td>
                                        <td>
                                            @if($term->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-warning">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if($term->is_active)
                                                    <button class="btn btn-warning btn-sm deactivate-term-btn" 
                                                            data-term-id="{{ $term->id }}" 
                                                            data-term-name="{{ $term->semester }}">
                                                        <i class="fas fa-pause me-1"></i>Deactivate
                                                    </button>
                                                @else
                                                    <button class="btn btn-success btn-sm activate-term-btn" 
                                                            data-term-id="{{ $term->id }}" 
                                                            data-term-name="{{ $term->semester }}">
                                                        <i class="fas fa-check me-1"></i>Activate
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">No academic terms found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="termActionModal" tabindex="-1" aria-labelledby="termActionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termActionModalLabel">Academic Term Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalMessage">Are you sure you want to perform this action?</p>
                <div class="alert alert-warning" id="modalWarning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Note:</strong> This will change the active academic term.
                </div>
                <p><strong>Term:</strong> <span id="selectedTermName"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmActionBtn">
                    <i class="fas fa-check me-1"></i>Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let selectedTermId = null;
    let actionType = null;
    
    // Handle activate button clicks
    document.querySelectorAll('.activate-term-btn').forEach(button => {
        button.addEventListener('click', function() {
            selectedTermId = this.getAttribute('data-term-id');
            const termName = this.getAttribute('data-term-name');
            actionType = 'activate';
            
            document.getElementById('selectedTermName').textContent = termName;
            document.getElementById('modalMessage').textContent = 'Are you sure you want to activate this academic term?';
            document.getElementById('modalWarning').innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i><strong>Note:</strong> This will deactivate the current active term and activate the selected one.';
            document.getElementById('confirmActionBtn').innerHTML = '<i class="fas fa-check me-1"></i>Activate Term';
            document.getElementById('confirmActionBtn').className = 'btn btn-success';
            
            const modal = new bootstrap.Modal(document.getElementById('termActionModal'));
            modal.show();
        });
    });
    
    // Handle deactivate button clicks
    document.querySelectorAll('.deactivate-term-btn').forEach(button => {
        button.addEventListener('click', function() {
            selectedTermId = this.getAttribute('data-term-id');
            const termName = this.getAttribute('data-term-name');
            actionType = 'deactivate';
            
            document.getElementById('selectedTermName').textContent = termName;
            document.getElementById('modalMessage').textContent = 'Are you sure you want to deactivate this academic term?';
            document.getElementById('modalWarning').innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i><strong>Note:</strong> This will deactivate the current active term.';
            document.getElementById('confirmActionBtn').innerHTML = '<i class="fas fa-pause me-1"></i>Deactivate Term';
            document.getElementById('confirmActionBtn').className = 'btn btn-warning';
            
            const modal = new bootstrap.Modal(document.getElementById('termActionModal'));
            modal.show();
        });
    });
    
    // Handle confirmation
    document.getElementById('confirmActionBtn').addEventListener('click', function() {
        if (selectedTermId && actionType) {
            if (actionType === 'activate') {
                activateTerm(selectedTermId);
            } else if (actionType === 'deactivate') {
                deactivateTerm(selectedTermId);
            }
        }
    });
    
    function activateTerm(termId) {
        const button = document.getElementById('confirmActionBtn');
        const originalText = button.innerHTML;
        
        // Show loading state
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Activating...';
        
        // Make AJAX request
        fetch('{{ route("coordinator.academic-terms.activate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                term_id: termId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.message);
                const modal = bootstrap.Modal.getInstance(document.getElementById('termActionModal'));
                modal.hide();
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('error', data.message);
                button.disabled = false;
                button.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Failed to activate term. Please try again.');
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }
    
    function deactivateTerm(termId) {
        const button = document.getElementById('confirmActionBtn');
        const originalText = button.innerHTML;
        
        // Show loading state
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Deactivating...';
        
        // Make AJAX request
        fetch('{{ route("coordinator.academic-terms.deactivate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                term_id: termId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.message);
                const modal = bootstrap.Modal.getInstance(document.getElementById('termActionModal'));
                modal.hide();
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('error', data.message);
                button.disabled = false;
                button.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Failed to deactivate term. Please try again.');
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }
    
    function showNotification(type, message) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
});
</script>
@endsection
