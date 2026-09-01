@if ($product)
@php
    $isWishlisted = \App\Models\Wishlist::isWishlisted($product->id);
@endphp
<button onclick="toggleWishlist(this, {{ $product->id }})"
    class="wishlist-toggle-btn"
    data-product-id="{{ $product->id }}"
    title="{{ $isWishlisted ? __('إزالة من المفضلة') : __('أضف للمفضلة') }}"
    style="width: 36px; height: 36px; border-radius: 50%; border: 1px solid #d2d2d7; background: #fff; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 150ms ease, border-color 150ms ease, background 150ms ease; flex-shrink: 0;"
    onmouseenter="this.style.borderColor='#ff3b30'"
    onmouseleave="this.style.borderColor='#d2d2d7'">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="{{ $isWishlisted ? '#ff3b30' : 'none' }}" stroke="{{ $isWishlisted ? '#ff3b30' : '#86868b' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
        style="transition: fill 200ms ease, stroke 200ms ease;">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
    </svg>
</button>

<script>
(function() {
    if (window._wishlistInit) return;
    window._wishlistInit = true;

    window.toggleWishlist = function(btn, productId) {
        btn.style.transform = 'scale(0.85)';
        setTimeout(() => btn.style.transform = 'scale(1)', 150);

        fetch('/wishlist/toggle/' + productId, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const svg = btn.querySelector('svg');
                if (data.added) {
                    svg.setAttribute('fill', '#ff3b30');
                    svg.setAttribute('stroke', '#ff3b30');
                    btn.style.borderColor = '#ff3b30';
                    btn.style.background = '#fff0f0';
                } else {
                    svg.setAttribute('fill', 'none');
                    svg.setAttribute('stroke', '#86868b');
                    btn.style.borderColor = '#d2d2d7';
                    btn.style.background = '#fff';
                }

                // Update all wishlist badges
                document.querySelectorAll('.wishlist-count-badge').forEach(badge => {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? '' : 'none';
                });
            }
        })
        .catch(() => {
            // Fallback: redirect to login
            window.location.href = '/customer/login';
        });
    };
})();
</script>
@else
    <span style="width: 36px; height: 36px;"></span>
@endif
