<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - CapTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="w-full max-w-md bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-6 text-center">CapTrack Register</h2>
        {{-- Success message --}}
        @if (session('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif
        {{-- Error message --}}
        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="POST" action="/register">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1">Name</label>
                <input type="text" name="name" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1">School Email</label>
                <input type="email" name="email" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1">Password</label>
                <input type="password" name="password" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" class="w-full p-2 border rounded" required>
            </div>
            {{-- Show role dropdown only if chairperson is logged in --}}
            @auth
                @if (Auth::check() && Auth::user()->hasRole('chairperson'))
    <div class="mb-4">
        <label class="block text-sm font-semibold mb-1">School</label>
        <select name="school_id" class="w-full p-2 border rounded" required>
            @foreach ($schools as $school)
                <option value="{{ $school->id }}">{{ $school->name }}</option>
            @endforeach
        </select>
    </div>
@endif
            <button type="submit" class="w-full bg-green-600 text-white p-2 rounded hover:bg-green-700">Register</button>
        </form>
    </div>
</body>
</html>
