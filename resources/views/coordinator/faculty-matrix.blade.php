@extends('layouts.coordinator')
@section('title', 'Faculty Matrix')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">
                <i class="fas fa-table me-2"></i>Faculty Matrix
            </h2>
            <p class="text-muted mb-0">Role visibility across your coordinated groups and defense panels</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Offerings</h5>
                    <h3 class="mb-0">{{ $summary['total_offerings'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Groups</h5>
                    <h3 class="mb-0">{{ $summary['total_groups'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">With Adviser</h5>
                    <h3 class="mb-0">{{ $summary['groups_with_adviser'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">With Schedule</h5>
                    <h3 class="mb-0">{{ $summary['groups_with_schedule'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-list-alt me-2"></i>Group-Faculty Matrix
            </h6>
        </div>
        <div class="card-body">
            @if($matrixRows->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Group</th>
                                <th>Offering</th>
                                <th>Coordinator</th>
                                <th>Adviser</th>
                                <th>Panel Chair</th>
                                <th>Panel Members</th>
                                <th>Stage</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($matrixRows as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['group_name'] }}</td>
                                    <td>{{ $row['offering_label'] }}</td>
                                    <td>{{ $row['coordinator_name'] }}</td>
                                    <td>{{ $row['adviser_name'] }}</td>
                                    <td>
                                        @if($row['panel_chairs']->isNotEmpty())
                                            {{ $row['panel_chairs']->implode(', ') }}
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row['panel_members']->isNotEmpty())
                                            {{ $row['panel_members']->implode(', ') }}
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>{{ $row['schedule_stage'] }}</td>
                                    <td>
                                        @php
                                            $statusClass = match(strtolower($row['schedule_status'])) {
                                                'scheduled' => 'primary',
                                                'in progress' => 'warning',
                                                'completed' => 'success',
                                                'cancelled' => 'danger',
                                                'not scheduled' => 'secondary',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">{{ $row['schedule_status'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-table fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Matrix Data Available</h5>
                    <p class="text-muted mb-0">No groups are currently linked to your coordinated offerings.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
