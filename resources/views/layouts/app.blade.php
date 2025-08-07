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

    {{-- Optional: add your own scripts or styles --}}
    @stack('styles')
</head>
<body class="bg-gray-100 font-sans text-gray-900">

    {{-- Conditionally include navigation if the partial exists --}}
    @if (auth()->check() && auth()->user()->role === 'student')
        @include('partials.nav.student')
    @elseif (session('is_student'))
        @include('partials.nav.student')
    @elseif (View::exists('partials.nav'))
        @include('partials.nav')
    @endif

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
