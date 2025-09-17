@extends('layouts.chairperson')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Schedule Details</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Group Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Group Name:</strong></td>
                                    <td>{{ $defenseSchedule->group->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Members:</strong></td>
                                    <td>{{ $defenseSchedule->group->members->count() ?? 0 }} students</td>
                                </tr>
                                <tr>
                                    <td><strong>Adviser:</strong></td>
                                    <td>
                                        @if($defenseSchedule->group->adviser)
                                            <span class="badge bg-info">{{ $defenseSchedule->group->adviser->name }}</span>
                                        @else
                                            <span class="text-muted">No adviser assigned</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Defense Type:</strong></td>
                                    <td>
                                        <span class="badge bg-info">{{ $defenseSchedule->defense_type_label }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Schedule Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Date:</strong></td>
                                    <td>{{ $defenseSchedule->scheduled_date->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Time:</strong></td>
                                    <td>{{ \Carbon\Carbon::parse($defenseSchedule->scheduled_time)->format('h:i A') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Room:</strong></td>
                                    <td><span class="badge bg-info">{{ $defenseSchedule->room }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Defense Type:</strong></td>
                                    <td>
                                        <span class="badge bg-info">{{ $defenseSchedule->defense_type_label }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        @if($defenseSchedule->status == 'scheduled')
                                            <span class="badge bg-primary">Scheduled</span>
                                        @elseif($defenseSchedule->status == 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-secondary">Cancelled</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    @if($defenseSchedule->coordinator_notes)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h5>Coordinator Notes</h5>
                                <div class="alert alert-light">
                                    {{ $defenseSchedule->coordinator_notes }}
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="mt-4">
                        <a href="{{ route('chairperson.scheduling.edit', $defenseSchedule) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Schedule
                        </a>
                        <a href="{{ route('chairperson.scheduling.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Panel Members
                        <span class="badge bg-primary ms-2">{{ $defenseSchedule->panelists->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($defenseSchedule->panelists->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($defenseSchedule->panelists as $panelist)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>{{ $panelist->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ ucfirst($panelist->pivot->role) }}</small>
                                            <br>
                                            <small class="text-muted">{{ ucfirst($panelist->role) }}</small>
                                        </div>
                                        <span class="badge bg-{{ $panelist->pivot->role == 'chair' ? 'danger' : ($panelist->pivot->role == 'adviser' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($panelist->pivot->role) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center">No panelists assigned.</p>
                    @endif
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-graduate me-2"></i>Group Members
                        <span class="badge bg-success ms-2">{{ $defenseSchedule->group->members->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($defenseSchedule->group->members->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($defenseSchedule->group->members as $member)
                                <div class="list-group-item">
                                    <div>
                                        <strong>{{ $member->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $member->student_id }} - {{ $member->course }}</small>
                                        @if($member->pivot->role)
                                            <br>
                                            <small class="text-muted">Role: {{ ucfirst($member->pivot->role) }}</small>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center">No members in this group.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
