@extends('layouts.adviser')

@section('title', 'Proposal Review')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Proposal Review</h2>
            <p class="text-muted mb-0">Review and provide feedback on student project proposals</p>
        </div>
        <div>
            <a href="{{ route('adviser.dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Proposals</h5>
                    <h3 class="mb-0" id="total-proposals">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Pending Review</h5>
                    <h3 class="mb-0" id="pending-review">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Approved</h5>
                    <h3 class="mb-0" id="approved">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Rejected</h5>
                    <h3 class="mb-0" id="rejected">0</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Proposals by Group -->
    @if(count($proposalsByGroup) > 0)
        @foreach($proposalsByGroup as $groupId => $groupData)
            @php
                $group = $groupData['group'];
                $proposals = $groupData['proposals'];
                $pendingCount = $groupData['pending_count'];
                $approvedCount = $groupData['approved_count'];
                $rejectedCount = $groupData['rejected_count'];
            @endphp
            
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>{{ $group->name }}
                            </h5>
                            <small class="text-muted">{{ $group->members->count() }} members</small>
                            @if($pendingCount > 0)
                                <span class="badge bg-warning ms-2">{{ $pendingCount }} pending review</span>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('adviser.groups.details', $group) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye"></i> Group Details
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Proposal Title</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($proposals as $proposal)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $proposal->student->name ?? 'Unknown' }}</div>
                                            <small class="text-muted">{{ $proposal->student->email ?? '' }}</small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $proposal->title ?? 'Project Proposal' }}</div>
                                            @if($proposal->objectives)
                                                <small class="text-muted">{{ Str::limit($proposal->objectives, 100) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($proposal->status)
                                                @case('pending')
                                                    <span class="badge bg-warning">Pending Review</span>
                                                    @break
                                                @case('approved')
                                                    <span class="badge bg-success">Approved</span>
                                                    @break
                                                @case('rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ ucfirst($proposal->status) }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            <small>{{ $proposal->submitted_at ? \Carbon\Carbon::parse($proposal->submitted_at)->format('M d, Y H:i') : 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ asset('storage/' . $proposal->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <a href="{{ route('adviser.proposal.show', $proposal->id) }}" class="btn btn-sm btn-outline-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($proposal->status === 'pending')
                                                    <a href="{{ route('adviser.proposal.edit', $proposal->id) }}" class="btn btn-sm btn-outline-warning" title="Review & Provide Feedback">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Proposals to Review</h5>
                <p class="text-muted">Your assigned groups haven't submitted any proposals yet.</p>
                <a href="{{ route('adviser.dashboard') }}" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                </a>
            </div>
        </div>
    @endif
</div>

<script>
// Load proposal statistics
document.addEventListener('DOMContentLoaded', function() {
    fetch('{{ route("adviser.proposal.stats") }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-proposals').textContent = data.total_proposals;
            document.getElementById('pending-review').textContent = data.pending_review;
            document.getElementById('approved').textContent = data.approved;
            document.getElementById('rejected').textContent = data.rejected;
        })
        .catch(error => console.error('Error loading stats:', error));
});
</script>
@endsection
