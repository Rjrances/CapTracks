@extends('layouts.app')

@section('title', 'Upload Project Submission')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Upload Project Submission</h2>
        <a href="{{ route('student.dashboard') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Return to Dashboard
        </a>
    </div>
    <form action="{{ route('student.project.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label for="type" class="form-label">Submission Type</label>
            <select name="type" id="type" class="form-select" required>
                <option value="proposal">Proposal</option>
                <option value="final">Final</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="file" class="form-label">File</label>
            <input type="file" name="file" id="file" class="form-control" required accept=".pdf,.doc,.docx,.zip">
        </div>
        <button type="submit" class="btn btn-success">Upload</button>
        <a href="{{ route('student.project') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection 