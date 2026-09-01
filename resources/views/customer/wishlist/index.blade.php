@extends('layouts.master')

@section('title')
    {{ __('المفضلة') }} — {{ get_option('general')['title'] ?? 'Z-Syst' }}
@endsection

@section('main_content')
    <div class="container-fluid" style="padding: 24px;">
        {{-- Page Header --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 style="font-weight: 700; letter-spacing: -0.01em;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    {{ __('المفضلة') }}
                </h4>
                <p class="text-muted mb-0" style="font-size: 14px;">{{ $items->total() }} {{ __('منتج') }}</p>
            </div>
        </div>

        @if ($items->count() > 0)
            <div class="row g-3">
                @foreach ($items as $item)
                    @php $product = $item->product; @endphp
                    <div class="col-md-6 col-lg-4 col-xl-3" id="wishlist-item-{{ $item->id }}">
                        <div class="card h-100" style="border-radius: 14px; border: 1px solid #e5e5ea; overflow: hidden; transition: transform 200ms ease, box-shadow 200ms ease;"
                             onmouseenter="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.08)'"
                             onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            {{-- Product Image --}}
                            <div style="height: 180px; background: #f5f5f7; display: flex; align-items: center; justify-content: center; position: relative;">
                                @if ($product && $product->images && is_array($product->images) && count($product->images) > 0)
                                    <img src="{{ asset($product->images[0]) }}" alt="{{ e($product->productName ?? '') }}" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#c7c7cc" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                @endif

                                {{-- Remove from wishlist --}}
                                <button onclick="removeWishlist({{ $item->id }}, {{ $product->id }})" title="{{ __('إزالة من المفضلة') }}"
                                    style="position: absolute; top: 10px; right: 10px; width: 32px; height: 32px; border-radius: 50%; border: none; background: rgba(255,255,255,0.9); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 150ms ease, background 150ms ease; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                    onmouseenter="this.style.background='#ff3b30'; this.querySelector('svg').setAttribute('stroke','#fff')"
                                    onmouseleave="this.style.background='rgba(255,255,255,0.9)'; this.querySelector('svg').setAttribute('stroke','#ff3b30')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff3b30" stroke-width="2" stroke-linecap="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                </button>

                                @if ($product && $product->prescription_required)
                                    <span style="position: absolute; top: 10px; left: 10px; background: #ff9500; color: #fff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px;">Rx</span>
                                @endif
                            </div>

                            <div class="card-body" style="padding: 14px;">
                                @if ($product && $product->category)
                                    <span style="font-size: 11px; font-weight: 600; color: #007aff; text-transform: uppercase; letter-spacing: 0.04em;">{{ $product->category->name }}</span>
                                @endif

                                <h6 style="margin: 4px 0 8px; font-weight: 600; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    @if ($product)
                                        <a href="{{ route('catalog.show', $product->id) }}" style="color: inherit; text-decoration: none;">{{ $product->productName }}</a>
                                    @else
                                        <span style="color: #86868b;">{{ __('المنتج غير متاح') }}</span>
                                    @endif
                                </h6>

                                @if ($product && $product->sales_price)
                                    <div class="d-flex align-items-center justify-content-between mt-auto">
                                        <span style="font-size: 18px; font-weight: 700; color: #1d1d1f;">{{ number_format($product->sales_price, 2) }}</span>
                                        @include('customer.cart.add-button', ['product' => $product])
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $items->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d2d2d7" stroke-width="1.5" style="margin-bottom: 16px;">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <h5 style="color: #1d1d1f; font-weight: 600;">{{ __('المفضلة فارغة') }}</h5>
                <p style="color: #86868b; font-size: 14px;">{{ __('أضف منتجاتك المفضلة للرجوع إليها لاحقاً') }}</p>
                <a href="{{ route('catalog.index') }}" class="btn" style="background: #007aff; color: #fff; border-radius: 10px; padding: 10px 24px; font-weight: 600; text-decoration: none; transition: transform 150ms ease;" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                    {{ __('تصفح الكتالوج') }}
                </a>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function removeWishlist(itemId, productId) {
            if (!confirm('{{ __("إزالة من المفضلة؟") }}')) return;

            fetch(`/wishlist/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const el = document.getElementById(`wishlist-item-${itemId}`);
                    if (el) {
                        el.style.transition = 'opacity 250ms ease, transform 250ms ease';
                        el.style.opacity = '0';
                        el.style.transform = 'scale(0.95)';
                        setTimeout(() => el.remove(), 250);
                    }
                    // Update badge if exists
                    const badge = document.getElementById('wishlist-count-badge');
                    if (badge) {
                        badge.textContent = data.count;
                        badge.style.display = data.count > 0 ? '' : 'none';
                    }
                    showToast(data.message, 'success');
                }
            });
        }

        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.style.cssText = `position: fixed; bottom: 24px; ${document.documentElement.dir === 'rtl' ? 'left' : 'right'}: 24px; background: ${type === 'success' ? '#34c759' : '#ff3b30'}; color: #fff; padding: 12px 20px; border-radius: 12px; font-size: 14px; font-weight: 500; z-index: 9999; transform: translateY(20px); opacity: 0; transition: all 200ms ease; backdrop-filter: blur(10px);`;
            toast.textContent = message;
            document.body.appendChild(toast);
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            });
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(20px)';
                setTimeout(() => toast.remove(), 200);
            }, 2500);
        }
    </script>
    @endpush
@endsection
