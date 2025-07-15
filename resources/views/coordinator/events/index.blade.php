@extends('layouts.coordinator')

@section('title', 'Events')

@section('content')
<div class="d-flex justify-content-center align-items-start" style="min-height: 80vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm p-5 w-100" style="max-width: 950px;">
        <div class="mb-4" style="margin-bottom: 1.2rem !important;">
            <h1 class="fw-bold mb-1" style="font-size:2.2rem; margin-bottom:0.1rem;">Events</h1>
            <div class="text-muted" style="font-size:1.1rem; margin-bottom:0;">View and manage all upcoming and past events</div>
        </div>
        <div class="mb-4 d-flex justify-content-end align-items-center">
            <a href="#" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm">Create New Event</a>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0 bg-white rounded-3" style="overflow:hidden;">
                <thead class="bg-light border-bottom">
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                    <tr style="border-bottom:1px solid #f0f0f0;">
                        <td class="fw-semibold">{{ $event->title }}</td>
                        <td>{{ $event->date ? \Carbon\Carbon::parse($event->date)->format('Y-m-d') : 'N/A' }}</td>
                        <td>{{ $event->time ? \Carbon\Carbon::parse($event->time)->format('h:i A') : 'N/A' }}</td>
                        <td>
                            <a href="{{ route('events.show', $event->id) }}" class="btn btn-outline-primary btn-sm rounded-pill me-1">View</a>
                            <a href="#" class="btn btn-outline-secondary btn-sm rounded-pill me-1">Edit</a>
                            <a href="#" class="btn btn-outline-danger btn-sm rounded-pill">Delete</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted">No events found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection 