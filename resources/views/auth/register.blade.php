<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - CapTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="w-full max-w-md bg-white p-6 rounded-lg shadow-md">
       <h2 class="text-2xl font-bold mb-6 text-center">CapTrack Login</h2>

@if (session('success'))
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
        {{ $errors->first() }}
    </div>
@endif


        @if (session('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                {{ session('success') }}
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

            @if (Auth::check() && Auth::user()->role === 'chairperson')
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-1">Role</label>
                    <select name="role" class="w-full p-2 border rounded" required>
                        <option value="student">Student</option>
                        <option value="coordinator">Coordinator</option>
                        <option value="adviser">Adviser</option>
                        <option value="panelist">Panelist</option>
                    </select>
                </div>
            @endif

            <button type="submit" class="w-full bg-green-600 text-white p-2 rounded hover:bg-green-700">Register</button>
        </form>
    </div>
</body>
</html>
