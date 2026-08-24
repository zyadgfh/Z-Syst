@extends('layouts.master')

@section('title', 'مقارنة المنتجات')

@section('main_content')
<div class="container-fluid" style="padding: 24px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('catalog.index') }}" style="color: #007aff; text-decoration: none;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            </a>
            <h4 style="font-weight: 700; margin: 0;">مقارنة المنتجات ({{ $products->count() }})</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('compare.history') }}" style="display: inline-flex; align-items: center; gap: 6px; background: #f5f5f7; color: #1d1d1f; border: none; border-radius: 10px; padding: 10px 18px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 150ms ease; text-decoration: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                السجل
            </a>
            <button onclick="shareComparison()" id="shareBtn" style="display: inline-flex; align-items: center; gap: 6px; background: #f5f5f7; color: #1d1d1f; border: none; border-radius: 10px; padding: 10px 18px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 150ms ease;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="18" cy="5" r="3"/>
                    <circle cx="6" cy="12" r="3"/>
                    <circle cx="18" cy="19" r="3"/>
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                </svg>
                مشاركة
            </button>
            <button onclick="copyShareLink()" id="copyBtn" style="display: none; inline-flex; align-items: center; gap: 6px; background: #d4edda; color: #155724; border: none; border-radius: 10px; padding: 10px 18px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 150ms ease;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                تم النسخ!
            </button>
        </div>
    </div>

    @if ($products->count() > 0)
        <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; overflow: hidden;">
            <div class="table-responsive">
                <table class="table mb-0" style="font-size: 14px; border-collapse: collapse; width: 100%;">
                    {{-- Product Headers --}}
                    <thead>
                        <tr>
                            <th style="background: #f5f5f7; padding: 20px 16px; min-width: 160px; vertical-align: top; border-bottom: 1px solid #e5e5ea; font-weight: 600; color: #6e6e73; font-size: 13px;">المنتج</th>
                            @foreach ($products as $product)
                                <th style="background: #f5f5f7; padding: 20px 16px; min-width: 200px; text-align: center; vertical-align: top; border-bottom: 1px solid #e5e5ea; position: relative;">
                                    <button onclick="removeCompare({{ $product->id }})" style="position: absolute; top: 8px; {{ app()->getLocale() === 'ar' ? 'left: 8px' : 'right: 8px' }}; width: 24px; height: 24px; border-radius: 50%; border: none; background: #f0f0f2; cursor: pointer; font-size: 14px; color: #86868b; transition: background 150ms ease;" onmouseenter="this.style.background='#ff3b30'; this.style.color='#fff'" onmouseleave="this.style.background='#f0f0f2'; this.style.color='#86868b'" title="إزالة من المقارنة">✕</button>

                                    {{-- Image --}}
                                    <div style="width: 120px; height: 120px; background: #f5f5f7; border-radius: 14px; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                        @if ($product->images && is_array($product->images) && count($product->images) > 0)
                                            <img src="{{ asset($product->images[0]) }}" alt="{{ e($product->productName) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#c7c7cc" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                        @endif
                                    </div>

                                    <a href="{{ route('catalog.show', $product->id) }}" style="font-size: 15px; font-weight: 600; color: #1d1d1f; text-decoration: none; line-height: 1.3; display: block;">{{ $product->productName }}</a>

                                    @if ($product->category)
                                        <span style="font-size: 11px; color: #86868b; text-transform: uppercase; letter-spacing: 0.04em; display: block; margin-top: 4px;">{{ $product->category->name }}</span>
                                    @endif

                                    @if ($product->sales_price)
                                        <div style="margin-top: 10px; font-size: 22px; font-weight: 700; color: #1d1d1f;">{{ number_format($product->sales_price, 2) }}</div>
                                    @endif

                                    {{-- Add to Cart --}}
                                    <div style="margin-top: 10px;">
                                        @include('customer.cart.add-button', ['product' => $product])
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($specs as $label => $values)
                            <tr style="border-bottom: 1px solid #f0f0f2; {{ $loop->even ? 'background: #fafafa;' : '' }}">
                                <td style="padding: 14px 16px; font-weight: 600; color: #6e6e73; font-size: 13px; white-space: nowrap;">{{ $label }}</td>
                                @foreach ($values as $value)
                                    <td style="padding: 14px 16px; text-align: center; color: #1d1d1f; font-size: 14px;">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach

                        {{-- Stock Status --}}
                        <tr style="border-bottom: 1px solid #f0f0f2; background: #fafafa;">
                            <td style="padding: 14px 16px; font-weight: 600; color: #6e6e73; font-size: 13px;">حالة المخزون</td>
                            @foreach ($products as $product)
                                @php
                                    $stock = $product->getTotalStockAttribute();
                                    $stockColor = $stock > 0 ? '#2e7d32' : '#ff3b30';
                                    $stockText = $stock > 0 ? "متوفر ({$stock})" : 'نفذ';
                                @endphp
                                <td style="padding: 14px 16px; text-align: center;">
                                    <span style="color: {{ $stockColor }}; font-weight: 600; font-size: 14px;">{{ $stockText }}</span>
                                </td>
                            @endforeach
                        </tr>

                        {{-- Reviews Summary --}}
                        @php
                            $reviewClass = \App\Models\ProductReview::class;
                        @endphp
                        <tr style="border-bottom: 1px solid #f0f0f2;">
                            <td style="padding: 14px 16px; font-weight: 600; color: #6e6e73; font-size: 13px;">التقييمات</td>
                            @foreach ($products as $product)
                                @php
                                    $avgRating = $reviewClass::where('product_id', $product->id)->where('approved', true)->avg('rating');
                                    $reviewCount = $reviewClass::where('product_id', $product->id)->where('approved', true)->count();
                                @endphp
                                <td style="padding: 14px 16px; text-align: center;">
                                    @if ($avgRating)
                                        <div style="font-size: 16px; color: #f57f17; letter-spacing: 2px;">
                                            @for ($i = 1; $i <= 5; $i++)
                                                {{ $i <= round($avgRating) ? '★' : '☆' }}
                                            @endfor
                                        </div>
                                        <span style="font-size: 13px; color: #86868b;">{{ number_format($avgRating, 1) }} ({{ $reviewCount }})</span>
                                    @else
                                        <span style="color: #86868b; font-size: 13px;">لا توجد تقييمات</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="text-center py-5">
            <p style="color: #86868b; font-size: 16px;">لم يتم تحديد منتجات للمقارنة</p>
            <a href="{{ route('catalog.index') }}" class="btn" style="background: #007aff; color: #fff; border-radius: 10px; padding: 10px 24px; font-weight: 600; text-decoration: none;">تصفح الكتالوج</a>
        </div>
    @endif
