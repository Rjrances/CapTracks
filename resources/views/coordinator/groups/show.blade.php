@extends('layouts.coordinator')

@section('title', 'Group Details')

@section('content')
<div class="container mt-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('coordinator.groups.index') }}">Groups</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $group->name }}</li>
        </ol>
    </nav>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title mb-3">{{ $group->name }}</h2>
            <p class="card-text text-muted mb-2">{{ $group->description ?: 'No description provided.' }}</p>
            <p><strong>Adviser:</strong> {{ $group->adviser ? $group->adviser->name : '—' }}</p>
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
                    <tr><td colspan="3" class="text-center text-muted">(Members list coming soon)</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('coordinator.groups.index') }}" class="btn btn-secondary">Back to Groups</a>
        <a href="#" class="btn btn-outline-primary">Edit Group</a>
        <a href="#" class="btn btn-outline-danger">Delete Group</a>
    </div>
</div>
@endsection 