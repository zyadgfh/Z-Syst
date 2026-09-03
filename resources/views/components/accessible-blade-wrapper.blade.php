@props([
    'role' => null,
    'aria-label' => null,
    'aria-describedby' => null,
    'tabindex' => null,
])

@php
$attributes = $attributes->merge([
    'role' => $role,
    'aria-label' => $aria_label,
    'aria-describedby' => $aria_describedby,
    'tabindex' => $tabindex,
]);
@endphp

<div {{ $attributes }}>
    {{ $slot }}
</div>