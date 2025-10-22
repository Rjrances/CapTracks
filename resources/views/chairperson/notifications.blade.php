@extends('layouts.chairperson')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1">Notifications</h1>
            <p class="text-muted mb-0">Stay updated with system activities and important updates</p>
        </div>
        <div class="d-flex gap-2">
            @if($notifications->where('is_read', false)->count() > 0)
                <button id="markAllReadBtn" class="btn btn-primary">
                    <i class="fas fa-check-double me-2"></i>Mark All as Read
                </button>
            @endif
            <a href="{{ route('chairperson.dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($notifications->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($notifications as $notification)
                        <div class="list-group-item {{ !$notification->is_read ? 'bg-light' : '' }} notification-item" data-notification-id="{{ $notification->id }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="mb-0 me-2">
                                            @if(!$notification->is_read)
                                                <span class="badge bg-primary me-2">New</span>
                                            @endif
                                            {{ $notification->title }}
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ $notification->created_at ? $notification->created_at->diffForHumans() : 'Recently' }}
                                        </small>
                                    </div>
                                    <p class="mb-2 text-muted">{{ $notification->message }}</p>
                                    @if($notification->link)
                                        <a href="{{ $notification->link }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-external-link-alt me-1"></i>View Details
                                        </a>
                                    @endif
                                </div>
                                <div class="ms-3">
                                    @if(!$notification->is_read)
                                        <button class="btn btn-sm btn-success mark-read-btn" data-notification-id="{{ $notification->id }}" title="Mark as read">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    @endif
                                    <button class="btn btn-sm btn-danger delete-notification-btn" data-notification-id="{{ $notification->id }}" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-center mt-4">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No notifications yet</h5>
                    <p class="text-muted">You'll see notifications here when there are updates</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const notificationId = this.dataset.notificationId;
            markNotificationAsRead(notificationId);
        });
    });

    const markAllReadBtn = document.getElementById('markAllReadBtn');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            markAllNotificationsAsRead();
        });
    }

    document.querySelectorAll('.delete-notification-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this notification?')) {
                const notificationId = this.dataset.notificationId;
                deleteNotification(notificationId);
            }
        });
    });
});

function markNotificationAsRead(notificationId) {
    fetch(`/chairperson/notifications/${notificationId}/mark-read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
            notificationItem.classList.remove('bg-light');
            notificationItem.querySelector('.mark-read-btn')?.remove();
            notificationItem.querySelector('.badge')?.remove();
            showAlert('Notification marked as read', 'success');
        } else {
            showAlert('Error marking notification as read', 'danger');
        }
    })
    .catch(() => showAlert('Error marking notification as read', 'danger'));
}

function markAllNotificationsAsRead() {
    fetch('/chairperson/notifications/mark-all-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('Error marking notifications as read', 'danger');
        }
    })
    .catch(() => showAlert('Error marking notifications as read', 'danger'));
}

function deleteNotification(notificationId) {
    fetch(`/chairperson/notifications/${notificationId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
            notificationItem.remove();
            showAlert('Notification deleted', 'success');
            
            if (document.querySelectorAll('.notification-item').length === 0) {
                setTimeout(() => location.reload(), 1000);
            }
        } else {
            showAlert('Error deleting notification', 'danger');
        }
    })
    .catch(() => showAlert('Error deleting notification', 'danger'));
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
    setTimeout(() => alertDiv.remove(), 3000);
}
</script>
@endpush
@endsection

