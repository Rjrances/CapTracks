<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chairperson Dashboard - CapTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>

    @include('partials.chairperson-sidebar')

    <div class="main-content" style="margin-left: 280px; min-height: 100vh;">
        <!-- Top Navigation Bar with Notifications -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-2">
            <div class="container-fluid">
                <div class="navbar-brand">
                    <h5 class="mb-0">@yield('title', 'Chairperson Dashboard')</h5>
                </div>
                
                <div class="navbar-nav ms-auto">
                    <!-- Notification Bell -->
                    <div class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell fa-lg text-muted"></i>
                            @php
                                // Count unread notifications for chairperson role OR specifically for this user
                                $user = auth()->user();
                                $notificationCount = \App\Models\Notification::where(function($query) use ($user) {
                                    $query->where('role', 'chairperson')
                                          ->orWhere('user_id', $user->id);
                                })
                                ->where('is_read', false)
                                ->count();
                            @endphp
                            @if($notificationCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    {{ $notificationCount > 99 ? '99+' : $notificationCount }}
                                </span>
                            @endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" style="width: 350px; max-height: 400px; overflow-y: auto;">
                            <div class="dropdown-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Notifications</h6>
                                <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-link text-decoration-none small p-0 border-0 bg-transparent">Mark all read</button>
                                </form>
                            </div>
                            <div class="dropdown-divider"></div>
                            
                            @php
                                // Get notifications for chairperson role OR specifically for this user
                                $user = auth()->user();
                                $recentNotifications = \App\Models\Notification::where(function($query) use ($user) {
                                    $query->where('role', 'chairperson')
                                          ->orWhere('user_id', $user->id);
                                })
                                ->latest()
                                ->take(10)
                                ->get();
                            @endphp
                            
                            @if($recentNotifications->count() > 0)
                                @foreach($recentNotifications as $notification)
                                    <a class="dropdown-item py-2 {{ $notification->is_read ? '' : 'bg-light' }}" 
                                       href="{{ $notification->redirect_url ?? '#' }}" 
                                       onclick="markNotificationAsRead({{ $notification->id }})">
                                        <div class="d-flex align-items-start">
                                            <div class="me-2">
                                                <i class="fas fa-{{ $notification->icon ?? 'bell' }} text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold">{{ $notification->title }}</div>
                                                <small class="text-muted">{{ Str::limit($notification->description, 60) }}</small>
                                                <br><small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-center text-primary" href="#">
                                    <i class="fas fa-eye me-2"></i>View All Notifications
                                </a>
                            @else
                                <div class="dropdown-item text-center text-muted py-3">
                                    <i class="fas fa-bell fa-2x mb-2"></i>
                                    <p class="mb-0">No notifications</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="nav-item dropdown ms-3">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>{{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="p-4">
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

            @yield('content')
        </div>
    </div>

    @include('partials.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function markNotificationAsRead(notificationId) {
        // Mark notification as read via AJAX
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
                // Update notification count
                updateNotificationCount();
                // Reload the page to show updated notifications
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function updateNotificationCount() {
        // Update the notification count badge
        const badge = document.querySelector('.badge');
        if (badge) {
            const currentCount = parseInt(badge.textContent);
            if (currentCount > 1) {
                badge.textContent = currentCount - 1;
            } else {
                badge.style.display = 'none';
            }
        }
    }

    // Add event listener for mark all read form submission
    document.addEventListener('DOMContentLoaded', function() {
        const markAllReadForm = document.querySelector('form[action*="mark-all-read"]');
        if (markAllReadForm) {
            markAllReadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show loading state
                const button = this.querySelector('button');
                const originalText = button.textContent;
                button.textContent = 'Marking...';
                button.disabled = true;

                // Submit the form
                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Hide notification badge
                        const badge = document.querySelector('.badge');
                        if (badge) {
                            badge.style.display = 'none';
                        }
                        // Reload page to show updated notifications
                        location.reload();
                    } else {
                        alert('Error marking notifications as read: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error marking notifications as read. Please try again.');
                })
                .finally(() => {
                    // Reset button state
                    button.textContent = originalText;
                    button.disabled = false;
                });
            });
        }
    });
    </script>
</body>
</html>
