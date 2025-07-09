<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Coordinator Dashboard - CapTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-6">
    <h1 class="text-3xl font-bold mb-4">Welcome, {{ auth()->user()->name }} (Coordinator)</h1>

    <p>Your School ID: {{ auth()->user()->school_id }}</p>

    <form method="POST" action="/logout" class="mt-4">
        @csrf
        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Logout</button>
    </form>
</body>
</html>
