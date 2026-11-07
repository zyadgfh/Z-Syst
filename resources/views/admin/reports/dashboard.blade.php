@extends('layouts.master')

@section('title')
    {{ __('Dashboard Reports') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="row g-3">
        <div class="col-md-4">
            <a href="{{ route('admin.dashboard-reports.overall') }}" class="text-decoration-none">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title">{{ __('Overall report') }}</h5>
                    <p class="card-text text-muted">{{ __('Sales, purchases, stock and financial summary.') }}</p>
                </div></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.dashboard-reports.warehouse') }}" class="text-decoration-none">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title">{{ __('Warehouse report') }}</h5>
                    <p class="card-text text-muted">{{ __('Stock distribution across warehouses.') }}</p>
                </div></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.dashboard-reports.transfers') }}" class="text-decoration-none">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title">{{ __('Transfers report') }}</h5>
                    <p class="card-text text-muted">{{ __('Inter-warehouse transfer activity.') }}</p>
                </div></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.dashboard-reports.sales-by-date') }}" class="text-decoration-none">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title">{{ __('Sales by date') }}</h5>
                    <p class="card-text text-muted">{{ __('Daily sales trend.') }}</p>
                </div></div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.subscription-reports.index') }}" class="text-decoration-none">
                <div class="card h-100"><div class="card-body">
                    <h5 class="card-title">{{ __('Subscription reports') }}</h5>
                    <p class="card-text text-muted">{{ __('Business subscriptions overview.') }}</p>
                </div></div>
            </a>
        </div>
    </div>
</div>

@endsection
