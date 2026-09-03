@extends('layouts.admin')

@section('title')
    {{ __('audit.Traceability & Recalls') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <!-- Traceability Stats -->
        <div class="gpt-dashboard-card counter-grid-4 mt-30 mb-30">
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="total_batches">0</h5>
                    <p>{{ __('audit.Total Batches') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="active_recalls">0</h5>
                    <p>{{ __('audit.Active Recalls') }}</p>
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
                    <h5 id="pending_actions">0</h5>
                    <p>{{ __('audit.Pending Actions') }}</p>
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
                    <h5 id="completed_actions">0</h5>
                    <p>{{ __('audit.Completed Actions') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h5>{{ __('audit.Batch Lots') }}</h5>
                        <p class="text-muted small">{{ __('audit.Manage batch lot tracking') }}</p>
                        <a href="{{ route('admin.traceability.batch-lots') }}" class="btn btn-primary btn-sm">{{ __('audit.View Batch Lots') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="display-4 mb-2" style="color: var(--color-destructive);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h5>{{ __('audit.Recall Events') }}</h5>
                        <p class="text-muted small">{{ __('audit.Initiate and manage recalls') }}</p>
                        <a href="{{ route('admin.traceability.recalls') }}" class="btn btn-primary btn-sm">{{ __('audit.Manage Recalls') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="display-4 mb-2" style="color: var(--color-accent);">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <h5>{{ __('audit.Traceability Logs') }}</h5>
                        <p class="text-muted small">{{ __('audit.View batch lot history') }}</p>
                        <a href="{{ route('admin.traceability.logs') }}" class="btn btn-primary btn-sm">{{ __('audit.View Logs') }}</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="erp-table-section">
            <div class="card">
                <div class="card-bodys">
                    <div class="chart-header p-16 border-0">
                        <h4>{{ __('audit.Recent Traceability Activity') }}</h4>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.traceability.logs') }}" class="view-btn">
                                {{ __('common.View All') }} <i class="fas fa-arrow-right view-arrow"></i>
                            </a>
                        </div>
                    </div>
                    <div class="erp-box-content">
                        <div class="table-container">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="table-header-content">{{ __('common.SL') }}.</th>
                                        <th class="table-header-content">{{ __('common.Date') }}</th>
                                        <th class="table-header-content">{{ __('common.Product') }}</th>
                                        <th class="table-header-content">{{ __('common.Batch Number') }}</th>
                                        <th class="table-header-content">{{ __('audit.Action Type') }}</th>
                                        <th class="table-header-content">{{ __('common.Quantity') }}</th>
                                        <th class="table-header-content">{{ __('common.Reference') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentActivities ?? [] as $activity)
                                        <tr class="table-content">
                                            <td class="table-single-content">{{ $loop->index + 1 }}</td>
                                            <td class="table-single-content">{{ formatted_date($activity->created_at) }}</td>
                                            <td class="table-single-content">{{ $activity->product->name }}</td>
                                            <td class="table-single-content">{{ $activity->batch_number }}</td>
                                            <td class="table-single-content">
                                                <span class="badge @if($activity->type == 'in') badge-soft-success @elseif($activity->type == 'out') badge-soft-warning @else badge-soft-info @endif">
                                                    {{ ucfirst($activity->type) }}
                                                </span>
                                            </td>
                                            <td class="table-single-content">{{ $activity->quantity }}</td>
                                            <td class="table-single-content">{{ $activity->reference_type }}: {{ $activity->reference_id }}</td>
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
                animateCounter('total_batches', {{ $totalBatches ?? 0 }});
                animateCounter('active_recalls', {{ $activeRecalls ?? 0 }});
                animateCounter('pending_actions', {{ $pendingActions ?? 0 }});
                animateCounter('completed_actions', {{ $completedActions ?? 0 }});
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

