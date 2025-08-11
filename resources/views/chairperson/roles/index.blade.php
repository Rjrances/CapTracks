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

    <!-- Role Assignment Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-user-edit me-2"></i>Assign Multiple Roles to Users
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('chairperson.roles.update', 0) }}" method="POST" id="roleUpdateForm">
                @csrf
                @method('POST')
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>School ID</th>
                                <th>Department</th>
                                <th>Current Roles</th>
                                <th>Assign Roles</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allUsers as $user)
                                <tr>
                                    <td>
                                        <strong>{{ $user->name }}</strong>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->school_id ?? 'N/A' }}</td>
                                    <td>{{ $user->department ?? 'N/A' }}</td>
                                    <td>
                                        @if(count($user->currentRoles) > 0)
                                            @foreach($user->currentRoles as $role)
                                                <span class="badge bg-primary me-1">{{ ucfirst($role) }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">No roles assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="roles[{{ $user->id }}][]" value="chairperson" 
                                                {{ in_array('chairperson', $user->currentRoles) ? 'checked' : '' }}>
                                            <label class="form-check-label">Chairperson</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="roles[{{ $user->id }}][]" value="coordinator" 
                                                {{ in_array('coordinator', $user->currentRoles) ? 'checked' : '' }}>
                                            <label class="form-check-label">Coordinator</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="roles[{{ $user->id }}][]" value="adviser" 
                                                {{ in_array('adviser', $user->currentRoles) ? 'checked' : '' }}>
                                            <label class="form-check-label">Adviser</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="roles[{{ $user->id }}][]" value="panelist" 
                                                {{ in_array('panelist', $user->currentRoles) ? 'checked' : '' }}>
                                            <label class="form-check-label">Panelist</label>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary update-user-roles" 
                                                data-user-id="{{ $user->id }}" 
                                                data-user-name="{{ $user->name }}">
                                            <i class="fas fa-save me-1"></i>Update
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <!-- Role Overview Section -->
    <div class="row">
        @foreach($roles as $roleKey => $role)
            @if($roleKey !== 'student')
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
            @endif
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
                            @if($roleKey !== 'student')
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="border rounded p-3 bg-white">
                                        <h4 class="text-primary mb-1">{{ $role['user_count'] }}</h4>
                                        <small class="text-muted">{{ $role['name'] }}</small>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle individual user role updates
    document.querySelectorAll('.update-user-roles').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const userName = this.dataset.userName;
            
            // Get the checked roles for this user
            const checkedRoles = [];
            const checkboxes = document.querySelectorAll(`input[name="roles[${userId}][]"]:checked`);
            checkboxes.forEach(checkbox => {
                checkedRoles.push(checkbox.value);
            });
            
            // Create form data
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'POST');
            checkedRoles.forEach(role => {
                formData.append('roles[]', role);
            });
            
            // Send AJAX request
            fetch(`/chairperson/roles/${userId}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert(`Roles updated successfully for ${userName}`);
                    // Reload the page to show updated roles
                    location.reload();
                } else {
                    alert('Error updating roles: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating roles. Please try again.');
            });
        });
    });
});
</script>
@endsection
