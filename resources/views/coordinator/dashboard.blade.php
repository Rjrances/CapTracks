@extends('layouts.coordinator')

@section('content')
<div class="max-w-7xl mx-auto p-6">

    <h1 class="text-4xl font-extrabold mb-8 text-gray-900">Coordinator Dashboard</h1>
    <p class="mb-10 text-gray-700 text-lg">Manage schedules, assignments, classes, and communications efficiently</p>

    {{-- Quick Actions --}}
    {{-- Upcoming Events --}}
    <section class="mb-14">
        <h2 class="text-3xl font-semibold mb-6 text-gray-900 border-b border-gray-300 pb-2">Upcoming Events</h2>
        @if($events->isEmpty())
            <p class="text-center text-gray-500 italic">No upcoming events.</p>
        @else
            <div class="overflow-x-auto bg-white rounded-lg shadow-md">
                <table class="min-w-full text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700 uppercase text-sm tracking-wide">
                        <tr>
                            <th class="px-6 py-4 border-b font-medium">Event</th>
                            <th class="px-6 py-4 border-b font-medium">Date</th>
                            <th class="px-6 py-4 border-b font-medium">Time</th>
                            <th class="px-6 py-4 border-b font-medium">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                        <tr class="hover:bg-gray-50 border-b">
                            <td class="px-6 py-5">{{ $event->title }}</td>
                            <td class="px-6 py-5">{{ $event->date ? \Carbon\Carbon::parse($event->date)->format('Y-m-d') : 'N/A' }}</td>
                            <td class="px-6 py-5">{{ $event->time ? \Carbon\Carbon::parse($event->time)->format('h:i A') : 'N/A' }}</td>
                            <td class="px-6 py-5">
                                <a href="{{ route('events.show', $event->id) }}" 
                                   class="text-blue-600 hover:underline font-medium">View Details</a>
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
        <h2 class="text-3xl font-semibold mb-6 text-gray-900 border-b border-gray-300 pb-2">Notifications</h2>
        @if($notifications->isEmpty())
            <p class="text-center text-gray-500 italic">No notifications at the moment.</p>
        @else
            <ul class="bg-white rounded-lg shadow-md divide-y divide-gray-200">
                @foreach($notifications as $note)
                <li class="px-6 py-5 hover:bg-gray-50 transition duration-150">
                    <p class="font-semibold text-gray-800">{{ $note->title }}</p>
                    <p class="text-gray-600 mt-1">{{ $note->description }}</p>
                </li>
                @endforeach
            </ul>
        @endif
    </section>

</div>
@endsection
