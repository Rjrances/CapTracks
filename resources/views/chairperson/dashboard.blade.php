@extends('layouts.chairperson')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 900px;">
        <div class="mb-4" style="margin-bottom: 1.2rem !important;">
            <h1 class="fw-bold mb-1" style="font-size:2.5rem; margin-bottom:0.1rem;">Chairperson Dashboard</h1>
            <div class="text-muted" style="font-size:1.1rem; margin-bottom:0;">Oversee offerings, teachers, schedules, and roles</div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-4">
            <div class="fw-semibold mb-2" style="font-size:1.2rem;">Quick Actions</div>
            <div class="d-flex gap-2">
                <a href="{{ route('chairperson.offerings.index') }}" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm border">Manage Offerings</a>
                <a href="{{ route('chairperson.teachers.index') }}" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm border">View Teachers</a>
                <a href="{{ route('chairperson.schedules.index') }}" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm border">View Schedules</a>
                <a href="{{ route('chairperson.upload-form') }}" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm border">Import Students</a>
                <a href="{{ url('/chairperson/manage-roles') }}" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm border">Manage Roles</a>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="mb-4">
            <div class="fw-bold mb-2" style="font-size:1.2rem;">Upcoming Events</div>
            <div class="table-responsive">
                <table class="table table-borderless align-middle mb-0 bg-white rounded-3" style="overflow:hidden;">
                    <thead class="bg-light border-bottom">
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td>{{ $event->title }}</td>
                            <td class="text-primary">{{ $event->date ? \Carbon\Carbon::parse($event->date)->format('Y-m-d') : 'N/A' }}</td>
                            <td>{{ $event->time ? \Carbon\Carbon::parse($event->time)->format('h:i A') : 'N/A' }}</td>
                            <td><a href="{{ route('events.show', $event->id) }}" class="fw-semibold text-decoration-none text-primary">View Details</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted">No upcoming events.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Notifications -->
        <div class="mb-2">
            <div class="fw-bold mb-2" style="font-size:1.2rem;">Notifications</div>
            <div class="bg-light rounded-3 p-3">
                @forelse($notifications as $note)
                <div class="d-flex align-items-start mb-3">
                    <div class="me-3 flex-shrink-0">
                        <span class="d-inline-flex align-items-center justify-content-center bg-white border rounded-circle" style="width:36px; height:36px;">
                            <svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='none' viewBox='0 0 24 24' stroke='currentColor' style='color:#6c757d;'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z'/></svg>
                        </span>
                    </div>
                    <div>
                        <div class="fw-semibold">{{ $note->title }}</div>
                        <div class="text-muted small">{{ $note->description }}</div>
                    </div>
                </div>
                @empty
                <div class="text-muted text-center">No notifications at the moment.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
