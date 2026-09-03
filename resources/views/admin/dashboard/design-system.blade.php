@extends('layouts.admin')

@section('title', __('dashboard.Dashboard'))

@section('main_content')
<!-- Dashboard Hero Section -->
<div class="dashboard-hero mb-3xl">
    <div class="card">
        <div class="card-header">
            <h1>{{ __('dashboard.Welcome Back') }}, {{ auth()->user()->name }}</h1>
            <p class="text-muted">{{ __('dashboard.Here\'s what\'s happening with your pharmacy today') }}</p>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="kpi-grid mb-3xl">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-lg">
        <!-- Total Sales -->
        <div class="card kpi-card">
            <div class="card-body">
                <div class="kpi-icon text-primary">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="kpi-value">{{ number_format($totalSales ?? 0) }}</h3>
                <p class="kpi-label text-muted">{{ __('dashboard.Total Sales') }}</p>
                <div class="kpi-trend text-success">
                    <i class="fas fa-arrow-up"></i> 12.5%
                </div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="card kpi-card">
            <div class="card-body">
                <div class="kpi-icon text-secondary">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5V7H9V5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="kpi-value">{{ number_format($totalOrders ?? 0) }}</h3>
                <p class="kpi-label text-muted">{{ __('dashboard.Total Orders') }}</p>
                <div class="kpi-trend text-success">
                    <i class="fas fa-arrow-up"></i> 8.2%
                </div>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div class="card kpi-card">
            <div class="card-body">
                <div class="kpi-icon text-warning">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 9V13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 17H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10.29 3.86L1.82 18C1.42368 18.6566 1.29404 19.4327 1.45625 20.1759C1.61846 20.9191 2.05915 21.5714 2.69 21.99L10.31 26.99C10.9193 27.3888 11.6607 27.6031 12.42 27.6C13.1763 27.5984 13.9155 27.3805 14.52 26.98L22.14 21.98C22.7721 21.5619 23.2135 20.9096 23.3758 20.1661C23.5382 19.4225 23.4082 18.646 23.01 17.99L14.54 3.86C14.1543 3.21978 13.5747 2.71948 12.8893 2.43489C12.2039 2.1503 11.4466 2.09695 10.7291 2.28316C10.0116 2.46937 9.36987 2.88568 8.90199 3.47306C8.43412 4.06044 8.16623 4.77887 8.13999 5.52399" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="kpi-value">{{ number_format($lowStockItems ?? 0) }}</h3>
                <p class="kpi-label text-muted">{{ __('dashboard.Low Stock Items') }}</p>
                <div class="kpi-trend text-destructive">
                    <i class="fas fa-arrow-down"></i> 3.1%
                </div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="card kpi-card">
            <div class="card-body">
                <div class="kpi-icon text-accent">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 16V12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 8H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="kpi-value">{{ number_format($pendingApprovals ?? 0) }}</h3>
                <p class="kpi-label text-muted">{{ __('dashboard.Pending Approvals') }}</p>
                <div class="kpi-trend text-info">
                    <i class="fas fa-clock"></i> Active
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions mb-3xl">
    <div class="card">
        <div class="card-header">
            <h2>{{ __('dashboard.Quick Actions') }}</h2>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-lg">
                <a href="{{ route('admin.sales.create') }}" class="quick-action btn btn-primary">
                    <i class="fas fa-plus"></i>
                    <span>{{ __('dashboard.New Sale') }}</span>
                </a>
                <a href="{{ route('admin.purchases.create') }}" class="quick-action btn btn-secondary">
                    <i class="fas fa-shopping-cart"></i>
                    <span>{{ __('dashboard.New Purchase') }}</span>
                </a>
                <a href="{{ route('admin.products.index') }}" class="quick-action btn btn-secondary">
                    <i class="fas fa-box"></i>
                    <span>{{ __('dashboard.Manage Products') }}</span>
                </a>
                <a href="{{ route('admin.reports.index') }}" class="quick-action btn btn-secondary">
                    <i class="fas fa-chart-bar"></i>
                    <span>{{ __('dashboard.View Reports') }}</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="recent-activity mb-3xl">
    <div class="card">
        <div class="card-header">
            <h2>{{ __('dashboard.Recent Activity') }}</h2>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('dashboard.Activity') }}</th>
                        <th>{{ __('common.User') }}</th>
                        <th>{{ __('dashboard.Time') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ __('dashboard.New sale created') }}</td>
                        <td>{{ auth()->user()->name }}</td>
                        <td>{{ now()->diffForHumans() }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('dashboard.Stock updated') }}</td>
                        <td>{{ auth()->user()->name }}</td>
                        <td>{{ now()->subHours(2)->diffForHumans() }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('dashboard.New product added') }}</td>
                        <td>{{ auth()->user()->name }}</td>
                        <td>{{ now()->subHours(4)->diffForHumans() }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('modal')
<!-- Modals if needed -->
@endpush
