<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - CapTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    @stack('styles')
</head>
<body>

    @include('partials.student-sidebar')

    <div class="main-content" style="margin-left: 280px; min-height: 100vh;">
        <!-- Top Navigation Bar with Notifications -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-2">
            <div class="container-fluid">
                <div class="navbar-brand">
                    <h5 class="mb-0">@yield('title', 'Student Dashboard')</h5>
                </div>
                
                <div class="navbar-nav ms-auto">
                    <!-- Notification Bell -->
                    <div class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell fa-lg text-muted"></i>
                            @php
                                $notificationCount = 0;
                                if (session('is_student') && session('student_id')) {
                                    $notificationCount = \App\Models\Notification::where('role', 'student')
                                        ->where('is_read', false)
                                        ->count();
                                }
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
                                $recentNotifications = collect();
                                if (session('is_student') && session('student_id')) {
                                    $studentId = session('student_id');
                                    $recentNotifications = \App\Models\Notification::where(function($query) use ($studentId) {
                                        $query->where('role', 'student')
                                              ->orWhere('user_id', $studentId);
                                    })
                                    ->where('is_read', false)
                                    ->latest()
                                    ->take(10)
                                    ->get();
                                }
                            @endphp
                            
                            @if($recentNotifications->count() > 0)
                                @foreach($recentNotifications as $notification)
                                    <form method="POST" action="{{ route('notifications.mark-read', $notification) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-link dropdown-item py-2 {{ $notification->is_read ? '' : 'bg-light' }} p-0 border-0 bg-transparent text-start w-100">
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
                                        </button>
                                    </form>
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
                            <i class="fas fa-user-circle me-2"></i>
                            @if(\Illuminate\Support\Facades\Auth::check())
                                {{ auth()->user()->name }}
                            @elseif(session('is_student') && session('student_id'))
                                @php
                                    $student = \App\Models\Student::find(session('student_id'));
                                    echo $student->name ?? 'Student';
                                @endphp
                            @else
                                Student
                            @endif
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
            @yield('content')
        </div>
    </div>

        @include('partials.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
    
    <style>
    .dropdown-item {
        transition: all 0.2s ease;
    }
    
    .dropdown-item:hover {
        background-color: rgba(13, 110, 253, 0.1) !important;
        transform: translateX(2px);
    }
    
    .dropdown-item.bg-light {
        opacity: 0.7;
    }
    
    .notification-badge {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .notification-item {
        border-left: 3px solid transparent;
        transition: all 0.3s ease;
    }
    
    .notification-item:hover {
        border-left-color: #0d6efd;
        background-color: rgba(13, 110, 253, 0.05);
    }
    </style>
</body>
</html>
