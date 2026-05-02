@extends('layouts.student')
@section('title', 'Preview submission')
@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Submission preview</h4>
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>
    <p class="text-muted small">{{ $typeLabel }} · {{ $panel['label'] }}</p>
    @include('partials.document-embed-panel', ['panel' => $panel])
</div>
@endsection
