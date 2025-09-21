@extends('layouts.adviser')
@section('title', 'Proposal Review')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Proposal Review</h2>
            <p class="text-muted mb-0">Review and manage student proposals</p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clipboard-check me-2"></i>Proposal Submissions
                    </h5>
                </div>
                <div class="card-body">
                    @if(empty($proposalsByGroup))
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Proposals Found</h5>
                            <p class="text-muted">No proposal submissions from your groups yet.</p>
                        </div>
                    @else
                        @foreach($proposalsByGroup as $groupData)
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">
                                        <i class="fas fa-users me-2"></i>{{ $groupData['group']->name }}
                                    </h6>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-warning">{{ $groupData['pending_count'] }} Pending</span>
                                        <span class="badge bg-success">{{ $groupData['approved_count'] }} Approved</span>
                                        <span class="badge bg-danger">{{ $groupData['rejected_count'] }} Rejected</span>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    @foreach($groupData['proposals'] as $proposal)
                                        <div class="col-md-6 mb-3">
                                            <div class="card border">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title mb-0">{{ $proposal->title }}</h6>
                                                        <span class="badge bg-{{ $proposal->status === 'pending' ? 'warning' : ($proposal->status === 'approved' ? 'success' : 'danger') }}">
                                                            {{ ucfirst($proposal->status) }}
                                                        </span>
                                                    </div>
                                                    <p class="text-muted small mb-2">
                                                        <i class="fas fa-user me-1"></i>{{ $proposal->student->name }}
                                                    </p>
                                                    <p class="text-muted small mb-2">
                                                        <i class="fas fa-clock me-1"></i>{{ $proposal->submitted_at->format('M d, Y H:i') }}
                                                    </p>
                                                    @if($proposal->objectives)
                                                        <p class="small text-muted mb-2">
                                                            <strong>Objectives:</strong> {{ Str::limit($proposal->objectives, 100) }}
                                                        </p>
                                                    @endif
                                                    <div class="d-flex gap-2">
                                                        <a href="{{ route('adviser.proposal.show', $proposal->id) }}" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye me-1"></i>View
                                                        </a>
                                                        @if($proposal->status === 'pending')
                                                            <a href="{{ route('adviser.proposal.edit', $proposal->id) }}" 
                                                               class="btn btn-sm btn-outline-warning">
                                                                <i class="fas fa-edit me-1"></i>Review
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
