@extends('layouts.master')

@section('title')
    {{ __('dashboard.Dashboard') }}
@endsection

@section('main_content')
<div class="container-fluid m-h-100">
    <!-- Dashboard Header -->
    <div class="dashboard-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="dashboard-title">{{ __('dashboard.Welcome Back') }}!</h2>
                <p class="dashboard-subtitle">{{ __('dashboard.Here\'s what\'s happening with your business today') }}</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary">
                    <i class="fas fa-download me-2"></i>{{ __('dashboard.Export Report') }}
                </button>
                <button class="btn btn-secondary">
                    <i class="fas fa-sync-alt me-2"></i>{{ __('common.Refresh') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Getting Started Checklist (for new users) -->
    @include('admin.dashboard.partials.getting-started')

    <!-- Role-Based Dashboard Content -->
    @include('admin.dashboard.partials.role-based-content')

    <!-- Stats Grid -->
    <div class="stats-grid-container">
        <div class="kpi-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-lg">
            <!-- Total Shops -->
            <div class="card kpi-card">
                <div class="kpi-icon">
                    <i class="fas fa-store text-primary"></i>
                </div>
                <div class="kpi-value" id="total_businesses">0</div>
                <div class="kpi-label">{{ __('dashboard.Total Shops') }}</div>
                <div class="kpi-trend stat-trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <span>12%</span>
                </div>
            </div>

            <!-- Expired Businesses -->
            <div class="card kpi-card">
                <div class="kpi-icon js-kpi-icon-amber">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                </div>
                <div class="kpi-value" id="expired_businesses">0</div>
                <div class="kpi-label">{{ __('dashboard.Expired Businesses') }}</div>
                <div class="kpi-trend stat-trend-down">
                    <i class="fas fa-arrow-down"></i>
                    <span>5%</span>
                </div>
            </div>

            <!-- Plan Subscribes -->
            <div class="card kpi-card">
                <div class="kpi-icon js-kpi-icon-green">
                    <i class="fas fa-users text-success"></i>
                </div>
                <div class="kpi-value" id="plan_subscribes">0</div>
                <div class="kpi-label">{{ __('dashboard.Plan Subscribes') }}</div>
                <div class="kpi-trend stat-trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <span>8%</span>
                </div>
            </div>

            <!-- Total Categories -->
            <div class="card kpi-card">
                <div class="kpi-icon js-kpi-icon-blue">
                    <i class="fas fa-th-large text-info"></i>
                </div>
                <div class="kpi-value" id="business_categories">0</div>
                <div class="kpi-label">{{ __('dashboard.Total Categories') }}</div>
                <div class="kpi-trend stat-trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <span>3%</span>
                </div>
            </div>

            <!-- Total Plans -->
            <div class="card kpi-card">
                <div class="kpi-icon js-kpi-icon-violet">
                    <i class="fas fa-file-alt" class="text-violet"></i>
                </div>
                <div class="kpi-value" id="total_plans">0</div>
                <div class="kpi-label">{{ __('dashboard.Total Plans') }}</div>
                <div class="kpi-trend stat-trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <span>15%</span>
                </div>
            </div>

            <!-- Revenue -->
            <div class="card kpi-card">
                <div class="kpi-icon" style="background: linear-gradient(135deg, rgba(21, 128, 61, 0.1) 0%, rgba(3, 105, 161, 0.1) 100%);">
                    <i class="fas fa-dollar-sign text-primary"></i>
                </div>
                <div class="kpi-value" id="total_revenue">$0</div>
                <div class="kpi-label">{{ __('dashboard.Total Revenue') }}</div>
                <div class="kpi-trend stat-trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <span>22%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="row">
            <!-- Subscription Plans Chart -->
            <div class="col-xxl-4 mb-4">
                <div class="card modern-card-chart">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title-group">
                            <h4>{{ __('dashboard.Subscription Plans') }}</h4>
                            <p class="card-subtitle">{{ __('dashboard.Monthly subscriptions overview') }}</p>
                        </div>
                        <div class="card-actions">
                            <select class="form-select form-select-sm modern-select">
                                @for ($i = date('Y'); $i >= 2022; $i--)
                                    <option @selected($i == date('Y')) value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="plans-chart" class="modern-chart"></canvas>
                        </div>
                        <div class="chart-legend">
                            <!-- Plans will be dynamically inserted here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Finance Overview Chart -->
            <div class="col-xxl-8 mb-4">
                <div class="card modern-card-chart">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title-group">
                            <h4>{{ __('dashboard.Finance Overview') }}</h4>
                            <p class="card-subtitle">{{ __('dashboard.Revenue and expenses analysis') }}</p>
                        </div>
                        <div class="card-actions">
                            <select class="form-select form-select-sm modern-select">
                                @for ($i = date('Y'); $i >= 2022; $i--)
                                    <option @selected($i == date('Y')) value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="finance-chart" class="modern-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="quick-actions-section">
        <div class="card modern-card">
            <div class="card-header">
                <h4>{{ __('dashboard.Quick Actions') }}</h4>
                <p class="card-subtitle">{{ __('dashboard.Frequently used actions') }}</p>
            </div>
            <div class="card-body">
                <div class="quick-actions-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-lg">
                    <a href="{{ route('admin.business.create') }}" class="quick-action-item card p-lg text-center">
                        <div class="action-icon action-icon-blue mx-auto">
                            <i class="fas fa-plus"></i>
                        </div>
                        <span>{{ __('dashboard.Add Business') }}</span>
                    </a>
                    <a href="{{ route('admin.users.create') }}" class="quick-action-item card p-lg text-center">
                        <div class="action-icon action-icon-green mx-auto">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <span>{{ __('dashboard.Add User') }}</span>
                    </a>
                    <a href="{{ route('admin.plans.create') }}" class="quick-action-item card p-lg text-center">
                        <div class="action-icon action-icon-purple mx-auto">
                            <i class="fas fa-file-plus"></i>
                        </div>
                        <span>{{ __('dashboard.Create Plan') }}</span>
                    </a>
                    <a href="{{ route('admin.business-categories.create') }}" class="quick-action-item card p-lg text-center">
                        <div class="action-icon action-icon-orange mx-auto">
                            <i class="fas fa-folder-plus"></i>
                        </div>
                        <span>{{ __('dashboard.Add Category') }}</span>
                    </a>
                    <a href="{{ route('admin.active-stores.index') }}" class="quick-action-item card p-lg text-center">
                        <div class="action-icon action-icon-pink mx-auto">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <span>{{ __('dashboard.View Reports') }}</span>
                    </a>
                    <a href="{{ route('admin.maintenance.index') }}" class="quick-action-item card p-lg text-center">
                        <div class="action-icon action-icon-red mx-auto">
                            <i class="fas fa-tools"></i>
                        </div>
                        <span>{{ __('dashboard.Maintenance') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS Styles -->
<style>
/* Dashboard Header */
.dashboard-header {
    padding: 1.5rem 0;
    animation: fadeInDown 0.6s ease-out;
}

.dashboard-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-foreground);
    margin-bottom: 0.5rem;
}

.dashboard-subtitle {
    color: var(--color-muted);
    font-size: 1rem;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

/* KPI Cards */
.kpi-card {
    transition: all 200ms ease;
}

.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.kpi-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: var(--radius-md);
    background: rgba(21, 128, 61, 0.1);
    margin-bottom: var(--space-md);
}

.kpi-value {
    font-size: 32px;
    font-weight: 700;
    font-family: 'Fira Code', 'Fira Sans', sans-serif;
    color: var(--color-foreground);
    margin-bottom: var(--space-xs);
}

.kpi-label {
    font-size: 14px;
    font-weight: 500;
    margin-bottom: var(--space-sm);
}

.kpi-trend {
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.stat-trend-up {
    color: #10b981;
    background: rgba(16, 185, 129, 0.1);
    padding: 4px 8px;
    border-radius: var(--radius-sm);
}

.stat-trend-down {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
    padding: 4px 8px;
    border-radius: var(--radius-sm);
}

/* Modern Cards */
.modern-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    transition: all 0.3s ease;
    overflow: hidden;
}

.modern-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.modern-card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--color-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title-group h4 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-foreground);
    margin-bottom: 0.25rem;
}

