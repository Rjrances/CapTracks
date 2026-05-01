@extends('layouts.coordinator')

@section('title', 'Compiled Rating Sheets')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Compiled Rating Sheets</h4>
            <small class="text-muted">{{ $schedule->group->name }} - {{ $schedule->stage_label }}</small>
        </div>
        <a href="{{ route('coordinator.defense.show', $schedule) }}" class="btn btn-outline-secondary">Back to Schedule</a>
    </div>

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
