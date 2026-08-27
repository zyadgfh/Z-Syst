@extends('landing::layouts.web.master', [
    'seo_title' => __('Product Catalog') . ' | ' . (get_option('general')['title'] ?? 'Z-Syst'),
    'seo_description' => __('Browse our complete pharmacy product catalog. Find medications, supplements, and healthcare products with detailed information on pricing, availability, and more.'),
    'seo_keywords' => __('pharmacy catalog, medications, pharmacy products, healthcare, medicines, supplements, pharmacy online'),
])

@section('title')
    {{ __('Product Catalog') }}
@endsection

@section('main_content')
    {{-- Hero Banner --}}
    <section class="catalog-hero" style="background: linear-gradient(135deg, #f5f5f7 0%, #e8e8ed 100%); padding: 80px 0 48px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8" data-aos="fade-right">
                    <span class="hero-pill">{{ __('Pharmacy Catalog') }}</span>
                    <h1 style="font-size: clamp(2rem, 4vw, 3rem); letter-spacing: -0.02em; margin-top: 12px;">
                        {{ __('Browse Our Products') }}
                    </h1>
                    <p style="color: #6e6e73; font-size: 1.1rem; max-width: 540px; margin-top: 8px;">
                        {{ __('Discover our wide range of pharmaceutical products, healthcare essentials, and medical supplies.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Filters & Products --}}
    <section style="padding: 48px 0 80px;">
        <div class="container">
            {{-- Sticky Search Bar (appears on scroll) --}}
            <div id="sticky-search-bar" style="position: sticky; top: 0; z-index: 100; background: rgba(255,255,255,0.85); backdrop-filter: blur(20px) saturate(180%); -webkit-backdrop-filter: blur(20px) saturate(180%); border-radius: 14px; padding: 12px 16px; margin-bottom: 20px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); display: flex; align-items: center; gap: 10px; opacity: 0; transform: translateY(-8px); transition: opacity 200ms ease, transform 200ms ease; pointer-events: none;" data-aos="fade-up">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#86868b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <form method="GET" action="{{ route('catalog.index') }}" style="display: flex; align-items: center; gap: 10px; flex: 1;">
                    @if (request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
                    @if (request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search products...') }}" style="flex: 1; border: none; background: transparent; outline: none; font-size: 15px; color: #1d1d1f; padding: 4px 0;">
                    <button type="submit" style="padding: 8px 16px; background: #1d1d1f; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: transform 120ms ease; white-space: nowrap;" onmousedown="this.style.transform='scale(0.95)'" onmouseup="this.style.transform='scale(1)'">{{ __('Search') }}</button>
                </form>
            </div>

            {{-- Full Search & Filter Bar --}}
            <div id="catalog-filters-full" class="catalog-filters" style="background: #fff; border-radius: 16px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 36px;" data-aos="fade-up">
                <form method="GET" action="{{ route('catalog.index') }}" id="catalogForm">
                    {{-- Search with Autocomplete --}}
                    <div style="position: relative; margin-bottom: 16px;">
                        <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">{{ __('Search Products') }}</label>
                        <div style="position: relative;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#86868b" stroke-width="2" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); pointer-events: none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" name="search" id="catalogSearchInput" value="{{ request('search') }}" placeholder="{{ __('Search by name, brand, or barcode...') }}" autocomplete="off" style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 12px 14px 12px 42px; font-size: 15px; outline: none;" onfocus="this.style.borderColor='#007aff'; this.style.boxShadow='0 0 0 3px rgba(0,122,255,0.15)'" onblur="setTimeout(()=>{this.style.borderColor='#d2d2d7'; this.style.boxShadow='none'; document.getElementById('autocomplete-dropdown').style.display='none'}, 200)">
                            <div id="autocomplete-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); border: 1px solid #e5e5ea; z-index: 200; max-height: 320px; overflow-y: auto; margin-top: 4px;"></div>
                        </div>
                    </div>

                    {{-- Category Pills --}}
                    <div style="margin-bottom: 16px;">
                        <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; display: block;">{{ __('Categories') }}</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <a href="{{ route('catalog.index', array_merge(request()->except('category', 'page'), ['category' => ''])) }}" style="padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500; text-decoration: none; border: 1px solid {{ !request('category') ? '#007aff' : '#d2d2d7' }}; background: {{ !request('category') ? '#007aff' : '#fff' }}; color: {{ !request('category') ? '#fff' : '#6e6e73' }}; transition: all 150ms ease;">
                                {{ __('All') }}
                            </a>
                            @foreach ($categories as $cat)
                                <a href="{{ route('catalog.index', array_merge(request()->except('category', 'page'), ['category' => $cat->id])) }}" style="padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500; text-decoration: none; border: 1px solid {{ request('category') == $cat->id ? '#007aff' : '#d2d2d7' }}; background: {{ request('category') == $cat->id ? '#007aff' : '#fff' }}; color: {{ request('category') == $cat->id ? '#fff' : '#6e6e73' }}; transition: all 150ms ease;">
                                    {{ $cat->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Price Range + Sort + Submit --}}
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">{{ __('Min Price') }}</label>
                            <input type="number" name="price_min" value="{{ request('price_min') }}" placeholder="{{ __('Min') }}" step="0.01" min="0" style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                        </div>
                        <div class="col-md-3">
                            <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">{{ __('Max Price') }}</label>
                            <input type="number" name="price_max" value="{{ request('price_max') }}" placeholder="{{ __('Max') }}" step="0.01" min="0" style="width: 100%; border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                        </div>
                        <div class="col-md-3">
                            <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block;">{{ __('Sort By') }}</label>
                            <select name="sort" class="form-select" style="border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px; font-size: 15px;">
                                <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>{{ __('Newest') }}</option>
                                <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>{{ __('Name') }}</option>
                                <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>{{ __('Price: Low → High') }}</option>
                                <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>{{ __('Price: High → Low') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn w-100" style="background: #1d1d1f; color: #fff; border-radius: 10px; padding: 10px 14px; font-weight: 600; transition: transform 150ms ease;" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">{{ __('Search') }}</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Products Grid --}}
            @if ($products->count() > 0)
                <div class="row g-4">
                    @foreach ($products as $product)
                        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 3) * 100 }}">
                            <a href="{{ route('catalog.show', $product->id) }}" class="product-card-link" style="text-decoration: none; color: inherit;">
                                <div class="product-card" style="background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: transform 200ms cubic-bezier(0.25, 0.46, 0.45, 0.94), box-shadow 200ms ease; height: 100%; display: flex; flex-direction: column;" onmouseenter="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)'" onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.04)'">
                                    {{-- Product Image --}}
                                    <div style="height: 200px; background: #f5f5f7; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                                        @if ($product->images && is_array($product->images) && count($product->images) > 0)
                                            <img src="{{ asset($product->images[0]) }}" alt="{{ e($product->productName) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#86868b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                                <polyline points="21 15 16 10 5 21"/>
                                            </svg>
                                        @endif
                                        {{-- Status Badges --}}
                                        @if ($product->prescription_required)
                                            <span style="position: absolute; top: 12px; left: 12px; background: #ff9500; color: #fff; font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 6px; letter-spacing: 0.02em;">{{ __('Rx Required') }}</span>
                                        @endif
                                        @if ($product->refrigerated)
                                            <span style="position: absolute; top: 12px; right: 12px; background: #007aff; color: #fff; font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 6px; letter-spacing: 0.02em;">❄ {{ __('Cold Chain') }}</span>
                                        @endif
                                    </div>

                                    {{-- Product Info --}}
                                    <div style="padding: 16px 20px; flex: 1; display: flex; flex-direction: column;">
                                        @if ($product->category)
                                            <span style="font-size: 11px; font-weight: 600; color: #007aff; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">{{ $product->category->name }}</span>
                                        @endif
                                        <h3 style="font-size: 16px; font-weight: 600; letter-spacing: -0.01em; margin: 0 0 4px; line-height: 1.3;">{{ $product->productName }}</h3>
                                        @if ($product->scientific_name)
                                            <p style="font-size: 13px; color: #86868b; margin: 0 0 8px; font-style: italic;">{{ $product->scientific_name }}</p>
                                        @endif
                                        @if ($product->strength || $product->dosage_form)
                                            <p style="font-size: 13px; color: #6e6e73; margin: 0 0 12px;">
                                                @if ($product->strength) {{ $product->strength }} @endif
                                                @if ($product->strength && $product->dosage_form) · @endif
                                                @if ($product->dosage_form) {{ $product->dosage_form }} @endif
                                            </p>
                                        @endif

                                        <div style="margin-top: auto; display: flex; align-items: center; justify-content: space-between; padding-top: 12px; border-top: 1px solid #f5f5f7;">
                                            @if ($product->sales_price)
                                                <span style="font-size: 18px; font-weight: 700; color: #1d1d1f;">{{ number_format($product->sales_price, 2) }}</span>
                                            @else
                                                <span style="font-size: 14px; color: #86868b;">{{ __('Contact for price') }}</span>
                                            @endif
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                @if ($product->manufacturer)
                                                    <span style="font-size: 12px; color: #86868b;">{{ $product->manufacturer->name }}</span>
                                                @endif
                                                @include('customer.wishlist.toggle-button', ['product' => $product])
                                                @include('customer.compare.compare-button', ['product' => $product])
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div style="margin-top: 48px; display: flex; justify-content: center;">
                    {{ $products->links() }}
                </div>
            @else
                <div style="text-align: center; padding: 80px 20px;" data-aos="fade-up">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d2d2d7" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 16px;">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <h3 style="color: #1d1d1f; font-size: 20px; font-weight: 600;">{{ __('No products found') }}</h3>
                    <p style="color: #86868b; font-size: 15px; margin-top: 8px;">{{ __('Try adjusting your search or filters to find what you\'re looking for.') }}</p>
                    <a href="{{ route('catalog.index') }}" style="display: inline-block; margin-top: 16px; padding: 10px 24px; background: #1d1d1f; color: #fff; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px;">{{ __('Clear Filters') }}</a>
                </div>
            @endif
        </div>
    </section>

    @push('css')
    <style>
        .catalog-hero h1 {
            font-weight: 700;
        }
        .product-card-link:focus-visible .product-card {
            outline: 2px solid #007aff;
            outline-offset: 2px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #007aff;
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15);
        }
        @media (prefers-reduced-motion: reduce) {
            .product-card { transition: none !important; }
            .product-card:hover { transform: none !important; }
            #sticky-search-bar { transition: none !important; }
        }
        @media (max-width: 768px) {
            .catalog-hero { padding: 48px 0 32px; }
        }
    </style>
    @endpush

    @push('js')
    <script>
        (function() {
            // ── Autocomplete ──
            const searchInput = document.getElementById('catalogSearchInput');
            const dropdown = document.getElementById('autocomplete-dropdown');
            let debounceTimer = null;

            if (searchInput && dropdown) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    const q = this.value.trim();
                    if (q.length < 2) { dropdown.style.display = 'none'; return; }
                    debounceTimer = setTimeout(function() {
                        fetch('{{ route("catalog.autocomplete") }}?q=' + encodeURIComponent(q))
                            .then(r => r.json())
                            .then(data => {
                                if (!data.data || data.data.length === 0) {
                                    dropdown.style.display = 'none';
                                    return;
                                }
                                let html = '';
                                data.data.forEach(function(p) {
                                    html += '<a href="' + p.url + '" style="display:flex;align-items:center;gap:12px;padding:10px 14px;text-decoration:none;color:inherit;border-bottom:1px solid #f0f0f2;transition:background 100ms;" onmouseenter="this.style.background='#f8f9fa'" onmouseleave="this.style.background='transparent'">';
                                    if (p.image) {
                                        html += '<img src="' + p.image + '" style="width:40px;height:40px;border-radius:8px;object-fit:cover;flex-shrink:0;">';
                                    } else {
                                        html += '<div style="width:40px;height:40px;border-radius:8px;background:#f5f5f7;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:18px;">💊</div>';
                                    }
                                    html += '<div style="flex:1;min-width:0;">';
                                    html += '<div style="font-weight:600;font-size:14px;color:#1d1d1f;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + p.name + '</div>';
                                    if (p.subtitle) html += '<div style="font-size:12px;color:#86868b;margin-top:1px;">' + p.subtitle + '</div>';
                                    html += '</div>';
                                    if (p.price) html += '<span style="font-weight:700;font-size:14px;color:#1d1d1f;">' + p.price + '</span>';
                                    html += '</a>';
                                });
                                dropdown.innerHTML = html;
                                dropdown.style.display = 'block';
                            });
                    }, 250);
                });
            }

            // ── Sticky Search Bar ──
            const stickyBar = document.getElementById('sticky-search-bar');
            const fullFilters = document.getElementById('catalog-filters-full');
            if (!stickyBar || !fullFilters) return;

            let ticking = false;

            function onScroll() {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(function() {
                    const rect = fullFilters.getBoundingClientRect();
                    const past = rect.bottom < 0;
                    if (past) {
                        stickyBar.style.opacity = '1';
                        stickyBar.style.transform = 'translateY(0)';
                        stickyBar.style.pointerEvents = 'auto';
                    } else {
                        stickyBar.style.opacity = '0';
                        stickyBar.style.transform = 'translateY(-8px)';
                        stickyBar.style.pointerEvents = 'none';
                    }
                    ticking = false;
                });
            }

            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        })();
    </script>
    @endpush
@endsection
