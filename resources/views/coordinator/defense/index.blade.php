@extends('layouts.coordinator')
@section('title', 'Defense Schedules')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-gavel me-2"></i>Defense Schedules
                    </h2>
                    <p class="text-muted mb-0">Manage 60% and 100% defense schedules for capstone projects</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('coordinator.defense.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Schedule
                    </a>
                </div>
            </div>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Filter Defense Schedules
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('coordinator.defense.index') }}" id="filterForm">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="offering" class="form-label">Offering:</label>
                                <select name="offering" id="offering" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Offerings</option>
                                    @foreach($offerings as $offering)
                                        <option value="{{ $offering->id }}" {{ request('offering') == $offering->id ? 'selected' : '' }}>
                                            {{ $offering->subject_code }} - {{ $offering->subject_title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <a href="{{ route('coordinator.defense.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear Filters
                                </a>
                            </div>
                        </div>
                    </form>
                    @if($activeTerm)
                        <div class="mt-3">
                            <span class="badge bg-info">
                                <i class="fas fa-calendar me-1"></i>
                                Showing defense schedules for: {{ $activeTerm->full_name }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
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
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Group</th>
                                        <th>Stage</th>
                                        <th>Room</th>
                                        <th>Panel Members</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($defenseSchedules as $schedule)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">
                                                    {{ $schedule->start_at->format('M d, Y') }}
                                                </div>
                                                <small class="text-muted">
                                                    {{ $schedule->start_at->format('h:i A') }} - {{ $schedule->end_at->format('h:i A') }}
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    Duration: {{ $schedule->formatted_duration }}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $schedule->group->name ?? 'N/A' }}</div>
                                                <small class="text-muted">
                                                    {{ $schedule->group->description ?? 'No description' }}
                                                </small>
                                                @if($schedule->group->offering)
                                                    <br>
                                                    <span class="badge bg-info">{{ $schedule->group->offering->subject_code }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $schedule->stage == 'proposal' ? 'info' : ($schedule->stage == 60 ? 'warning' : 'danger') }}">
                                                    {{ $schedule->stage_label }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $schedule->room }}</span>
                                            </td>
                                            <td>
                                                @if($schedule->defensePanels->count() > 0)
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach($schedule->defensePanels as $panel)
                                                            <span class="badge bg-primary">
                                                                {{ $panel->faculty->name }} ({{ $panel->role_label }})
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-warning">No panel assigned</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-success">{{ ucfirst($schedule->status) }}</span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('coordinator.defense.show', $schedule->id) }}" 
                                                       class="btn btn-outline-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('coordinator.defense.edit', $schedule->id) }}" 
                                                       class="btn btn-outline-primary" title="Edit Schedule">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('coordinator.defense.destroy', $schedule->id) }}" 
                                                          method="POST" class="d-inline" 
                                                          onsubmit="return confirm('Are you sure you want to delete this defense schedule?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger" title="Delete Schedule">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-4">
                            {{ $defenseSchedules->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Defense Schedules Found</h5>
                            <p class="text-muted">
                                @if(request('academic_term_id') || request('offering'))
                                    Try adjusting your filter criteria
                                @else
                                    You don't have any defense schedules yet, or there are no groups assigned to your offerings.
                                @endif
                            </p>
                            @if(!request('academic_term_id') && !request('offering'))
                                <div class="mt-3">
                                    <a href="{{ route('coordinator.defense.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Create First Schedule
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('academic_term_id').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
    document.getElementById('offering').addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});
</script>
@endsection
