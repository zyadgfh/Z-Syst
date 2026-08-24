@props(['product', 'compact' => false])

<button onclick="addToCart({{ $product->id }}, this)"
    class="{{ $compact ? 'cart-add-btn-compact' : 'cart-add-btn' }}">
    @if ($compact)
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
    @else
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
        <span>{{ __('Add to Cart') }}</span>
    @endif
</button>

<style>
    .cart-add-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 12px;
        background: #15803d;
        color: white;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 150ms ease;
    }
    .cart-add-btn:hover {
        background: #166534;
    }
    .cart-add-btn:active {
        transform: scale(0.96);
    }
    .cart-add-btn.adding {
        background: #10b981;
    }
    .cart-add-btn-compact {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #15803d;
        color: white;
        border: none;
        cursor: pointer;
        transition: all 150ms ease;
    }
    .cart-add-btn-compact:hover {
        background: #166534;
    }
    .cart-add-btn-compact:active {
        transform: scale(0.92);
    }
    @media (prefers-reduced-motion: reduce) {
        .cart-add-btn, .cart-add-btn-compact { transition: none; }
    }
</style>

<script>
function addToCart(productId, btn) {
    btn.classList.add('adding');
    btn.disabled = true;

    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ product_id: productId, quantity: 1 })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateCartBadge(data.cart_count);
            // Brief success animation
            const origHTML = btn.innerHTML;
            btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>`;
            setTimeout(() => {
                btn.innerHTML = origHTML;
                btn.classList.remove('adding');
                btn.disabled = false;
            }, 1200);
        } else {
            alert(data.error || 'Failed to add to cart');
            btn.classList.remove('adding');
            btn.disabled = false;
        }
    })
    .catch(() => {
        btn.classList.remove('adding');
        btn.disabled = false;
    });
}
</script>
