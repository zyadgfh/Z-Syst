@extends('layouts.master')

@section('title')
    {{ __('Loyalty & CRM') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <!-- Loyalty Stats -->
        <div class="gpt-dashboard-card counter-grid-4 mt-30 mb-30">
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="total_members">0</h5>
                    <p>{{ __('Total Members') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 7C11.2091 7 13 5.20914 13 3C13 0.790861 11.2091 -1 9 -1C6.79086 -1 5 0.790861 5 3C5 5.20914 6.79086 7 9 7Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="active_programs">0</h5>
                    <p>{{ __('Active Programs') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 12V8H6C4.89543 8 4 7.10457 4 6C4 4.89543 4.89543 4 6 4H20" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M4 6V20C4 21.1046 4.89543 22 6 22H20" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 11L12 17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 14L12 17L15 14" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="points_issued">0</h5>
                    <p>{{ __('Points Issued') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2V20M12 2L8 6M12 2L16 6M4 22H20" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="points_redeemed">0</h5>
                    <p>{{ __('Points Redeemed') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 9L12 15L18 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="display-4 mb-2" style="color: var(--color-primary);">
                            <i class="fas fa-gift"></i>
                        </div>
                        <h5>{{ __('Loyalty Programs') }}</h5>
                        <p class="text-muted small">{{ __('Manage programs') }}</p>
                        <a href="{{ route('admin.loyalty.programs') }}" class="btn btn-primary btn-sm">{{ __('View Programs') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="display-4 mb-2" style="color: var(--color-accent);">
                            <i class="fas fa-history"></i>
                        </div>
                        <h5>{{ __('Transactions') }}</h5>
                        <p class="text-muted small">{{ __('Point redemption history') }}</p>
                        <a href="{{ route('admin.loyalty.transactions') }}" class="btn btn-primary btn-sm">{{ __('View Transactions') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="display-4 mb-2" style="color: var(--color-secondary);">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h5>{{ __('Customer Interactions') }}</h5>
                        <p class="text-muted small">{{ __('Track interactions') }}</p>
                        <a href="{{ route('admin.loyalty.interactions') }}" class="btn btn-primary btn-sm">{{ __('View Interactions') }}</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="erp-table-section">
            <div class="card">
                <div class="card-bodys">
                    <div class="chart-header p-16 border-0">
                        <h4>{{ __('Top Loyalty Customers') }}</h4>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.loyalty.customers') }}" class="view-btn">
                                {{ __('View All') }} <i class="fas fa-arrow-right view-arrow"></i>
                            </a>
                        </div>
                    </div>
                    <div class="erp-box-content">
                        <div class="table-container">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="table-header-content">{{ __('SL') }}.</th>
                                        <th class="table-header-content">{{ __('Customer') }}</th>
                                        <th class="table-header-content">{{ __('Points Balance') }}</th>
                                        <th class="table-header-content">{{ __('Tier') }}</th>
                                        <th class="table-header-content">{{ __('Total Spent') }}</th>
                                        <th class="table-header-content">{{ __('Joined') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topCustomers ?? [] as $customer)
                                        <tr class="table-content">
                                            <td class="table-single-content">{{ $loop->index + 1 }}</td>
                                            <td class="table-single-content">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="customer-avatar">
                                                        @if($customer->image)
                                                            <img src="{{ asset($customer->image) }}" alt="{{ $customer->name }}">
                                                        @else
                                                            <div class="avatar-placeholder">{{ substr($customer->name, 0, 1) }}</div>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <strong>{{ $customer->name }}</strong>
                                                        <small class="d-block text-muted">{{ $customer->email }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="table-single-content">
                                                <span class="badge-soft-success">{{ $customer->points_balance }}</span>
                                            </td>
                                            <td class="table-single-content">
                                                <span class="badge @if($customer->tier == 'gold') bg-warning @elseif($customer->tier == 'silver') bg-secondary @else badge-soft-info @endif">
                                                    {{ ucfirst($customer->tier) }}
                                                </span>
                                            </td>
                                            <td class="table-single-content">{{ format_currency($customer->total_spent) }}</td>
                                            <td class="table-single-content">{{ formatted_date($customer->created_at) }}</td>
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
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize counters
                animateCounter('total_members', {{ $totalMembers ?? 0 }});
                animateCounter('active_programs', {{ $activePrograms ?? 0 }});
                animateCounter('points_issued', {{ $pointsIssued ?? 0 }});
                animateCounter('points_redeemed', {{ $pointsRedeemed ?? 0 }});
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
        </script>
    @endpush
@endsection

