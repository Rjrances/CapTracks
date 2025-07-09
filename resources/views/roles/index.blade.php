@extends('layouts.app')

@section('content')
    <h2>Manage User Roles</h2>

    @if(session('success'))
        <div>{{ session('success') }}</div>
    @endif

    <table>
        <tr>
            <th>Name</th><th>Email</th><th>Role</th><th>Actions</th>
        </tr>
        @foreach($users as $user)
            <tr>
                <form action="{{ route('roles.update', $user->id) }}" method="POST">
                    @csrf
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <select name="role">
                            <option value="student" {{ $user->role === 'student' ? 'selected' : '' }}>Student</option>
                            <option value="coordinator" {{ $user->role === 'coordinator' ? 'selected' : '' }}>Coordinator</option>
                            <option value="adviser" {{ $user->role === 'adviser' ? 'selected' : '' }}>Adviser</option>
                            <option value="panelist" {{ $user->role === 'panelist' ? 'selected' : '' }}>Panelist</option>
                        </select>
                    </td>
                    <td><button type="submit">Update</button></td>
                </form>
            </tr>
        @endforeach
    </table>
@endsection