.card-subtitle {
    color: var(--color-muted);
    font-size: 0.875rem;
    margin: 0;
}

.modern-select {
    border-radius: 0.5rem;
    border: 1px solid var(--color-border);
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.modern-select:focus {
    border-color: #15803D;
    box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.1);
}

.modern-card-body {
    padding: 1.5rem;
}

.chart-container {
    position: relative;
    height: 300px;
}

.modern-chart {
    width: 100% !important;
    height: 100% !important;
}

/* Quick Actions */
.quick-actions-section {
    margin-top: 2rem;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.quick-action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.5rem;
    border-radius: 1rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    text-decoration: none;
    color: var(--color-foreground);
    transition: all 0.3s ease;
    gap: 0.75rem;
}

.quick-action-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.action-icon {
    width: 50px;
    height: 50px;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
}

.action-icon-blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
.action-icon-green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.action-icon-purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
.action-icon-orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.action-icon-pink { background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); }
.action-icon-red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }

.quick-action-item span {
    font-weight: 600;
    font-size: 0.875rem;
    text-align: center;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    :root {
        --text-primary: #ffffff;
        --text-secondary: #a0aec0;
        --border-color: #2d3748;
    }

    .stat-card,
    .modern-card,
    .card {
        background: #1a202c;
    }

    .quick-action-item {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
    }
}

/* Form select styling */
select.input {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2315803D' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 36px;
}
</style>

<!-- Enhanced JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    animateStats();
    initCharts();
});

function animateStats() {
    const stats = [
        { id: 'total_businesses', target: 156 },
        { id: 'expired_businesses', target: 12 },
        { id: 'plan_subscribes', target: 234 },
        { id: 'business_categories', target: 45 },
        { id: 'total_plans', target: 8 },
        { id: 'total_revenue', target: 125000, prefix: '$' }
    ];

    stats.forEach(stat => {
        const element = document.getElementById(stat.id);
        if (element) {
            animateValue(element, 0, stat.target, 1500, stat.prefix || '');
        }
    });
}

function animateValue(element, start, end, duration, prefix = '') {
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        if (current >= end) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = prefix + Math.floor(current).toLocaleString();
    }, 16);
}

function initCharts() {
    console.log('Charts initialized');
}
</script>
@endsection