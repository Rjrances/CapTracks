<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - CapTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">
    <div class="hidden md:flex flex-col justify-center items-start flex-1 bg-gradient-to-br from-blue-600 to-indigo-700 text-white p-12">
        <div class="text-5xl font-bold mb-6">*</div>
        <h1 class="text-4xl font-extrabold leading-tight mb-4">Hello<br>CapTrack! <span class="inline-block">👋</span></h1>
        <p class="text-lg mb-auto">Secure your account by setting a strong password. Your privacy and security are important to us!</p>
        <footer class="mt-12 text-sm opacity-70">© 2024 CapTrack. All rights reserved.</footer>
    </div>
    <div class="flex flex-col justify-center items-center flex-1 bg-white p-8 shadow-lg min-h-screen">
        <div class="w-full max-w-md">
            <div class="flex items-center justify-center mb-4">
                <span class="text-2xl font-extrabold text-blue-700 tracking-wide">CapTrack</span>
            </div>
            <h2 class="text-2xl font-bold mb-6 text-gray-900 text-center">Change Your Password</h2>
            @if(Auth::guard('student')->check())
                @php
                    $studentAccount = Auth::guard('student')->user();
                    $student = $studentAccount->student;
                @endphp
                <p class="text-sm text-gray-600 text-center mb-4">Welcome, {{ $student->name }}! Please set your password.</p>
            @elseif(Auth::check())
                <p class="text-sm text-gray-600 text-center mb-4">Welcome, {{ Auth::user()->name }}! Please set your password.</p>
            @endif
            @if ($errors->any())
                <ul class="bg-red-100 text-red-700 p-3 rounded mb-4 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
            <form method="POST" action="/change-password" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold mb-1">New Password</label>
                    <input type="password" name="password" class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded font-semibold hover:bg-blue-700 transition">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>
