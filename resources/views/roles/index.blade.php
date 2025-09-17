@extends('layouts.app')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">Manage User Roles</h2>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Current Roles</th>
                                    <th>Assign Roles</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <form action="{{ route('roles.update', $user->id) }}" method="POST">
                                            @csrf
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                @php
                                                    $userRoles = $user->roles->pluck('name')->toArray();
                                                @endphp
                                                @if(count($userRoles) > 0)
                                                    @foreach($userRoles as $role)
                                                        <span class="badge bg-primary me-1">{{ ucfirst($role) }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">No roles assigned</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="roles[]" value="chairperson" id="chairperson_{{ $user->id }}" {{ in_array('chairperson', $userRoles) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="chairperson_{{ $user->id }}">Chairperson</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="roles[]" value="coordinator" id="coordinator_{{ $user->id }}" {{ in_array('coordinator', $userRoles) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="coordinator_{{ $user->id }}">Coordinator</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="roles[]" value="adviser" id="adviser_{{ $user->id }}" {{ in_array('adviser', $userRoles) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="adviser_{{ $user->id }}">Adviser</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="roles[]" value="panelist" id="panelist_{{ $user->id }}" {{ in_array('panelist', $userRoles) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="panelist_{{ $user->id }}">Panelist</label>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="submit" class="btn btn-primary btn-sm">Update Roles</button>
                                            </td>
                                        </form>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
