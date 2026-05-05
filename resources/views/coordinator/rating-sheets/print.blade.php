<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Defense Rating Summary - {{ $schedule->group->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111827; }
        h1, h2, h3, p { margin: 0 0 8px 0; }
        .meta { margin-bottom: 16px; }
        .card { border: 1px solid #d1d5db; border-radius: 8px; padding: 12px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; font-size: 12px; }
        th { background: #f3f4f6; }
        .muted { color: #6b7280; }
        .summary { margin: 12px 0; padding: 10px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; }
        @media print { .no-print { display: none; } body { margin: 10px; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print</button>

    <h1>Defense Rating Summary</h1>
    <div class="meta">
        <p><strong>Group:</strong> {{ $schedule->group->name }}</p>
        <p><strong>Stage:</strong> {{ $schedule->stage_label }}</p>
        <p><strong>Schedule:</strong> {{ $schedule->start_at?->format('M d, Y h:i A') }} | {{ $schedule->room }}</p>
    </div>

    <div class="summary">
        <p><strong>Average Score:</strong> {{ number_format((float) $averageScore, 2) }}</p>
        @if($schedule->evaluationSummary && $schedule->status === 'completed')
            <p><strong>Final Recommendation:</strong> {{ str_replace('_', ' ', ucfirst($schedule->evaluationSummary->final_recommendation)) }}</p>
            <p><strong>Finalized At:</strong> {{ optional($schedule->evaluationSummary->finalized_at)->format('M d, Y h:i A') }}</p>
            <p><strong>Finalized By:</strong> {{ $schedule->evaluationSummary->finalizedBy->name ?? 'Coordinator' }}</p>
            @if($schedule->evaluationSummary->final_notes)
                <p><strong>Final Notes:</strong> {{ $schedule->evaluationSummary->final_notes }}</p>
            @endif
        @else
            <p class="muted">Not finalized yet.</p>
        @endif
    </div>

    <h3>Per-member Final Results</h3>
    <table>
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
                    <td>{{ number_format((float) $result['final_score'], 2) }}</td>
                    <td>{{ $result['grade_label'] }}</td>
                    <td>{{ $result['status'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">No member results yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @foreach($ratingSheets as $sheet)
        <div class="card">
            <h3>{{ $sheet->faculty->name ?? 'Unknown Faculty' }}</h3>
            <p class="muted">Submitted: {{ optional($sheet->submitted_at)->format('M d, Y h:i A') ?? 'N/A' }}</p>
            <p><strong>Total Score:</strong> {{ number_format((float) $sheet->total_score, 2) }}</p>
            <p><strong>Recommendation:</strong> {{ str_replace('_', ' ', ucfirst((string) $sheet->recommendation)) }}</p>
            @if($sheet->recommendation_reason)
                <p><strong>Re-Defend Reason:</strong> {{ $sheet->recommendation_reason }}</p>
            @endif
            @if($sheet->remarks)
                <p><strong>Remarks:</strong> {{ $sheet->remarks }}</p>
            @endif

            <table>
                <thead>
                    <tr>
                        <th>Criterion</th>
                        <th>Score</th>
                        <th>Max</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($sheet->criteria ?? []) as $criterion)
                        <tr>
                            <td>{{ $criterion['name'] ?? '-' }}</td>
                            <td>{{ number_format((float) ($criterion['score'] ?? 0), 2) }}</td>
                            <td>{{ number_format((float) ($criterion['max_points'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>

