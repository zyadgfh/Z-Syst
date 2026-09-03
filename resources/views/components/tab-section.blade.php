@props([
    'title' => '',
    'icon' => null,
    'badge' => null,
    'badgeColor' => 'secondary',
    'id' => null,
])

<div {{ $attributes->merge(['class' => 'tab-pane fade', 'id' => $id]) }}>
    @if($title || $icon || $badge)
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="text-muted mb-0">
                @if($icon)
                    <i class="{{ $icon }} me-1"></i>
                @endif
                {{ $title }}
            </h6>
            @if($badge)
                <span class="badge badge-soft-{{ $badgeColor }}">{{ $badge }}</span>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
