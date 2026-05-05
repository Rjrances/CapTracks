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
        </x-coordinator.intro>

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
                            @if($recommendation === 'pass')
                                <span class="badge bg-success">Pass</span>
                            @elseif($recommendation === 'conditional_pass')
                                <span class="badge bg-warning text-dark">Conditional Pass</span>
                            @elseif($recommendation === 'redefend')
                                <span class="badge bg-danger">Re-Defend</span>
                            @else
                                <span class="badge bg-secondary">Not Set</span>
                            @endif
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
