@php
    $role = auth()->user()->role->name ?? 'guest'; // adjust according to your User-Role setup
@endphp

<nav class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <div class="text-2xl font-bold text-blue-700">
        CapTrack
    </div>

    <ul class="flex space-x-6 items-center">
        {{-- Common Links --}}
        <li><a href="{{ route('coordinator-dashboard') }}" class="hover:text-blue-600">Coordinator</a></li>
        <li><a href="{{ route('chairperson-dashboard') }}" class="hover:text-blue-600">Chairperson</a></li>

        {{-- Role-specific Links --}}
        @if ($role === 'coordinator')
            <li><a href="{{ route('classes.create') }}" class="hover:text-blue-600">Create Class</a></li>
            <li><a href="{{ route('classes.index') }}" class="hover:text-blue-600">All Classes</a></li>
        @elseif ($role === 'chairperson')
            <li><a href="{{ route('manage-roles') }}" class="hover:text-blue-600">Manage Roles</a></li>
            <li><a href="{{ url('/chairperson/offerings') }}" class="hover:text-blue-600">Offerings</a></li>
            <li><a href="{{ url('/chairperson/teachers') }}" class="hover:text-blue-600">Teachers</a></li>
        @endif

        {{-- User Info and Logout --}}
        <li class="ml-6 font-semibold text-gray-700">Hi, {{ auth()->user()->name }}</li>
        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-red-600 hover:text-red-800">Logout</button>
            </form>
        </li>
    </ul>
</nav>
