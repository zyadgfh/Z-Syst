@props([
    'items' => [],
    'title' => null,
    'compact' => false,
])

@if($title)
    <h6 class="text-muted mb-2">{{ $title }}</h6>
@endif

<dl class="mb-0 {{ $compact ? 'mb-0' : 'row' }}">
    @foreach($items as $key => $value)
        @if($compact)
            <div class="d-flex justify-content-between py-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                <span class="text-muted">{{ $key }}</span>
                <span class="fw-semibold">{{ $value }}</span>
            </div>
        @else
            <dt class="col-sm-4">{{ $key }}</dt>
            <dd class="col-sm-8 mb-2">{{ $value }}</dd>
        @endif
    @endforeach
</dl>