</div>

@push('scripts')
<script>
    function removeCompare(productId) {
        fetch('/compare/remove/' + productId, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.count === 0) {
                window.location.href = '{{ route("catalog.index") }}';
            } else {
                window.location.reload();
            }
        });
    }

    function getShareUrl() {
        var ids = @json($products->pluck('id')->values());
        var base = window.location.origin + '/compare';
        var params = ids.map(function(id) { return 'ids[]=' + id; }).join('&');
        return base + '?' + params;
    }

    function shareComparison() {
        var url = getShareUrl();

        if (navigator.share) {
            navigator.share({
                title: 'مقارنة المنتجات - {{ config("app.name") }}',
                text: 'قارن المنتجات مع بعضها:',
                url: url,
            }).catch(function() {});
        } else {
            copyShareLink();
        }
    }

    function copyShareLink() {
        var url = getShareUrl();
        navigator.clipboard.writeText(url).then(function() {
            var shareBtn = document.getElementById('shareBtn');
            var copyBtn = document.getElementById('copyBtn');
            shareBtn.style.display = 'none';
            copyBtn.style.display = 'inline-flex';
            setTimeout(function() {
                shareBtn.style.display = 'inline-flex';
                copyBtn.style.display = 'none';
            }, 2500);
        }).catch(function() {
            var input = document.createElement('input');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            var shareBtn = document.getElementById('shareBtn');
            var copyBtn = document.getElementById('copyBtn');
            shareBtn.style.display = 'none';
            copyBtn.style.display = 'inline-flex';
            setTimeout(function() {
                shareBtn.style.display = 'inline-flex';
                copyBtn.style.display = 'none';
            }, 2500);
        });
    }
</script>
@endpush
@endsection
