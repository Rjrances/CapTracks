<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'CapTrack')</title>

    {{-- Bootstrap 5 CDN for consistent styling --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Tailwind CSS (or your CSS framework) --}}
    <link href="{{ asset('css/app.css') }}" rel="stylesheet" />
    {{-- Font Awesome for icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    {{-- Optional: add your own scripts or styles --}}
    @stack('styles')
</head>
<body class="bg-gray-100 font-sans text-gray-900">

    {{-- Conditionally include navigation if the partial exists --}}
    @if (auth()->check() && auth()->user()->hasRole('student'))
        @include('partials.nav.student')
    @elseif (session('is_student'))
        @include('partials.nav.student')
    @elseif (View::exists('partials.nav'))
        @include('partials.nav')
    @endif

    {{-- Notification Bell (Top-Right) --}}
    @auth
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1050;">
        <div class="dropdown">
            <button class="btn btn-outline-primary position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell"></i>
                @php
                    $notificationCount = \App\Models\Notification::where('role', auth()->user()->getPrimaryRoleAttribute())->where('is_read', false)->count();
                @endphp
                @if($notificationCount > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ $notificationCount > 99 ? '99+' : $notificationCount }}
                    </span>
                @endif
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
                <li><h6 class="dropdown-header">Notifications</h6></li>
                @php
                    $notifications = \App\Models\Notification::where('role', auth()->user()->getPrimaryRoleAttribute())
                        ->latest()
                        ->take(5)
                        ->get();
                @endphp
                @forelse($notifications as $notification)
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">{{ $notification->title }}</h6>
                                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="mb-1">{{ Str::limit($notification->message, 100) }}</p>
                        </a>
                    </li>
                @empty
                    <li><span class="dropdown-item-text text-muted">No new notifications</span></li>
                @endforelse
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center" href="{{ route('coordinator.notifications') }}">View All Notifications</a></li>
            </ul>
        </div>
    </div>
    @endauth

    <main class="container mx-auto p-6">
        {{-- Where page content goes --}}
        @yield('content')
    </main>

    {{-- Common footer --}}
    @include('partials.footer')

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
