@extends('layouts.app')

@section('title', 'All Groups')

@section('content')
<div class="container mt-5">
    <h2 class="fw-bold mb-4">All Groups</h2>
    @if(isset($groups) && count($groups))
        <div class="row g-4">
            @foreach($groups as $group)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">{{ $group->name }}</h5>
                            <p class="card-text text-muted">{{ $group->description }}</p>
                            <p class="mb-2"><strong>Adviser:</strong> {{ $group->adviser->name ?? 'N/A' }}</p>
                            <a href="{{ route('student.group', ['id' => $group->id]) }}" class="btn btn-primary btn-sm">View</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info">No groups found.</div>
    @endif
</div>
@endsection 