@extends('layouts.master')

@section('title', __('dashboard.Analytics Dashboard'))

@section('main_content')
<!-- Analytics Header -->
<div class="analytics-header mb-xl">
    <div class="card">
        <div class="card-body">
            <h1>{{ __('dashboard.Analytics Dashboard') }}</h1>
            <p class="text-muted">{{ __('dashboard.Advanced analytics and performance metrics') }}</p>
        </div>
    </div>
</div>

<!-- Performance Overview -->
<div class="performance-overview mb-xl">
    <div class="card">
        <div class="card-header">
            <h2>{{ __('dashboard.Performance Overview') }}</h2>
        </div>
        <div class="card-body">
            <canvas id="performanceChart" height="120"></canvas>
        </div>
    </div>
</div>

<!-- Analytics KPIs -->
<div class="analytics-kpis mb-xl">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-lg">
        <div class="card kpi-card">
            <div class="card-body">
                <div class="kpi-icon text-primary">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="kpi-value">$245,670</h3>
                <p class="kpi-label text-muted">{{ __('dashboard.Monthly Revenue') }}</p>
                <div class="kpi-trend text-success">
                    <i class="fas fa-arrow-up"></i> 22.4%
                </div>
            </div>
        </div>

        <div class="card kpi-card">
            <div class="card-body">
                <div class="kpi-icon text-secondary">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 8V4L8 8L12 4V8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="kpi-value">15.2%</h3>
                <p class="kpi-label text-muted">{{ __('dashboard.Growth Rate') }}</p>
                <div class="kpi-trend text-success">
                    <i class="fas fa-arrow-up"></i> 3.8%
                </div>
            </div>
        </div>

        <div class="card kpi-card">
            <div class="card-body">
                <div class="kpi-icon text-accent">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 7L9 18L4 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="kpi-value">94.5%</h3>
                <p class="kpi-label text-muted">{{ __('dashboard.Customer Satisfaction') }}</p>
                <div class="kpi-trend text-success">
                    <i class="fas fa-arrow-up"></i> 1.2%
                </div>
            </div>
        </div>

        <div class="card kpi-card">
            <div class="card-body">
                <div class="kpi-icon text-warning">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="kpi-value">1,847</h3>
                <p class="kpi-label text-muted">{{ __('dashboard.Active Customers') }}</p>
                <div class="kpi-trend text-success">
                    <i class="fas fa-arrow-up"></i> 8.7%
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Analytics -->
<div class="detailed-analytics mb-xl">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-lg">
        <!-- Revenue by Category -->
        <div class="card">
            <div class="card-header">
                <h2>{{ __('dashboard.Revenue by Category') }}</h2>
            </div>
            <div class="card-body">
                <canvas id="revenueCategoryChart" height="100"></canvas>
            </div>
        </div>

        <!-- Customer Retention -->
        <div class="card">
            <div class="card-header">
                <h2>{{ __('dashboard.Customer Retention') }}</h2>
            </div>
            <div class="card-body">
                <canvas id="retentionChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Forecast Section -->
<div class="forecast-section mb-xl">
    <div class="card">
        <div class="card-header">
            <h2>{{ __('dashboard.AI-Powered Forecasting') }}</h2>
        </div>
        <div class="card-body">
            <div class="forecast-alert mb-lg">
                <div class="alert alert-info d-flex align-items-center gap-lg">
                    <div class="alert-icon text-info">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 16V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 8H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <h4>{{ __('dashboard.AI Prediction') }}</h4>
                        <p class="text-muted text-sm">{{ __('dashboard.Based on historical data, sales are expected to increase by 15% next month') }}</p>
                    </div>
                </div>
            </div>
            <canvas id="forecastChart" height="100"></canvas>
        </div>
    </div>
</div>

@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Performance Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Revenue',
                data: [180000, 195000, 210000, 190000, 225000, 240000, 230000, 245000, 260000, 255000, 270000, 245670],
                borderColor: '#15803D',
                backgroundColor: 'rgba(21, 128, 61, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Expenses',
                data: [120000, 130000, 140000, 135000, 145000, 150000, 148000, 155000, 160000, 158000, 165000, 152000],
                borderColor: '#0369A1',
                backgroundColor: 'rgba(3, 105, 161, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Revenue by Category Chart
    const revenueCategoryCtx = document.getElementById('revenueCategoryChart').getContext('2d');
    new Chart(revenueCategoryCtx, {
        type: 'bar',
        data: {
            labels: ['Medicines', 'Supplements', 'Medical Devices', 'Personal Care'],
            datasets: [{
                label: 'Revenue',
                data: [120000, 45000, 52000, 28000],
                backgroundColor: ['#15803D', '#22C55E', '#0369A1', '#F59E0B']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Customer Retention Chart
    const retentionCtx = document.getElementById('retentionChart').getContext('2d');
    new Chart(retentionCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Retention Rate',
                data: [85, 87, 89, 88, 92, 94.5],
                borderColor: '#22C55E',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    min: 70,
                    max: 100
                }
            }
        }
    });

    // Forecast Chart
    const forecastCtx = document.getElementById('forecastChart').getContext('2d');
    new Chart(forecastCtx, {
        type: 'line',
        data: {
            labels: ['Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May'],
            datasets: [{
                label: 'Actual Sales',
                data: [245670, null, null, null, null],
                borderColor: '#15803D',
                backgroundColor: '#15803D',
                pointRadius: 6
            }, {
                label: 'Forecast',
                data: [null, 282000, 295000, 310000, 325000, 340000],
                borderColor: '#0369A1',
                backgroundColor: 'rgba(3, 105, 161, 0.1)',
                borderDash: [5, 5],
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
</script>
@endpush
