@extends('layouts.adviser')

@section('title', 'Rating Sheet')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Panel Rating Sheet</h4>
            <small class="text-muted">{{ $schedule->group->name }} - {{ $schedule->stage_label }}</small>
        </div>
        <a href="{{ request()->routeIs('coordinator.*') ? route('coordinator.defense.index') : route('adviser.dashboard') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            <div>{{ session('success') }}</div>
            <div class="mt-2 d-flex gap-2 flex-wrap">
                <a href="{{ route('adviser.panel-groups') }}" class="btn btn-sm btn-success">
                    Back to Panel Groups
                </a>
                @if(session('next_rating_sheet_url'))
                    <a href="{{ session('next_rating_sheet_url') }}" class="btn btn-sm btn-outline-success">
                        Go to Next Pending Rating ({{ session('next_rating_group_name', 'next group') }})
                    </a>
                @endif
            </div>
        </div>
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
    @if(!empty($isFinalized))
        <div class="alert alert-warning">
            This defense result is already finalized. Ratings are now locked unless the coordinator reopens the result.
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <strong>Schedule:</strong>
                {{ $schedule->start_at->format('M d, Y h:i A') }} - {{ $schedule->room }}
            </div>

            <form action="{{ request()->routeIs('coordinator.*') ? route('coordinator.rating-sheets.rate.submit', $schedule) : route('adviser.rating-sheets.submit', $schedule) }}" method="POST">
                @csrf

                @php
                    $criteria = old('criteria_names')
                        ? collect(old('criteria_names'))->map(function ($name, $index) {
                            return [
                                'criterion_id' => old('criteria_ids')[$index] ?? null,
                                'scope' => old('criteria_scopes')[$index] ?? 'group',
                                'name' => $name,
                                'max_points' => old('criteria_max_points')[$index] ?? 10,
                                'score' => old('criteria_scores')[$index] ?? 0,
                            ];
                        })->toArray()
                        : ($existingRating?->criteria ?? $groupCriteria ?? $defaultCriteria);
                    $individualScores = collect(old('individual_scores', []));
                    if ($individualScores->isEmpty()) {
                        $individualScores = collect($existingRating->individual_scores ?? [])->mapWithKeys(function ($row) {
                            return [$row['student_id'] => $row['score'] ?? 0];
                        });
                    }
                @endphp

                @foreach($criteria as $index => $criterion)
                    <div class="row mb-2 align-items-center">
                        <div class="col-md-7">
                            <div class="form-control bg-light">
                                {{ $criterion['name'] }}
                                <span class="badge bg-light text-secondary border ms-2 text-uppercase">{{ $criterion['scope'] ?? 'group' }}</span>
                            </div>
                            <input type="hidden" name="criteria_ids[]" value="{{ $criterion['criterion_id'] ?? '' }}">
                            <input type="hidden" name="criteria_scopes[]" value="{{ $criterion['scope'] ?? 'group' }}">
                            <input type="hidden" name="criteria_names[]" value="{{ $criterion['name'] }}">
                            <input type="hidden" name="criteria_max_points[]" value="{{ $criterion['max_points'] ?? 10 }}">
                        </div>
                        <div class="col-md-3">
                            <input type="number" step="0.1" min="0" max="{{ $criterion['max_points'] ?? 10 }}" name="criteria_scores[]" class="form-control criteria-score-input" value="{{ $criterion['score'] }}" required {{ !empty($isFinalized) ? 'disabled' : '' }}>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">/ {{ number_format((float) ($criterion['max_points'] ?? 10), 2) }}</small>
                        </div>
                    </div>
                @endforeach

                <hr class="my-4">
                <h6 class="mb-2">{{ $individualCriterion['name'] ?? 'Individual Contribution' }} (per member)</h6>
                <small class="text-muted d-block mb-3">Each member is scored out of {{ number_format((float) ($individualCriterion['max_points'] ?? 100), 2) }}.</small>

                @foreach($groupMembers as $member)
                    <div class="row mb-2 align-items-center">
                        <div class="col-md-7">
                            <div class="form-control bg-light">{{ $member->name }} ({{ $member->student_id }})</div>
                        </div>
                        <div class="col-md-3">
                            <input
                                type="number"
                                step="0.1"
                                min="0"
                                max="{{ $individualCriterion['max_points'] ?? 100 }}"
                                name="individual_scores[{{ $member->student_id }}]"
                                class="form-control"
                                value="{{ $individualScores->get($member->student_id, 0) }}"
                                required
                                {{ !empty($isFinalized) ? 'disabled' : '' }}>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">/ {{ number_format((float) ($individualCriterion['max_points'] ?? 100), 2) }}</small>
                        </div>
                    </div>
                @endforeach

                <div class="mt-3">
                    <strong>Total Score:</strong>
                    <span id="total-score">{{ number_format(collect($criteria)->sum('score'), 2) }}</span>
                    <small class="text-muted ms-2">/ {{ number_format(collect($criteria)->sum(fn ($criterion) => (float) ($criterion['max_points'] ?? 0)), 2) }}</small>
                    <small class="text-muted ms-2">Enter at least one non-zero score to submit.</small>
                </div>

                <div class="mt-3">
                    <label for="recommendation" class="form-label">Panel Recommendation</label>
                    <select id="recommendation" name="recommendation" class="form-select" required {{ !empty($isFinalized) ? 'disabled' : '' }}>
                        @php
                            $selectedRecommendation = old('recommendation', $existingRating->recommendation ?? 'pass');
                        @endphp
                        <option value="pass" {{ $selectedRecommendation === 'pass' ? 'selected' : '' }}>Pass</option>
                        <option value="conditional_pass" {{ $selectedRecommendation === 'conditional_pass' ? 'selected' : '' }}>Conditional Pass</option>
                        <option value="redefend" {{ $selectedRecommendation === 'redefend' ? 'selected' : '' }}>Re-Defend</option>
                    </select>
                    <small class="text-muted">Choose the panel outcome recommendation for this defense.</small>
                </div>

                <div class="mt-3" id="redefend-reason-wrapper" style="display: none;">
                    <label for="recommendation_reason" class="form-label">Reason for Re-Defend</label>
                    <textarea id="recommendation_reason" name="recommendation_reason" rows="3" class="form-control" {{ !empty($isFinalized) ? 'disabled' : '' }}>{{ old('recommendation_reason', $existingRating->recommendation_reason ?? '') }}</textarea>
                    <small class="text-muted">Required when recommendation is Re-Defend.</small>
                </div>

                <div class="mt-3">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="4" class="form-control" {{ !empty($isFinalized) ? 'disabled' : '' }}>{{ old('remarks', $existingRating->remarks ?? '') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary mt-3" id="submit-rating-btn" {{ !empty($isFinalized) ? 'disabled' : '' }}>
                    {{ $existingRating ? 'Update Rating Sheet' : 'Submit Rating Sheet' }}
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const scoreInputs = document.querySelectorAll('.criteria-score-input');
    const totalEl = document.getElementById('total-score');
    const submitBtn = document.getElementById('submit-rating-btn');
    const recommendationSelect = document.getElementById('recommendation');
    const redefendReasonWrapper = document.getElementById('redefend-reason-wrapper');
    const redefendReasonInput = document.getElementById('recommendation_reason');

    function updateTotalAndState() {
        let total = 0;
        scoreInputs.forEach((input) => {
            const value = parseFloat(input.value);
            total += Number.isNaN(value) ? 0 : value;
        });

        totalEl.textContent = total.toFixed(2);
        submitBtn.disabled = total <= 0;
    }

    scoreInputs.forEach((input) => {
        input.addEventListener('input', updateTotalAndState);
    });

    function toggleRedefendReason() {
        const isRedefend = recommendationSelect.value === 'redefend';
        redefendReasonWrapper.style.display = isRedefend ? 'block' : 'none';
        redefendReasonInput.required = isRedefend;
    }

    recommendationSelect.addEventListener('change', toggleRedefendReason);

    updateTotalAndState();
    toggleRedefendReason();
});
</script>
@endsection
