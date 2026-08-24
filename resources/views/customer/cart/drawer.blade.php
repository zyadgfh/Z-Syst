<!-- Cart Drawer (Side Panel) -->
<div id="cart-drawer" class="cart-drawer" style="display: none;">
    <!-- Backdrop -->
    <div class="cart-drawer-backdrop" onclick="closeCartDrawer()"></div>

    <!-- Drawer Panel -->
    <div class="cart-drawer-panel">
        <!-- Header -->
        <div class="cart-drawer-header">
            <div class="flex items-center gap-2">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#111827" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
                <span class="font-semibold" style="color: #111827;">{{ __('Shopping Cart') }}</span>
                <span id="cart-drawer-count" class="text-xs px-2 py-0.5 rounded-full" style="background: #f3f4f6; color: #6b7280;">0</span>
            </div>
            <button onclick="closeCartDrawer()" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-100 transition-all">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Cart Items -->
        <div id="cart-drawer-items" class="cart-drawer-body">
            <!-- Items loaded via JS -->
            <div class="cart-drawer-empty" id="cart-drawer-empty">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
                <p class="mt-3 text-sm" style="color: #9ca3af;">{{ __('Your cart is empty') }}</p>
                <a href="{{ route('catalog.index') }}" class="mt-3 inline-block px-4 py-2 rounded-lg text-sm font-medium text-white" style="background: #15803d; text-decoration: none;">
                    {{ __('Browse Products') }}
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div id="cart-drawer-footer" class="cart-drawer-footer" style="display: none;">
            <div class="flex justify-between items-center mb-4">
                <span class="font-medium" style="color: #6b7280;">{{ __('Subtotal') }}</span>
                <span id="cart-drawer-subtotal" class="font-bold text-lg" style="color: #111827;">$0.00</span>
            </div>
            <a href="{{ route('checkout') }}" id="cart-checkout-btn"
               class="block w-full py-3 rounded-xl text-white font-semibold text-sm text-center transition-all"
               style="background: #15803d; text-decoration: none;"
               onmousedown="this.style.transform='scale(0.98)'" onmouseup="this.style.transform='scale(1)'">
                {{ __('Proceed to Checkout') }}
            </a>
        </div>
    </div>
</div>

