@extends('layouts.app')

@section('title', 'My Project Submissions')

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">My Project Submissions</h2>
    <a href="{{ route('student.project.create') }}" class="btn btn-success mb-3">Upload New Submission</a>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($submissions && count($submissions))
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Type</th>
                <th>File</th>
                <th>Status</th>
                <th>Teacher Comment</th>
                <th>Submitted At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($submissions as $submission)
            <tr>
                <td class="text-capitalize">{{ $submission->type }}</td>
                <td><a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank">Download</a></td>
                <td class="text-capitalize">{{ $submission->status }}</td>
                <td>{{ $submission->teacher_comment ?? '-' }}</td>
                <td>{{ $submission->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->format('Y-m-d H:i') : '-' }}</td>
                <td>
                    <form action="{{ route('student.project.destroy', $submission->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('Delete this submission?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
        <div class="alert alert-info">No submissions yet.</div>
    @endif
</div>
@endsection 