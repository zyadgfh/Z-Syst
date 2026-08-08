@extends('layouts.master')

@section('title')
    {{ __('Products') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <div class="erp-table-section">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-top-form">
                        <div class="table-search">
                            <span><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="{{ __('Search products...') }}" id="search-input">
                        </div>
                        <div class="d-flex gap-2">
                            @can('products-create')
                                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>{{ __('Add Product') }}
                                </a>
                            @endcan
                            <button class="btn btn-secondary" onclick="exportToExcel()">
                                <i class="fas fa-file-excel me-2"></i>{{ __('Export') }}
                            </button>
                        </div>
                    </div>

                    <div class="erp-box-content">
                        <div class="table-container">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="table-header-content">
                                            <input type="checkbox" class="form-check-input" id="select-all">
                                        </th>
                                        <th class="table-header-content">{{ __('SL') }}.</th>
                                        <th class="table-header-content">{{ __('Product Name') }}</th>
                                        <th class="table-header-content">{{ __('Generic Name') }}</th>
                                        <th class="table-header-content">{{ __('SKU') }}</th>
                                        <th class="table-header-content">{{ __('Category') }}</th>
                                        <th class="table-header-content">{{ __('Stock') }}</th>
                                        <th class="table-header-content">{{ __('Price') }}</th>
                                        <th class="table-header-content">{{ __('Expiry') }}</th>
                                        <th class="table-header-content">{{ __('Status') }}</th>
                                        <th class="table-header-content">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="products-table-body">
                                    @foreach ($products as $product)
                                        <tr class="table-content">
                                            <td class="table-single-content">
                                                <input type="checkbox" class="form-check-input product-checkbox" value="{{ $product->id }}">
                                            </td>
                                            <td class="table-single-content">{{ $loop->index + 1 }}</td>
                                            <td class="table-single-content">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="product-avatar">
                                                        @if($product->image)
                                                            <img src="{{ asset($product->image) }}" alt="{{ $product->name }}">
                                                        @else
                                                            <div class="avatar-placeholder">{{ substr($product->name, 0, 1) }}</div>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <strong>{{ $product->name }}</strong>
                                                        <small class="d-block text-muted">{{ $product->barcode }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="table-single-content">{{ $product->generic_name }}</td>
                                            <td class="table-single-content">{{ $product->sku }}</td>
                                            <td class="table-single-content">{{ $product->category->name ?? '-' }}</td>
                                            <td class="table-single-content">
                                                <span class="badge @if($product->stock_quantity <= $product->reorder_level) out-of-stock @elseif($product->stock_quantity <= $product->reorder_level * 2) low-stock @else badge-soft-success @endif">
                                                    {{ $product->stock_quantity }}
                                                </span>
                                            </td>
                                            <td class="table-single-content">{{ format_currency($product->selling_price) }}</td>
                                            <td class="table-single-content">
                                                @if($product->nearest_expiry_date)
                                                    <span class="badge @if($product->days_until_expiry <= 0) expired @elseif($product->days_until_expiry <= 30) expiry-soon @else badge-soft-success @endif">
                                                        {{ formatted_date($product->nearest_expiry_date) }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="table-single-content">
                                                <span class="badge @if($product->is_active) badge-soft-success @else badge-soft-danger @endif">
                                                    {{ $product->is_active ? __('Active') : __('Inactive') }}
                                                </span>
                                            </td>
                                            <td class="table-single-content">
                                                <div class="action-buttons">
                                                    @can('products-read')
                                                        <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-sm btn-info" title="{{ __('View') }}">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    @endcan
                                                    @can('products-update')
                                                        <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-sm btn-warning" title="{{ __('Edit') }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                    @can('products-delete')
                                                        <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="{{ $product->id }}" title="{{ __('Delete') }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($products->hasPages())
                            <div class="pagination">
                                {{ $products->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.components.multi-delete-modal')

    @push('script')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Search functionality
                const searchInput = document.getElementById('search-input');
                if (searchInput) {
                    searchInput.addEventListener('input', debounce(function() {
                        const query = this.value;
                        if (query.length >= 2) {
                            window.location.href = '{{ route('admin.products.index') }}?search=' + encodeURIComponent(query);
                        }
                    }, 500));
                }

                // Select all functionality
                const selectAll = document.getElementById('select-all');
                const checkboxes = document.querySelectorAll('.product-checkbox');
                
                if (selectAll) {
                    selectAll.addEventListener('change', function() {
                        checkboxes.forEach(cb => cb.checked = this.checked);
                    });
                }

                // Delete button functionality
                document.querySelectorAll('.delete-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const productId = this.dataset.id;
                        if (confirm('{{ __("Are you sure you want to delete this product?") }}')) {
                            deleteProduct(productId);
                        }
                    });
                });
            });

            function deleteProduct(productId) {
                fetch(`{{ route('admin.products.destroy', ':id') }}`.replace(':id', productId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message);
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        toastr.error(data.message);
                    }
                })
                .catch(error => {
                    toastr.error('{{ __("An error occurred") }}');
                });
            }

            function exportToExcel() {
                window.location.href = '{{ route('admin.products.export') }}';
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
