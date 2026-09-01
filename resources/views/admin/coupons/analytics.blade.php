@extends('layouts.master')

@section('title', 'تحليلات الكوبونات')

@section('main_content')
<div class="container-fluid" style="padding: 24px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.coupons.index') }}" style="color: #007aff; text-decoration: none;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            </a>
            <h4 style="font-weight: 700; margin: 0;">تحليلات الكوبونات</h4>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; background: linear-gradient(135deg, #e3f2fd, #fff);">
                <div class="card-body" style="padding: 20px;">
                    <p style="font-size: 12px; color: #86868b; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">إجمالي الاستخدامات</p>
                    <h3 style="font-weight: 700; color: #1565c0; margin: 6px 0 0;">{{ number_format($totalUsages) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; background: linear-gradient(135deg, #e8f5e9, #fff);">
                <div class="card-body" style="padding: 20px;">
                    <p style="font-size: 12px; color: #86868b; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">إجمالي الخصومات</p>
                    <h3 style="font-weight: 700; color: #2e7d32; margin: 6px 0 0;">${{ number_format($totalDiscountGiven, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; background: linear-gradient(135deg, #f3e5f5, #fff);">
                <div class="card-body" style="padding: 20px;">
                    <p style="font-size: 12px; color: #86868b; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">الإيرادات بالكوبونات</p>
                    <h3 style="font-weight: 700; color: #7b1fa2; margin: 6px 0 0;">${{ number_format($couponRevenue, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; background: linear-gradient(135deg, #fff8e1, #fff);">
                <div class="card-body" style="padding: 20px;">
                    <p style="font-size: 12px; color: #86868b; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">متوسط قيمة الطلب</p>
                    <h3 style="font-weight: 700; color: #f57f17; margin: 6px 0 0;">${{ number_format($avgOrderValue ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea;">
                <div class="card-body d-flex align-items-center gap-3" style="padding: 16px 20px;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #e3f2fd; display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1565c0" stroke-width="2"><path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6"/><path d="M2 8h20v4H2z"/></svg>
                    </div>
                    <div>
                        <p style="font-size: 12px; color: #86868b; margin: 0;">الكوبونات النشطة</p>
                        <p style="font-size: 20px; font-weight: 700; margin: 0; color: #1d1d1f;">{{ $activeCoupons }} <span style="font-size: 13px; color: #86868b; font-weight: 400;">/ {{ $totalCoupons }}</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea;">
                <div class="card-body d-flex align-items-center gap-3" style="padding: 16px 20px;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #e8f5e9; display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <div>
                        <p style="font-size: 12px; color: #86868b; margin: 0;">الطلبات باستخدام كوبونات</p>
                        <p style="font-size: 20px; font-weight: 700; margin: 0; color: #1d1d1f;">{{ $ordersWithCoupons }} <span style="font-size: 13px; color: #86868b; font-weight: 400;">/ {{ $totalOrders }}</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea;">
                <div class="card-body d-flex align-items-center gap-3" style="padding: 16px 20px;">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #fff3e0; display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e65100" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <p style="font-size: 12px; color: #86868b; margin: 0;">نسبة التحويل</p>
                        <p style="font-size: 20px; font-weight: 700; margin: 0; color: #1d1d1f;">{{ $conversionRate }}%</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea;">
                <div class="card-body" style="padding: 20px;">
                    <h6 style="font-weight: 600; margin-bottom: 16px;">الاستخدام والخصومات — آخر 30 يوم</h6>
                    <canvas id="usageTrendChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea;">
                <div class="card-body" style="padding: 20px;">
                    <h6 style="font-weight: 600; margin-bottom: 16px;">التوزيع حسب النوع</h6>
                    <canvas id="typeChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Performing Coupons --}}
    <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; overflow: hidden;">
        <div class="card-body" style="padding: 20px;">
            <h6 style="font-weight: 600; margin-bottom: 16px;">أكثر الكوبونات استخداماً</h6>
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size: 14px;">
                    <thead style="background: #f5f5f7;">
                        <tr>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">الكود</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">النوع</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">الاستخدامات</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">إجمالي الخصم</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topCoupons as $coupon)
                            <tr style="border-bottom: 1px solid #f0f0f2;">
                                <td style="padding: 12px 16px;"><code style="background: #f5f5f7; padding: 4px 10px; border-radius: 6px; font-weight: 600;">{{ $coupon->code }}</code></td>
                                <td style="padding: 12px 16px;">
                                    @if ($coupon->type === 'percentage')
                                        <span style="background: #e8f5e9; color: #2e7d32; padding: 4px 10px; border-radius: 6px; font-size: 13px; font-weight: 600;">{{ $coupon->value }}%</span>
                                    @else
                                        <span style="background: #e3f2fd; color: #1565c0; padding: 4px 10px; border-radius: 6px; font-size: 13px; font-weight: 600;">${{ number_format($coupon->value, 2) }}</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; font-weight: 600;">{{ $coupon->usages_count }}</td>
                                <td style="padding: 12px 16px; font-weight: 600; color: #2e7d32;">${{ number_format($coupon->usages_sum_discount_amount ?? 0, 2) }}</td>
                                <td style="padding: 12px 16px;">
                                    @if ($coupon->isCurrentlyValid())
                                        <span style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">نشط</span>
                                    @else
                                        <span style="background: #f8d7da; color: #721c24; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">منتهي</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4" style="color: #86868b;">لا توجد بيانات بعد</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // ── Usage Trend Chart ──
    const trendData = @json($usageOverTime);
    if (trendData.length > 0) {
        new Chart(document.getElementById('usageTrendChart'), {
            type: 'bar',
            data: {
                labels: trendData.map(d => d.date.substring(5)),
                datasets: [
                    {
                        label: 'الاستخدامات',
                        data: trendData.map(d => d.uses),
                        backgroundColor: 'rgba(0,122,255,0.7)',
                        borderRadius: 6,
                        yAxisID: 'y',
                    },
                    {
                        label: 'الخصومات ($)',
                        data: trendData.map(d => d.discount),
                        type: 'line',
                        borderColor: '#ff3b30',
                        backgroundColor: 'rgba(255,59,48,0.08)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointBackgroundColor: '#ff3b30',
                        yAxisID: 'y1',
                    }
                ],
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, position: 'left', title: { display: true, text: 'الاستخدامات' } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'الخصومات ($)' } },
                },
            },
        });
    }

    // ── Type Distribution Chart ──
    const typeData = @json($typeDistribution);
    if (Object.keys(typeData).length > 0) {
        const typeLabels = { percentage: 'نسبة مئوية', fixed: 'مبلغ ثابت' };
        const typeColors = { percentage: '#007aff', fixed: '#34c759' };
        new Chart(document.getElementById('typeChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(typeData).map(k => typeLabels[k] || k),
                datasets: [{
                    data: Object.values(typeData),
                    backgroundColor: Object.keys(typeData).map(k => typeColors[k] || '#86868b'),
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } } },
            },
        });
    }
</script>
@endpush
@endsection
