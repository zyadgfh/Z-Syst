@props([
    'value' => '0',
    'label' => '',
    'icon' => null,
    'color' => 'primary',
    'subtitle' => null,
    'size' => 'md',
])

@php
$colorMap = [
    'primary' => 'bg-primary bg-opacity-10 text-primary',
    'success' => 'bg-success bg-opacity-10 text-success',
    'danger' => 'bg-danger bg-opacity-10 text-danger',
    'warning' => 'bg-warning bg-opacity-10 text-warning',
    'info' => 'bg-info bg-opacity-10 text-info',
    'secondary' => 'bg-secondary bg-opacity-10 text-secondary',
];

$valueColorMap = [
    'primary' => 'text-primary',
    'success' => 'text-success',
    'danger' => 'text-danger',
    'warning' => 'text-warning',
    'info' => 'text-info',
    'secondary' => 'text-secondary',
];

$sizeClasses = [
    'sm' => 'py-2',
    'md' => 'py-3',
    'lg' => 'py-4',
];

$iconBg = $colorMap[$color] ?? $colorMap['primary'];
$valueColor = $valueColorMap[$color] ?? $valueColorMap['primary'];
$padClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div {{ $attributes->merge(['class' => "card border-0 shadow-sm"]) }}>
    <div class="card-body {{ $padClass }}">
        <div class="d-flex align-items-center">
            @if($icon)
                <div class="flex-shrink-0 me-3">
                    <div class="{{ $iconBg }} rounded-circle p-3">
                        <i class="{{ $icon }}"></i>
                    </div>
                </div>
            @endif
            <div>
                <div class="fs-{{ $size === 'lg' ? '3' : ($size === 'sm' ? '5' : '4') }} fw-bold {{ $valueColor }}">
                    {{ $value }}
                </div>
                <small class="text-muted">{{ $label }}</small>
                @if($subtitle)
                    <div><small class="text-muted">{{ $subtitle }}</small></div>
                @endif
            </div>
        </div>
    </div>
</div>
