@extends('layouts.coordinator')

@section('title')
Compare proposals — {{ $studentGroup->name ?? 'Group' }}
@endsection

@section('content')
<div class="container-fluid mt-4 px-md-4">
        <x-coordinator.intro description="Side-by-side preview of two proposal file versions for QA and grading.">
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </x-coordinator.intro>
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
