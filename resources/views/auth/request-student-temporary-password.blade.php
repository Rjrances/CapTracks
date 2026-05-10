<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Temporary password — CapTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <div class="hidden md:flex flex-col justify-center items-start flex-1 bg-gradient-to-br from-blue-600 to-indigo-700 text-white p-12">
        <div class="mb-8 text-center">
            <img src="{{ asset('images/Logo.png') }}" alt="CapTrack Logo" class="w-24 h-24 mb-1 mx-auto">
            <h1 class="text-4xl font-extrabold leading-tight mb-4 text-white">CapTrack</h1>
        </div>
        <p class="text-lg mb-auto">First time signing in? We will email a one-time password to the address we have on record for your student ID.</p>
        <footer class="mt-12 text-sm opacity-70">© 2024 CapTrack. All rights reserved.</footer>
    </div>
    <div class="flex flex-col justify-center items-center flex-1 bg-white p-8 shadow-lg min-h-screen">
        <div class="w-full max-w-md">
            <div class="flex items-center justify-center mb-6">
                <img src="{{ asset('images/Logo.png') }}" alt="CapTrack Logo" class="w-12 h-12 mr-3" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                <span class="text-2xl font-extrabold text-blue-700 tracking-wide" style="display: inline-block;">CapTrack</span>
            </div>
            <h2 class="text-2xl font-bold mb-2 text-gray-900">Email temporary password</h2>
            <p class="text-sm text-gray-600 mb-6">Enter your student ID (same as on the login page). If your account is pending first login, you will receive an email with a temporary password.</p>

            @if (session('status'))
                <div class="bg-green-50 text-green-800 border border-green-200 p-3 rounded mb-4 text-sm">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.student-credentials.store') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="student_id" class="block text-sm font-semibold mb-1">Student ID</label>
                    <input type="text" name="student_id" id="student_id" value="{{ old('student_id') }}" required autocomplete="username" class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Your ID number" />
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded font-semibold hover:bg-blue-700 transition">Send email</button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-600">
                <a href="{{ route('login') }}" class="text-blue-600 font-medium hover:underline">Back to login</a>
            </p>
        </div>
    </div>
</body>
</html>
