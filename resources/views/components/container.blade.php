@props([
    'size' => 'md',
    'centered' => true,
])

@php
$sizes = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    '3xl' => 'max-w-3xl',
    '4xl' => 'max-w-4xl',
    '5xl' => 'max-w-5xl',
    '6xl' => 'max-w-6xl',
    '7xl' => 'max-w-7xl',
    'full' => 'max-w-full',
];

$classes = [
    'w-full',
    'mx-auto',
    'px-4',
    'sm:px-6',
    'lg:px-8',
    $sizes[$size] ?? $sizes['7xl'],
    $centered ? 'mx-auto' : '',
];
@endphp

<div {{ $attributes->merge(['class' => implode(' ', array_filter($classes))]) }}>
    {{ $slot }}
</div>