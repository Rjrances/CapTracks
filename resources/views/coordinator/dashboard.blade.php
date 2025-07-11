@extends('layouts.coordinator')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Coordinator Dashboard</h1>
    <p class="mb-8 text-gray-600">Manage schedules, assignments, classes, and communications</p>

    {{-- Quick Actions --}}
    <div class="flex flex-wrap gap-4 mb-10">
        <a href="{{ route('classes.create') }}" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded shadow">
            Create New Class
        </a>
        <a href="{{ route('classes.index') }}" 
           class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-3 rounded shadow">
            View All Classes
        </a>
    </div>

    {{-- Upcoming Events --}}
    <section class="mb-10">
        <h2 class="text-2xl font-semibold mb-4">Upcoming Events</h2>
        @if($events->isEmpty())
            <p class="text-gray-500">No upcoming events.</p>
        @else
            <div class="overflow-x-auto bg-white rounded shadow">
                <table class="min-w-full text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-sm">
                        <tr>
                            <th class="px-6 py-3 border-b">Event</th>
                            <th class="px-6 py-3 border-b">Date</th>
                            <th class="px-6 py-3 border-b">Time</th>
                            <th class="px-6 py-3 border-b">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                        <tr class="hover:bg-gray-50 border-b">
                            <td class="px-6 py-4">{{ $event->title }}</td>
                            <td class="px-6 py-4">{{ \Carbon\Carbon::parse($event->date)->format('Y-m-d') }}</td>
                            <td class="px-6 py-4">{{ \Carbon\Carbon::parse($event->time)->format('h:i A') }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('events.show', $event->id) }}" 
                                   class="text-blue-600 hover:underline">View Details</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Notifications --}}
    <section>
        <h2 class="text-2xl font-semibold mb-4">Notifications</h2>
        @if($notifications->isEmpty())
            <p class="text-gray-500">No notifications at the moment.</p>
        @else
            <ul class="bg-white rounded shadow divide-y divide-gray-200">
                @foreach($notifications as $note)
                <li class="px-6 py-4">
                    <p class="font-semibold">{{ $note->title }}</p>
                    <p class="text-gray-600">{{ $note->description }}</p>
                </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection
    