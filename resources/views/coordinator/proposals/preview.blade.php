@extends('layouts.coordinator')

@section('title')
Proposal preview — {{ $studentGroup->name ?? 'Group' }}
@endsection

@section('content')
<div class="container-fluid mt-4">
        <x-coordinator.intro description="Embedded document preview without downloading the source file.">
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </x-coordinator.intro>
    @include('partials.document-embed-panel', ['panel' => $panel])
</div>
@endsection
