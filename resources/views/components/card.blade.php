@props([
    'variant' => 'default',
    'padding' => 'md',
    'shadow' => 'md',
    'border' => true,
])

@php
$variants = [
    'default' => 'card',
    'primary' => 'card bg-primary/10 border-primary',
    'success' => 'card bg-success/10 border-success',
    'danger' => 'card bg-destructive/10 border-destructive',
    'warning' => 'card bg-warning/10 border-warning',
];

$paddings = [
    'none' => 'p-0',
    'sm' => 'p-sm',
    'md' => 'p-lg',
    'lg' => 'p-xl',
    'xl' => 'p-2xl',
];

$shadows = [
    'none' => 'shadow-none',
    'sm' => 'shadow-sm',
    'md' => 'shadow-md',
    'lg' => 'shadow-lg',
    'xl' => 'shadow-xl',
];

$classes = [
    $variants[$variant] ?? $variants['default'],
    $shadows[$shadow] ?? $shadows['md'],
];
@endphp

<div {{ $attributes->merge(['class' => implode(' ', $classes)]) }}>
    {{ $slot }}
</div>