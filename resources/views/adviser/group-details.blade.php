@extends('layouts.adviser')
@section('title', 'Group Details')
@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 900px;">
        <div class="mb-4" style="margin-bottom: 1.2rem !important;">
            <h1 class="fw-bold mb-1 text-center" style="font-size:2.5rem; margin-bottom:0.1rem;">Group Details</h1>
            <div class="text-muted text-center" style="font-size:1.1rem; margin-bottom:0;">{{ $group->name }}</div>
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
        <div class="mb-4">
            <div class="fw-bold mb-2" style="font-size:1.2rem;">
                <i class="fas fa-info-circle me-2"></i>Group Information
            </div>
            <div class="bg-light rounded-3 p-3">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Group Name:</strong><br>
                            <span class="text-muted">{{ $group->name }}</span>
                        </div>
                        <div class="mb-3">
                            <strong>Description:</strong><br>
                            <span class="text-muted">{{ $group->description ?: 'No description provided' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Created:</strong><br>
                            <span class="text-muted">{{ $group->created_at->format('F j, Y') }}</span>
                        </div>
                        <div class="mb-3">
                            <strong>Total Members:</strong><br>
                            <span class="text-muted">{{ $group->members->count() }} members</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-4">
            <div class="fw-bold mb-2" style="font-size:1.2rem;">
                <i class="fas fa-users me-2"></i>Group Members
            </div>
            <div class="bg-light rounded-3 p-3">
                @if($group->members->count() > 0)
                    @foreach($group->members as $member)
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                            <div class="me-3 flex-shrink-0">
                                <span class="d-inline-flex align-items-center justify-content-center bg-secondary border rounded-circle" style="width:36px; height:36px;">
                                    <i class="fas fa-user text-white"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $member->name }}</div>
                                <div class="text-muted small">
                                    <i class="fas fa-id-card me-1"></i>
                                    {{ $member->student_id }}
                                </div>
                                <div class="text-muted small">
                                    <i class="fas fa-envelope me-1"></i>
                                    {{ $member->email }}
                                </div>
                                <div class="text-muted small">
                                    <i class="fas fa-graduation-cap me-1"></i>
                                    {{ $member->course }} • {{ $member->semester }}
                                </div>
                            </div>
                            <div>
                                <span class="badge bg-{{ $member->pivot->role === 'leader' ? 'primary' : 'secondary' }}">
                                    {{ ucfirst($member->pivot->role) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-muted text-center">
                        <i class="fas fa-users fa-2x mb-2"></i><br>
                        No members found
                    </div>
                @endif
            </div>
        </div>
        <div class="mb-4">
            <div class="fw-bold mb-2" style="font-size:1.2rem;">
                <i class="fas fa-file-alt me-2"></i>Project Submissions
            </div>
            <div class="bg-light rounded-3 p-3">
                <div class="text-muted text-center">
                    <i class="fas fa-file-alt fa-2x mb-2"></i><br>
                    Project submissions feature coming soon
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-center gap-2">
            <a href="{{ route('adviser.groups') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Groups
            </a>
            <a href="{{ route('adviser.dashboard') }}" class="btn btn-outline-primary">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
        </div>
    </div>
</div>
@endsection 
