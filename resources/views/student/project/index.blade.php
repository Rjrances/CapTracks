@extends('layouts.student')
@section('title', 'Project Submissions')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Quick File Uploads</h2>
            <p class="text-muted mb-0">Supplementary project documents and files</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('student.proposal') }}" class="btn btn-outline-info">
                <i class="fas fa-file-contract me-2"></i>Project Proposals
            </a>
            <a href="{{ route('student.milestones') }}" class="btn btn-outline-primary">
                <i class="fas fa-tasks me-2"></i>View Milestones
            </a>
        </div>
    </div>
    
    <div class="alert alert-info mb-4">
        <div class="d-flex align-items-start">
            <i class="fas fa-info-circle me-3 mt-1"></i>
            <div>
                <h6 class="alert-heading mb-2">Quick File Uploads</h6>
                <p class="mb-0">This section is for uploading supplementary documents like final reports, presentations, and additional materials. For formal project proposals, please use the <strong>Project Proposals</strong> section.</p>
            </div>
        </div>
    </div>
    
    <a href="{{ route('student.project.create') }}" class="btn btn-success mb-3">
        <i class="fas fa-upload me-2"></i>Upload New File
    </a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($submissions && count($submissions))
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th style="width: 12%;">Type</th>
                    <th style="width: 25%;">Title/Description</th>
                    <th style="width: 10%;">File</th>
                    <th style="width: 10%;">Review Status</th>
                    <th style="width: 20%;">Teacher Comment</th>
                    <th style="width: 13%;">Submitted At</th>
                    <th style="width: 10%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($submissions as $submission)
                <tr>
                    <td class="text-center">
                        <span class="badge bg-{{ $submission->type === 'other' ? 'info' : ($submission->type === 'proposal' ? 'primary' : 'success') }}">
                            {{ $submission->type === 'other' ? 'Task' : ucfirst($submission->type) }}
                        </span>
                    </td>
                    <td>
                        @if($submission->title)
                            <div class="text-truncate" style="max-width: 200px;" title="{{ $submission->title }}">
                                <strong>{{ Str::limit($submission->title, 30) }}</strong>
                            </div>
                            @if($submission->objectives)
                                <div class="text-truncate text-muted small" style="max-width: 200px;" title="{{ $submission->objectives }}">
                                    {{ Str::limit($submission->objectives, 50) }}
                                </div>
                            @endif
                        @else
                            <em class="text-muted">No title</em>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($submission->file_path)
                            <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i>
                            </a>
                        @else
                            <span class="text-muted small">No file</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-{{ $submission->status === 'pending' ? 'warning' : ($submission->status === 'approved' ? 'success' : 'danger') }}">
                            {{ ucfirst($submission->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="text-truncate" style="max-width: 150px;" title="{{ $submission->teacher_comment ?? 'No comment' }}">
                            {{ $submission->teacher_comment ? Str::limit($submission->teacher_comment, 30) : '-' }}
                        </div>
                    </td>
                    <td class="text-center">
                        <small>{{ $submission->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->format('M d, Y') : '-' }}</small>
                    </td>
                    <td class="text-center">
                        <form action="{{ route('student.project.destroy', $submission->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this submission?')" title="Delete submission">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
        <div class="alert alert-info">No submissions yet.</div>
    @endif
</div>
@push('styles')
<style>
.table-responsive {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.table th {
    font-weight: 600;
    font-size: 0.9rem;
    padding: 12px 8px;
    border-bottom: 2px solid #dee2e6;
}
.table td {
    padding: 10px 8px;
    vertical-align: middle;
    font-size: 0.9rem;
}
.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.badge {
    font-size: 0.75rem;
    padding: 4px 8px;
}
.btn-sm {
    padding: 4px 8px;
    font-size: 0.8rem;
}
/* Responsive adjustments */
@media (max-width: 768px) {
    .table th, .table td {
        padding: 8px 4px;
        font-size: 0.8rem;
    }
    .text-truncate {
        max-width: 120px !important;
    }
    .btn-sm {
        padding: 2px 6px;
        font-size: 0.75rem;
    }
}
</style>
@endpush
@endsection 
