@extends('layouts.adviser')

@section('title', 'My Groups')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div>
                <h1 class="h2 mb-1 text-dark fw-bold">
                    <i class="fas fa-users me-3 text-primary"></i>
                    My Groups
                </h1>
                <p class="text-muted mb-0">Manage your assigned student groups and monitor their progress</p>
            </div>
        </div>
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
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white border-0">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-users me-2"></i>
                            <h5 class="mb-0 fw-bold">Assigned Groups</h5>
                            <span class="badge bg-light text-success ms-auto">{{ $groups->count() }} group(s)</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($groups->count() > 0)
                            @foreach($groups as $group)
                                <div class="group-item p-4 {{ !$loop->last ? 'border-bottom' : '' }}">
                                    <div class="row align-items-center">
                                        <!-- Group Icon -->
                                        <div class="col-auto">
                                            <div class="group-icon-wrapper">
                                                <span class="group-icon">
                                                    <i class="fas fa-users"></i>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Group Details -->
                                        <div class="col">
                                            <div class="group-content">
                                                <h6 class="fw-bold text-dark mb-2">
                                                    <i class="fas fa-layer-group me-2 text-success"></i>
                                                    {{ $group->name }}
                                                </h6>
                                                
                                                <div class="group-meta mb-3">
                                                    <span class="badge bg-info me-2">
                                                        <i class="fas fa-users me-1"></i>
                                                        {{ $group->members->count() }} members
                                                    </span>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        Created {{ $group->created_at->diffForHumans() }}
                                                    </span>
                                                </div>
                                                
                                                @if($group->description)
                                                    <div class="group-description mb-3">
                                                        <div class="alert alert-light border-start border-success border-3">
                                                            <i class="fas fa-info-circle me-2 text-success"></i>
                                                            <strong>Description:</strong> {{ Str::limit($group->description, 100) }}
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                                <!-- Action Buttons -->
                                                <div class="group-actions">
                                                    <a href="{{ route('adviser.groups.details', $group) }}" class="btn btn-success btn-sm px-3 me-2">
                                                        <i class="fas fa-eye me-1"></i> View Details
                                                    </a>
                                                    <a href="{{ route('adviser.project.index') }}" class="btn btn-outline-info btn-sm px-3">
                                                        <i class="fas fa-file-alt me-1"></i> View Projects
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                    <!-- Pagination -->
                    @if($groups->hasPages())
                        <div class="d-flex justify-content-center p-4 border-top">
                            {{ $groups->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <div class="empty-state">
                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No Groups Assigned</h5>
                            <p class="text-muted mb-0">You don't have any student groups assigned yet.</p>
                        </div>
                    </div>
                @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS for better styling -->
<style>
.group-icon-wrapper {
    display: flex;
    align-items: center;
    justify-content-center;
}

.group-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content-center;
    font-size: 1.2rem;
    color: white;
    background: linear-gradient(135deg, #28a745, #20c997);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    transition: all 0.3s ease;
}

.group-item {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.group-item:hover {
    background-color: #f8f9fa;
    border-left-color: #28a745;
    transform: translateX(5px);
}

.group-meta .badge {
    font-size: 0.8rem;
    padding: 0.5rem 0.75rem;
}

.group-description .alert {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border: none;
    border-radius: 8px;
}

.group-actions .btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.group-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.empty-state {
    padding: 2rem;
}

.empty-state i {
    opacity: 0.6;
}

.card {
    border-radius: 12px;
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
}

.group-item:last-child {
    border-bottom: none;
}

/* Success theme colors */
.bg-success {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
}

.btn-success {
    background: linear-gradient(135deg, #28a745, #20c997);
    border: none;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.btn-success:hover {
    background: linear-gradient(135deg, #218838, #1e7e34);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
}
</style>
@endsection 