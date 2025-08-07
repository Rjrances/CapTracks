@extends('layouts.chairperson')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Manage User Roles</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($users->count())
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID Number</th>
                <th>Name</th>
                <th>Email</th>
                <th>Current Role</th>
                <th>Change Role</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->school_id ?? 'N/A' }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td class="text-capitalize">{{ $user->role }}</td>
                <td>
                    <form method="POST" action="{{ route('chairperson.roles.update', $user) }}" class="d-flex align-items-center gap-2">
                        @csrf
                        <select name="role" class="form-select form-select-sm">
                            <option value="coordinator" @selected($user->role == 'coordinator')>Coordinator</option>
                            <option value="adviser" @selected($user->role == 'adviser')>Adviser</option>
                            <option value="panelist" @selected($user->role == 'panelist')>Panelist</option>
                        </select>
                        <button type="submit" class="btn btn-success btn-sm ms-2">
                            Update
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
        <p>No users available for role assignment.</p>
    @endif
</div>
@endsection
