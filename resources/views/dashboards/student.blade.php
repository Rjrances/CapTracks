@extends('layouts.student')
@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 1200px;">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">Welcome, {{ auth()->check() ? auth()->user()->name : 'Student' }}!</h1>
                        <p class="text-muted mb-0">Access your project, group, proposal, and milestones</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('student.group') }}" class="btn btn-primary">
                            <i class="fas fa-users me-2"></i>View Group
                        </a>
                        <a href="{{ route('student.milestones') }}" class="btn btn-outline-primary">
                            <i class="fas fa-flag me-2"></i>Milestones
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Current Academic Term Context
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($activeTerm ?? null)
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <h4 class="mb-0 me-3">{{ $activeTerm->full_name }}</h4>
                                        <span class="badge bg-success fs-6">Active</span>
                                    </div>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Current term for all academic operations and project work
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="text-muted small">Student View</span>
                                </div>
                            </div>
                        @else
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <h4 class="mb-0 me-3 text-warning">No Active Term</h4>
                                        <span class="badge bg-warning fs-6">Inactive</span>
                                    </div>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Please contact your coordinator about the current academic term
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="text-muted small">Student View</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-project-diagram fa-3x mb-3"></i>
                        <h5 class="card-title">Project Submissions</h5>
                        <p class="card-text">Upload and manage your project files</p>
                        <a href="{{ route('student.project') }}" class="btn btn-light">
                            <i class="fas fa-arrow-right me-2"></i>Access
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h5 class="card-title">My Group</h5>
                        <p class="card-text">View group details and members</p>
                        <a href="{{ route('student.group') }}" class="btn btn-light">
                            <i class="fas fa-arrow-right me-2"></i>Access
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                        <h5 class="card-title">Proposal & Endorsement</h5>
                        <p class="card-text">Submit and track your project proposal</p>
                        <a href="{{ route('student.proposal') }}" class="btn btn-light">
                            <i class="fas fa-arrow-right me-2"></i>Access
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-flag fa-3x mb-3"></i>
                        <h5 class="card-title">Milestones</h5>
                        <p class="card-text">Track your project progress</p>
                        <a href="{{ route('student.milestones') }}" class="btn btn-light">
                            <i class="fas fa-arrow-right me-2"></i>Access
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Project Status Overview
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center py-4">
                            <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">Project Status</h6>
                            <p class="text-muted small">Your project status and progress will appear here.</p>
                            <a href="{{ route('student.project') }}" class="btn btn-primary">
                                <i class="fas fa-eye me-1"></i> View Project Status
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('student.project') }}" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Upload Project File
                            </a>
                            <a href="{{ route('student.group') }}" class="btn btn-outline-primary">
                                <i class="fas fa-users me-2"></i>View Group Details
                            </a>
                            <a href="{{ route('student.milestones') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-flag me-2"></i>Check Milestones
                            </a>
                            <a href="{{ route('student.proposal') }}" class="btn btn-outline-success">
                                <i class="fas fa-file-alt me-2"></i>Submit Proposal
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Recent Activity
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center py-3">
                            <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                            <p class="text-muted small mb-0">No recent activity</p>
                            <small class="text-muted">Your activities will appear here</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar me-2"></i>Upcoming Deadlines
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center py-4">
                            <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No upcoming deadlines</h6>
                            <p class="text-muted small">Deadlines will appear here when they are set.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 
