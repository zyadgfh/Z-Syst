{{-- Setting Row Partial --}}
@php
    $def = $setting['definition'];
    $effectiveValue = $setting['effective_value'];
    $source = $setting['source'];
    $isOverridden = $setting['is_overridden'];
    $canEditSystem = auth()->user()->can('settings.system.edit');
    $canEditOrg = auth()->user()->can('settings.organization.edit');
@endphp

<div class="setting-row {{ $isOverridden ? 'has-override' : '' }}" id="setting-{{ str_replace('.', '-', $def->key) }}">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="flex-1 min-w-200">
            <div class="setting-name">
                {{ __($def->name) }}
                @if($isOverridden)
                    <span class="badge bg-primary bg-opacity-10 text-primary ms-1" class="text-10">{{ __('common.Override') }}</span>
                @endif
            </div>
            <div class="setting-description">{{ __($def->description) }}</div>
            <div class="setting-key mt-1">{{ $def->key }}</div>
            <div class="mt-1">
                @php
                    $sourceLabels = [
                        'default' => __('common.Default'),
                        'system' => __('common.System'),
                        'organization' => __('settings.Organization'),
                        'branch' => __('common.Branch'),
                        'role' => __('roles.Role'),
                        'user' => __('common.User'),
                    ];
                @endphp
                <span class="source-badge source-{{ $source }}">{{ $sourceLabels[$source] ?? $source }}</span>
            </div>
        </div>
        <div class="setting-control">
            @if($def->type === 'boolean')
                <label class="toggle-switch">
                    <input type="checkbox"
                           data-key="{{ $def->key }}"
                           data-type="boolean"
                           {{ $effectiveValue ? 'checked' : '' }}
                           onchange="trackChange(this)">
                    <span class="toggle-slider"></span>
                </label>

            @elseif($def->type === 'select' && isset($def->type_options['options']))
                <select class="form-select form-select-sm" class="w-200"
                        data-key="{{ $def->key }}"
                        data-type="select"
                        onchange="trackChange(this)">
                    @foreach($def->type_options['options'] as $option)
                        <option value="{{ $option['value'] }}" {{ $effectiveValue == $option['value'] ? 'selected' : '' }}>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>

            @elseif($def->type === 'integer')
                <input type="number"
                       class="form-control form-control-sm"
                       class="w-120"
                       data-key="{{ $def->key }}"
                       data-type="integer"
                       value="{{ $effectiveValue ?? '' }}"
                       onchange="trackChange(this)">

            @elseif($def->type === 'decimal')
                <input type="number"
                       class="form-control form-control-sm"
                       class="w-120"
                       data-key="{{ $def->key }}"
                       data-type="decimal"
                       value="{{ $effectiveValue ?? '' }}"
                       step="0.01"
                       onchange="trackChange(this)">

            @else
                <input type="text"
                       class="form-control form-control-sm"
                       class="w-200"
                       data-key="{{ $def->key }}"
                       data-type="string"
                       value="{{ $effectiveValue ?? '' }}"
                       onchange="trackChange(this)">
            @endif
        </div>
    </div>
</div>
