@extends('layouts.coordinator')

@section('title', 'Proposal Review')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">
                    <i class="fas fa-file-alt me-2"></i>Proposal Review
                </h2>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(empty($proposalsByOffering))
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Proposals Found</h4>
                    <p class="text-muted">There are no proposals to review for your coordinated offerings.</p>
                </div>
            @else
                @foreach($proposalsByOffering as $offeringId => $data)
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-0">
                                        <i class="fas fa-book me-2"></i>
                                        {{ $data['offering']->subject_code }} - {{ $data['offering']->subject_title }}
                                    </h5>
                                    <small class="opacity-75">
                                        {{ $data['offering']->offer_code }} - {{ $data['total_groups'] }} groups
                                    </small>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="d-flex gap-3 justify-content-end">
                                        <span class="badge bg-warning">{{ $data['pending_count'] }} Pending</span>
                                        <span class="badge bg-success">{{ $data['approved_count'] }} Approved</span>
                                        <span class="badge bg-danger">{{ $data['rejected_count'] }} Rejected</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            @if($data['proposals']->isEmpty())
                                <div class="text-center py-3">
                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No proposals submitted for this offering yet.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Group</th>
                                                <th>Student</th>
                                                <th>Title</th>
                                                <th>Status</th>
                                                <th>Submitted</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($data['proposals'] as $proposal)
                                                @php
                                                    $student = $proposal->getStudentData();
                                                    $group = $student ? $student->groups()->first() : null;
                                                @endphp
                                                <tr>
                                                    <td>
                                                        @if($group)
                                                            <span class="badge bg-info">{{ $group->name }}</span>
                                                        @else
                                                            <span class="text-muted">No Group</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($student)
                                                            <strong>{{ $student->name }}</strong>
                                                            <br><small class="text-muted">{{ $student->student_id }}</small>
                                                        @else
                                                            <span class="text-muted">Unknown</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <strong>{{ $proposal->title ?? 'Untitled Proposal' }}</strong>
                                                        @if($proposal->description)
                                                            <br><small class="text-muted">{{ Str::limit($proposal->description, 50) }}</small>
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
                                                        <small>{{ $proposal->submitted_at ? $proposal->submitted_at->format('M d, Y H:i') : 'N/A' }}</small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <a href="{{ route('coordinator.proposals.show', $proposal->id) }}" 
                                                               class="btn btn-outline-primary" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="{{ route('coordinator.proposals.review', $proposal->id) }}" 
                                                               class="btn btn-outline-warning" title="Review">
                                                                <i class="fas fa-gavel"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>

@endsection
