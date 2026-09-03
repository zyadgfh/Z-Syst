@extends('layouts.master')

@section('title', __('orders.Online Store Analytics'))

@section('main_content')
<div class="container-fluid m-h-100">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="dashboard-title">{{ __('orders.Online Store Analytics') }}</h2>
            <p class="dashboard-subtitle">{{ __('orders.Insights from your online pharmacy store') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.customer-orders.index') }}" class="btn btn-secondary">
                <i class="fas fa-list me-2"></i>{{ __('orders.View Orders') }}
            </a>
            <select onchange="window.location.href='?period='+this.value" class="form-select w-auto">
                @foreach ([7, 14, 30, 60, 90] as $p)
                    <option value="{{ $p }}" @selected($period == $p)>{{ $p }} {{ __('common.days') }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card kpi-card p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon js-kpi-icon-blue">
                        <i class="fas fa-shopping-bag" style="color: #3b82f6;"></i>
                    </div>
                    <div>
                        <div class="kpi-value">{{ $kpis['total_orders'] }}</div>
                        <div class="kpi-label">{{ __('dashboard.Total Orders') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.1);">
                        <i class="fas fa-dollar-sign" class="text-green"></i>
                    </div>
                    <div>
                        <div class="kpi-value">${{ number_format($kpis['total_revenue'], 0) }}</div>
                        <div class="kpi-label">{{ __('dashboard.Total Revenue') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon js-kpi-icon-amber">
                        <i class="fas fa-clock" class="text-amber"></i>
                    </div>
                    <div>
                        <div class="kpi-value">{{ $kpis['pending_orders'] }}</div>
                        <div class="kpi-label">{{ __('orders.Pending Orders') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon js-kpi-icon-violet">
                        <i class="fas fa-receipt" class="text-violet"></i>
                    </div>
                    <div>
                        <div class="kpi-value">${{ number_format($kpis['average_order_value'], 2) }}</div>
                        <div class="kpi-label">{{ __('orders.Avg. Order Value') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Stats -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card kpi-card p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon js-kpi-icon-pink">
                        <i class="fas fa-users" style="color: #ec4899;"></i>
                    </div>
                    <div>
                        <div class="kpi-value">{{ $kpis['total_customers'] }}</div>
                        <div class="kpi-label">{{ __('orders.Total Customers') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card kpi-card p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon js-kpi-icon-teal">
                        <i class="fas fa-user-plus" style="color: #14b8a6;"></i>
                    </div>
                    <div>
                        <div class="kpi-value">{{ $kpis['new_customers'] }}</div>
                        <div class="kpi-label">{{ __('orders.New Customers') }} ({{ $period }} {{ __('common.days') }})</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Revenue Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('orders.Revenue Over Time') }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Orders by Status -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('orders.Orders by Status') }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Products -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('orders.Top Selling Products') }}</h5>
                </div>
                <div class="card-body">
                    @if ($topProducts->isEmpty())
                        <div class="text-center py-4 text-muted">{{ __('orders.No sales data yet.') }}</div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('common.Product') }}</th>
                                        <th class="text-center">{{ __('orders.Sold') }}</th>
                                        <th class="text-end">{{ __('orders.Revenue') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topProducts as $index => $tp)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <span class="fw-medium">{{ $tp->product->productName ?? 'N/A' }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge" style="background: #eff6ff; color: #1e40af;">{{ $tp->total_sold }}</span>
                                            </td>
                                            <td class="text-end fw-bold">${{ number_format($tp->total_revenue, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('orders.Recent Orders') }}</h5>
                    <a href="{{ route('admin.customer-orders.index') }}" class="text-decoration-none" style="color: #15803d; font-size: 13px;">
                        {{ __('common.View All') }} →
                    </a>
                </div>
                <div class="card-body">
                    @if ($recentOrders->isEmpty())
                        <div class="text-center py-4 text-muted">{{ __('orders.No orders yet.') }}</div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($recentOrders as $order)
                                <a href="{{ route('admin.customer-orders.show', $order) }}"
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-medium" class="fs-14">{{ $order->order_number }}</div>
                                        <small class="text-muted">{{ $order->customer_name }} · {{ $order->created_at->diffForHumans() }}</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold" class="text-green-dark">${{ number_format($order->total_amount, 2) }}</div>
                                        <span class="badge" style="font-size: 11px; background: {{ match($order->status) {
                                            'pending' => '#fef3c7; color: #92400e',
                                            'confirmed' => '#dbeafe; color: #1e40af',
                                            'delivered' => '#f0fdf4; color: #166534',
                                            'cancelled' => '#fef2f2; color: #991b1b',
                                            default => '#f3f4f6; color: #374151',
                                        } }}">{{ ucfirst($order->status) }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revenueData = @json($revenueByDay);
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx && Object.keys(revenueData).length > 0) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: Object.keys(revenueData).map(d => new Date(d).toLocaleDateString()),
                datasets: [{
                    label: '{{ __('orders.Revenue') }}',
                    data: Object.values(revenueData),
                    borderColor: '#15803d',
                    backgroundColor: 'rgba(21, 128, 61, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#15803d',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Status Chart
    const statusData = @json($ordersByStatus);
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx && Object.keys(statusData).length > 0) {
        const colors = {
            pending: '#f59e0b', confirmed: '#3b82f6', processing: '#8b5cf6',
            shipped: '#6366f1', delivered: '#10b981', cancelled: '#ef4444'
        };
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: Object.keys(statusData).map(s => colors[s] || '#9ca3af'),
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { padding: 16 } } },
                cutout: '60%',
            }
        });
    }
});
</script>
@endsection
