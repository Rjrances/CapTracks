{{--
    Expects: $panel with keys: label, kind, iframeSrc, downloadUrl
--}}
@php
    $iframeSrc = $panel['iframeSrc'] ?? null;
    $kind = $panel['kind'] ?? 'unknown';
@endphp
<div class="border rounded bg-light h-100 d-flex flex-column" style="min-height: 65vh;">
    <div class="px-2 py-1 small text-muted border-bottom bg-white rounded-top">
        <strong>{{ $panel['label'] ?? '' }}</strong>
    </div>
    @if($iframeSrc)
        <iframe
            src="{{ $iframeSrc }}"
            class="flex-grow-1 w-100 rounded-bottom"
            style="min-height: 62vh; border: 0;"
            title="Document preview"
            loading="lazy"
        ></iframe>
    @else
        <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center p-4 text-center text-muted">
            <p class="mb-2">No in-browser preview for this format ({{ $kind }}).</p>
            <a href="{{ $panel['downloadUrl'] ?? '#' }}" class="btn btn-primary btn-sm" target="_blank" rel="noopener">
                <i class="fas fa-download me-1"></i>Open / download
            </a>
        </div>
    @endif
</div>
