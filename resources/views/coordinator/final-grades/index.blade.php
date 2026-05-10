@extends('layouts.coordinator')
@section('title', 'Final grades')
@section('content')
<div class="container-fluid">
    <x-coordinator.intro description="Finalized defense outcomes by stage for groups in your coordinated offerings (active term when set).">
        <a href="{{ route('coordinator.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Dashboard
        </a>
    </x-coordinator.intro>

    @if(!$activeTerm)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>No active academic term — showing groups without term filtering where applicable.
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Group</th>
                            <th scope="col">Offering</th>
                            <th scope="col">Adviser</th>
                            <th scope="col" class="text-center">Proposal</th>
                            <th scope="col" class="text-center">60%</th>
                            <th scope="col" class="text-center">100%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php $g = $row['group']; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $g->name }}</td>
                                <td>
                                    @if($g->offering)
                                        <span class="text-muted">{{ $g->offering->subject_code }}</span>
                                        <small class="d-block text-muted">{{ Str::limit($g->offering->subject_title, 42) }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $g->adviser->name ?? '—' }}</td>
                                @foreach(['proposal', '60', '100'] as $stageKey)
                                    @php
                                        $schedule = $row['schedules'][$stageKey] ?? null;
                                        $summary = $schedule?->evaluationSummary;
                                    @endphp
                                    <td class="text-center small">
                                        @if($summary)
                                            <div class="fw-semibold">{{ number_format((float) $summary->final_average_score, 2) }}</div>
                                            @php
                                                $rec = $summary->final_recommendation;
                                                $recLabel = match ($rec) {
                                                    'pass' => 'Pass',
                                                    'conditional_pass' => 'Conditional',
                                                    'redefend' => 'Redefend',
                                                    default => $rec ? ucfirst(str_replace('_', ' ', (string) $rec)) : '—',
                                                };
                                            @endphp
                                            <span class="badge bg-secondary">{{ $recLabel }}</span>
                                            <div class="mt-1">
                                                <a href="{{ route('coordinator.rating-sheets.show', $schedule) }}" class="small">Rating sheets</a>
                                            </div>
                                        @elseif($schedule)
                                            <span class="text-muted">Finalizing pending</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No groups in your offerings for this scope.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <p class="text-muted small mt-2 mb-0">
        Cells show the latest <strong>completed</strong> defense per stage with a finalized rating summary. Empty means no completed defense for that stage yet.
    </p>
</div>
@endsection
