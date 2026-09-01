@props([
    'cols' => 1,
    'gap' => 'md',
    'responsive' => true,
])

@php
$gaps = [
    'none' => 'gap-0',
    'sm' => 'gap-2',
    'md' => 'gap-4',
    'lg' => 'gap-6',
    'xl' => 'gap-8',
];

$baseClasses = [
    'grid',
    $gaps[$gap] ?? $gaps['md'],
];

if ($responsive) {
    $responsiveClasses = [
        'grid-cols-1',
        'sm:grid-cols-2',
        'md:grid-cols-3',
        'lg:grid-cols-4',
        'xl:grid-cols-5',
        '2xl:grid-cols-6',
    ];
    $classes = array_merge($baseClasses, $responsiveClasses);
} else {
    $classes = array_merge($baseClasses, ['grid-cols-' . $cols]);
}
@endphp

<div {{ $attributes->merge(['class' => implode(' ', $classes)]) }}>
    {{ $slot }}
</div>