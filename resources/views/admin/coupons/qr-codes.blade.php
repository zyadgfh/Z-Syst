@extends('layouts.master')

@section('title', 'أكواد الكوبونات — QR Codes')

@section('main_content')
    <div class="container-fluid" style="padding: 24px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('admin.coupons.index') }}" style="color: #007aff; text-decoration: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                </a>
                <h4 style="font-weight: 700; margin: 0;">
                    📱 أكواد الكوبونات — QR Codes
                    <span style="font-size: 14px; font-weight: 400; color: #86868b; margin-left: 8px;">({{ $codes->count() }} كود)</span>
                </h4>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" style="display: inline-flex; align-items: center; gap: 6px; background: #007aff; color: #fff; border: none; border-radius: 10px; padding: 10px 18px; font-weight: 600; font-size: 14px; cursor: pointer;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    طباعة
                </button>
                <button onclick="downloadAll()" style="display: inline-flex; align-items: center; gap: 6px; background: #f5f5f7; color: #1d1d1f; border: none; border-radius: 10px; padding: 10px 18px; font-weight: 600; font-size: 14px; cursor: pointer;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    تحميل CSV
                </button>
            </div>
        </div>

        <div id="qrGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px;">
            @foreach ($codes as $item)
                <div class="qr-card" style="background: #fff; border-radius: 14px; border: 1px solid #e5e5ea; padding: 16px; text-align: center; transition: box-shadow 150ms ease;" onmouseenter="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseleave="this.style.boxShadow='none'">
                    <div style="width: 160px; height: 160px; margin: 0 auto 12px; background: #f8f8fa; border-radius: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <img src="{{ $item['qr_url'] }}" alt="QR Code for {{ $item['code'] }}" style="width: 100%; height: 100%; object-fit: contain;" loading="lazy">
                    </div>

                    <code style="display: block; font-size: 16px; font-weight: 700; color: #1d1d1f; letter-spacing: 0.06em; margin-bottom: 6px; background: #f5f5f7; padding: 6px 12px; border-radius: 8px;">{{ $item['code'] }}</code>

                    <div style="font-size: 13px; color: #6e6e73; margin-bottom: 4px;">
                        @if ($item['type'] === 'percentage')
                            <span style="background: #e8f5e9; color: #2e7d32; padding: 2px 8px; border-radius: 4px; font-weight: 600;">{{ $item['value'] }}%</span>
                        @else
                            <span style="background: #e3f2fd; color: #1565c0; padding: 2px 8px; border-radius: 4px; font-weight: 600;">{{ number_format($item['value'], 2) }}</span>
                        @endif
                    </div>

                    @if ($item['description'])
                        <div style="font-size: 11px; color: #86868b; margin-top: 4px;">{{ $item['description'] }}</div>
                    @endif

                    @if ($item['expires_at'])
                        <div style="font-size: 11px; color: #86868b; margin-top: 2px;">ينتهي: {{ $item['expires_at'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <script>
        function downloadAll() {
            var ids = @json($codes->pluck('code')->keys()->values());
            // Build CSV content client-side from the page data
            var csvContent = '\uFEFF'; // BOM for UTF-8
            csvContent += 'الكود,النوع,القيمة,الوصف,تاريخ الانتهاء\n';

            @json($codes)->forEach(function(item) {
                csvContent += '"' + item.code + '","' +
                    (item.type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت') + '",' +
                    item.value + ',"' + (item.description || '') + '","' + (item.expires_at || '') + '"\n';
            });

            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'coupon_codes_qr_{{ now()->format("Y-m-d") }}.csv';
            a.click();
            URL.revokeObjectURL(url);
        }

        @media print {
            .qr-card {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .btn, button {
                display: none !important;
            }
        }
    </script>
@endsection
