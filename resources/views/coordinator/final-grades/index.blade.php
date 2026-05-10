@extends('layouts.coordinator')
@section('title', 'Final grades')
@section('content')
<div class="container-fluid">
    <x-coordinator.intro description="Class list of per-student defense grades by panel role. Scores appear only after each defense is finalized.">
        <a href="{{ route('coordinator.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Dashboard
        </a>
    </x-coordinator.intro>

    @if(!$activeTerm)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>No active academic term — showing groups without term filtering where applicable.
        </div>
    @endif

    @php
        $stageTabs = [
            '60' => '60%',
            '100' => '100%',
        ];
    @endphp

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-pills gap-2 mb-3" id="finalGradeStageTabs" role="tablist">
                @foreach($stageTabs as $stageKey => $stageLabel)
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link {{ $loop->first ? 'active' : '' }}"
                            id="tab-{{ $stageKey }}"
                            data-bs-toggle="pill"
                            data-bs-target="#pane-{{ $stageKey }}"
                            type="button"
                            role="tab"
                            aria-controls="pane-{{ $stageKey }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        >
                            {{ $stageLabel }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content" id="finalGradeStageTabContent">
                @foreach($stageTabs as $stageKey => $stageLabel)
                    @php
                        $finalizedCount = collect($rows)->filter(fn ($row) => (bool) ($row['stages'][$stageKey]['is_finalized'] ?? false))->count();
                        $pendingCount = collect($rows)->filter(function ($row) use ($stageKey) {
                            $stage = $row['stages'][$stageKey] ?? null;
                            return $stage && ! $stage['is_finalized'] && ! is_null($stage['schedule']);
                        })->count();
                    @endphp
                    <div
                        class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                        id="pane-{{ $stageKey }}"
                        role="tabpanel"
                        aria-labelledby="tab-{{ $stageKey }}"
                    >
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                            <span class="badge bg-success-subtle text-success-emphasis border">Finalized: {{ $finalizedCount }}</span>
                            <span class="badge bg-warning-subtle text-warning-emphasis border">Pending finalization: {{ $pendingCount }}</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Student</th>
                                        <th scope="col">Student ID</th>
                                        <th scope="col">Group</th>
                                        <th scope="col">Chair</th>
                                        <th scope="col">Member</th>
                                        <th scope="col">Coordinator</th>
                                        <th scope="col">Average</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rows as $row)
                                        @php
                                            $student = $row['student'];
                                            $group = $row['group'];
                                            $stage = $row['stages'][$stageKey];
                                            $schedule = $stage['schedule'];
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">{{ $student->name }}</td>
                                            <td>{{ $student->student_id }}</td>
                                            <td>
                                                <div>{{ $group->name }}</div>
                                                <small class="text-muted">{{ $group->offering->subject_code ?? '—' }}</small>
                                            </td>
                                            <td class="text-center">
                                                @if($stage['is_finalized'])
                                                    {{ is_null($stage['scores']['chair']) ? '—' : number_format((float) $stage['scores']['chair'], 2) }}
                                                @elseif($schedule)
                                                    <span class="text-muted">Pending</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($stage['is_finalized'])
                                                    {{ is_null($stage['scores']['member']) ? '—' : number_format((float) $stage['scores']['member'], 2) }}
                                                @elseif($schedule)
                                                    <span class="text-muted">Pending</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($stage['is_finalized'])
                                                    {{ is_null($stage['scores']['coordinator']) ? '—' : number_format((float) $stage['scores']['coordinator'], 2) }}
                                                @elseif($schedule)
                                                    <span class="text-muted">Pending</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center fw-semibold">
                                                @if($stage['is_finalized'])
                                                    {{ is_null($stage['average']) ? '—' : number_format((float) $stage['average'], 2) }}
                                                @elseif($schedule)
                                                    <span class="text-muted">Pending</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($stage['is_finalized'])
                                                    <span class="badge {{ $stage['status'] === 'Passed' ? 'bg-success' : ($stage['status'] === 'Failed' ? 'bg-danger' : 'bg-secondary') }}">
                                                        {{ $stage['status'] }}
                                                    </span>
                                                @elseif($schedule)
                                                    <a href="{{ route('coordinator.rating-sheets.show', $schedule) }}" class="btn btn-outline-warning btn-sm">
                                                        Finalize
                                                    </a>
                                                @else
                                                    <span class="text-muted">Not scheduled</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">No students in your offerings for this scope.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <p class="text-muted small mt-2 mb-0">
        Grades use the latest completed defense per stage. Scores appear only after <strong>Finalize result</strong>; otherwise cells stay pending.
    </p>
</div>
@endsection
