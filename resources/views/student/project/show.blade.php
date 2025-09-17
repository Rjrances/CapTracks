@extends('layouts.student')
@section('title', 'Submission Details')
@section('content')
<div class="container mt-5">
    <h2 class="mb-4">Submission Details</h2>
    <div class="card">
        <div class="card-body">
            <p><strong>Type:</strong> <span class="text-capitalize">{{ $submission->type }}</span></p>
            <p><strong>Status:</strong> <span class="text-capitalize">{{ $submission->status }}</span></p>
            <p><strong>Teacher Comment:</strong> {{ $submission->teacher_comment ?? '-' }}</p>
            <p><strong>Submitted At:</strong> {{ $submission->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->format('Y-m-d H:i') : '-' }}</p>
            <p><strong>File:</strong> <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank">Download</a></p>
        </div>
    </div>
    <a href="{{ route('student.project') }}" class="btn btn-secondary mt-3">Back to Submissions</a>
</div>
@endsection 
