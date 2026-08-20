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

<div class="input-group">
    @if($label)
        <label for="{{ $id ?? $name }}" class="input-label">
            {{ $label }}
            @if($required)
                <span class="text-destructive">*</span>
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
        {{ $attributes->merge(['class' => 'input ' . ($error ? 'input-error' : '')]) }}
    >

    @if($error)
        <p class="input-error-message">
            {{ $error }}
        </p>
    @endif

    @if($hint && !$error)
        <p class="input-helper">
            {{ $hint }}
        </p>
    @endif
</div>