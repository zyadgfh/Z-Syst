@props([
    'variant' => 'default',
    'padding' => 'md',
    'shadow' => 'md',
    'border' => true,
])

@php
$variants = [
    'default' => 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700',
    'primary' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
    'success' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
    'danger' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
    'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
];

$paddings = [
    'none' => 'p-0',
    'sm' => 'p-4',
    'md' => 'p-6',
    'lg' => 'p-8',
    'xl' => 'p-10',
];

$shadows = [
    'none' => 'shadow-none',
    'sm' => 'shadow-sm',
    'md' => 'shadow-md',
    'lg' => 'shadow-lg',
    'xl' => 'shadow-xl',
];

$classes = [
    'rounded-lg',
    'transition-all',
    'duration-200',
    $variants[$variant] ?? $variants['default'],
    $paddings[$padding] ?? $paddings['md'],
    $shadows[$shadow] ?? $shadows['md'],
    $border ? 'border' : 'border-0',
];
@endphp

<div {{ $attributes->merge(['class' => implode(' ', $classes)]) }}>
    {{ $slot }}
</div>