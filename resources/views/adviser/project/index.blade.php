@extends('layouts.adviser')

@section('title', 'Project Management')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Project Management</h2>
            <p class="text-muted mb-0">Review and manage project submissions from your groups</p>
        </div>
        <a href="{{ route('adviser.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Project Overview Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Groups</h5>
                    <h3 class="mb-0">{{ $groups->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Submissions</h5>
                    <h3 class="mb-0">{{ $submissions->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Pending Review</h5>
                    <h3 class="mb-0">{{ $submissions->where('status', 'pending')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Approved</h5>
                    <h3 class="mb-0">{{ $submissions->where('status', 'approved')->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects by Group -->
    @if($groups->count() > 0)
        @foreach($groups as $group)
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>{{ $group->name }}
                            </h5>
                            <small class="text-muted">{{ $group->members->count() }} members</small>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('adviser.groups.details', $group) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye"></i> Group Details
                            </a>
                            <a href="{{ route('adviser.groups.tasks', $group) }}" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-tasks"></i> Tasks
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $groupSubmissions = $submissionsByGroup[$group->id]['submissions'] ?? collect();
                    @endphp
                    
                    @if($groupSubmissions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Type</th>
                                        <th>File</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($groupSubmissions as $submission)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $submission->student->name ?? 'Unknown' }}</div>
                                                <small class="text-muted">{{ $submission->student->email ?? '' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary text-capitalize">{{ $submission->type }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download me-1"></i>Download
                                                </a>
                                            </td>
                                            <td>
                                                @if($submission->status === 'pending')
                                                    <span class="badge bg-warning">Pending Review</span>
                                                @elseif($submission->status === 'approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @elseif($submission->status === 'rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($submission->status) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $submission->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->format('M d, Y H:i') : 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('adviser.project.show', $submission->id) }}" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('adviser.project.edit', $submission->id) }}" class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No submissions from this group yet</h6>
                            <p class="text-muted small">Submissions will appear here when students upload documents.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No groups assigned yet</h5>
                <p class="text-muted">You don't have any groups assigned to you yet. Groups will appear here when you accept adviser invitations.</p>
                <a href="{{ route('adviser.invitations') }}" class="btn btn-primary">
                    <i class="fas fa-envelope me-2"></i>Check Invitations
                </a>
            </div>
        </div>
    @endif

    <!-- Recent Submissions -->
    @if($submissions->count() > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>Recent Submissions
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Group</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissions->take(5) as $submission)
                                <tr>
                                    <td>{{ $submission->student->name ?? 'Unknown' }}</td>
                                    <td>
                                        @php
                                            $group = $groups->first(function($g) use ($submission) {
                                                return $g->members->contains('id', $submission->student_id);
                                            });
                                        @endphp
                                        {{ $group ? $group->name : 'Unknown Group' }}
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary text-capitalize">{{ $submission->type }}</span>
                                    </td>
                                    <td>
                                        @if($submission->status === 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($submission->status === 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @elseif($submission->status === 'rejected')
                                            <span class="badge bg-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $submission->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->diffForHumans() : 'N/A' }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('adviser.project.show', $submission->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Review
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection 