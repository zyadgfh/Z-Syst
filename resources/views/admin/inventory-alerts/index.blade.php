@extends('layouts.admin')

@section('title', 'تنبيهات المخزون')

@section('main_content')
    <div class="container-fluid" class="card-body-lg">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 class="fw-700">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-align-lg">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    <line x1="12" y1="2" x2="12" y2="4"/>
                </svg>
                تنبيهات المخزون
                <span id="alerts-count-badge" class="js-alert-count-badge">{{ $stats['total'] }}</span>
            </h4>
            <div class="d-flex gap-2">
                <button onclick="runInventoryScan()" class="btn" id="scan-btn" class="btn-dark" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-align"><path d="M21 12a9 9 0 11-6.22-8.56"/><polyline points="21 3 21 9 15 9"/></svg>
                    فحص المخزون
                </button>
                <button onclick="acknowledgeAll()" class="btn js-btn-secondary">
                    تأكيد الكل
                </button>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card" class="card-border-gradient-red">
                    <div class="card-body" class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="section-subtitle-xs">حرج</p>
                                <h3 class="stat-lg-red">{{ $stats['critical'] }}</h3>
                            </div>
                            <div class="icon-container-44 icon-container-red">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" class="card-border-gradient-amber">
                    <div class="card-body" class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="section-subtitle-xs">تحذير</p>
                                <h3 class="stat-lg-amber">{{ $stats['warning'] }}</h3>
                            </div>
                            <div class="icon-container-44 icon-container-amber">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-gradient-gray">
                    <div class="card-body" class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="section-subtitle-xs">نفذ من المخزون</p>
                                <h3 class="fw-700-dark-mt4">{{ $stats['out_of_stock'] }}</h3>
                            </div>
                            <div class="icon-container-44 bg-gray">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-gradient-red">
                    <div class="card-body" class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="section-subtitle-xs">قرب انتهاء الصلاحية</p>
                                <h3 class="stat-lg-red">{{ $stats['expiring'] }}</h3>
                            </div>
                            <div class="js-icon-container bg-red">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
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
                        <h6 class="heading-md">التنبيهات خلال آخر 30 يوم</h6>
                        <canvas id="alertsTrendChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card" class="card-clean-bordered">
                    <div class="card-body" class="card-body">
                        <h6 class="heading-md">حسب النوع</h6>
                        <canvas id="alertsTypeChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-4" class="card-clean-bordered">
            <div class="card-body" class="p-16">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label-xs">بحث</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="اسم المنتج أو الباركود..." class="input-clean-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-xs">النوع</label>
                        <select name="type" class="form-select" class="input-clean-sm">
                            <option value="">الكل</option>
                            <option value="low_stock" {{ request('type') === 'low_stock' ? 'selected' : '' }}>مخزون منخفض</option>
                            <option value="out_of_stock" {{ request('type') === 'out_of_stock' ? 'selected' : '' }}>نفذ</option>
                            <option value="expiring_soon" {{ request('type') === 'expiring_soon' ? 'selected' : '' }}>قرب انتهاء</option>
                            <option value="expired" {{ request('type') === 'expired' ? 'selected' : '' }}>منتهي الصلاحية</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label-xs">الخطورة</label>
                        <select name="severity" class="form-select" class="input-clean-sm">
                            <option value="">الكل</option>
                            <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>حرج</option>
                            <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>تحذير</option>
                            <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>معلومات</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="inline-flex-center-gap6 fz14 pt-8">
                            <input type="checkbox" name="acknowledged" value="1" {{ request('acknowledged') ? 'checked' : '' }} class="icon-md">
                            تمت القراءة
                        </label>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn w-100 pad-sm" class="btn-dark">بحث</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Alerts List --}}
        <div class="card" class="card-clean">
            @forelse ($alerts as $alert)
                <div id="alert-{{ $alert->id }}" class="alert-item d-flex align-items-start gap-3" class="card-header" style="border-bottom: 1px solid #f0f0f2; transition: background 150ms ease; {{ $alert->acknowledged ? 'opacity: 0.5;' : '' }}"
                     onmouseenter="this.style.background='#f9f9fb'" onmouseleave="this.style.background='transparent'">
                    {{-- Severity indicator --}}
                    <div data-severity-dot="{{ match($alert->severity) { 'critical' => 'critical', 'warning' => 'warning', default => 'info' } }}"></div>

                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge" data-alert-type="{{ $alert->type }}">
                                {{ match($alert->type) { 'low_stock' => 'مخزون منخفض', 'out_of_stock' => 'نفذ من المخزون', 'expiring_soon' => 'قرب انتهاء', 'expired' => 'منتهي الصلاحية', 'overstock' => 'مخزون زائد', default => $alert->type } }}
                            </span>
                            @if ($alert->product)
                                <a href="{{ route('admin.items.show', $alert->product_id) }}" class="fz14-semibold-blue">{{ $alert->product->productName }}</a>
                            @endif
                        </div>
                        <p class="fz13-subtle">{{ $alert->message }}</p>
                        <div class="d-flex align-items-center gap-3 mt-2" class="stat-label">
                            <span>{{ $alert->created_at->diffForHumans() }}</span>
                            @if ($alert->current_stock > 0)
                                <span>المخزون الحالي: <strong>{{ $alert->current_stock }}</strong></span>
                            @endif
                            @if ($alert->suggested_reorder_qty)
                                <span>الكمية المقترحة للطلب: <strong class="c-blue">{{ $alert->suggested_reorder_qty }}</strong></span>
                            @endif
                            @if ($alert->suggested_reorder_date)
                                <span>التاريخ المقترح: {{ $alert->suggested_reorder_date->format('Y-m-d') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-noshrink">
                        @if ($alert->product && $alert->suggested_reorder_qty)
                            <a href="{{ route('admin.purchases.create') }}" class="btn btn-sm btn-green-outline-sm"
                               onmousedown="this.style.transform='scale(0.95)'" onmouseup="this.style.transform='scale(1)'">
                                طلب شراء
                            </a>
                        @endif
                        @if (!$alert->acknowledged)
                            <button onclick="acknowledgeAlert({{ $alert->id }})" class="btn btn-sm" class="btn-apple-gray-xs">
                                تم
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#34c759" stroke-width="1.5" class="mb-12">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <h5 class="c-dark fw-600">لا توجد تنبيهات</h5>
                    <p class="text-14 text-muted">جميع المنتجات في حالة مخزون جيدة</p>
                </div>
            @endforelse
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $alerts->withQueryString()->links() }}
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // ── Load Chart Data ──
        fetch('{{ route("admin.inventory-alerts.chart-data") }}')
            .then(r => r.json())
            .then(data => {
                // Trend Chart
                const trendCtx = document.getElementById('alertsTrendChart');
                if (trendCtx && data.dailyTrend) {
                    new Chart(trendCtx, {
                        type: 'line',
                        data: {
                            labels: Object.keys(data.dailyTrend).map(d => d.substring(5)),
                            datasets: [{
                                label: 'التنبيهات',
                                data: Object.values(data.dailyTrend),
                                borderColor: '#007aff',
                                backgroundColor: 'rgba(0,122,255,0.08)',
                                fill: true,
                                tension: 0.4,
                                pointRadius: 3,
                                pointBackgroundColor: '#007aff',
                            }],
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { grid: { display: false } },
                                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                            },
                        },
                    });
                }

                // Type Chart
                const typeCtx = document.getElementById('alertsTypeChart');
                if (typeCtx && data.byType) {
                    const typeLabels = { low_stock: 'مخزون منخفض', out_of_stock: 'نفذ', expiring_soon: 'قرب انتهاء', expired: 'منتهي', overstock: 'زائد' };
                    const typeColors = { low_stock: '#ff9500', out_of_stock: '#ff3b30', expiring_soon: '#ffcc00', expired: '#c0392b', overstock: '#34c759' };

                    new Chart(typeCtx, {
                        type: 'doughnut',
                        data: {
                            labels: Object.keys(data.byType).map(k => typeLabels[k] || k),
                            datasets: [{
                                data: Object.values(data.byType),
                                backgroundColor: Object.keys(data.byType).map(k => typeColors[k] || '#86868b'),
                                borderWidth: 0,
                            }],
                        },
                        options: {
                            responsive: true,
                            cutout: '65%',
                            plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true } } },
                        },
                    });
                }
            })
            .catch(() => {});

        // ── Actions ──
        function acknowledgeAlert(id) {
            fetch(`/admin/inventory-alerts/${id}/acknowledge`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const el = document.getElementById(`alert-${id}`);
                    if (el) {
                        el.style.transition = 'opacity 300ms ease';
                        el.style.opacity = '0.3';
                        el.querySelector('.btn-sm:last-child')?.remove();
                    }
                }
            });
        }

        function acknowledgeAll() {
            if (!confirm('تأكيد تأكيد جميع التنبيهات؟')) return;

            fetch('/admin/inventory-alerts/acknowledge-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
            });
        }

        function runInventoryScan() {
            const btn = document.getElementById('scan-btn');
            btn.disabled = true;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="js-spinning"><circle cx="12" cy="12" r="10"/></svg> جاري الفحص...';

            fetch('/admin/inventory-alerts/scan', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = 'فحص المخزون';
            });
        }
    </script>
    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
    @endpush
@endsection
