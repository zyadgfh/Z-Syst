@props([
    'title' => null,
    'description' => null,
    'variant' => 'default',
])

@php
$variants = [
    'default' => 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700',
    'destructive' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
];

$classes = [
    'border',
    'rounded-lg',
    'p-4',
    'shadow-sm',
    $variants[$variant] ?? $variants['default'],
];
@endphp

<div {{ $attributes->merge(['class' => implode(' ', $classes)]) }}>
    @if($title)
        <div class="flex items-center space-x-2 mb-2">
            @if($variant === 'destructive')
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            @endif
            <h5 class="font-semibold text-sm text-gray-900 dark:text-gray-100">{{ $title }}</h5>
        </div>
    @endif
    
    @if($description)
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
    @endif
    
    {{ $slot }}
</div>