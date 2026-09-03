@extends('layouts.admin')

@section('title')
    {{ __('Pharmacy Dashboard') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <!-- Pharmacy Dashboard Stats -->
        <div class="gpt-dashboard-card counter-grid-6 mt-30 mb-30">
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="total_products">0</h5>
                    <p>{{ __('Total Products') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 11H5M19 11L16 8M19 11L16 14M5 11L8 8M5 11L8 14" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 7V17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="low_stock">0</h5>
                    <p>{{ __('Low Stock') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 9V3M12 9L9 6M12 9L15 6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 15V21M12 15L9 18M12 15L15 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M3 12H21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="expiring_soon">0</h5>
                    <p>{{ __('Expiring Soon') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="white" stroke-width="2"/>
                        <path d="M12 6V12L16 14" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="total_sales">0</h5>
                    <p>{{ __('Today Sales') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2V20M12 2L8 6M12 2L16 6M4 22H20" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="prescriptions">0</h5>
                    <p>{{ __('Prescriptions') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12H15M9 16H15M17 21H7C5.89543 21 5 20.1046 5 19V5C5 3.89543 5.89543 3 7 3H12.5858C12.851 3 13.1054 3.10536 13.2929 3.29289L18.7071 8.70711C18.8946 8.89464 19 9.149 19 9.41421V19C19 20.1046 18.1046 21 17 21Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="purchase_orders">0</h5>
                    <p>{{ __('Purchase Orders') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row gpt-dashboard-chart">
            <div class="col-xxl-4 mb-30">
                <div class="card new-card sms-report border-0 p-0 h-100">
                    <div class="chart-header">
                        <h4>{{ __('Stock by Category') }}</h4>
                        <div class="gpt-up-down-arrow position-relative">
                            <select class="form-control stock-period">
                                <option value="week">{{ __('This Week') }}</option>
                                <option value="month">{{ __('This Month') }}</option>
                                <option value="year">{{ __('This Year') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="content">
                            <canvas id="stock-chart" class="subscription-css"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-8 mb-30">
                <div class="card new-card dashboard-card border-0 p-0 h-100">
                    <div class="chart-header">
                        <h4>{{ __('Sales Overview') }}</h4>
                        <div class="gpt-up-down-arrow position-relative">
                            <select class="form-control sales-period">
                                <option value="week">{{ __('This Week') }}</option>
                                <option value="month" selected>{{ __('This Month') }}</option>
                                <option value="year">{{ __('This Year') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-flex align-items-center justify-content-center gap-2 pt-2 pb-2">
                            <div class="green-circle"></div>
                            <p>{{ __('Total Revenue:') }} <strong id="total_revenue">$0</strong></p>
                        </div>
                        <div class="content">
                            <canvas id="sales-chart" class="chart-css"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expiry Alerts Table -->
        <div class="erp-table-section dashboard">
            <div class="card">
                <div class="card-bodys">
                    <div class="chart-header p-16 border-0">
                        <h4>{{ __('Expiry Alerts') }}</h4>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.products.index') }}" class="view-btn">
                                {{ __('View All') }} <i class="fas fa-arrow-right view-arrow"></i>
                            </a>
                        </div>
                    </div>
                    <div class="erp-box-content">
                        <div class="top-customer-table table-container mt-0">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="table-header-content">{{ __('SL') }}.</th>
                                        <th class="table-header-content">{{ __('Product Name') }}</th>
                                        <th class="table-header-content">{{ __('Batch Number') }}</th>
                                        <th class="table-header-content">{{ __('Expiry Date') }}</th>
                                        <th class="table-header-content">{{ __('Quantity') }}</th>
                                        <th class="table-header-content">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($expiringProducts ?? [] as $product)
                                        <tr class="table-content">
                                            <td class="table-single-content">{{ $loop->index + 1 }}</td>
                                            <td class="table-single-content">{{ $product->name }}</td>
                                            <td class="table-single-content">{{ $product->batch_number }}</td>
                                            <td class="table-single-content">{{ formatted_date($product->expiry_date) }}</td>
                                            <td class="table-single-content">{{ $product->quantity }}</td>
                                            <td class="table-single-content">
                                                @if($product->days_until_expiry <= 0)
                                                    <span class="badge expired">{{ __('Expired') }}</span>
                                                @elseif($product->days_until_expiry <= 30)
                                                    <span class="badge expiry-soon">{{ __('Expiring Soon') }}</span>
                                                @else
                                                    <span class="badge-soft-success">{{ __('Good') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Sales Table -->
        <div class="erp-table-section dashboard">
            <div class="card">
                <div class="card-bodys">
                    <div class="chart-header p-16 border-0">
                        <h4>{{ __('Recent Sales') }}</h4>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.sales.index') }}" class="view-btn">
                                {{ __('View All') }} <i class="fas fa-arrow-right view-arrow"></i>
                            </a>
                        </div>
                    </div>
                    <div class="erp-box-content">
                        <div class="top-customer-table table-container mt-0">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="table-header-content">{{ __('SL') }}.</th>
                                        <th class="table-header-content">{{ __('Invoice #') }}</th>
                                        <th class="table-header-content">{{ __('Date') }}</th>
                                        <th class="table-header-content">{{ __('Customer') }}</th>
                                        <th class="table-header-content">{{ __('Total') }}</th>
                                        <th class="table-header-content">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentSales ?? [] as $sale)
                                        <tr class="table-content">
                                            <td class="table-single-content">{{ $loop->index + 1 }}</td>
                                            <td class="table-single-content">{{ $sale->invoice_number }}</td>
                                            <td class="table-single-content">{{ formatted_date($sale->created_at) }}</td>
                                            <td class="table-single-content">{{ $sale->customer_name }}</td>
                                            <td class="table-single-content">{{ format_currency($sale->total) }}</td>
                                            <td class="table-single-content">
                                                <span class="badge badge-soft-success">{{ $sale->payment_status }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            // Dashboard data initialization
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize counters with animation
                animateCounter('total_products', {{ $totalProducts ?? 0 }});
                animateCounter('low_stock', {{ $lowStock ?? 0 }});
                animateCounter('expiring_soon', {{ $expiringSoon ?? 0 }});
                animateCounter('total_sales', {{ $todaySales ?? 0 }});
                animateCounter('prescriptions', {{ $prescriptions ?? 0 }});
                animateCounter('purchase_orders', {{ $purchaseOrders ?? 0 }});

                // Initialize charts
                initStockChart();
                initSalesChart();
            });

            function animateCounter(elementId, targetValue) {
                const element = document.getElementById(elementId);
                if (!element) return;
                
                let currentValue = 0;
                const increment = targetValue / 50;
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= targetValue) {
                        element.textContent = targetValue;
                        clearInterval(timer);
                    } else {
                        element.textContent = Math.floor(currentValue);
                    }
                }, 30);
            }

            function initStockChart() {
                const ctx = document.getElementById('stock-chart');
                if (!ctx) return;

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: @json($stockCategories ?? ['Medicines', 'Supplements', 'Equipment', 'Other']),
                        datasets: [{
                            data: @json($stockData ?? [30, 20, 15, 35]),
                            backgroundColor: [
                                '#15803D',
                                '#22C55E',
                                '#0369A1',
                                '#BBF7D0'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            }
                        }
                    }
                });
            }

            function initSalesChart() {
                const ctx = document.getElementById('sales-chart');
                if (!ctx) return;

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($salesLabels ?? ['Week 1', 'Week 2', 'Week 3', 'Week 4']),
                        datasets: [{
                            label: '{{ __("Sales") }}',
                            data: @json($salesData ?? [1200, 1900, 1500, 2100]),
                            borderColor: '#15803D',
                            backgroundColor: 'rgba(21, 128, 61, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        </script>
    @endpush
@endsection
