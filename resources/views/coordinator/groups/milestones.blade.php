@extends('layouts.coordinator')

@section('title', 'Group Milestones')

@section('content')
<div class="container mt-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('coordinator.groups.index') }}">Groups</a></li>
            <li class="breadcrumb-item"><a href="{{ route('coordinator.groups.show', $group->id) }}">{{ $group->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Milestones</li>
        </ol>
    </nav>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title mb-3">Milestones for: {{ $group->name }}</h2>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Milestone</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Proposal Submission</td>
                        <td><span class="badge bg-warning">Pending</span></td>
                        <td>2025-08-01</td>
                        <td><a href="#" class="btn btn-sm btn-outline-info">View</a></td>
                    </tr>
                    <tr>
                        <td>Defense</td>
                        <td><span class="badge bg-secondary">Not Started</span></td>
                        <td>2025-09-15</td>
                        <td><a href="#" class="btn btn-sm btn-outline-info">View</a></td>
                    </tr>
                    <tr>
                        <td>Final Submission</td>
                        <td><span class="badge bg-success">Completed</span></td>
                        <td>2025-10-30</td>
                        <td><a href="#" class="btn btn-sm btn-outline-info">View</a></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center text-muted">(Milestone data coming soon)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <a href="{{ route('coordinator.groups.show', $group->id) }}" class="btn btn-secondary">Back to Group</a>
</div>
@endsection 