@props([
    'headers' => [],
    'striped' => false,
    'hover' => true,
    'bordered' => true,
    'emptyIcon' => 'fas fa-inbox',
    'emptyText' => 'No data found.',
    'emptyColspan' => null,
])

@php
$colspan = $emptyColspan ?? count($headers);
@endphp

<div class="table-responsive">
    <table class="table table-{{ $bordered ? '' : 'borderless' }} table-sm {{ $hover ? 'table-hover' : '' }} {{ $striped ? 'table-striped' : '' }} mb-0">
        @if(count($headers) > 0)
            <thead class="table-light">
                <tr>
                    @foreach($headers as $header)
                        <th @if(isset($header['class'])) class="{{ $header['class'] }}" @endif
                            @if(isset($header['style'])) style="{{ $header['style'] }}" @endif>
                            {{ $header['label'] ?? $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
            @if(isset($empty) && $empty)
                <tr>
                    <td colspan="{{ $colspan }}" class="text-center text-muted py-4">
                        <i class="{{ $emptyIcon }} fa-2x mb-2 d-block"></i>
                        {{ $emptyText }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