<style>
    .cart-drawer {
        position: fixed;
        inset: 0;
        z-index: 9999;
        pointer-events: none;
    }
    .cart-drawer.active {
        pointer-events: all;
    }
    .cart-drawer-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(4px);
        opacity: 0;
        transition: opacity 250ms ease;
    }
    .cart-drawer.active .cart-drawer-backdrop {
        opacity: 1;
    }
    .cart-drawer-panel {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        max-width: 420px;
        background: white;
        transform: translateX(100%);
        transition: transform 300ms cubic-bezier(0.32, 0.72, 0, 1);
        display: flex;
        flex-direction: column;
        box-shadow: -4px 0 24px rgba(0, 0, 0, 0.12);
    }
    [dir="rtl"] .cart-drawer-panel {
        right: auto;
        left: 0;
        transform: translateX(-100%);
    }
    [dir="rtl"] .cart-drawer.active .cart-drawer-panel {
        transform: translateX(0);
    }
    .cart-drawer.active .cart-drawer-panel {
        transform: translateX(0);
    }
    .cart-drawer-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #f3f4f6;
    }
    .cart-drawer-body {
        flex: 1;
        overflow-y: auto;
        padding: 16px 20px;
    }
    .cart-drawer-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        min-height: 300px;
    }
    .cart-drawer-footer {
        padding: 16px 20px;
        border-top: 1px solid #f3f4f6;
        background: white;
    }
    .cart-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f9fafb;
        animation: cartItemIn 250ms ease;
    }
    @keyframes cartItemIn {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    .cart-item-image {
        width: 56px;
        height: 56px;
        border-radius: 10px;
        background: #f3f4f6;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .cart-item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .cart-item-details {
        flex: 1;
        min-width: 0;
    }
    .cart-item-name {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cart-item-price {
        font-size: 12px;
        color: #6b7280;
        margin-top: 2px;
    }
    .cart-item-qty {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 6px;
    }
    .cart-qty-btn {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 14px;
        color: #374151;
        transition: all 150ms ease;
    }
    .cart-qty-btn:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }
    .cart-qty-btn:active {
        transform: scale(0.92);
    }
    .cart-item-total {
        font-size: 14px;
        font-weight: 600;
        color: #111827;
        flex-shrink: 0;
    }
    .cart-item-remove {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: none;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #9ca3af;
        transition: all 150ms ease;
        flex-shrink: 0;
    }
    .cart-item-remove:hover {
        background: #fef2f2;
        color: #dc2626;
    }
    @media (prefers-reduced-motion: reduce) {
        .cart-drawer-backdrop { transition: none; }
        .cart-drawer-panel { transition: none; }
        .cart-item { animation: none; }
    }
</style>

<script>
function openCartDrawer() {
    const drawer = document.getElementById('cart-drawer');
    drawer.style.display = 'block';
    requestAnimationFrame(() => {
        drawer.classList.add('active');
    });
    document.body.style.overflow = 'hidden';
    loadCartDrawer();
}

function closeCartDrawer() {
    const drawer = document.getElementById('cart-drawer');
    drawer.classList.remove('active');
    setTimeout(() => {
        drawer.style.display = 'none';
    }, 300);
    document.body.style.overflow = '';
}

function loadCartDrawer() {
    fetch('{{ route("cart.index") }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const container = document.getElementById('cart-drawer-items');
        const empty = document.getElementById('cart-drawer-empty');
        const footer = document.getElementById('cart-drawer-footer');
        const countEl = document.getElementById('cart-drawer-count');
        const subtotalEl = document.getElementById('cart-drawer-subtotal');

        countEl.textContent = data.count;
        subtotalEl.textContent = '$' + data.subtotal;

        if (data.items.length === 0) {
            container.innerHTML = '';
            container.appendChild(empty.cloneNode(true));
            empty.style.display = 'flex';
            footer.style.display = 'none';
            return;
        }

        empty.style.display = 'none';
        footer.style.display = 'block';

        let html = '';
        data.items.forEach(item => {
            const img = item.product?.images?.[0]
                ? `<img src="${'/storage/' + item.product.images[0]}" alt="">`
                : `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>`;

            html += `
            <div class="cart-item" id="cart-item-${item.id}">
                <div class="cart-item-image">${img}</div>
                <div class="cart-item-details">
                    <div class="cart-item-name">${item.product?.productName || 'Product'}</div>
                    <div class="cart-item-price">$${parseFloat(item.product?.sales_price || 0).toFixed(2)}</div>
                    <div class="cart-item-qty">
                        <button class="cart-qty-btn" onclick="updateCartQty(${item.id}, ${item.quantity - 1})">−</button>
                        <span class="text-sm font-medium" style="color: #111827; min-width: 20px; text-align: center;">${item.quantity}</span>
                        <button class="cart-qty-btn" onclick="updateCartQty(${item.id}, ${item.quantity + 1})">+</button>
                    </div>
                </div>
                <div class="text-end">
                    <div class="cart-item-total">$${parseFloat(item.total_price || 0).toFixed(2)}</div>
                    <button class="cart-item-remove" onclick="removeCartItem(${item.id})">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                    </button>
                </div>
            </div>`;
        });

        container.innerHTML = html;
    });
}

function updateCartQty(itemId, qty) {
    if (qty < 1) { removeCartItem(itemId); return; }
    fetch(`/cart/${itemId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ quantity: qty })
    })
    .then(r => r.json())
    .then(data => {
        updateCartBadge(data.cart_count);
        loadCartDrawer();
    });
}

function removeCartItem(itemId) {
    fetch(`/cart/${itemId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        updateCartBadge(data.cart_count);
        loadCartDrawer();
    });
}

function updateCartBadge(count) {
    ['cart-badge', 'cart-badge-mobile'].forEach(id => {
        const badge = document.getElementById(id);
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
    });
}

// Load cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    fetch('{{ route("cart.count") }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => updateCartBadge(data.count));
});
</script>
