<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>CapTrack - Capstone Project Management System</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            'captrack-blue': '#2563eb',
                            'captrack-indigo': '#4338ca',
                            'captrack-dark': '#1e293b',
                        }
                    }
                }
            }
        </script>
    </head>
    <body class="bg-gray-50 font-sans">
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center space-x-4">
                        <img src="{{ asset('images/Logo.png') }}" alt="CapTrack Logo" class="w-10 h-10" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <div class="text-2xl font-bold text-usjr-dark" style="display: none;">📊</div>
                        <div>
                                                    <h1 class="text-xl font-bold text-captrack-dark">CapTrack</h1>
                        <p class="text-xs text-gray-600">Capstone Project Management</p>
                        </div>
                    </div>
                    <nav class="hidden md:flex space-x-8">
                        <a href="#home" class="text-captrack-dark hover:text-captrack-blue transition-colors">Home</a>
                        <a href="#features" class="text-captrack-dark hover:text-captrack-blue transition-colors">Features</a>
                        <a href="#about" class="text-captrack-dark hover:text-captrack-blue transition-colors">About</a>
                    </nav>
                    <div class="flex items-center space-x-4">
                        @auth
                            <a href="{{ route('login') }}" class="bg-captrack-blue text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition-colors">
                                Login
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-captrack-dark hover:text-captrack-blue transition-colors">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="bg-captrack-blue text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition-colors">
                                    Register
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </header>
        <section id="home" class="relative bg-gradient-to-br from-blue-600 to-indigo-700 py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div class="text-white">
                        <h1 class="text-5xl lg:text-6xl font-bold mb-6 leading-tight text-white">
                            CAPSTONE PROJECTS
                            <span class="block">MADE SIMPLE</span>
                        </h1>
                        <p class="text-xl mb-8 text-white font-medium">
                            Streamline your capstone project management with our comprehensive tracking system. 
                            From proposal to defense, we've got you covered.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="{{ route('login') }}" class="bg-white text-captrack-blue px-8 py-4 rounded-lg font-bold text-lg hover:bg-gray-100 transition-colors text-center">
                                Get Started Now
                            </a>
                            <a href="#features" class="border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:text-captrack-blue transition-colors text-center">
                                Learn More
                            </a>
                        </div>
                    </div>
                    <div class="relative">
                        <div class="bg-white rounded-2xl p-8 shadow-2xl">
                            <img src="{{ asset('images/Logo.png') }}" alt="CapTrack Dashboard Preview" class="w-full h-64 object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <div class="w-full h-64 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white text-4xl font-bold" style="display: none;">📊</div>
                            <p class="text-center text-black mt-4">CapTrack Dashboard Preview</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section id="features" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-captrack-dark mb-4">Features for Different Roles</h2>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                        CapTrack provides specialized features tailored to each role in the capstone project ecosystem
                    </p>
                </div>
                <div class="grid lg:grid-cols-2 gap-12 mb-16">
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-8 rounded-2xl border border-blue-200">
                        <div class="flex items-center mb-6">
                            <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center mr-4">
                                <span class="text-2xl text-white">👨‍🎓</span>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-captrack-dark">Students</h3>
                                <p class="text-gray-600">Manage your capstone journey</p>
                            </div>
                        </div>
                        <ul class="space-y-3">
                            <li class="flex items-start space-x-3">
                                <span class="text-blue-500 text-lg">✓</span>
                                <span class="text-gray-700">Create and manage project proposals</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-blue-500 text-lg">✓</span>
                                <span class="text-gray-700">Track milestone progress and deadlines</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-blue-500 text-lg">✓</span>
                                <span class="text-gray-700">Submit project documents and files</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-blue-500 text-lg">✓</span>
                                <span class="text-gray-700">Collaborate with team members</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-blue-500 text-lg">✓</span>
                                <span class="text-gray-700">Schedule and manage defense appointments</span>
                            </li>
                        </ul>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-8 rounded-2xl border border-green-200">
                        <div class="flex items-center mb-6">
                            <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mr-4">
                                <span class="text-2xl text-white">👨‍🏫</span>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-captrack-dark">Advisers</h3>
                                <p class="text-gray-600">Guide and mentor students</p>
                            </div>
                        </div>
                        <ul class="space-y-3">
                            <li class="flex items-start space-x-3">
                                <span class="text-green-500 text-lg">✓</span>
                                <span class="text-gray-700">Review and approve project proposals</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-green-500 text-lg">✓</span>
                                <span class="text-gray-700">Monitor student progress and provide feedback</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-green-500 text-lg">✓</span>
                                <span class="text-gray-700">Evaluate milestone submissions</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-green-500 text-lg">✓</span>
                                <span class="text-gray-700">Schedule consultation meetings</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-green-500 text-lg">✓</span>
                                <span class="text-gray-700">Participate in defense panels</span>
                            </li>
                        </ul>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-violet-50 p-8 rounded-2xl border border-purple-200">
                        <div class="flex items-center mb-6">
                            <div class="w-16 h-16 bg-purple-500 rounded-full flex items-center justify-center mr-4">
                                <span class="text-2xl text-white">👨‍💼</span>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-captrack-dark">Coordinators</h3>
                                <p class="text-gray-600">Oversee program management</p>
                            </div>
                        </div>
                        <ul class="space-y-3">
                            <li class="flex items-start space-x-3">
                                <span class="text-purple-500 text-lg">✓</span>
                                <span class="text-gray-700">Manage class offerings and enrollments</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-purple-500 text-lg">✓</span>
                                <span class="text-gray-700">Create and assign milestone templates</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-purple-500 text-lg">✓</span>
                                <span class="text-gray-700">Organize events and schedules</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-purple-500 text-lg">✓</span>
                                <span class="text-gray-700">Generate comprehensive reports</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-purple-500 text-lg">✓</span>
                                <span class="text-gray-700">Facilitate group formations</span>
                            </li>
                        </ul>
                    </div>
                    <div class="bg-gradient-to-br from-orange-50 to-amber-50 p-8 rounded-2xl border border-orange-200">
                        <div class="flex items-center mb-6">
                            <div class="w-16 h-16 bg-orange-500 rounded-full flex items-center justify-center mr-4">
                                <span class="text-2xl text-white">👨‍🎓</span>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-captrack-dark">Chairperson</h3>
                                <p class="text-gray-600">Strategic oversight and management</p>
                            </div>
                        </div>
                        <ul class="space-y-3">
                            <li class="flex items-start space-x-3">
                                <span class="text-orange-500 text-lg">✓</span>
                                <span class="text-gray-700">Manage faculty roles and permissions</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-orange-500 text-lg">✓</span>
                                <span class="text-gray-700">Oversee program-wide statistics</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-orange-500 text-lg">✓</span>
                                <span class="text-gray-700">Approve major program changes</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-orange-500 text-lg">✓</span>
                                <span class="text-gray-700">Monitor academic standards</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-orange-500 text-lg">✓</span>
                                <span class="text-gray-700">Generate institutional reports</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="text-center mb-12">
                    <h3 class="text-3xl font-bold text-captrack-dark mb-4">Platform Features</h3>
                    <p class="text-lg text-gray-600">Core capabilities that benefit all users</p>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="bg-gray-50 p-8 rounded-xl hover:shadow-lg transition-shadow">
                        <div class="w-16 h-16 bg-captrack-blue rounded-full flex items-center justify-center mb-6">
                            <span class="text-2xl text-white">📋</span>
                        </div>
                        <h3 class="text-xl font-bold text-captrack-dark mb-4">Project Tracking</h3>
                        <p class="text-gray-600">
                            Monitor project progress, milestones, and deadlines with our intuitive tracking system.
                        </p>
                    </div>
                    <div class="bg-gray-50 p-8 rounded-xl hover:shadow-lg transition-shadow">
                        <div class="w-16 h-16 bg-captrack-blue rounded-full flex items-center justify-center mb-6">
                            <span class="text-2xl text-white">👥</span>
                        </div>
                        <h3 class="text-xl font-bold text-captrack-dark mb-4">Team Collaboration</h3>
                        <p class="text-gray-600">
                            Facilitate seamless communication between students, advisers, and coordinators.
                        </p>
                    </div>
                    <div class="bg-gray-50 p-8 rounded-xl hover:shadow-lg transition-shadow">
                        <div class="w-16 h-16 bg-captrack-blue rounded-full flex items-center justify-center mb-6">
                            <span class="text-2xl text-white">📊</span>
                        </div>
                        <h3 class="text-xl font-bold text-captrack-dark mb-4">Progress Analytics</h3>
                        <p class="text-gray-600">
                            Get detailed insights and reports on project performance and completion rates.
                        </p>
                    </div>
                    <div class="bg-gray-50 p-8 rounded-xl hover:shadow-lg transition-shadow">
                        <div class="w-16 h-16 bg-captrack-blue rounded-full flex items-center justify-center mb-6">
                            <span class="text-2xl text-white">📅</span>
                        </div>
                        <h3 class="text-xl font-bold text-captrack-dark mb-4">Schedule Management</h3>
                        <p class="text-gray-600">
                            Organize defense schedules, meetings, and important events efficiently.
                        </p>
                    </div>
                    <div class="bg-gray-50 p-8 rounded-xl hover:shadow-lg transition-shadow">
                        <div class="w-16 h-16 bg-captrack-blue rounded-full flex items-center justify-center mb-6">
                            <span class="text-2xl text-white">📁</span>
                        </div>
                        <h3 class="text-xl font-bold text-captrack-dark mb-4">Document Management</h3>
                        <p class="text-gray-600">
                            Store and organize project documents, submissions, and related files securely.
                        </p>
                    </div>
                    <div class="bg-gray-50 p-8 rounded-xl hover:shadow-lg transition-shadow">
                        <div class="w-16 h-16 bg-captrack-blue rounded-full flex items-center justify-center mb-6">
                            <span class="text-2xl text-white">🔔</span>
                        </div>
                        <h3 class="text-xl font-bold text-captrack-dark mb-4">Smart Notifications</h3>
                        <p class="text-gray-600">
                            Stay updated with automated notifications for deadlines and important events.
                        </p>
                    </div>
                </div>
            </div>
        </section>
        <section id="about" class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <h2 class="text-4xl font-bold text-captrack-dark mb-6">About CapTrack</h2>
                        <p class="text-lg text-gray-600 mb-6">
                            CapTrack is a comprehensive capstone project management system designed specifically for educational institutions. 
                            Our platform streamlines the entire capstone process from initial proposal to final defense.
                        </p>
                        <p class="text-lg text-gray-600 mb-8">
                            Built with modern technology and user experience in mind, CapTrack helps students, faculty, and administrators 
                            work together more efficiently while maintaining high standards of project quality.
                        </p>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-captrack-blue mb-2">100+</div>
                                <div class="text-gray-600">Active Projects</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-captrack-blue mb-2">500+</div>
                                <div class="text-gray-600">Happy Users</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-8 rounded-2xl shadow-lg">
                        <h3 class="text-2xl font-bold text-captrack-dark mb-6">Key Benefits</h3>
                        <ul class="space-y-4">
                            <li class="flex items-start space-x-3">
                                <span class="text-captrack-blue text-xl">✓</span>
                                <span class="text-gray-700">Reduced administrative workload by 60%</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-captrack-blue text-xl">✓</span>
                                <span class="text-gray-700">Improved project completion rates</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-captrack-blue text-xl">✓</span>
                                <span class="text-gray-700">Enhanced communication between stakeholders</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-captrack-blue text-xl">✓</span>
                                <span class="text-gray-700">Real-time progress tracking and reporting</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <span class="text-captrack-blue text-xl">✓</span>
                                <span class="text-gray-700">Secure document storage and management</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <section class="py-20 bg-captrack-dark">
            <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-bold text-white mb-6">Ready to Transform Your Capstone Management?</h2>
                <p class="text-xl text-gray-300 mb-8">
                    Join hundreds of students and faculty who are already using CapTrack to streamline their capstone projects.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('login') }}" class="bg-captrack-blue text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-blue-700 transition-colors">
                        Start Using CapTrack
                    </a>
                    <a href="#features" class="border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:text-usjr-dark transition-colors">
                        Explore Features
                    </a>
                </div>
            </div>
        </section>
        <footer class="bg-gray-900 text-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="md:col-span-2">
                        <div class="flex items-center space-x-4 mb-4">
                            <img src="{{ asset('images/Logo.png') }}" alt="CapTrack Logo" class="w-10 h-10" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <div class="text-2xl font-bold" style="display: none;">📊</div>
                            <h3 class="text-xl font-bold">CapTrack</h3>
                        </div>
                        <p class="text-gray-400 mb-4">
                            The comprehensive capstone project management system designed to streamline your academic journey.
                        </p>
                        <p class="text-gray-400">
                            © 2024 CapTrack. All rights reserved.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                        <ul class="space-y-2">
                            <li><a href="#home" class="text-gray-400 hover:text-white transition-colors">Home</a></li>
                            <li><a href="#features" class="text-gray-400 hover:text-white transition-colors">Features</a></li>
                            <li><a href="#about" class="text-gray-400 hover:text-white transition-colors">About</a></li>
                            <li><a href="{{ route('login') }}" class="text-gray-400 hover:text-white transition-colors">Login</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>
        <script>
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        </script>
    </body>
</html>
