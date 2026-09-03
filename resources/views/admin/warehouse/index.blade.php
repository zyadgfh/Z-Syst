@extends('layouts.master')

@section('title')
    {{ __('warehouses.Warehouse & Stock') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <!-- Warehouse Stats -->
        <div class="gpt-dashboard-card counter-grid-4 mt-30 mb-30">
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="total_items">0</h5>
                    <p>{{ __('warehouses.Total Items') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 7L12 3L4 7M20 7L12 11M20 7V17L12 21M4 7L12 11M4 7V17L12 21M12 11V21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="total_value">$0</h5>
                    <p>{{ __('purchases.Total Value') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2V20M12 2L8 6M12 2L16 6M4 22H20" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="low_stock_items">0</h5>
                    <p>{{ __('dashboard.Low Stock Items') }}</p>
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
                    <h5 id="expired_items">0</h5>
                    <p>{{ __('warehouses.Expired Items') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="white" stroke-width="2"/>
                        <path d="M12 6V12L16 14" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Stock Table -->
        <div class="erp-table-section">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-top-form">
                        <div class="table-search">
                            <span><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="{{ __('warehouses.Search stock...') }}" id="search-input">
                        </div>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="filter-status">
                                <option value="">{{ __('common.All Status') }}</option>
                                <option value="in_stock">{{ __('products.In Stock') }}</option>
                                <option value="low_stock">{{ __('products.Low Stock') }}</option>
                                <option value="out_of_stock">{{ __('products.Out of Stock') }}</option>
                            </select>
                            <select class="form-select" id="filter-expiry">
                                <option value="">{{ __('warehouses.All Expiry') }}</option>
                                <option value="expired">{{ __('common.Expired') }}</option>
                                <option value="expiring_soon">{{ __('audit.Expiring Soon') }}</option>
                                <option value="good">{{ __('warehouses.Good') }}</option>
                            </select>
                            @can('stock-adjust')
                                <button class="btn btn-primary" onclick="showAdjustModal()">
                                    <i class="fas fa-plus me-2"></i>{{ __('warehouses.Stock Adjustment') }}
                                </button>
                            @endcan
                        </div>
                    </div>

                    <div class="erp-box-content">
                        <div class="table-container">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="table-header-content">{{ __('common.SL') }}.</th>
                                        <th class="table-header-content">{{ __('common.Product') }}</th>
                                        <th class="table-header-content">{{ __('common.Batch Number') }}</th>
                                        <th class="table-header-content">{{ __('warehouses.Warehouse') }}</th>
                                        <th class="table-header-content">{{ __('common.Quantity') }}</th>
                                        <th class="table-header-content">{{ __('common.Purchase Price') }}</th>
                                        <th class="table-header-content">{{ __('common.Expiry Date') }}</th>
                                        <th class="table-header-content">{{ __('common.Status') }}</th>
                                        <th class="table-header-content">{{ __('common.Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="stock-table-body">
                                    @foreach ($stockBatches as $batch)
                                        <tr class="table-content">
                                            <td class="table-single-content">{{ $loop->index + 1 }}</td>
                                            <td class="table-single-content">
                                                <div>
                                                    <strong>{{ $batch->product->name }}</strong>
                                                    <small class="d-block text-muted">{{ $batch->product->sku }}</small>
                                                </div>
                                            </td>
                                            <td class="table-single-content">{{ $batch->batch_number }}</td>
                                            <td class="table-single-content">{{ $batch->warehouse->name ?? '-' }}</td>
                                            <td class="table-single-content">
                                                <span class="badge @if($batch->quantity <= 0) out-of-stock @elseif($batch->quantity <= $batch->product->reorder_level) low-stock @else badge-soft-success @endif">
                                                    {{ $batch->quantity }}
                                                </span>
                                            </td>
                                            <td class="table-single-content">{{ format_currency($batch->purchase_price) }}</td>
                                            <td class="table-single-content">
                                                @if($batch->expiry_date)
                                                    <span class="badge @if($batch->expiry_date < now()) expired @elseif($batch->expiry_date <= now()->addDays(30)) expiry-soon @else badge-soft-success @endif">
                                                        {{ formatted_date($batch->expiry_date) }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="table-single-content">
                                                @if($batch->expiry_date && $batch->expiry_date < now())
                                                    <span class="badge expired">{{ __('common.Expired') }}</span>
                                                @elseif($batch->quantity <= 0)
                                                    <span class="badge out-of-stock">{{ __('products.Out of Stock') }}</span>
                                                @elseif($batch->quantity <= $batch->product->reorder_level)
                                                    <span class="badge low-stock">{{ __('products.Low Stock') }}</span>
                                                @else
                                                    <span class="badge-soft-success">{{ __('products.In Stock') }}</span>
                                                @endif
                                            </td>
                                            <td class="table-single-content">
                                                <div class="action-buttons">
                                                    @can('stock-read')
                                                        <a href="{{ route('admin.stock.show', $batch->id) }}" class="btn btn-sm btn-info" title="{{ __('common.View') }}">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    @endcan
                                                    @can('stock-adjust')
                                                        <button type="button" class="btn btn-sm btn-warning adjust-btn" data-id="{{ $batch->id }}" title="{{ __('products.Adjust') }}">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($stockBatches->hasPages())
                            <div class="pagination">
                                {{ $stockBatches->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <div class="modal fade" id="stockAdjustModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('warehouses.Stock Adjustment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="stock-adjust-form">
                        @csrf
                        <input type="hidden" name="batch_id" id="adjust-batch-id">
                        
                        <div class="mb-3">
                            <label class="form-label">{{ __('products.Adjustment Type') }}</label>
                            <select class="form-select" name="adjustment_type" required>
                                <option value="add">{{ __('warehouses.Add Stock') }}</option>
                                <option value="remove">{{ __('warehouses.Remove Stock') }}</option>
                                <option value="adjust">{{ __('warehouses.Adjust to Quantity') }}</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">{{ __('common.Quantity') }}</label>
                            <input type="number" class="form-control" name="quantity" required min="1">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">{{ __('common.Reason') }}</label>
                            <textarea class="form-control" name="reason" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.Cancel') }}</button>
                    <button type="button" class="btn btn-primary" onclick="submitStockAdjustment()">{{ __('warehouses.Save Adjustment') }}</button>
                </div>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize counters
                animateCounter('total_items', {{ $totalItems ?? 0 }});
                document.getElementById('total_value').textContent = '{{ format_currency($totalValue ?? 0) }}';
                animateCounter('low_stock_items', {{ $lowStockItems ?? 0 }});
                animateCounter('expired_items', {{ $expiredItems ?? 0 }});

                // Search functionality
                const searchInput = document.getElementById('search-input');
                if (searchInput) {
                    searchInput.addEventListener('input', debounce(function() {
                        const query = this.value;
                        if (query.length >= 2) {
                            const status = document.getElementById('filter-status').value;
                            const expiry = document.getElementById('filter-expiry').value;
                            window.location.href = '{{ route('admin.warehouse.index') }}?search=' + encodeURIComponent(query) + '&status=' + status + '&expiry=' + expiry;
                        }
                    }, 500));
                }

                // Filter functionality
                document.getElementById('filter-status').addEventListener('change', applyFilters);
                document.getElementById('filter-expiry').addEventListener('change', applyFilters);

                // Adjust button functionality
                document.querySelectorAll('.adjust-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const batchId = this.dataset.id;
                        document.getElementById('adjust-batch-id').value = batchId;
                        const modal = new bootstrap.Modal(document.getElementById('stockAdjustModal'));
                        modal.show();
                    });
                });
            });

            function applyFilters() {
                const search = document.getElementById('search-input').value;
                const status = document.getElementById('filter-status').value;
                const expiry = document.getElementById('filter-expiry').value;
                
                let url = '{{ route('admin.warehouse.index') }}?';
                if (search) url += 'search=' + encodeURIComponent(search) + '&';
                if (status) url += 'status=' + status + '&';
                if (expiry) url += 'expiry=' + expiry;
                
                window.location.href = url;
            }

            function showAdjustModal() {
                document.getElementById('adjust-batch-id').value = '';
                document.getElementById('stock-adjust-form').reset();
                const modal = new bootstrap.Modal(document.getElementById('stockAdjustModal'));
                modal.show();
            }

            function submitStockAdjustment() {
                const form = document.getElementById('stock-adjust-form');
                const formData = new FormData(form);
                
                fetch('{{ route('admin.stock.adjust') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message);
                        bootstrap.Modal.getInstance(document.getElementById('stockAdjustModal')).hide();
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        toastr.error(data.message);
                    }
                })
                .catch(error => {
                    toastr.error('{{ __('common.An error occurred') }}');
                });
            }

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

            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
        </script>
    @endpush
@endsection
