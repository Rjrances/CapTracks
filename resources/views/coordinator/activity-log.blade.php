@extends('layouts.coordinator')

@section('title', 'Student Activity Log')

@section('content')
<div class="container-fluid">
        <x-coordinator.intro description="Recent actions by students in groups linked to your coordinated offerings.">
            <form method="GET" action="{{ route('coordinator.activity-log') }}" class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label for="student_id" class="form-label small mb-0 text-muted">Member</label>
                    <select name="student_id" id="student_id" class="form-select form-select-sm" style="min-width: 220px;">
                        <option value="">All students</option>
                        @foreach($studentsForFilter as $stu)
                            <option value="{{ $stu->student_id }}" {{ (string) ($filterStudentId ?? '') === (string) $stu->student_id ? 'selected' : '' }}>
                                {{ $stu->name }} ({{ $stu->student_id }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-filter me-1"></i>Apply
                </button>
                @if($filterStudentId)
                    <a href="{{ route('coordinator.activity-log') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </form>
        </x-coordinator.intro>

            @if($activityLogs->isEmpty())
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Activity Logs Yet</h5>
                        <p class="text-muted mb-0">Student activities from your coordinated offerings will appear here.</p>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activityLogs as $log)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $log->student->name ?? 'Unknown Student' }}</div>
                                                <small class="text-muted">{{ $log->student_id ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ str_replace('_', ' ', ucfirst($log->action)) }}</span>
                                            </td>
                                            <td>{{ $log->description }}</td>
                                            <td>{{ $log->created_at->format('M d, Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $activityLogs->links() }}
                        </div>
                    </div>
                </div>
            @endif
</div>
@endsection
