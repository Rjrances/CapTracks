@extends('layouts.coordinator')

@section('title', 'Defense Requests')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-inbox me-2"></i>Pending Defense Requests
                    </h2>
                    <p class="text-muted mb-0">Review and process defense requests from your assigned offerings.</p>
                </div>
                <a href="{{ route('coordinator.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Requests</h6>
                    <span class="badge bg-warning text-dark">{{ $pendingRequests->count() }} pending</span>
                </div>
                <div class="card-body p-0">
                    @if($pendingRequests->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                            <h6 class="mb-1">No pending requests</h6>
                            <p class="text-muted mb-0">All defense requests are already processed.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Group</th>
                                        <th>Defense Type</th>
                                        <th>Requested Date</th>
                                        <th>Adviser</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingRequests as $request)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $request->group->name }}</div>
                                                <small class="text-muted">
                                                    {{ $request->group->members->pluck('name')->take(3)->implode(', ') }}
                                                </small>
                                            </td>
                                            <td>{{ $request->defense_type_label }}</td>
                                            <td>{{ optional($request->requested_at)->format('M d, Y h:i A') ?? '-' }}</td>
                                            <td>{{ $request->group->adviser->name ?? 'Not assigned' }}</td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <a href="{{ route('coordinator.defense-requests.create-schedule', $request) }}" class="btn btn-sm btn-success">
                                                        <i class="fas fa-calendar-plus me-1"></i>Approve / Schedule
                                                    </a>
                                                    <form action="{{ route('coordinator.defense-requests.reject', $request) }}" method="POST" class="d-flex gap-2">
                                                        @csrf
                                                        <input
                                                            type="text"
                                                            name="coordinator_notes"
                                                            class="form-control form-control-sm"
                                                            placeholder="Reason for rejection"
                                                            required
                                                        >
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
