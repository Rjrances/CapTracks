@extends('layouts.adviser')
@section('title', 'Calendar')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Calendar</h2>
            <p class="text-muted mb-0">View your schedule and important dates</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar me-2"></i>Academic Calendar
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Calendar View</h5>
                        <p class="text-muted">Calendar functionality will be implemented here.</p>
                        <div class="mt-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <i class="fas fa-graduation-cap fa-2x text-primary mb-2"></i>
                                            <h6>Defense Schedules</h6>
                                            <p class="text-muted small">View upcoming defense schedules</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <i class="fas fa-tasks fa-2x text-success mb-2"></i>
                                            <h6>Milestone Deadlines</h6>
                                            <p class="text-muted small">Track project milestone deadlines</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <i class="fas fa-users fa-2x text-info mb-2"></i>
                                            <h6>Group Meetings</h6>
                                            <p class="text-muted small">Schedule and manage group meetings</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
