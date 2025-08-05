@extends('layouts.coordinator')

@section('title', 'Groups')

@section('content')
<div class="d-flex justify-content-center align-items-start" style="min-height: 80vh; background: transparent;">
    <div class="bg-white rounded-4 shadow-sm p-5 w-100" style="max-width: 950px;">
        <div class="mb-4" style="margin-bottom: 1.2rem !important;">
            <h1 class="fw-bold mb-1" style="font-size:2.2rem; margin-bottom:0.1rem;">Groups</h1>
            <div class="text-muted" style="font-size:1.1rem; margin-bottom:0;">Monitor and manage all capstone project groups</div>
        </div>
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <form class="d-flex" method="GET" action="">
                <input type="text" name="search" class="form-control rounded-pill me-2" placeholder="Search groups..." style="max-width: 250px;" value="{{ request('search') }}">
                <button class="btn btn-primary rounded-pill px-4" type="submit">Search</button>
            </form>
            <a href="{{ route('coordinator.groups.create') }}" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm">Create New Group</a>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0 bg-white rounded-3" style="overflow:hidden;">
                <thead class="bg-light border-bottom">
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Members</th>
                        <th>Adviser</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $group)
                    <tr style="border-bottom:1px solid #f0f0f0;">
                        <td class="fw-semibold">{{ $group->name }}</td>
                        <td class="text-muted">{{ $group->description ?? '-' }}</td>
                        <td>
                            <span class="badge bg-primary">{{ $group->members->count() }} members</span>
                        </td>
                        <td>
                            @if($group->adviser)
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-success me-2">Assigned</span>
                                    {{ $group->adviser->name }}
                                </div>
                            @else
                                <span class="badge bg-warning">No Adviser</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('coordinator.groups.show', $group->id) }}" class="btn btn-outline-primary btn-sm rounded-pill me-1">View</a>
                            <a href="{{ route('coordinator.groups.assignAdviser', $group->id) }}" class="btn btn-outline-secondary btn-sm rounded-pill me-1">Assign Adviser</a>
                            <a href="{{ route('coordinator.groups.milestones', $group->id) }}" class="btn btn-outline-success btn-sm rounded-pill me-1">Milestones</a>
                            <form action="{{ route('coordinator.groups.destroy', $group->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this group? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">No groups found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 d-flex justify-content-center">
            {{ $groups->links() }}
        </div>
    </div>
</div>
@endsection 