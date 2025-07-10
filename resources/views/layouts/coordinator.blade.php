@extends('layouts.coordinator')

@section('title', 'Coordinator Dashboard')

@section('content')
<div class="p-6 max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold mb-4">Coordinator Dashboard</h1>
    <p class="text-gray-600 mb-8">Manage schedules, assignments, classes, and communications</p>

    {{-- Quick Actions --}}
    <div class="flex gap-4 mb-8">
        <a href="{{ route('classes.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Create New Class
        </a>
        <a href="{{ route('classes.index') }}" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300">
            View All Classes
        </a>
    </div>

    {{-- Upcoming Events --}}
    <section class="mb-8">
        <h2 class="text-xl font-semibold mb-4">Upcoming Events</h2>
        <div class="overflow-x-auto bg-white shadow rounded">
            <table class="min-w-full text-left">
                <thead class="bg-gray-100 text-sm text-gray-700 uppercase">
                    <tr>
                        <th class="px-6 py-3">Event</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Time</th>
                        <th class="px-6 py-3">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4">{{ $event->title }}</td>
                        <td class="px-6 py-4">{{ \Carbon\Carbon::parse($event->date)->format('Y-m-d') }}</td>
                        <td class="px-6 py-4">{{ \Carbon\Carbon::parse($event->time)->format('h:i A') }}</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('events.show', $event->id) }}" class="text-blue-600 hover:underline">
                                View Details
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-gray-500 px-6 py-4">No upcoming events.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Notifications --}}
    <section>
        <h2 class="text-xl font-semibold mb-4">Notifications</h2>
        <ul class="bg-white shadow rounded divide-y">
            @forelse ($notifications as $note)
            <li class="px-6 py-4">
                <p class="font-medium">{{ $note->title }}</p>
                <p class="text-sm text-gray-600">{{ $note->description }}</p>
            </li>
            @empty
            <li class="px-6 py-4 text-gray-500 text-sm">No notifications at the moment.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
