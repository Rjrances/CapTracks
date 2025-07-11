@extends('layouts.chairperson') {{-- This assumes layouts/chairperson.blade.php exists --}}

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">Manage User Roles</h1>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-3 mb-4 rounded">
            {{ session('success') }}
        </div>
    @endif

    <table class="w-full table-auto border-collapse mb-4">
        <thead>
            <tr class="bg-gray-200">
                <th class="p-2 text-left">Name</th>
                <th class="p-2 text-left">Email</th>
                <th class="p-2 text-left">Current Role</th>
                <th class="p-2 text-left">Change Role</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr class="border-b">
                <td class="p-2">{{ $user->name }}</td>
                <td class="p-2">{{ $user->email }}</td>
                <td class="p-2 capitalize">{{ $user->role }}</td>
                <td class="p-2">
                    <form method="POST" action="{{ route('chairperson.roles.update', $user) }}" class="d-flex align-items-center gap-2">
                        @csrf
                        <select name="role" class="form-select form-select-sm">
                            <option value="student" @selected($user->role == 'student')>Student</option>
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

    @if($users->isEmpty())
        <p class="text-gray-500">No users available for role assignment.</p>
    @endif
</div>
@endsection
