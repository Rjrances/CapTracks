@props(['schedule'])

@php
    $stage = (string) ($schedule->stage ?? '');
    $palette = match ($stage) {
        'proposal' => 'bg-info',
        '60' => 'bg-warning text-dark',
        '100' => 'bg-success',
        default => 'bg-secondary',
    };
    $label = $schedule->stage_label ?? 'Defense';
@endphp

<span
    role="status"
    title="Defense stage for this rating"
    {{ $attributes->merge(['class' => 'badge '.$palette.' rounded-pill fw-semibold px-3 py-2 fs-6']) }}
>
    {{ $label }}
</span>
