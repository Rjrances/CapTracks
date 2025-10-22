@extends('layouts.chairperson')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">
                <i class="fas fa-users-cog me-2"></i>Role Management
            </h2>
            @if($activeTerm)
                <p class="text-muted mb-0">
                    <i class="fas fa-calendar-alt me-1"></i>
                    Showing role assignments for: <strong>{{ $activeTerm->semester }}</strong>
                    <span class="badge bg-success ms-2">Active Term</span>
                </p>
            @else
                <p class="text-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    No active academic term set. Please set an active term to manage roles.
                </p>
            @endif
        </div>
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

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-user-edit me-2"></i>Assign Multiple Roles to Users
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'faculty_id', 'direction' => request('sort') == 'faculty_id' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                   class="text-decoration-none text-dark d-flex align-items-center">
                                    <i class="fas fa-id-card me-2"></i>ID Number
                                    @if(request('sort') == 'faculty_id')
                                        @if(request('direction') == 'asc')
                                            <i class="fas fa-sort-up ms-1"></i>
                                        @else
                                            <i class="fas fa-sort-down ms-1"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="border-0">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => request('sort') == 'name' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                   class="text-decoration-none text-dark d-flex align-items-center">
                                    <i class="fas fa-user me-2"></i>User
                                    @if(request('sort') == 'name')
                                        @if(request('direction') == 'asc')
                                            <i class="fas fa-sort-up ms-1"></i>
                                        @else
                                            <i class="fas fa-sort-down ms-1"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="border-0">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'email', 'direction' => request('sort') == 'email' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                   class="text-decoration-none text-dark d-flex align-items-center">
                                    <i class="fas fa-envelope me-2"></i>Email
                                    @if(request('sort') == 'email')
                                        @if(request('direction') == 'asc')
                                            <i class="fas fa-sort-up ms-1"></i>
                                        @else
                                            <i class="fas fa-sort-down ms-1"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="border-0">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'department', 'direction' => request('sort') == 'department' && request('direction') == 'asc' ? 'desc' : 'asc']) }}" 
                                   class="text-decoration-none text-dark d-flex align-items-center">
                                    <i class="fas fa-building me-2"></i>Department
                                    @if(request('sort') == 'department')
                                        @if(request('direction') == 'asc')
                                            <i class="fas fa-sort-up ms-1"></i>
                                        @else
                                            <i class="fas fa-sort-down ms-1"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="border-0">
                                <i class="fas fa-user-tag me-2"></i>Current Roles
                            </th>
                            <th class="border-0">
                                <i class="fas fa-cogs me-2"></i>Assign Roles
                            </th>
                            <th class="border-0">
                                <i class="fas fa-tools me-2"></i>Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allUsers as $user)
                            <tr class="align-middle">
                                <td>
                                    <span class="badge bg-primary text-white">{{ $user->faculty_id ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <strong class="text-dark">{{ $user->name }}</strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="mailto:{{ $user->email }}" class="text-decoration-none">
                                        <i class="fas fa-envelope me-1"></i>{{ $user->email }}
                                    </a>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $user->department ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <div id="current-roles-{{ $user->id }}">
                                        @if($user->all_roles && count($user->all_roles) > 0)
                                            @foreach($user->all_roles as $role)
                                                <span class="badge bg-{{ $role === 'chairperson' ? 'danger' : ($role === 'coordinator' ? 'primary' : ($role === 'adviser' ? 'success' : ($role === 'panelist' ? 'warning' : 'secondary'))) }} me-1 mb-1">
                                                    {{ ucfirst($role) }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">
                                                <i class="fas fa-user-slash me-1"></i>No roles assigned
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="role-assignment-container">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input role-checkbox" type="checkbox" 
                                                           name="roles[{{ $user->faculty_id }}][]" value="chairperson" 
                                                           id="chairperson-{{ $user->id }}"
                                                           {{ in_array('chairperson', $user->all_roles) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="chairperson-{{ $user->id }}">
                                                        <i class="fas fa-crown me-1 text-danger"></i>Chairperson
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input role-checkbox" type="checkbox" 
                                                           name="roles[{{ $user->faculty_id }}][]" value="coordinator" 
                                                           id="coordinator-{{ $user->id }}"
                                                           {{ in_array('coordinator', $user->all_roles) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="coordinator-{{ $user->id }}">
                                                        <i class="fas fa-tasks me-1 text-primary"></i>Coordinator
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input role-checkbox" type="checkbox" 
                                                           name="roles[{{ $user->faculty_id }}][]" value="teacher" 
                                                           id="teacher-{{ $user->id }}"
                                                           {{ in_array('teacher', $user->all_roles) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="teacher-{{ $user->id }}">
                                                        <i class="fas fa-chalkboard-teacher me-1 text-secondary"></i>Teacher
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input role-checkbox" type="checkbox" 
                                                           name="roles[{{ $user->faculty_id }}][]" value="adviser" 
                                                           id="adviser-{{ $user->id }}"
                                                           {{ in_array('adviser', $user->all_roles) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="adviser-{{ $user->id }}">
                                                        <i class="fas fa-user-graduate me-1 text-success"></i>Adviser
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input role-checkbox" type="checkbox" 
                                                           name="roles[{{ $user->faculty_id }}][]" value="panelist" 
                                                           id="panelist-{{ $user->id }}"
                                                           {{ in_array('panelist', $user->all_roles) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="panelist-{{ $user->id }}">
                                                        <i class="fas fa-gavel me-1 text-warning"></i>Panelist
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm update-user-roles" 
                                            data-user-id="{{ $user->faculty_id }}" 
                                            data-user-name="{{ $user->name }}">
                                        <i class="fas fa-save me-1"></i>Update
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    @if($allUsers->hasPages())
        <div class="d-flex justify-content-center mt-4">
            <nav aria-label="Role pagination">
                {{ $allUsers->appends(request()->query())->links('pagination::bootstrap-5') }}
            </nav>
        </div>
    @endif
</div>
<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    font-size: 16px;
}

.role-assignment-container {
    min-width: 200px;
}

.role-checkbox:checked + label {
    color: #0d6efd !important;
}

.role-checkbox:checked + label i {
    opacity: 1;
}

.role-checkbox + label i {
    opacity: 0.6;
    transition: opacity 0.2s;
}

.badge {
    font-size: 0.75em;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
}

.bg-gradient-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.bg-gradient-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border: none;
    transition: all 0.2s;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
}

.update-user-roles:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.text-primary { color: #007bff !important; }
.text-success { color: #28a745 !important; }
.text-warning { color: #ffc107 !important; }
.text-info { color: #17a2b8 !important; }
.text-danger { color: #dc3545 !important; }
.text-secondary { color: #6c757d !important; }

.pagination {
    margin-bottom: 0;
}
.pagination .page-link {
    color: #495057;
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
}
.pagination .page-link:hover {
    color: #0056b3;
    background-color: #e9ecef;
    border-color: #dee2e6;
}
.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}
.pagination .page-item.disabled .page-link {
    color: #6c757d;
    pointer-events: none;
    background-color: #fff;
    border-color: #dee2e6;
}
.pagination .page-item:not(:first-child) .page-link {
    margin-left: -1px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.update-user-roles').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const userName = this.dataset.userName;
            const button = this;
            button.disabled = true;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Updating...';
            
            const checkedRoles = [];
            const checkboxes = document.querySelectorAll(`input[name="roles[${userId}][]"]:checked`);
            checkboxes.forEach(checkbox => {
                checkedRoles.push(checkbox.value);
            });
            
            if (checkedRoles.length === 0) {
                alert('Please select at least one role for ' + userName);
                button.disabled = false;
                button.innerHTML = originalText;
                return;
            }
            
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'POST');
            checkedRoles.forEach(role => {
                formData.append('roles[]', role);
            });
            
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
                    updateCurrentRolesDisplay(userId);

                    showNotification('success', `Roles updated successfully for ${userName}`);
                } else {
                    showNotification('error', 'Error updating roles: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'Error updating roles. Please try again.');
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = originalText;
            });
        });
    });
    document.querySelectorAll('.role-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const label = this.nextElementSibling;
            if (this.checked) {
                label.classList.add('text-primary');
            } else {
                label.classList.remove('text-primary');
            }
            const userId = this.name.match(/roles\[(.*?)\]/)[1];
            updateCurrentRolesDisplay(userId);
        });
    });
});

function updateCurrentRolesDisplay(userId) {
    const container = document.getElementById(`current-roles-${userId}`);
    if (!container) return;
    const checkedRoles = [];
    const checkboxes = document.querySelectorAll(`input[name="roles[${userId}][]"]:checked`);
    checkboxes.forEach(checkbox => {
        checkedRoles.push(checkbox.value);
    });
    
    const roleColors = {
        'chairperson': 'danger',
        'coordinator': 'primary', 
        'teacher': 'secondary',
        'adviser': 'success',
        'panelist': 'warning'
    };
    
    if (checkedRoles.length === 0) {
        container.innerHTML = '<span class="text-muted"><i class="fas fa-user-slash me-1"></i>No roles assigned</span>';
    } else {
        const badges = checkedRoles.map(role => 
            `<span class="badge bg-${roleColors[role] || 'secondary'} me-1 mb-1">${role.charAt(0).toUpperCase() + role.slice(1)}</span>`
        ).join('');
        container.innerHTML = badges;
    }
}

function showNotification(type, message) {
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>
@endsection
