@extends('layouts.adviser')
@section('title', 'Adviser Groups')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0">Your assigned adviser groups with submissions and mentoring context</p>
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
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $allSubmissions = $groups->getCollection()->flatMap(function ($group) {
            return $group->members->flatMap(function ($member) {
                return $member->submissions ?? collect();
            });
        });
        $pendingSubmissionCount = $allSubmissions->where('status', 'pending')->count();
    @endphp

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Adviser Groups</h5>
                    <h3 class="mb-0">{{ $groups->total() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Submissions</h5>
                    <h3 class="mb-0">{{ $allSubmissions->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Pending Review</h5>
                    <h3 class="mb-0">{{ $pendingSubmissionCount }}</h3>
                </div>
            </div>
        </div>
    </div>

    @if($groups->count() > 0)
        @foreach($groups as $group)
            @php
                $groupSubmissions = $group->members->flatMap(function ($member) {
                    return $member->submissions ?? collect();
                })->sortByDesc('submitted_at');
                $pendingCount = $groupSubmissions->where('status', 'pending')->count();
                $latestGroupSubmission = $groupSubmissions->first();
                $latestGroupProposal = $groupSubmissions
                    ->where('type', 'proposal')
                    ->sortByDesc('submitted_at')
                    ->first();
            @endphp
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-user-tie me-2"></i>
                                {{ $group->name }}
                                <span class="badge bg-light text-dark ms-2">Adviser</span>
                            </h5>
                            <small class="text-white-50">{{ $group->members->count() }} members</small>
                            @if($pendingCount > 0)
                                <span class="badge bg-warning ms-2">{{ $pendingCount }} pending review</span>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('adviser.groups.details', $group) }}" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-eye"></i> Group Details
                            </a>
                            <a href="{{ $latestGroupSubmission ? route('adviser.project.show', $latestGroupSubmission->id) : route('adviser.project.index', ['group' => $group->id]) }}" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-file-alt"></i> Review Projects
                            </a>
                            <a href="{{ $latestGroupProposal ? route('adviser.proposal.show', $latestGroupProposal->id) : route('adviser.proposal.index', ['group' => $group->id]) }}" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-clipboard-check"></i> Review Proposal
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($groupSubmissions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Type</th>
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
                                            <td><span class="badge bg-secondary text-capitalize">{{ $submission->type }}</span></td>
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
                                            <td><small>{{ $submission->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->format('M d, Y H:i') : 'N/A' }}</small></td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <a href="{{ route('adviser.project.show', $submission->id) }}" class="btn btn-sm btn-outline-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('adviser.project.edit', $submission->id) }}" class="btn btn-sm btn-outline-warning" title="Review & Edit">
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

        @if($groups->hasPages())
            <div class="d-flex justify-content-center">
                {{ $groups->links() }}
            </div>
        @endif
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No adviser groups assigned yet</h5>
                <p class="text-muted">You don't have any groups assigned as adviser yet.</p>
                <a href="{{ route('adviser.invitations') }}" class="btn btn-primary">
                    <i class="fas fa-envelope me-2"></i>Check Adviser Invitations
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
