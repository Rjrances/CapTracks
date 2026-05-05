@extends('layouts.coordinator')
@section('title')
{{ $group->name }}
@endsection
@section('content')
<div class="container-fluid">
        <x-coordinator.intro :description="'Members, adviser assignment, and status for this capstone group ('.($group->offering->subject_code ?? 'offering').').'">
            <a href="{{ route('coordinator.groups.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-users me-2"></i>All groups
            </a>
            <a href="{{ route('coordinator.groups.milestones', $group->id) }}" class="btn btn-outline-secondary">
                <i class="fas fa-flag me-2"></i>Milestones
            </a>
        </x-coordinator.intro>
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('coordinator.groups.index') }}">Groups</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $group->name }}</li>
        </ol>
    </nav>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title mb-3 h4">{{ $group->name }}</h2>
            <p class="card-text text-muted mb-2">{{ $group->description ?: 'No description provided.' }}</p>
            <p><strong>Adviser:</strong> 
                @if($group->adviser)
                    <span class="badge bg-success me-2">Assigned</span>
                    {{ $group->adviser->name }} ({{ $group->adviser->email }})
                @else
                    <span class="badge bg-warning">No Adviser Assigned</span>
                @endif
            </p>
            <p><strong>Status:</strong> <span class="badge bg-info">Active</span></p>
        </div>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light"><strong>Group Members</strong></div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($group->members as $member)
                    <tr>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->email }}</td>
                        <td>
                            <span class="badge bg-secondary">{{ $member->pivot->role ?? 'Member' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-muted">No members found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('coordinator.groups.index') }}" class="btn btn-secondary">Back to Groups</a>
        <form action="{{ route('coordinator.groups.destroy', $group->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this group? This action cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">Delete Group</button>
        </form>
    </div>
</div>
@endsection 
