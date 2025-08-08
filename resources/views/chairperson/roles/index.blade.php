@extends('layouts.chairperson')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">System Roles</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        @foreach($roles as $roleKey => $role)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user-tag me-2"></i>{{ $role['name'] }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">{{ $role['description'] }}</p>
                        
                        <div class="mb-3">
                            <h6 class="fw-semibold text-primary">
                                <i class="fas fa-users me-1"></i>Users with this role ({{ $role['user_count'] }}):
                            </h6>
                            @if($role['users']->count() > 0)
                                <div class="list-group list-group-flush">
                                    @foreach($role['users'] as $user)
                                        <div class="list-group-item d-flex justify-content-between align-items-start p-2">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-semibold">{{ $user->name }}</div>
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope me-1"></i>{{ $user->email }}
                                                </small>
                                                @if($user->school_id)
                                                    <br><small class="text-muted">
                                                        <i class="fas fa-id-card me-1"></i>{{ $user->school_id }}
                                                    </small>
                                                @endif
                                                @if($user->department || $user->position)
                                                    <br><small class="text-muted">
                                                        <i class="fas fa-building me-1"></i>
                                                        {{ $user->department ?? 'N/A' }}
                                                        @if($user->position)
                                                            • {{ $user->position }}
                                                        @endif
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-user-slash fa-2x mb-2"></i>
                                    <p class="mb-0">No users assigned</p>
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <h6 class="fw-semibold text-success">
                                <i class="fas fa-key me-1"></i>Permissions:
                            </h6>
                            <ul class="list-unstyled mb-0">
                                @foreach($role['permissions'] as $permission)
                                    <li class="mb-1">
                                        <i class="fas fa-check text-success me-2"></i>
                                        <small>{{ $permission }}</small>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Role ID: <code>{{ $roleKey }}</code>
                        </small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Role Summary -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-chart-pie me-2"></i>Role Distribution Summary
                    </h5>
                    <div class="row text-center">
                        @foreach($roles as $roleKey => $role)
                            <div class="col-md-2 col-sm-4 col-6 mb-3">
                                <div class="border rounded p-3 bg-white">
                                    <h4 class="text-primary mb-1">{{ $role['user_count'] }}</h4>
                                    <small class="text-muted">{{ $role['name'] }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
