@extends('layouts.adviser')
@section('title', 'Project Submissions')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0">Review and provide feedback on student submissions</p>
        </div>
        <a href="{{ route('adviser.groups') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to All My Groups
        </a>
    </div>
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            @if(session('panel_rating_sheet_url'))
                <div class="mt-2">
                    <a href="{{ session('panel_rating_sheet_url') }}" class="btn btn-sm btn-success">
                        <i class="fas fa-clipboard-check me-1"></i>
                        Go to Rating Sheet for {{ session('panel_group_name', 'this group') }}
                    </a>
                </div>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Groups</h5>
                    <h3 class="mb-0">{{ $allGroups->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Adviser Groups</h5>
                    <h3 class="mb-0">{{ $adviserGroups->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Pending Review</h5>
                    <h3 class="mb-0">{{ $submissions->where('status', 'pending')->count() }}</h3>
                    <small>Requires your attention</small>
                </div>
            </div>
        </div>
    </div>
    @if($allGroups->count() > 0)
        @foreach($allGroups as $group)
            @php
                $groupData = $submissionsByGroup[$group->id] ?? null;
                $groupSubmissions = $groupData['submissions'] ?? collect();
                $userRole = $groupData['user_role'] ?? 'adviser';
                $pendingCount = $groupSubmissions->where('status', 'pending')->count();
                $schedule = $group->defense_schedule ?? null;
                $panels = collect($schedule?->defensePanels ?? []);
                $missingRoles = [];
                if ($userRole === 'panel' && $schedule) {
                    if ($panels->where('role', 'chair')->where('status', 'accepted')->isEmpty()) {
                        $missingRoles[] = 'Chair';
                    }
                    if ($panels->where('role', 'member')->where('status', 'accepted')->isEmpty()) {
                        $missingRoles[] = 'Member';
                    }
                }
                $ratingBlockedMessage = empty($missingRoles)
                    ? null
                    : 'Ratings open only after confirmation from: ' . implode(' and ', $missingRoles) . '.';
            @endphp
            <div class="card mb-4">
                <div class="card-header {{ $userRole === 'adviser' ? 'bg-success' : 'bg-info' }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas {{ $userRole === 'adviser' ? 'fa-user-tie' : 'fa-gavel' }} me-2"></i>
                                {{ $group->name }}
                                <span class="badge bg-light text-dark ms-2">
                                    {{ $userRole === 'adviser' ? 'Adviser' : 'Panel Member' }}
                                </span>
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
                            @if($userRole === 'panel' && $schedule)
                                <a href="{{ $ratingBlockedMessage ? '#' : route('adviser.rating-sheets.show', $schedule) }}"
                                   class="btn btn-warning btn-sm js-rating-sheet-link"
                                   data-rating-blocked="{{ $ratingBlockedMessage ? '1' : '0' }}"
                                   data-rating-message="{{ $ratingBlockedMessage }}"
                                   data-rating-group="{{ $group->name }}">
                                    <i class="fas fa-clipboard-check"></i> Rating Sheet
                                </a>
                            @endif
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
                                            <td>
                                                <span class="badge bg-secondary text-capitalize">{{ $submission->type }}</span>
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
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No groups assigned yet</h5>
                <p class="text-muted">You don't have any groups assigned as adviser yet. Groups will appear here once you accept adviser invitations.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="{{ route('adviser.invitations') }}" class="btn btn-primary">
                        <i class="fas fa-envelope me-2"></i>Check Adviser Invitations
                    </a>
                    <a href="{{ route('adviser.panel-invitations') }}" class="btn btn-info">
                        <i class="fas fa-gavel me-2"></i>Check Panel Invitations
                    </a>
                    <a href="{{ route('adviser.groups') }}" class="btn btn-outline-primary">
                        <i class="fas fa-layer-group me-2"></i>View All Groups
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>

<div class="modal fade" id="ratingBlockedModal" tabindex="-1" aria-labelledby="ratingBlockedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ratingBlockedModalLabel">Cannot Open Rating Sheet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="ratingBlockedMessageText"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('ratingBlockedModal');
    const modalMessage = document.getElementById('ratingBlockedMessageText');
    const blockedModal = modalElement ? new bootstrap.Modal(modalElement) : null;

    function showBlockedModal(message, groupName) {
        if (!blockedModal || !modalMessage) return;
        const safeMessage = (message || 'Ratings are not available yet.').trim();
        const prefix = groupName ? `Group: ${groupName}\n\n` : '';
        modalMessage.textContent = `${prefix}${safeMessage}`;
        blockedModal.show();
    }

    document.querySelectorAll('.js-rating-sheet-link').forEach((link) => {
        link.addEventListener('click', function (event) {
            if (this.dataset.ratingBlocked !== '1') return;
            event.preventDefault();
            showBlockedModal(this.dataset.ratingMessage, this.dataset.ratingGroup);
        });
    });

    @if(session('rating_sheet_blocked_message'))
        showBlockedModal(@json(session('rating_sheet_blocked_message')), @json(session('rating_sheet_blocked_group')));
    @endif
});
</script>
@endsection 
