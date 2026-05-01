@extends('layouts.adviser')

@section('title', 'Activity Log')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-history me-2"></i>Student Activity Log
            </h5>
        </div>
        <div class="card-body">
            @if($logs->isEmpty())
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p class="mb-0">No activity logs found.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Student</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('M d, Y h:i A') }}</td>
                                    <td>{{ $log->student->name ?? ($log->student_id ?: '-') }}</td>
                                    <td><span class="badge bg-secondary">{{ $log->action }}</span></td>
                                    <td>{{ $log->description }}</td>
                                    <td>{{ $log->user->name ?? 'System/Student' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
