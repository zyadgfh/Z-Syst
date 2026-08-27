@extends('landing::layouts.web.master', [
    'seo_title' => $product->productName . ' | ' . (get_option('general')['title'] ?? 'Z-Syst'),
    'seo_description' => strip_tags(Str::limit($product->description ?: $product->productName, 160, '')),
    'seo_image' => $product->images && is_array($product->images) && count($product->images) > 0 ? asset($product->images[0]) : null,
    'seo_keywords' => implode(', ', array_filter([$product->productName, $product->scientific_name, $product->active_ingredient, $product->category?->name, $product->manufacturer?->name])),
])

@section('title')
    {{ $product->productName }}
@endsection

@section('main_content')
    <section style="padding: 48px 0 80px;">
        <div class="container">
            {{-- Breadcrumb --}}
            <nav style="margin-bottom: 32px;" data-aos="fade-up">
                <ol style="display: flex; align-items: center; gap: 8px; list-style: none; padding: 0; margin: 0; font-size: 14px;">
                    <li><a href="{{ route('home') }}" style="color: #007aff; text-decoration: none;">{{ __('Home') }}</a></li>
                    <li style="color: #86868b;">/</li>
                    <li><a href="{{ route('catalog.index') }}" style="color: #007aff; text-decoration: none;">{{ __('Catalog') }}</a></li>
                    <li style="color: #86868b;">/</li>
                    <li style="color: #1d1d1f; font-weight: 500;">{{ $product->productName }}</li>
                </ol>
            </nav>

            <div class="row g-5">
                {{-- Product Image Gallery --}}
                <div class="col-lg-6" data-aos="fade-right">
                    @php
                        $hasImages = $product->images && is_array($product->images) && count($product->images) > 0;
                        $imageCount = $hasImages ? count($product->images) : 0;
                    @endphp
                    <div id="product-gallery" style="position: relative;">
                        {{-- Main Image --}}
                        <div id="gallery-main" style="background: #f5f5f7; border-radius: 20px; overflow: hidden; aspect-ratio: 4/3; display: flex; align-items: center; justify-content: center; position: relative;">
                            @if ($hasImages)
                                @foreach ($product->images as $idx => $img)
                                    <img src="{{ asset($img) }}" alt="{{ e($product->productName) }} ({{ $idx + 1 }})" class="gallery-image" data-index="{{ $idx }}" style="width: 100%; height: 100%; object-fit: contain; position: absolute; top: 0; left: 0; {{ $idx === 0 ? '' : 'opacity: 0; pointer-events: none;' }} transition: opacity 250ms ease;">
                                @endforeach
                            @else
                                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#c7c7cc" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21 15 16 10 5 21"/>
                                </svg>
                            @endif
                            @if ($hasImages && $imageCount > 1)
                                {{-- Navigation Arrows --}}
                                <button type="button" onclick="galleryNav(-1)" style="position: absolute; top: 50%; transform: translateY(-50%); {{ app()->getLocale() === 'ar' ? 'right: 12px' : 'left: 12px' }}; width: 40px; height: 40px; border-radius: 50%; border: none; background: rgba(255,255,255,0.85); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 150ms ease, background 150ms ease; z-index: 2;" onmouseenter="this.style.background='rgba(255,255,255,1)'" onmouseleave="this.style.background='rgba(255,255,255,0.85)'" aria-label="{{ __('Previous image') }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1d1d1f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                                </button>
                                <button type="button" onclick="galleryNav(1)" style="position: absolute; top: 50%; transform: translateY(-50%); {{ app()->getLocale() === 'ar' ? 'left: 12px' : 'right: 12px' }}; width: 40px; height: 40px; border-radius: 50%; border: none; background: rgba(255,255,255,0.85); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 150ms ease, background 150ms ease; z-index: 2;" onmouseenter="this.style.background='rgba(255,255,255,1)'" onmouseleave="this.style.background='rgba(255,255,255,0.85)'" aria-label="{{ __('Next image') }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1d1d1f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                                </button>
                                {{-- Image Counter --}}
                                <span id="gallery-counter" style="position: absolute; bottom: 12px; {{ app()->getLocale() === 'ar' ? 'right: 16px' : 'left: 16px' }}; background: rgba(0,0,0,0.5); backdrop-filter: blur(8px); color: #fff; font-size: 12px; font-weight: 500; padding: 4px 10px; border-radius: 8px; z-index: 2;">1 / {{ $imageCount }}</span>
                            @endif
                        </div>
                        {{-- Thumbnails --}}
                        @if ($hasImages && $imageCount > 1)
                            <div style="display: flex; gap: 8px; margin-top: 12px; overflow-x: auto; padding-bottom: 4px; scroll-snap-type: x mandatory;">
                                @foreach ($product->images as $idx => $img)
                                    <button type="button" onclick="galleryGoTo({{ $idx }})" class="gallery-thumb" data-index="{{ $idx }}" style="flex-shrink: 0; width: 64px; height: 64px; border-radius: 10px; overflow: hidden; border: 2px solid {{ $idx === 0 ? '#007aff' : 'transparent' }}; background: #f5f5f7; cursor: pointer; padding: 0; transition: border-color 150ms ease, opacity 150ms ease; scroll-snap-align: start; {{ $idx === 0 ? '' : 'opacity: 0.6;' }}" onmouseenter="this.style.opacity='1'" onmouseleave="this.style.opacity='{{ $idx === 0 ? '1' : '0.6' }}'" aria-label="{{ __('View image') }} {{ $idx + 1 }}">
                                        <img src="{{ asset($img) }}" alt="" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Product Details --}}
                <div class="col-lg-6" data-aos="fade-left">
                    @if ($product->category)
                        <span style="font-size: 12px; font-weight: 600; color: #007aff; text-transform: uppercase; letter-spacing: 0.05em;">{{ $product->category->name }}</span>
                    @endif

                    <h1 style="font-size: clamp(1.5rem, 3vw, 2.2rem); font-weight: 700; letter-spacing: -0.02em; margin: 8px 0 16px; line-height: 1.15;">
                        {{ $product->productName }}
                    </h1>

                    @if ($product->scientific_name)
                        <p style="font-size: 16px; color: #86868b; font-style: italic; margin: 0 0 16px;">{{ $product->scientific_name }}</p>
                    @endif

                    {{-- Price --}}
                    @if ($product->sales_price)
                        <div style="background: #f5f5f7; border-radius: 14px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <span style="font-size: 12px; color: #86868b; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 2px;">{{ __('Price') }}</span>
                                <span style="font-size: 28px; font-weight: 700; color: #1d1d1f;">{{ number_format($product->sales_price, 2) }}</span>
                            </div>
                            @if ($product->wholesale_price)
                                <div style="text-align: right;">
                                    <span style="font-size: 12px; color: #86868b; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 2px;">{{ __('Wholesale') }}</span>
                                    <span style="font-size: 20px; font-weight: 600; color: #34c759;">{{ number_format($product->wholesale_price, 2) }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Wishlist Button --}}
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 24px;">
                        @include('customer.wishlist.toggle-button', ['product' => $product])
                        <span style="font-size: 14px; color: #6e6e73;">{{ __('Add to Wishlist') }}</span>
                    </div>

                    {{-- Quick Info Badges --}}
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px;">
                        @if ($product->prescription_required)
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; background: #fff3e0; color: #e65100; border-radius: 8px; font-size: 13px; font-weight: 500;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ __('Prescription Required') }}
                            </span>
                        @endif
                        @if ($product->controlled_item)
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; background: #ffebee; color: #c62828; border-radius: 8px; font-size: 13px; font-weight: 500;">
                                {{ __('Controlled Substance') }}
                            </span>
                        @endif
                        @if ($product->refrigerated)
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; background: #e3f2fd; color: #1565c0; border-radius: 8px; font-size: 13px; font-weight: 500;">
                                ❄ {{ __('Refrigerated') }}
                            </span>
                        @endif
                        @if ($product->stock_status)
                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; background: {{ $product->stock_status === 'in_stock' ? '#e8f5e9' : ($product->stock_status === 'low_stock' ? '#fff3e0' : '#ffebee') }}; color: {{ $product->stock_status === 'in_stock' ? '#2e7d32' : ($product->stock_status === 'low_stock' ? '#e65100' : '#c62828') }}; border-radius: 8px; font-size: 13px; font-weight: 500;">
                                {{ ucfirst(str_replace('_', ' ', $product->stock_status)) }}
                            </span>
                        @endif
                    </div>

                    {{-- Description --}}
                    @if ($product->description)
                        <div style="margin-bottom: 24px;">
                            <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">{{ __('Description') }}</h3>
                            <p style="font-size: 15px; color: #424245; line-height: 1.6;">{{ $product->description }}</p>
                        </div>
                    @endif

                    {{-- Product Details Table --}}
                    <div style="margin-bottom: 24px;">
                        <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">{{ __('Product Details') }}</h3>
                        <div style="background: #f5f5f7; border-radius: 14px; overflow: hidden;">
                            <table style="width: 100%; font-size: 14px; border-collapse: collapse;">
                                <tbody>
                                    @if ($product->strength)
                                        <tr style="border-bottom: 1px solid #e8e8ed;">
                                            <td style="padding: 12px 16px; color: #86868b; font-weight: 500; width: 40%;">{{ __('Strength') }}</td>
                                            <td style="padding: 12px 16px; color: #1d1d1f;">{{ $product->strength }}</td>
                                        </tr>
                                    @endif
                                    @if ($product->dosage_form)
                                        <tr style="border-bottom: 1px solid #e8e8ed;">
                                            <td style="padding: 12px 16px; color: #86868b; font-weight: 500;">{{ __('Dosage Form') }}</td>
                                            <td style="padding: 12px 16px; color: #1d1d1f;">{{ $product->dosage_form }}</td>
                                        </tr>
                                    @endif
                                    @if ($product->active_ingredient)
                                        <tr style="border-bottom: 1px solid #e8e8ed;">
                                            <td style="padding: 12px 16px; color: #86868b; font-weight: 500;">{{ __('Active Ingredient') }}</td>
                                            <td style="padding: 12px 16px; color: #1d1d1f;">{{ $product->active_ingredient }}</td>
                                        </tr>
                                    @endif
                                    @if ($product->concentration)
                                        <tr style="border-bottom: 1px solid #e8e8ed;">
                                            <td style="padding: 12px 16px; color: #86868b; font-weight: 500;">{{ __('Concentration') }}</td>
                                            <td style="padding: 12px 16px; color: #1d1d1f;">{{ $product->concentration }}</td>
                                        </tr>
                                    @endif
                                    @if ($product->manufacturer)
                                        <tr style="border-bottom: 1px solid #e8e8ed;">
                                            <td style="padding: 12px 16px; color: #86868b; font-weight: 500;">{{ __('Manufacturer') }}</td>
                                            <td style="padding: 12px 16px; color: #1d1d1f;">{{ $product->manufacturer->name }}</td>
                                        </tr>
                                    @endif
                                    @if ($product->package_size)
                                        <tr style="border-bottom: 1px solid #e8e8ed;">
                                            <td style="padding: 12px 16px; color: #86868b; font-weight: 500;">{{ __('Package Size') }}</td>
                                            <td style="padding: 12px 16px; color: #1d1d1f;">{{ $product->package_size }} {{ $product->package_unit ?? '' }}</td>
                                        </tr>
                                    @endif
                                    @if ($product->barcode)
                                        <tr>
                                            <td style="padding: 12px 16px; color: #86868b; font-weight: 500;">{{ __('Barcode') }}</td>
                                            <td style="padding: 12px 16px; color: #1d1d1f; font-family: monospace;">{{ $product->barcode }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Back to Catalog --}}
                    <a href="{{ route('catalog.index') }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #1d1d1f; color: #fff; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 15px; transition: transform 150ms ease;" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'" onmouseleave="this.style.transform='scale(1)'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m7-7l-7 7 7 7"/></svg>
                        {{ __('Back to Catalog') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Recommended Products --}}
    @if (isset($recommendedProducts) && $recommendedProducts->count() > 0)
    <section style="padding: 0 0 80px;">
        <div class="container">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                <h2 style="font-size: clamp(1.2rem, 2.5vw, 1.5rem); font-weight: 700; letter-spacing: -0.02em; margin: 0;">
                    {{ __('You May Also Like') }}
                </h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px;">
                @foreach ($recommendedProducts as $rec)
                    <a href="{{ route('catalog.show', $rec->id) }}" style="text-decoration: none; color: inherit;">
                        <div style="background: #fff; border-radius: 14px; overflow: hidden; border: 1px solid #f0f0f2; transition: transform 200ms cubic-bezier(0.25,0.46,0.45,0.94), box-shadow 200ms ease; height: 100%;"
                             onmouseenter="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.06)'"
                             onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <div style="height: 150px; background: #f5f5f7; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                @if ($rec->images && is_array($rec->images) && count($rec->images) > 0)
                                    <img src="{{ asset($rec->images[0]) }}" alt="{{ e($rec->productName) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#c7c7cc" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                @endif
                            </div>
                            <div style="padding: 12px 14px;">
                                @if ($rec->category)
                                    <span style="font-size: 10px; font-weight: 600; color: #007aff; text-transform: uppercase; letter-spacing: 0.05em;">{{ $rec->category->name }}</span>
                                @endif
                                <h4 style="font-size: 14px; font-weight: 600; margin: 4px 0 8px; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $rec->productName }}</h4>
                                @if ($rec->sales_price)
                                    <span style="font-size: 16px; font-weight: 700; color: #1d1d1f;">{{ number_format($rec->sales_price, 2) }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @push('css')
    <style>
        @media (prefers-reduced-motion: reduce) {
            a[onmousedown] { transition: none !important; }
            a[onmousedown]:active { transform: none !important; }
            .gallery-image { transition: none !important; }
        }
        .gallery-thumb:focus-visible {
            outline: 2px solid #007aff;
            outline-offset: 2px;
        }
    </style>
    @endpush

    @push('js')
    <script>
        (function() {
            let current = 0;
            const total = {{ $hasImages ? $imageCount : 0 }};
            if (total <= 1) return;

            window.galleryNav = function(dir) {
                let next = current + dir;
                if (next < 0) next = total - 1;
                if (next >= total) next = 0;
                galleryGoTo(next);
            };

            window.galleryGoTo = function(idx) {
                if (idx === current) return;
                const images = document.querySelectorAll('.gallery-image');
                const thumbs = document.querySelectorAll('.gallery-thumb');
                const counter = document.getElementById('gallery-counter');

                images[current].style.opacity = '0';
                images[current].style.pointerEvents = 'none';
                thumbs[current].style.borderColor = 'transparent';
                thumbs[current].style.opacity = '0.6';

                current = idx;

                images[current].style.opacity = '1';
                images[current].style.pointerEvents = 'auto';
                thumbs[current].style.borderColor = '#007aff';
                thumbs[current].style.opacity = '1';
                if (counter) counter.textContent = (current + 1) + ' / ' + total;
            };

            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft') galleryNav(-1);
                if (e.key === 'ArrowRight') galleryNav(1);
            });
        })();
    </script>
    @endpush
@endsection
