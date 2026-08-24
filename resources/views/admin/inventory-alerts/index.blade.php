@extends('layouts.master')

@section('title', 'تنبيهات المخزون')

@section('main_content')
    <div class="container-fluid" style="padding: 24px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 style="font-weight: 700;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    <line x1="12" y1="2" x2="12" y2="4"/>
                </svg>
                تنبيهات المخزون
                <span id="alerts-count-badge" style="background: #ff3b30; color: #fff; font-size: 12px; font-weight: 600; padding: 2px 8px; border-radius: 10px; vertical-align: super; margin-left: 8px;">{{ $stats['total'] }}</span>
            </h4>
            <div class="d-flex gap-2">
                <button onclick="runInventoryScan()" class="btn" id="scan-btn" style="background: #1d1d1f; color: #fff; border-radius: 10px; padding: 10px 20px; font-weight: 600; transition: transform 150ms ease;" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;"><path d="M21 12a9 9 0 11-6.22-8.56"/><polyline points="21 3 21 9 15 9"/></svg>
                    فحص المخزون
                </button>
                <button onclick="acknowledgeAll()" class="btn" style="background: #f5f5f7; color: #1d1d1f; border-radius: 10px; padding: 10px 20px; font-weight: 600;">
                    تأكيد الكل
                </button>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; background: linear-gradient(135deg, #fff0f0, #fff);">
                    <div class="card-body" style="padding: 20px;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p style="font-size: 12px; color: #86868b; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">حرج</p>
                                <h3 style="font-weight: 700; color: #ff3b30; margin: 4px 0 0;">{{ $stats['critical'] }}</h3>
                            </div>
                            <div style="width: 44px; height: 44px; border-radius: 12px; background: #ff3b30; display: flex; align-items: center; justify-content: center;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; background: linear-gradient(135deg, #fff8e1, #fff);">
                    <div class="card-body" style="padding: 20px;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p style="font-size: 12px; color: #86868b; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">تحذير</p>
                                <h3 style="font-weight: 700; color: #ff9500; margin: 4px 0 0;">{{ $stats['warning'] }}</h3>
                            </div>
                            <div style="width: 44px; height: 44px; border-radius: 12px; background: #ff9500; display: flex; align-items: center; justify-content: center;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; background: linear-gradient(135deg, #f5f5f7, #fff);">
                    <div class="card-body" style="padding: 20px;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p style="font-size: 12px; color: #86868b; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">نفذ من المخزون</p>
                                <h3 style="font-weight: 700; color: #1d1d1f; margin: 4px 0 0;">{{ $stats['out_of_stock'] }}</h3>
                            </div>
                            <div style="width: 44px; height: 44px; border-radius: 12px; background: #6e6e73; display: flex; align-items: center; justify-content: center;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; background: linear-gradient(135deg, #fff5f5, #fff);">
                    <div class="card-body" style="padding: 20px;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p style="font-size: 12px; color: #86868b; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">قرب انتهاء الصلاحية</p>
                                <h3 style="font-weight: 700; color: #ff3b30; margin: 4px 0 0;">{{ $stats['expiring'] }}</h3>
                            </div>
                            <div style="width: 44px; height: 44px; border-radius: 12px; background: #c0392b; display: flex; align-items: center; justify-content: center;">
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
                <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea;">
                    <div class="card-body" style="padding: 20px;">
                        <h6 style="font-weight: 600; margin-bottom: 16px;">التنبيهات خلال آخر 30 يوم</h6>
                        <canvas id="alertsTrendChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea;">
                    <div class="card-body" style="padding: 20px;">
                        <h6 style="font-weight: 600; margin-bottom: 16px;">حسب النوع</h6>
                        <canvas id="alertsTypeChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-4" style="border-radius: 14px; border: 1px solid #e5e5ea;">
            <div class="card-body" style="padding: 16px;">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; display: block;">بحث</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="اسم المنتج أو الباركود..." style="border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px;">
                    </div>
                    <div class="col-md-2">
                        <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; display: block;">النوع</label>
                        <select name="type" class="form-select" style="border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px;">
                            <option value="">الكل</option>
                            <option value="low_stock" {{ request('type') === 'low_stock' ? 'selected' : '' }}>مخزون منخفض</option>
                            <option value="out_of_stock" {{ request('type') === 'out_of_stock' ? 'selected' : '' }}>نفذ</option>
                            <option value="expiring_soon" {{ request('type') === 'expiring_soon' ? 'selected' : '' }}>قرب انتهاء</option>
                            <option value="expired" {{ request('type') === 'expired' ? 'selected' : '' }}>منتهي الصلاحية</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; display: block;">الخطورة</label>
                        <select name="severity" class="form-select" style="border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px;">
                            <option value="">الكل</option>
                            <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>حرج</option>
                            <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>تحذير</option>
                            <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>معلومات</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-size: 14px; padding-top: 8px;">
                            <input type="checkbox" name="acknowledged" value="1" {{ request('acknowledged') ? 'checked' : '' }} style="width: 16px; height: 16px;">
                            تمت القراءة
                        </label>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn w-100" style="background: #1d1d1f; color: #fff; border-radius: 10px; padding: 10px; font-weight: 600;">بحث</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Alerts List --}}
        <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; overflow: hidden;">
            @forelse ($alerts as $alert)
                <div id="alert-{{ $alert->id }}" class="alert-item d-flex align-items-start gap-3" style="padding: 16px 20px; border-bottom: 1px solid #f0f0f2; transition: background 150ms ease; {{ $alert->acknowledged ? 'opacity: 0.5;' : '' }}"
                     onmouseenter="this.style.background='#f9f9fb'" onmouseleave="this.style.background='transparent'">
                    {{-- Severity indicator --}}
                    <div style="width: 10px; height: 10px; border-radius: 50%; margin-top: 6px; flex-shrink: 0; background: {{ match($alert->severity) { 'critical' => '#ff3b30', 'warning' => '#ff9500', default => '#34c759' } }};"></div>

                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge" style="background: {{ match($alert->type) { 'low_stock' => '#fff3cd', 'out_of_stock' => '#f8d7da', 'expiring_soon' => '#d4edda', 'expired' => '#f8d7da', 'overstock' => '#d1ecf1', default => '#e2e3e5' }}; color: {{ match($alert->type) { 'low_stock' => '#856404', 'out_of_stock' => '#721c24', 'expiring_soon' => '#155724', 'expired' => '#721c24', 'overstock' => '#0c5460', default => '#383d41' } }}; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; border: none;">
                                {{ match($alert->type) { 'low_stock' => 'مخزون منخفض', 'out_of_stock' => 'نفذ من المخزون', 'expiring_soon' => 'قرب انتهاء', 'expired' => 'منتهي الصلاحية', 'overstock' => 'مخزون زائد', default => $alert->type } }}
                            </span>
                            @if ($alert->product)
                                <a href="{{ route('admin.items.show', $alert->product_id) }}" style="font-size: 14px; font-weight: 600; color: #007aff; text-decoration: none;">{{ $alert->product->productName }}</a>
                            @endif
                        </div>
                        <p style="font-size: 13px; color: #6e6e73; margin: 0;">{{ $alert->message }}</p>
                        <div class="d-flex align-items-center gap-3 mt-2" style="font-size: 12px; color: #86868b;">
                            <span>{{ $alert->created_at->diffForHumans() }}</span>
                            @if ($alert->current_stock > 0)
                                <span>المخزون الحالي: <strong>{{ $alert->current_stock }}</strong></span>
                            @endif
                            @if ($alert->suggested_reorder_qty)
                                <span>الكمية المقترحة للطلب: <strong style="color: #007aff;">{{ $alert->suggested_reorder_qty }}</strong></span>
                            @endif
                            @if ($alert->suggested_reorder_date)
                                <span>التاريخ المقترح: {{ $alert->suggested_reorder_date->format('Y-m-d') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex gap-2" style="flex-shrink: 0;">
                        @if ($alert->product && $alert->suggested_reorder_qty)
                            <a href="{{ route('admin.purchases.create') }}" class="btn btn-sm" style="background: #e8f5e9; color: #2e7d32; border: none; border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 600; text-decoration: none; transition: transform 150ms ease;"
                               onmousedown="this.style.transform='scale(0.95)'" onmouseup="this.style.transform='scale(1)'">
                                طلب شراء
                            </a>
                        @endif
                        @if (!$alert->acknowledged)
                            <button onclick="acknowledgeAlert({{ $alert->id }})" class="btn btn-sm" style="background: #f5f5f7; color: #1d1d1f; border: none; border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 600;">
                                تم
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#34c759" stroke-width="1.5" style="margin-bottom: 12px;">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <h5 style="color: #1d1d1f; font-weight: 600;">لا توجد تنبيهات</h5>
                    <p style="color: #86868b; font-size: 14px;">جميع المنتجات في حالة مخزون جيدة</p>
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
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"/></svg> جاري الفحص...';

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
