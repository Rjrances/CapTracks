<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - CapTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <!-- Left Panel: Branding -->
    <div class="hidden md:flex flex-col justify-center items-start flex-1 bg-gradient-to-br from-blue-600 to-indigo-700 text-white p-12">
        <!-- Logo Section -->
        <div class="mb-8 text-center">
            <img src="{{ asset('images/Logo.png') }}" alt="CapTrack Logo" class="w-24 h-24 mb-1 mx-auto" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <div class="text-6xl font-bold mb-1" style="display: none;">📊</div>
            <h1 class="text-4xl font-extrabold leading-tight mb-4 text-white">CapTrack</h1>
        </div>
        <p class="text-lg mb-auto">Skip repetitive and manual capstone tasks. Get highly productive through automation and save tons of time!</p>
        <footer class="mt-12 text-sm opacity-70">© 2024 CapTrack. All rights reserved.</footer>
    </div>
    <!-- Right Panel: Login Form -->
    <div class="flex flex-col justify-center items-center flex-1 bg-white p-8 shadow-lg min-h-screen">
        <div class="w-full max-w-md">
            <div class="flex items-center justify-center mb-6">
                <img src="{{ asset('images/Logo.png') }}" alt="CapTrack Logo" class="w-12 h-12 mr-3" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                <span class="text-2xl font-extrabold text-blue-700 tracking-wide" style="display: inline-block;">CapTrack</span>
            </div>
            <h2 class="text-2xl font-bold mb-6 text-gray-900">Welcome Back!</h2>
            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                    {{ $errors->first() }}
                </div>
            @endif
            <form method="POST" action="/login" class="space-y-5">
                @csrf
                <div>
                    <label for="school_id" class="block text-sm font-semibold mb-1">ID Number (Faculty/Staff ID or Student ID)</label>
                    <input type="text" name="school_id" id="school_id" required class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your ID number" />
                </div>
                <div>
                    <label for="password" class="block text-sm font-semibold mb-1">
                        Password <span class="text-xs text-gray-500">(Leave blank for first-time login or students)</span>
                    </label>
                    <input type="password" name="password" id="password" class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded font-semibold hover:bg-blue-700 transition">Login Now</button>
            </form>
            <div class="text-center mt-6">
                <a href="/password/reset" class="text-blue-600 hover:underline text-sm">Forgot password? Click here</a>
            </div>
        </div>
    </div>
</body>
</html>
