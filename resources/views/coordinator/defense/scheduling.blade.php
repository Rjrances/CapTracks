@extends('layouts.coordinator')

@section('title', 'Defense Schedules')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Defense Schedules</h2>
        <div class="text-muted">
            <i class="fas fa-info-circle me-1"></i>Read-only view
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-filter me-2"></i>Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('coordinator.defense.scheduling') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-6">
                        <label for="term" class="form-label">Academic Term</label>
                        <select name="term" id="term" class="form-select form-select-sm">
                            <option value="">All Terms</option>
                            @foreach($filterOptions['terms'] as $term)
                                <option value="{{ $term->id }}" {{ $filters['term'] == $term->id ? 'selected' : '' }}>
                                    {{ $term->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm me-2">
                            <i class="fas fa-search me-1"></i>Apply Filters
                        </button>
                        <a href="{{ route('coordinator.defense.scheduling') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Defense Schedules Table -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-calendar-alt me-2"></i>
                Defense Schedules ({{ $defenseSchedules->count() }})
            </h6>
        </div>
        <div class="card-body">
            @if($defenseSchedules->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Group</th>
                                <th>Adviser</th>
                                <th>Panel Members</th>
                                <th>Venue</th>
                                <th>Status</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($defenseSchedules as $schedule)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ \Carbon\Carbon::parse($schedule->start_at)->format('M d, Y') }}
                                        </div>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($schedule->start_at)->format('h:i A') }}
                                        </small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $schedule->group->name ?? 'N/A' }}</div>
                                        <small class="text-muted">
                                            {{ $schedule->group->description ?? 'No description' }}
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            Term: {{ $schedule->academicTerm->full_name ?? 'N/A' }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($schedule->group->adviser)
                                            <div class="fw-semibold">{{ $schedule->group->adviser->name }}</div>
                                            <small class="text-muted">{{ $schedule->group->adviser->email }}</small>
                                        @else
                                            <span class="text-warning">No Adviser</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($schedule->defensePanels->count() > 0)
                                            @foreach($schedule->defensePanels->take(3) as $panel)
                                                <div class="small">
                                                    <span class="badge bg-info me-1">{{ $panel->role }}</span>
                                                    {{ $panel->faculty->name }}
                                                </div>
                                            @endforeach
                                            @if($schedule->defensePanels->count() > 3)
                                                <small class="text-muted">+{{ $schedule->defensePanels->count() - 3 }} more</small>
                                            @endif
                                        @else
                                            <span class="text-muted">No panel assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $schedule->room ?? 'TBD' }}</div>
                                    </td>
                                    <td>
                                        @if($schedule->status === 'scheduled')
                                            <span class="badge bg-primary">Scheduled</span>
                                        @elseif($schedule->status === 'in_progress')
                                            <span class="badge bg-warning">In Progress</span>
                                        @elseif($schedule->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($schedule->status === 'cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($schedule->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $progress = $schedule->group->overall_progress_percentage ?? 0;
                                        @endphp
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar 
                                                @if($progress >= 80) bg-success
                                                @elseif($progress >= 60) bg-warning
                                                @else bg-danger
                                                @endif" 
                                                role="progressbar" 
                                                style="width: {{ $progress }}%" 
                                                aria-valuenow="{{ $progress }}" 
                                                aria-valuemin="0" aria-valuemax="100">
                                                {{ $progress }}%
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            @if($progress >= 80)
                                                100% Defense Ready
                                            @elseif($progress >= 60)
                                                Progress Review Ready
                                            @else
                                                Not Ready
                                            @endif
                                        </small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h5>No Defense Schedules Found</h5>
                    <p class="text-muted">
                        @if($filters['term'])
                            No schedules match the current term filter. Try selecting a different term.
                        @else
                            There are no defense schedules currently available.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-submit filters when changed
document.addEventListener('DOMContentLoaded', function() {
    const filterSelects = document.querySelectorAll('#filterForm select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
});
</script>
@endpush
@endsection
