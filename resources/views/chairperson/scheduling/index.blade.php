@extends('layouts.chairperson')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">Scheduling</h2>
                    @if($activeTerm)
                        <p class="text-muted mb-0">
                            <i class="fas fa-calendar-alt me-1"></i>
                            @if($showAllTerms)
                                Showing defense schedules for: <strong>All Terms</strong>
                                <span class="badge bg-info ms-2">All Terms</span>
                            @else
                                Showing defense schedules for: <strong>{{ $activeTerm->full_name }}</strong>
                                <span class="badge bg-success ms-2">Active Term</span>
                            @endif
                        </p>
                    @else
                        <p class="text-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            No active academic term set. Please set an active term to view defense schedules.
                        </p>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    @if($activeTerm)
                        @if($showAllTerms)
                            <a href="{{ route('chairperson.scheduling.index') }}" class="btn btn-outline-success">
                                <i class="fas fa-calendar-check"></i> Show Active Term Only
                            </a>
                        @else
                            <a href="{{ route('chairperson.scheduling.index', ['show_all' => true]) }}" class="btn btn-outline-info">
                                <i class="fas fa-calendar-alt"></i> Show All Terms
                            </a>
                        @endif
                    @endif
                    <a href="{{ route('chairperson.scheduling.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Schedule Defense
                    </a>
                </div>
            </div>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Group</th>
                                    <th>Defense Type</th>
                                    <th>Date & Time</th>
                                    <th>Room</th>
                                    <th>Panelists</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($defenseSchedules as $schedule)
                                    <tr>
                                        <td>
                                            <strong>{{ $schedule->group->name ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                {{ $schedule->group->members->count() ?? 0 }} members
                                                @if($schedule->group->adviser)
                                                    • Adviser: {{ $schedule->group->adviser->name }}
                                                @else
                                                    • No adviser assigned
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $schedule->defense_type == '60_percent' ? 'warning' : ($schedule->defense_type == '100_percent' ? 'danger' : 'info') }}">
                                                {{ $schedule->defense_type_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $schedule->scheduled_date->format('M d, Y') }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    {{ \Carbon\Carbon::parse($schedule->scheduled_time)->format('h:i A') }}
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $schedule->room }}</span>
                                        </td>
                                        <td>
                                            @foreach($schedule->panelists as $panelist)
                                                <div class="mb-1">
                                                    <small>
                                                        <strong>{{ ucfirst($panelist->pivot->role) }}:</strong>
                                                        {{ $panelist->name }}
                                                    </small>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>
                                            @if($schedule->status == 'scheduled')
                                                <span class="badge bg-primary">Scheduled</span>
                                            @elseif($schedule->status == 'completed')
                                                <span class="badge bg-success">Completed</span>
                                            @else
                                                <span class="badge bg-secondary">Cancelled</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('chairperson.scheduling.show', $schedule) }}" 
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="{{ route('chairperson.scheduling.edit', $schedule) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                @if($schedule->status == 'scheduled')
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle" 
                                                                data-bs-toggle="dropdown">
                                                            <i class="fas fa-check"></i> Status
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <form action="{{ route('chairperson.scheduling.update-status', $schedule) }}" 
                                                                      method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="status" value="completed">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-check text-success"></i> Mark Completed
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form action="{{ route('chairperson.scheduling.update-status', $schedule) }}" 
                                                                      method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="status" value="cancelled">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-times text-danger"></i> Cancel
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                @endif
                                                <form action="{{ route('chairperson.scheduling.destroy', $schedule) }}" 
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this defense schedule?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No defense schedules found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.btn-group .btn {
    margin-right: 2px;
}
.btn-group .btn:last-child {
    margin-right: 0;
}
</style>
@endsection
