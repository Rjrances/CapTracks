@extends('layouts.app')

@section('title', 'My Group')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="bg-white rounded-4 shadow-sm pt-4 px-5 pb-5 w-100" style="max-width: 700px;">
        @if($group)
            <h2 class="fw-bold mb-2">{{ $group->name }}</h2>
            <div class="mb-3 text-muted">{{ $group->description }}</div>
            <div class="mb-3">
                <strong>Adviser:</strong> {{ $group->adviser->name ?? 'N/A' }}
            </div>
            <div class="mb-3">
                <strong>Members:</strong>
                <ul class="list-group list-group-flush">
                    @foreach($group->members ?? [] as $member)
                        <li class="list-group-item">{{ $member->name }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="mt-4">
                <h5 class="fw-semibold">Group Messaging</h5>
                <div class="alert alert-info">Group chat coming soon!</div>
            </div>
        @else
            <div class="alert alert-warning text-center">
                You are not assigned to any group yet.<br>
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                    <a href="{{ route('student.group.create') }}" class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm border">Create Group</a>
                    <a href="{{ route('student.group.index') }}" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm border">View All Groups</a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection 