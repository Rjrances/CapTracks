@extends('layouts.adviser')

@section('title', 'Rating Sheet')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Panel Rating Sheet</h4>
            <small class="text-muted">{{ $schedule->group->name }} - {{ $schedule->stage_label }}</small>
        </div>
        <a href="{{ route('adviser.dashboard') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <strong>Schedule:</strong>
                {{ $schedule->start_at->format('M d, Y h:i A') }} - {{ $schedule->room }}
            </div>

            <form action="{{ route('adviser.rating-sheets.submit', $schedule) }}" method="POST">
                @csrf

                @php
                    $criteria = old('criteria_names')
                        ? collect(old('criteria_names'))->map(function ($name, $index) {
                            return [
                                'name' => $name,
                                'score' => old('criteria_scores')[$index] ?? 0,
                            ];
                        })->toArray()
                        : ($existingRating?->criteria ?? $defaultCriteria);
                @endphp

                @foreach($criteria as $index => $criterion)
                    <div class="row mb-2 align-items-center">
                        <div class="col-md-8">
                            <input type="text" name="criteria_names[]" class="form-control" value="{{ $criterion['name'] }}" required>
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="0.01" min="0" max="100" name="criteria_scores[]" class="form-control" value="{{ $criterion['score'] }}" required>
                        </div>
                    </div>
                @endforeach

                <div class="mt-3">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="4" class="form-control">{{ old('remarks', $existingRating->remarks ?? '') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary mt-3">
                    Submit Rating Sheet
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
