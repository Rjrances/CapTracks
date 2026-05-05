@extends('layouts.coordinator')
@section('title', 'Notifications')
@section('content')
<div class="container-fluid">
        <x-coordinator.intro description="Alerts, assignments, and system messages for your coordinator account.">
            <button type="button" class="btn btn-outline-primary" onclick="markAllAsRead()">
                <i class="fas fa-check-double me-2"></i>Mark All as Read
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="refreshNotifications()">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
        </x-coordinator.intro>
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
                                <button class="btn btn-outline-danger" onclick="deleteSelected()" id="deleteSelectedBtn" disabled>
                                    <i class="fas fa-trash me-2"></i>Delete Selected
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
</div>
<script>
function updateSelectedCount() {
    const selectedCount = document.querySelectorAll('.notification-checkbox:checked').length;
    const selectedCountEl = document.getElementById('selectedCount');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    if (selectedCountEl) selectedCountEl.textContent = selectedCount;
    if (deleteSelectedBtn) deleteSelectedBtn.disabled = selectedCount === 0;
}
function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        fetch('{{ route("coordinator.notifications.mark-all-read") }}', {
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
function deleteNotification(notificationId) {
    if (confirm('Are you sure you want to delete this notification?')) {
        fetch(`{{ url('coordinator/notifications') }}/${notificationId}`, {
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
                alert('Error deleting notification: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting notification');
        });
    }
}
function deleteSelected() {
    const selectedIds = Array.from(document.querySelectorAll('.notification-checkbox:checked'))
        .map(cb => cb.value);
    if (selectedIds.length === 0) return;
    if (confirm(`Are you sure you want to delete ${selectedIds.length} selected notification(s)?`)) {
        fetch('{{ route("coordinator.notifications.delete-multiple") }}', {
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
                alert('Error deleting notifications: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting notifications');
        });
    }
}
function refreshNotifications() {
    location.reload();
}
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.notification-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });
    }
    document.querySelectorAll('.notification-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    updateSelectedCount();
});
</script>
@endsection
