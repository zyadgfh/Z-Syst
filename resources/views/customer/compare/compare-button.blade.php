@if ($product)
@php
    $isComparing = in_array($product->id, session('compare_ids', []));
@endphp
<button onclick="toggleCompare(this, {{ $product->id }})"
    class="compare-toggle-btn {{ $isComparing ? 'compare-active' : '' }}"
    data-product-id="{{ $product->id }}"
    title="إضافة للمقارنة"
    style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid {{ $isComparing ? '#007aff' : '#d2d2d7' }}; background: {{ $isComparing ? '#e3f2fd' : 'transparent' }}; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 150ms ease; flex-shrink: 0;"
    onmouseenter="if(!this.classList.contains('compare-active')){this.style.borderColor='#007aff'; this.style.background='#f0f7ff'}"
    onmouseleave="if(!this.classList.contains('compare-active')){this.style.borderColor='#d2d2d7'; this.style.background='transparent'}">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="{{ $isComparing ? '#007aff' : '#86868b' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 20V10M12 20V4M6 20v-6"/>
    </svg>
</button>
@else
    <span style="width: 32px; height: 32px;"></span>
@endif
