@extends('layouts.adviser')

@section('title', 'My Groups')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 90vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm pt-3 px-5 pb-5 w-100" style="max-width: 900px;">
        <div class="mb-4" style="margin-bottom: 1.2rem !important;">
            <h1 class="fw-bold mb-1 text-center" style="font-size:2.5rem; margin-bottom:0.1rem;">My Groups</h1>
            <div class="text-muted text-center" style="font-size:1.1rem; margin-bottom:0;">Manage your assigned student groups</div>
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

        <!-- Groups List -->
        <div class="mb-4">
            <div class="fw-bold mb-2" style="font-size:1.2rem;">
                <i class="fas fa-users me-2"></i>Assigned Groups
            </div>
            <div class="bg-light rounded-3 p-3">
                @if($groups->count() > 0)
                    @foreach($groups as $group)
                        <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                            <div class="me-3 flex-shrink-0">
                                <span class="d-inline-flex align-items-center justify-content-center bg-primary border rounded-circle" style="width:36px; height:36px;">
                                    <i class="fas fa-users text-white"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $group->name }}</div>
                                <div class="text-muted small mb-2">
                                    <i class="fas fa-users me-1"></i>
                                    {{ $group->members->count() }} members
                                </div>
                                @if($group->description)
                                    <div class="text-muted small mb-2">
                                        {{ Str::limit($group->description, 100) }}
                                    </div>
                                @endif
                                <div class="d-flex gap-2">
                                    <a href="{{ route('adviser.groups.details', $group) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <a href="{{ route('adviser.project.index') }}" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-file-alt"></i> View Projects
                                    </a>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    Created {{ $group->created_at->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                    @endforeach

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $groups->links() }}
                    </div>
                @else
                    <div class="text-muted text-center">
                        <i class="fas fa-users fa-2x mb-2"></i><br>
                        No groups assigned yet
                    </div>
                @endif
            </div>
        </div>

        <!-- Back to Dashboard -->
        <div class="text-center">
            <a href="{{ route('adviser.dashboard') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection 