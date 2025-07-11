<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - CapTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="w-full max-w-md bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-6 text-center">CapTrack Login</h2>

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/login">
            @csrf

            <div class="mb-4">
                <label for="school_id" class="block text-sm font-semibold mb-1">School ID</label>
                <input type="text" name="school_id" id="school_id" required class="w-full p-2 border rounded" />
            </div>

            <div class="mb-4">
                <label for="password" class="block text-sm font-semibold mb-1">
                    Password <span class="text-xs text-gray-500">(Leave blank if first-time login)</span>
                </label>
                <input type="password" name="password" id="password" class="w-full p-2 border rounded" />
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Login</button>
        </form>

        <p class="text-center text-sm mt-4">
            No account? <a href="/register" class="text-blue-600 hover:underline">Register here</a>
        </p>
    </div>
</body>
</html>
