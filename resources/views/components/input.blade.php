@props([
    'type' => 'text',
    'name' => null,
    'id' => null,
    'value' => '',
    'placeholder' => '',
    'disabled' => false,
    'readonly' => false,
    'required' => false,
    'error' => null,
    'label' => null,
    'hint' => null,
])

@php
$inputClasses = [
    'w-full',
    'px-4',
    'py-2',
    'border',
    'rounded-lg',
    'transition-colors',
    'duration-200',
    'focus:outline-none',
    'focus:ring-2',
    'focus:ring-blue-500',
    'focus:border-transparent',
    $error ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600',
    $disabled ? 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed' : 'bg-white dark:bg-gray-900',
    $readonly ? 'bg-gray-50 dark:bg-gray-800' : '',
    'dark:text-white',
];

$labelClasses = [
    'block',
    'text-sm',
    'font-medium',
    'mb-1',
    $error ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300',
];

$errorClasses = [
    'mt-1',
    'text-sm',
    'text-red-600 dark:text-red-400',
];

$hintClasses = [
    'mt-1',
    'text-sm',
    'text-gray-500 dark:text-gray-400',
];
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $id ?? $name }}" {{ $attributes->merge(['class' => implode(' ', $labelClasses)]) }}>
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id ?? $name }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => implode(' ', $inputClasses)]) }}
    >

    @if($error)
        <p {{ $attributes->merge(['class' => implode(' ', $errorClasses)]) }}>
            {{ $error }}
        </p>
    @endif

    @if($hint && !$error)
        <p {{ $attributes->merge(['class' => implode(' ', $hintClasses)]) }}>
            {{ $hint }}
        </p>
    @endif
</div>