@props([
    'variant' => 'default',
    'bordered' => true,
    'striped' => false,
    'hover' => true,
])

@php
$variants = [
    'default' => 'bg-white dark:bg-gray-800',
    'simple' => 'bg-transparent',
];

$borderClasses = $bordered ? 'border border-gray-200 dark:border-gray-700' : 'border-0';
$stripedClasses = $striped ? 'even:bg-gray-50 dark:even:bg-gray-900/50' : '';
$hoverClasses = $hover ? 'hover:bg-gray-50 dark:hover:bg-gray-900/50' : '';

$classes = [
    'w-full',
    'text-left',
    'rounded-lg',
    'overflow-hidden',
    $variants[$variant] ?? $variants['default'],
    $borderClasses,
];
@endphp

<div {{ $attributes->merge(['class' => implode(' ', $classes)]) }}>
    <table class="w-full">
        <thead class="bg-gray-50 dark:bg-gray-900/50">
            <tr>
                {{ $header }}
            </tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>

@once
<style>
    /* Table accessibility */
    table {
        border-collapse: collapse;
    }
    
    th {
        font-weight: 600;
        color: #374151;
    }
    
    .dark th {
        color: #e5e7eb;
    }
    
    td, th {
        padding: 0.75rem 1rem;
        text-align: left;
    }
    
    /* Focus styles for keyboard navigation */
    tr:focus-within {
        outline: 2px solid #3b82f6;
        outline-offset: -2px;
    }
</style>
@endonce