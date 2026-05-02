@extends('layouts.adviser')
@section('title', 'Preview proposal')
@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Proposal preview — {{ $studentGroup->name }}</h4>
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>
    @include('partials.document-embed-panel', ['panel' => $panel])
</div>
@endsection
