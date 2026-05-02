@extends('layouts.student')
@section('title', 'Compare submissions')
@section('content')
<div class="container-fluid mt-4 px-md-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4 class="mb-0">Compare {{ $typeLabel }} versions</h4>
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>
    <p class="text-muted small mb-3">Same submission type only. Office preview uses Microsoft’s online viewer.</p>
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
