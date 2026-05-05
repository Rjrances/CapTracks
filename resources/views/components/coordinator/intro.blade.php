@props(['description' => ''])
<div class="row mt-n2">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-3">
            @if(filled($description))
                <p class="text-body-secondary mb-0">{{ $description }}</p>
            @endif
            @isset($slot)
                @if(!$slot->isEmpty())
                    <div class="d-flex gap-2 flex-shrink-0 align-items-start flex-wrap">{{ $slot }}</div>
                @endif
            @endisset
        </div>
    </div>
</div>
