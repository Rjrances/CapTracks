@extends('layouts.coordinator')

@section('title')
Rating Sheets
@endsection

@section('content')
<div class="container-fluid">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3">
                <header class="min-w-0 flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3 mb-2">
                        <h1 class="h3 mb-0 fw-semibold text-dark">{{ $schedule->group->name }}</h1>
                        <x-rating-sheet.stage-badge :schedule="$schedule" />
                    </div>
                    <p class="text-body-secondary small mb-0">
                        Aggregated panel scores, recommendations, and finalization for this defense session.
                    </p>
                </header>
                <div class="d-flex flex-wrap gap-2 flex-shrink-0 align-items-start">
                    <a href="{{ route('coordinator.defense.show', $schedule) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Defense details
                    </a>
                    <a href="{{ route('coordinator.rating-sheets.rate.show', $schedule) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-clipboard-check me-1"></i>Rate as coordinator
                    </a>
                    <a href="{{ route('coordinator.rating-sheets.print', $schedule) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-print me-1"></i>Print
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($errors->has('finalize'))
        <div class="alert alert-danger">{{ $errors->first('finalize') }}</div>
    @endif

    @if($isFinalized)
        <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong>Finalized:</strong>
                {{ optional($schedule->evaluationSummary->finalized_at)->format('M d, Y h:i A') }}
                by {{ $schedule->evaluationSummary->finalizedBy->name ?? 'Coordinator' }}.
                <span class="ms-2">Final recommendation:
                    @php
                        $finalRecommendation = $schedule->evaluationSummary->final_recommendation;
                        $finalBadgeClass = match($finalRecommendation) {
                            'pass' => 'bg-success',
                            'conditional_pass' => 'bg-warning text-dark',
                            'redefend' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <span class="badge {{ $finalBadgeClass }}">{{ str_replace('_', ' ', ucfirst($finalRecommendation)) }}</span>
                </span>
                <span class="ms-2">Average: {{ number_format((float) $schedule->evaluationSummary->final_average_score, 2) }}</span>
            </div>
            <form method="POST" action="{{ route('coordinator.rating-sheets.reopen', $schedule) }}" class="d-flex gap-2 align-items-center flex-wrap">
                @csrf
                <input type="text" name="reopen_reason" class="form-control form-control-sm" placeholder="Reason for reopening" required style="min-width: 260px;">
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-rotate-left me-1"></i>Reopen
                </button>
            </form>
        </div>
    @else
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-7">
                        <h2 class="h6 text-uppercase text-muted fw-semibold mb-3">Finalize defense result</h2>
                        @if($readiness['is_ready'])
                            <div class="alert alert-success py-2 mb-0 d-flex align-items-start gap-2">
                                <i class="fas fa-check-circle mt-1"></i>
                                <span>All required accepted panelists have submitted. You may finalize when ready.</span>
                            </div>
                        @else
                            <p class="text-muted mb-2 small fw-semibold text-uppercase">Outstanding submissions</p>
                            <p class="mb-2">Waiting for {{ count($missingPanelNames) }} required panelist(s) before finalization:</p>
                            <ul class="mb-0 ps-3">
                                @foreach($missingPanelNames as $name)
                                    <li class="mb-1">{{ $name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div class="col-lg-5">
                        <form method="POST" action="{{ route('coordinator.rating-sheets.finalize', $schedule) }}" class="border rounded-3 p-3 bg-light bg-opacity-50">
                            @csrf
                            <label for="final_notes" class="form-label small fw-semibold text-muted mb-1">Final notes <span class="fw-normal">(optional)</span></label>
                            <textarea name="final_notes" id="final_notes" rows="3" class="form-control form-control-sm mb-3" placeholder="Summary notes for the record (optional)"></textarea>
                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                                @disabled(! $readiness['is_ready'])
                                title="{{ $readiness['is_ready'] ? '' : 'Finalize is available after all required panelists submit.' }}"
                            >
                                <i class="fas fa-check-circle me-1"></i>Finalize result
                            </button>
                            @if(! $readiness['is_ready'])
                                <p class="small text-muted mb-0 mt-2 text-center">This action unlocks when every required panelist has submitted.</p>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-2 bg-white">
                    <h2 class="h6 mb-0 fw-semibold"><i class="fas fa-chart-line text-muted me-2"></i>Average score</h2>
                </div>
                <div class="card-body">
                    @if(!is_null($averageScore))
                        <p class="display-6 fw-semibold text-dark mb-0">{{ number_format((float) $averageScore, 2) }}</p>
                        <p class="small text-muted mb-0 mt-1">Mean of submitted panel totals</p>
                    @else
                        <p class="text-muted mb-0">No submissions yet.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-2 bg-white">
                    <h2 class="h6 mb-0 fw-semibold"><i class="fas fa-poll text-muted me-2"></i>Panel recommendations</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-success rounded-pill px-3 py-2">Pass: {{ $recommendationCounts['pass'] ?? 0 }}</span>
                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Conditional pass: {{ $recommendationCounts['conditional_pass'] ?? 0 }}</span>
                        <span class="badge bg-danger rounded-pill px-3 py-2">Re-defend: {{ $recommendationCounts['redefend'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header py-2 bg-white">
            <h2 class="h6 mb-0 fw-semibold"><i class="fas fa-user-graduate text-muted me-2"></i>Per-member final results</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Student</th>
                            <th>Student ID</th>
                            <th>Final score</th>
                            <th>Equivalent</th>
                            <th class="pe-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($memberResults as $result)
                            <tr>
                                <td class="ps-3">{{ $result['student_name'] }}</td>
                                <td>{{ $result['student_id'] }}</td>
                                <td>{{ is_null($result['final_score']) ? 'N/A' : number_format((float) $result['final_score'], 2) }}</td>
                                <td>{{ $result['grade_label'] }}</td>
                                <td class="pe-3">
                                    <span class="badge {{ $result['status'] === 'Passed' ? 'bg-success' : ($result['status'] === 'Pending' ? 'bg-secondary' : 'bg-danger') }}">
                                        {{ $result['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No member results yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2 bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h2 class="h6 mb-0 fw-semibold"><i class="fas fa-file-alt text-muted me-2"></i>Submitted rating sheets</h2>
        </div>
        <div class="card-body">
            @if($ratingSheets->isEmpty())
                <p class="text-muted mb-0">No rating sheets submitted yet.</p>
            @else
                @foreach($ratingSheets as $sheet)
                    <div class="border rounded-3 p-3 mb-3 mb-md-4 bg-light bg-opacity-25">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                            <div>
                                <span class="fw-semibold">{{ $sheet->faculty->name ?? 'Unknown faculty' }}</span>
                                <span class="text-muted small ms-2">{{ optional($sheet->submitted_at)->format('M d, Y h:i A') }}</span>
                            </div>
                            <span class="badge bg-primary rounded-pill">Total: {{ number_format((float) $sheet->total_score, 2) }}</span>
                        </div>

                        <div class="mb-2">
                            <span class="small text-muted me-2">Recommendation:</span>
                            @php
                                $recommendation = $sheet->recommendation;
                            @endphp
                            @php
                                $recommendationBadgeClass = match($recommendation) {
                                    'pass' => 'bg-success',
                                    'conditional_pass' => 'bg-warning text-dark',
                                    'redefend' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                                $recommendationLabel = match($recommendation) {
                                    'pass' => 'Pass',
                                    'conditional_pass' => 'Conditional pass',
                                    'redefend' => 'Re-defend',
                                    default => 'Not set',
                                };
                            @endphp
                            <span class="badge {{ $recommendationBadgeClass }}">{{ $recommendationLabel }}</span>
                        </div>

                        @if($sheet->recommendation_reason)
                            <div class="mb-3">
                                <span class="small fw-semibold text-muted">Re-defend reason</span>
                                <p class="mb-0 small">{{ $sheet->recommendation_reason }}</p>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-2 bg-white">
                                <thead class="table-light">
                                    <tr>
                                        <th>Criterion</th>
                                        <th class="text-end" style="width: 7rem">Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(($sheet->criteria ?? []) as $criterion)
                                        <tr>
                                            <td>{{ $criterion['name'] ?? '—' }}</td>
                                            <td class="text-end">{{ $criterion['score'] ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($sheet->remarks)
                            <div>
                                <span class="small fw-semibold text-muted">Remarks</span>
                                <p class="mb-0 small">{{ $sheet->remarks }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection
