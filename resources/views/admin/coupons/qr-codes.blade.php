@extends('layouts.admin')

@section('title', 'أكواد الكوبونات — QR Codes')

@section('main_content')
    <div class="container-fluid" class="card-body-lg">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('admin.coupons.index') }}" class="link-blue">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                </a>
                <h4 class="heading-bold">
                    📱 أكواد الكوبونات — QR Codes
                    <span class="fz14-normal-muted-ml8">({{ $codes->count() }} كود)</span>
                </h4>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn-inline-blue">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    طباعة
                </button>
                <button onclick="downloadAll()" class="btn-inline-gray">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    تحميل CSV
                </button>
            </div>
        </div>

        <div id="qrGrid" class="grid-auto-fill">
            @foreach ($codes as $item)
                <div class="qr-card card-apple" onmouseenter="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseleave="this.style.boxShadow='none'">
                    <div class="img-preview">
                        <img src="{{ $item['qr_url'] }}" alt="QR Code for {{ $item['code'] }}" class="thumb-contain w-full h-full" loading="lazy">
                    </div>

                    <code class="block-code">{{ $item['code'] }}</code>

                    <div class="fz13-subtle-mb4">
                        @if ($item['type'] === 'percentage')
                            <span class="badge-green-tag">{{ $item['value'] }}%</span>
                        @else
                            <span class="badge-blue-tag">{{ number_format($item['value'], 2) }}</span>
                        @endif
                    </div>

                    @if ($item['description'])
                        <div class="fz11-muted-mt4">{{ $item['description'] }}</div>
                    @endif

                    @if ($item['expires_at'])
                        <div class="fz11-muted-mt2">ينتهي: {{ $item['expires_at'] }}</div>
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
