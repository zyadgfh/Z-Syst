@extends('layouts.master')

@section('title', 'تحليلات الكوبونات')

@section('main_content')
<div class="container-fluid" class="card-body-lg">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.coupons.index') }}" class="link-blue">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            </a>
            <h4 class="heading-bold">تحليلات الكوبونات</h4>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card js-card-gradient-info">
                <div class="card-body">
                    <p class="section-subtitle-xs">إجمالي الاستخدامات</p>
                    <h3 class="js-stat-value-lg" style="color: #1565c0;">{{ number_format($totalUsages) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card js-card-gradient-success">
                <div class="card-body">
                    <p class="section-subtitle-xs">إجمالي الخصومات</p>
                    <h3 class="js-stat-value-lg" style="color: #2e7d32;">${{ number_format($totalDiscountGiven, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card js-card-gradient-purple">
                <div class="card-body">
                    <p class="section-subtitle-xs">الإيرادات بالكوبونات</p>
                    <h3 class="js-stat-value-lg" style="color: #7b1fa2;">${{ number_format($couponRevenue, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card js-card-gradient-amber">
                <div class="card-body">
                    <p class="section-subtitle-xs">متوسط قيمة الطلب</p>
                    <h3 class="js-stat-value-lg" style="color: #f57f17;">${{ number_format($avgOrderValue ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card" class="card-clean-bordered">
                <div class="card-body d-flex align-items-center gap-3" class="p-16-20">
                    <div class="js-icon-container-sm" style="background: #e3f2fd;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1565c0" stroke-width="2"><path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6"/><path d="M2 8h20v4H2z"/></svg>
                    </div>
                    <div>
                        <p class="fs-12-color-muted">الكوبونات النشطة</p>
                        <p class="section-title-xl">{{ $activeCoupons }} <span class="section-subtitle">/ {{ $totalCoupons }}</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" class="card-clean-bordered">
                <div class="card-body d-flex align-items-center gap-3" class="p-16-20">
                    <div class="js-icon-container-sm" style="background: #e8f5e9;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <div>
                        <p class="fs-12-color-muted">الطلبات باستخدام كوبونات</p>
                        <p class="section-title-xl">{{ $ordersWithCoupons }} <span class="section-subtitle">/ {{ $totalOrders }}</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" class="card-clean-bordered">
                <div class="card-body d-flex align-items-center gap-3" class="p-16-20">
                    <div class="js-icon-container-sm" style="background: #fff3e0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e65100" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <p class="fs-12-color-muted">نسبة التحويل</p>
                        <p class="section-title-xl">{{ $conversionRate }}%</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card" class="card-clean-bordered">
                <div class="card-body" class="card-body">
                    <h6 class="heading-md">الاستخدام والخصومات — آخر 30 يوم</h6>
                    <canvas id="usageTrendChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" class="card-clean-bordered">
                <div class="card-body" class="card-body">
                    <h6 class="heading-md">التوزيع حسب النوع</h6>
                    <canvas id="typeChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Performing Coupons --}}
    <div class="card" class="card-clean">
        <div class="card-body" class="card-body">
            <h6 class="heading-md">أكثر الكوبونات استخداماً</h6>
            <div class="table-responsive">
                <table class="table table-hover mb-0" class="fs-14">
                    <thead class="bg-light">
                        <tr>
                            <th class="tab-btn-upper">الكود</th>
                            <th class="tab-btn-upper">النوع</th>
                            <th class="tab-btn-upper">الاستخدامات</th>
                            <th class="tab-btn-upper">إجمالي الخصم</th>
                            <th class="tab-btn-upper">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topCoupons as $coupon)
                            <tr class="border-bottom-light">
                                <td class="card-body-sm"><code class="js-badge-code">{{ $coupon->code }}</code></td>
                                <td class="card-body-sm">
                                    @if ($coupon->type === 'percentage')
                                        <span class="js-badge-success" style="font-size: 13px;">{{ $coupon->value }}%</span>
                                    @else
                                        <span class="js-badge-info" style="font-size: 13px;">${{ number_format($coupon->value, 2) }}</span>
                                    @endif
                                </td>
                                <td class="p-12-16-600">{{ $coupon->usages_count }}</td>
                                <td class="js-table-cell" style="font-weight: 600; color: #2e7d32;">${{ number_format($coupon->usages_sum_discount_amount ?? 0, 2) }}</td>
                                <td class="card-body-sm">
                                    @if ($coupon->isCurrentlyValid())
                                        <span class="js-badge-success" style="font-size: 12px;">نشط</span>
                                    @else
                                        <span class="js-badge-pill" style="background: #f8d7da; color: #721c24; font-size: 12px;">منتهي</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4" class="text-muted-custom">لا توجد بيانات بعد</td>
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
