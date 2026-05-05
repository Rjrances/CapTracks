@extends('layouts.student')
@section('title', 'Project Submissions')
@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
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
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($submissions && count($submissions))
    @php
        $subCollection = collect($submissions);
        $projectCompareByType = $subCollection->groupBy('type')->filter(function ($g) {
            return $g->count() >= 2;
        });
        $projectCompareTemplate = str_replace(
            ['11111111', '22222222'],
            ['__L__', '__R__'],
            route('student.project.submissions.compare', ['left' => 11111111, 'right' => 22222222])
        );
    @endphp
    @if($projectCompareByType->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0"><i class="fas fa-columns me-2"></i>Compare two versions (same type)</h6>
            </div>
            <div class="card-body py-2">
                <p class="small text-muted mb-2">Side-by-side preview for two uploads of the same category.</p>
                @foreach($projectCompareByType as $type => $versions)
                    <div class="row g-2 align-items-end mb-2">
                        <div class="col-auto">
                            <span class="badge bg-secondary text-uppercase">{{ $type }}</span>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-0" for="proj-cmp-{{ $type }}-a">Version A</label>
                            <select id="proj-cmp-{{ $type }}-a" class="form-select form-select-sm">
                                @foreach($versions as $s)
                                    <option value="{{ $s->id }}">v{{ $s->version ?? 1 }} — {{ $s->submitted_at ? \Carbon\Carbon::parse($s->submitted_at)->format('M d, Y') : 'N/A' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-0" for="proj-cmp-{{ $type }}-b">Version B</label>
                            <select id="proj-cmp-{{ $type }}-b" class="form-select form-select-sm">
                                @foreach($versions as $s)
                                    <option value="{{ $s->id }}">v{{ $s->version ?? 1 }} — {{ $s->submitted_at ? \Carbon\Carbon::parse($s->submitted_at)->format('M d, Y') : 'N/A' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-sm btn-outline-primary project-compare-go" data-t="{{ $type }}">
                                <i class="fas fa-columns me-1"></i>Compare
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @push('scripts')
        <script>
            (function () {
                var tpl = @json($projectCompareTemplate);
                document.querySelectorAll('.project-compare-go').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var t = btn.getAttribute('data-t');
                        var l = document.getElementById('proj-cmp-' + t + '-a').value;
                        var r = document.getElementById('proj-cmp-' + t + '-b').value;
                        if (!l || !r || l === r) {
                            alert('Choose two different submissions.');
                            return;
                        }
                        window.location.href = tpl.replace('__L__', l).replace('__R__', r);
                    });
                });
            })();
        </script>
        @endpush
    @endif
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
                    <th style="width: 14%;">Actions</th>
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
                        @if($submission->file_path)
                            <a href="{{ route('student.project.submission.preview', $submission) }}" class="btn btn-secondary btn-sm me-1" title="Preview">
                                <i class="fas fa-eye"></i>
                            </a>
                        @endif
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
