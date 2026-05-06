@extends('layouts.coordinator')

@section('title')
Rating sheets — {{ $schedule->group->name }}
@endsection

@section('content')
<div class="container-fluid">
        <x-coordinator.intro :description="'Aggregated panel scores and recommendations for '.$schedule->group->name.' — '.$schedule->stage_label.'.'">
            <a href="{{ route('coordinator.defense.show', $schedule) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Defense details
            </a>
            <a href="{{ route('coordinator.rating-sheets.rate.show', $schedule) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-clipboard-check me-1"></i>Rate as Coordinator
            </a>
            <a href="{{ route('coordinator.rating-sheets.print', $schedule) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-print me-1"></i>Print
            </a>
        </x-coordinator.intro>

    @if($errors->has('finalize'))
        <div class="alert alert-danger">{{ $errors->first('finalize') }}</div>
    @endif

    @if($isFinalized)
        <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong>Finalized:</strong>
                {{ optional($schedule->evaluationSummary->finalized_at)->format('M d, Y h:i A') }}
                by {{ $schedule->evaluationSummary->finalizedBy->name ?? 'Coordinator' }}.
                <span class="ms-2">Final Recommendation:
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
            <form method="POST" action="{{ route('coordinator.rating-sheets.reopen', $schedule) }}" class="d-flex gap-2 align-items-center">
                @csrf
                <input type="text" name="reopen_reason" class="form-control form-control-sm" placeholder="Reason for reopening" required style="min-width: 260px;">
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-rotate-left me-1"></i>Reopen
                </button>
            </form>
        </div>
    @else
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h6 class="mb-1">Finalize Result</h6>
                        @if($readiness['is_ready'])
                            <p class="text-success mb-0">All required accepted panelists have submitted. You can finalize now.</p>
                        @else
                            <p class="text-muted mb-2">
                                Waiting for {{ count($missingPanelNames) }} required panelist(s):
                            </p>
                            <ul class="mb-0">
                                @foreach($missingPanelNames as $name)
                                    <li>{{ $name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('coordinator.rating-sheets.finalize', $schedule) }}" class="d-flex flex-column gap-2" style="min-width: 320px;">
                        @csrf
                        <textarea name="final_notes" rows="2" class="form-control" placeholder="Optional final notes"></textarea>
                        <button type="submit" class="btn btn-primary" {{ $readiness['is_ready'] ? '' : 'disabled' }}>
                            <i class="fas fa-check-circle me-1"></i>Finalize Result
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <strong>Average Score:</strong>
            @if(!is_null($averageScore))
                {{ number_format((float) $averageScore, 2) }}
            @else
                No submissions yet
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <strong>Panel Recommendations:</strong>
            <div class="mt-2 d-flex gap-2 flex-wrap">
                <span class="badge bg-success">Pass: {{ $recommendationCounts['pass'] ?? 0 }}</span>
                <span class="badge bg-warning text-dark">Conditional Pass: {{ $recommendationCounts['conditional_pass'] ?? 0 }}</span>
                <span class="badge bg-danger">Re-Defend: {{ $recommendationCounts['redefend'] ?? 0 }}</span>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <strong>Per-member Final Results</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Final Score</th>
                            <th>Equivalent</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($memberResults as $result)
                            <tr>
                                <td>{{ $result['student_name'] }}</td>
                                <td>{{ $result['student_id'] }}</td>
                                <td>{{ is_null($result['final_score']) ? 'N/A' : number_format((float) $result['final_score'], 2) }}</td>
                                <td>{{ $result['grade_label'] }}</td>
                                <td>
                                    <span class="badge {{ $result['status'] === 'Passed' ? 'bg-success' : ($result['status'] === 'Pending' ? 'bg-secondary' : 'bg-danger') }}">
                                        {{ $result['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No member results yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($ratingSheets->isEmpty())
                <div class="text-muted">No rating sheets submitted yet.</div>
            @else
                @foreach($ratingSheets as $sheet)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>{{ $sheet->faculty->name ?? 'Unknown Faculty' }}</strong>
                                <small class="text-muted ms-2">{{ optional($sheet->submitted_at)->format('M d, Y h:i A') }}</small>
                            </div>
                            <span class="badge bg-primary">Total: {{ number_format((float) $sheet->total_score, 2) }}</span>
                        </div>

                        <div class="mb-2">
                            <strong>Recommendation:</strong>
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
                                    'conditional_pass' => 'Conditional Pass',
                                    'redefend' => 'Re-Defend',
                                    default => 'Not Set',
                                };
                            @endphp
                            <span class="badge {{ $recommendationBadgeClass }}">{{ $recommendationLabel }}</span>
                        </div>

                        @if($sheet->recommendation_reason)
                            <div class="mb-2">
                                <strong>Re-Defend Reason:</strong>
                                <p class="mb-0">{{ $sheet->recommendation_reason }}</p>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-sm mb-2">
                                <thead>
                                    <tr>
                                        <th>Criterion</th>
                                        <th>Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(($sheet->criteria ?? []) as $criterion)
                                        <tr>
                                            <td>{{ $criterion['name'] ?? '-' }}</td>
                                            <td>{{ $criterion['score'] ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($sheet->remarks)
                            <div>
                                <strong>Remarks:</strong>
                                <p class="mb-0">{{ $sheet->remarks }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection
