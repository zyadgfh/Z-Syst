@props([
    'class' => '',
])

@php
$baseClasses = [
    'animate-pulse',
    'bg-gray-200',
    'rounded',
];

$classes = array_merge($baseClasses, explode(' ', $class));
@endphp

<div {{ $attributes->merge(['class' => implode(' ', $classes)]) }}>
    {{ $slot }}
</div>