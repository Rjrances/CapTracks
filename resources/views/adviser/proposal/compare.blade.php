@extends('layouts.adviser')
@section('title', 'Compare proposal versions')
@section('content')
<div class="container-fluid mt-4 px-md-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4 class="mb-0">Compare proposal versions — {{ $studentGroup->name }}</h4>
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>
    <p class="text-muted small mb-3">Side-by-side preview for versions of this student’s proposal.</p>
    <div class="row g-3">
        <div class="col-lg-6">
            @include('partials.document-embed-panel', ['panel' => $leftPanel])
        </div>
        <div class="col-lg-6">
            @include('partials.document-embed-panel', ['panel' => $rightPanel])
        </div>
    </div>
</div>
@endsection
