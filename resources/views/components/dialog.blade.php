@props([
    'title' => null,
    'show' => false,
])

@php
$classes = [
    'fixed',
    'inset-0',
    'z-50',
    'flex',
    'items-center',
    'justify-center',
    'bg-black/50',
    'backdrop-blur-sm',
    'transition-opacity',
    'duration-200',
];

$displayClasses = $show ? [] : ['hidden'];
@endphp

<div {{ $attributes->merge(['class' => implode(' ', array_merge($classes, $displayClasses))]) }}>
    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        @if($title)
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif
        
        {{ $slot }}
    </div>
</div>