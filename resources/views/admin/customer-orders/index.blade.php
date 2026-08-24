@extends('layouts.master')

@section('title', __('Customer Orders'))

@section('main_content')
<div class="container-fluid m-h-100">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="dashboard-title">{{ __('Customer Orders') }}</h2>
            <p class="dashboard-subtitle">{{ __('Manage online orders from your store') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.customer-orders.export-csv', request()->query()) }}" class="btn btn-secondary">
                <i class="fas fa-download me-2"></i>{{ __('Export CSV') }}
            </a>
            <a href="{{ route('admin.online-store.index') }}" class="btn btn-primary">
                <i class="fas fa-chart-line me-2"></i>{{ __('Analytics') }}
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card kpi-card p-3 text-center">
                <div class="kpi-value" style="color: #111827;">{{ $stats['total'] }}</div>
                <div class="kpi-label">{{ __('Total') }}</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card kpi-card p-3 text-center" style="border-left: 3px solid #f59e0b;">
                <div class="kpi-value" style="color: #f59e0b;">{{ $stats['pending'] }}</div>
                <div class="kpi-label">{{ __('Pending') }}</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card kpi-card p-3 text-center" style="border-left: 3px solid #3b82f6;">
                <div class="kpi-value" style="color: #3b82f6;">{{ $stats['processing'] }}</div>
                <div class="kpi-label">{{ __('Processing') }}</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card kpi-card p-3 text-center" style="border-left: 3px solid #8b5cf6;">
                <div class="kpi-value" style="color: #8b5cf6;">{{ $stats['shipped'] }}</div>
                <div class="kpi-label">{{ __('Shipped') }}</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card kpi-card p-3 text-center" style="border-left: 3px solid #10b981;">
                <div class="kpi-value" style="color: #10b981;">{{ $stats['delivered'] }}</div>
                <div class="kpi-label">{{ __('Delivered') }}</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card kpi-card p-3 text-center" style="border-left: 3px solid #10b981;">
                <div class="kpi-value" style="color: #10b981;">${{ number_format($stats['revenue'], 2) }}</div>
                <div class="kpi-label">{{ __('Revenue') }}</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.customer-orders.index') }}" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="{{ __('Search orders...') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">{{ __('All Status') }}</option>
                        @foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="payment_status" class="form-select">
                        <option value="">{{ __('All Payment') }}</option>
                        @foreach (['unpaid', 'paid', 'partially_refunded', 'refunded'] as $ps)
                            <option value="{{ $ps }}" @selected(request('payment_status') === $ps)>{{ ucfirst(str_replace('_', ' ', $ps)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" placeholder="{{ __('From') }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" placeholder="{{ __('To') }}">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-body">
            @if ($orders->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x mb-3" style="color: #d1d5db;"></i>
                    <p style="color: #6b7280;">{{ __('No orders found.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Order #') }}</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Items') }}</th>
                                <th>{{ __('Total') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Payment') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.customer-orders.show', $order) }}" class="fw-bold text-decoration-none" style="color: #15803d;">
                                            {{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <div>{{ $order->customer_name }}</div>
                                        <small style="color: #6b7280;">{{ $order->customer_email }}</small>
                                    </td>
                                    <td>{{ $order->items->count() }}</td>
                                    <td class="fw-bold">${{ number_format($order->total_amount, 2) }}</td>
                                    <td>
                                        <span class="badge"
                                              style="background: {{ match($order->status) {
                                                  'pending' => '#fef3c7; color: #92400e',
                                                  'confirmed' => '#dbeafe; color: #1e40af',
                                                  'processing' => '#ede9fe; color: #5b21b6',
                                                  'shipped' => '#e0e7ff; color: #3730a3',
                                                  'delivered' => '#f0fdf4; color: #166534',
                                                  'cancelled' => '#fef2f2; color: #991b1b',
                                                  default => '#f3f4f6; color: #374151',
                                              } }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge"
                                              style="background: {{ $order->payment_status === 'paid' ? '#f0fdf4; color: #166534' : '#fef2f2; color: #991b1b' }}">
                                            {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <a href="{{ route('admin.customer-orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-3">
                    {{ $orders->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
