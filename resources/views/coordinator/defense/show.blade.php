@extends('layouts.coordinator')

@section('title', 'Defense Schedule Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-eye me-2"></i>Defense Schedule Details
                    </h2>
                    <p class="text-muted mb-0">View complete information for {{ $defenseSchedule->group->name }}'s defense</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('coordinator.defense.edit', $defenseSchedule->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit Schedule
                    </a>
                    <a href="{{ route('coordinator.defense.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Schedules
                    </a>
                </div>
            </div>

            <!-- Schedule Overview Card -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>Schedule Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-muted">Group</label>
                                    <div class="h6 mb-0">{{ $defenseSchedule->group->name }}</div>
                                    <small class="text-muted">{{ $defenseSchedule->group->description ?? 'No description' }}</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-muted">Defense Stage</label>
                                    <div>
                                        <span class="badge bg-{{ $defenseSchedule->stage == 'proposal' ? 'info' : ($defenseSchedule->stage == 60 ? 'warning' : 'danger') }} fs-6">
                                            {{ $defenseSchedule->stage_label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-muted">Date & Time</label>
                                    <div class="h6 mb-0">{{ $defenseSchedule->start_at->format('l, F d, Y') }}</div>
                                    <div class="text-muted">
                                        {{ $defenseSchedule->start_at->format('h:i A') }} - {{ $defenseSchedule->end_at->format('h:i A') }}
                                    </div>
                                    <small class="text-muted">Duration: {{ $defenseSchedule->formatted_duration }}</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-muted">Room</label>
                                    <div class="h6 mb-0">{{ $defenseSchedule->room }}</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-muted">Academic Term</label>
                                    <div class="h6 mb-0">{{ $defenseSchedule->academicTerm->school_year }} - {{ $defenseSchedule->academicTerm->semester }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-muted">Status</label>
                                    <div>
                                        <span class="badge bg-success fs-6">{{ ucfirst($defenseSchedule->status) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('coordinator.defense.edit', $defenseSchedule->id) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>Edit Schedule
                                </a>
                                <form action="{{ route('coordinator.defense.destroy', $defenseSchedule->id) }}" method="POST" class="d-grid">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" 
                                            onclick="return confirm('Are you sure you want to delete this defense schedule? This action cannot be undone.')">
                                        <i class="fas fa-trash me-2"></i>Delete Schedule
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Group Information Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-users me-2"></i>Group Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-muted">Group Name</label>
                            <div class="h6 mb-0">{{ $defenseSchedule->group->name }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-muted">Offering</label>
                            <div class="h6 mb-0">
                                @if($defenseSchedule->group->offering)
                                    {{ $defenseSchedule->group->offering->subject_code }} - {{ $defenseSchedule->group->offering->subject_title }}
                                @else
                                    <span class="text-muted">No offering assigned</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-muted">Adviser</label>
                            <div class="h6 mb-0">
                                @if($defenseSchedule->group->adviser)
                                    {{ $defenseSchedule->group->adviser->name }}
                                    <br>
                                    <small class="text-muted">{{ $defenseSchedule->group->adviser->email }}</small>
                                @else
                                    <span class="text-warning">No adviser assigned</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-muted">Group Members</label>
                            <div>
                                @if($defenseSchedule->group->members->count() > 0)
                                    @foreach($defenseSchedule->group->members as $member)
                                        <div class="mb-1">
                                            <span class="badge bg-info">{{ $member->name }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-muted">No members assigned</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel Members Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-user-tie me-2"></i>Panel Members
                    </h6>
                </div>
                <div class="card-body">
                    @if($defenseSchedule->defensePanels->count() > 0)
                        <div class="row">
                            <!-- Automatically included members -->
                            @if($defenseSchedule->group->adviser || ($defenseSchedule->group->offering && $defenseSchedule->group->offering->teacher))
                                <div class="col-md-6 mb-3">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white">
                                            <strong><i class="fas fa-check-circle me-2"></i>Automatically Included</strong>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled mb-0">
                                                @if($defenseSchedule->group->adviser)
                                                    <li class="mb-2">
                                                        <i class="fas fa-user-tie text-success me-2"></i>
                                                        <strong>{{ $defenseSchedule->group->adviser->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $defenseSchedule->group->adviser->email }}</small>
                                                        <span class="badge bg-success ms-2">Adviser</span>
                                                    </li>
                                                @endif
                                                @if($defenseSchedule->group->offering && $defenseSchedule->group->offering->teacher)
                                                    <li class="mb-2">
                                                        <i class="fas fa-chalkboard-teacher text-success me-2"></i>
                                                        <strong>{{ $defenseSchedule->group->offering->teacher->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $defenseSchedule->group->offering->teacher->email }}</small>
                                                        <span class="badge bg-success ms-2">Coordinator</span>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Manually added panel members -->
                            @php
                                $manualMembers = $defenseSchedule->defensePanels->filter(function($panel) use ($defenseSchedule) {
                                    return $panel->faculty_id != $defenseSchedule->group->adviser_id && 
                                           (!($defenseSchedule->group->offering && $defenseSchedule->group->offering->teacher_id == $panel->faculty_id));
                                });
                            @endphp
                            
                            @if($manualMembers->count() > 0)
                                <div class="col-md-6 mb-3">
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <strong><i class="fas fa-user-plus me-2"></i>Additional Members</strong>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled mb-0">
                                                @foreach($manualMembers as $panel)
                                                    <li class="mb-2">
                                                        <i class="fas fa-user text-primary me-2"></i>
                                                        <strong>{{ $panel->faculty->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $panel->faculty->email }}</small>
                                                        <span class="badge bg-primary ms-2">{{ ucfirst($panel->role) }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-user-slash fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No panel members assigned</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Notes Section (if any) -->
            @if($defenseSchedule->coordinator_notes)
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-sticky-note me-2"></i>Coordinator Notes
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $defenseSchedule->coordinator_notes }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.badge {
    font-size: 0.875em;
}

.form-label {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.h6 {
    font-size: 1.1rem;
    font-weight: 600;
}
</style>
@endsection
