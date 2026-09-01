@extends('layouts.master')

@section('title', 'سجل المقارنات')

@section('main_content')
<div class="container-fluid" style="padding: 24px; max-width: 900px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('catalog.index') }}" style="color: #007aff; text-decoration: none;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            </a>
            <h4 style="font-weight: 700; margin: 0;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#007aff" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                سجل المقارنات
            </h4>
        </div>
    </div>

    @if ($history->isEmpty())
        <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; padding: 48px; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
            <h5 style="color: #6e6e73; margin-bottom: 8px;">لا توجد مقارنات سابقة</h5>
            <p style="color: #86868b; font-size: 14px; margin-bottom: 20px;">ستظهر هنا جميع المقارنات التي قمت بها سابقاً</p>
            <a href="{{ route('catalog.index') }}" style="display: inline-flex; align-items: center; gap: 6px; background: #007aff; color: #fff; border-radius: 10px; padding: 10px 24px; font-weight: 600; text-decoration: none; font-size: 14px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                تصفح المنتجات
            </a>
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 16px;">
            @foreach ($history as $item)
                <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; overflow: hidden; transition: box-shadow 150ms ease;" onmouseenter="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.06)'" onmouseleave="this.style.boxShadow='none'">
                    <div style="padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f0f0f2; background: #fafafa;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="background: #e8f0fe; color: #007aff; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                {{ count($item['products']) }} منتجات
                            </span>
                            <span style="font-size: 12px; color: #86868b;">
                                {{ $item['last_viewed'] ? $item['last_viewed']->diffForHumans() : '' }}
                                · مشاهدة {{ $item['view_count'] }} مرة
                            </span>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <a href="{{ $item['compare_url'] }}" style="display: inline-flex; align-items: center; gap: 4px; background: #007aff; color: #fff; border: none; border-radius: 8px; padding: 6px 14px; font-size: 13px; font-weight: 600; text-decoration: none; transition: transform 150ms ease;" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                                إعادة المقارنة
                            </a>
                            <button onclick="shareComparison('{{ $item['share_token'] }}')" style="display: inline-flex; align-items: center; gap: 4px; background: #f5f5f7; color: #1d1d1f; border: none; border-radius: 8px; padding: 6px 14px; font-size: 13px; font-weight: 600; cursor: pointer; transition: transform 150ms ease;" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                                مشاركة
                            </button>
                        </div>
                    </div>

                    <div style="padding: 16px 20px; display: flex; gap: 16px; overflow-x: auto;">
                        @foreach ($item['products'] as $product)
                            <div style="min-width: 140px; text-align: center; flex-shrink: 0;">
                                <div style="width: 80px; height: 80px; background: #f5f5f7; border-radius: 12px; margin: 0 auto 8px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    @if ($product->images && is_array($product->images) && count($product->images) > 0)
                                        <img src="{{ asset($product->images[0]) }}" alt="{{ e($product->productName) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#c7c7cc" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    @endif
                                </div>
                                <a href="{{ route('catalog.show', $product->id) }}" style="font-size: 13px; font-weight: 600; color: #1d1d1f; text-decoration: none; display: block; line-height: 1.3;">{{ Str::limit($product->productName, 30) }}</a>
                                @if ($product->sales_price)
                                    <span style="font-size: 13px; font-weight: 700; color: #007aff; margin-top: 2px; display: block;">{{ number_format($product->sales_price, 2) }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
    function shareComparison(shareToken) {
        var url = '{{ url("/compare?share=") }}' + shareToken;

        if (navigator.share) {
            navigator.share({
                title: 'مقارنة المنتجات',
                text: 'شاهد هذه المقارنة:',
                url: url,
            }).catch(function() {});
        } else {
            navigator.clipboard.writeText(url).then(function() {
                alert('تم نسخ رابط المشاركة!');
            }).catch(function() {
                var input = document.createElement('input');
                input.value = url;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                alert('تم نسخ رابط المشاركة!');
            });
        }
    }
</script>
@endpush
@endsection
