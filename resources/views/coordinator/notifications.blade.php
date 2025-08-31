@extends('layouts.coordinator')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Notifications</h1>
                    <p class="text-muted mb-0">Manage and view all system notifications</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="markAllAsRead()">
                        <i class="fas fa-check-double me-2"></i>Mark All as Read
                    </button>
                    <button class="btn btn-outline-secondary" onclick="refreshNotifications()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                </div>
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

            <!-- Filter Options -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <label for="roleFilter" class="form-label">Filter by Role</label>
                            <select class="form-select" id="roleFilter">
                                <option value="">All Roles</option>
                                <option value="coordinator">Coordinator</option>
                                <option value="adviser">Adviser</option>
                                <option value="student">Student</option>
                                <option value="panelist">Panelist</option>
                                <option value="chairperson">Chairperson</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Filter by Status</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="unread">Unread</option>
                                <option value="read">Read</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="dateFilter" class="form-label">Filter by Date</label>
                            <select class="form-select" id="dateFilter">
                                <option value="">All Time</option>
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" onclick="applyFilters()">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">All Notifications</h5>
                        <span class="badge bg-primary">{{ $notifications->count() }} total</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($notifications->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                        <th style="width: 60px;">Status</th>
                                        <th>Title & Description</th>
                                        <th style="width: 120px;">Role</th>
                                        <th style="width: 150px;">Date</th>
                                        <th style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($notifications as $notification)
                                        <tr class="{{ $notification->is_read ? '' : 'table-warning' }}" 
                                            data-notification-id="{{ $notification->id }}">
                                            <td>
                                                <input type="checkbox" class="form-check-input notification-checkbox" 
                                                       value="{{ $notification->id }}">
                                            </td>
                                            <td>
                                                @if($notification->is_read)
                                                    <span class="badge bg-success">Read</span>
                                                @else
                                                    <span class="badge bg-warning">Unread</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-start">
                                                    <div class="me-3">
                                                        <i class="fas fa-{{ $notification->icon ?? 'bell' }} text-primary"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold">{{ $notification->title }}</div>
                                                        <div class="text-muted small">{{ $notification->description }}</div>
                                                        @if($notification->redirect_url)
                                                            <small class="text-info">
                                                                <i class="fas fa-link me-1"></i>
                                                                Clickable notification
                                                            </small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ ucfirst($notification->role) }}</span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $notification->created_at->format('M d, Y') }}<br>
                                                    {{ $notification->created_at->format('g:i A') }}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    @if($notification->redirect_url)
                                                        <a href="{{ $notification->redirect_url }}" 
                                                           class="btn btn-outline-primary btn-sm"
                                                           title="View Details">
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </a>
                                                    @endif
                                                    <button class="btn btn-outline-secondary btn-sm" 
                                                            onclick="toggleReadStatus({{ $notification->id }})"
                                                            title="{{ $notification->is_read ? 'Mark as Unread' : 'Mark as Read' }}">
                                                        <i class="fas fa-{{ $notification->is_read ? 'eye-slash' : 'eye' }}"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm" 
                                                            onclick="deleteNotification({{ $notification->id }})"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-bell fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No notifications found</h5>
                            <p class="text-muted">You're all caught up! Check back later for new updates.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Bulk Actions -->
            @if($notifications->count() > 0)
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted">
                                    <span id="selectedCount">0</span> of {{ $notifications->count() }} selected
                                </span>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary" onclick="markSelectedAsRead()" id="markReadBtn" disabled>
                                    <i class="fas fa-check me-2"></i>Mark Selected as Read
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteSelected()" id="deleteSelectedBtn" disabled>
                                    <i class="fas fa-trash me-2"></i>Delete Selected
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
// Select all functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.notification-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateSelectedCount();
});

// Update selected count
document.querySelectorAll('.notification-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const selectedCount = document.querySelectorAll('.notification-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = selectedCount;
    
    // Enable/disable bulk action buttons
    document.getElementById('markReadBtn').disabled = selectedCount === 0;
    document.getElementById('deleteSelectedBtn').disabled = selectedCount === 0;
}

// Toggle read status
function toggleReadStatus(notificationId) {
    fetch(`/notifications/${notificationId}/mark-read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating notification status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating notification status');
    });
}

// Mark all as read
function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        fetch('{{ route("notifications.mark-all-read") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error marking notifications as read: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error marking notifications as read');
        });
    }
}

// Mark selected as read
function markSelectedAsRead() {
    const selectedIds = Array.from(document.querySelectorAll('.notification-checkbox:checked'))
        .map(cb => cb.value);
    
    if (selectedIds.length === 0) return;

    fetch('{{ route("notifications.mark-multiple-read") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_ids: selectedIds }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error marking notifications as read');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error marking notifications as read');
    });
}

// Delete notification
function deleteNotification(notificationId) {
    if (confirm('Are you sure you want to delete this notification?')) {
        fetch(`/notifications/${notificationId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting notification');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting notification');
        });
    }
}

// Delete selected notifications
function deleteSelected() {
    const selectedIds = Array.from(document.querySelectorAll('.notification-checkbox:checked'))
        .map(cb => cb.value);
    
    if (selectedIds.length === 0) return;

    if (confirm(`Are you sure you want to delete ${selectedIds.length} selected notification(s)?`)) {
        fetch('{{ route("notifications.delete-multiple") }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_ids: selectedIds }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting notifications');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting notifications');
        });
    }
}

// Apply filters
function applyFilters() {
    const roleFilter = document.getElementById('roleFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    
    // Build query string
    const params = new URLSearchParams();
    if (roleFilter) params.append('role', roleFilter);
    if (statusFilter) params.append('status', statusFilter);
    if (dateFilter) params.append('date', dateFilter);
    
    // Redirect with filters
    window.location.href = `{{ route('coordinator.notifications') }}?${params.toString()}`;
}

// Refresh notifications
function refreshNotifications() {
    location.reload();
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>
@endsection
