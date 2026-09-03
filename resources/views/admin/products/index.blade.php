@extends('layouts.admin')

@section('title')
    {{ __('products.Items Management') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <div class="erp-table-section">
            <div class="card">
                <div class="card-bodys">
                    {{-- Header --}}
                    <div class="table-top-form d-flex flex-wrap justify-content-between align-items-center gap-3 p-16">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="mb-0">{{ __('products.Items Management') }}</h5>
                            <span class="badge badge-soft-info">{{ $products->total() }} {{ __('products.items') }}</span>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            @can('create', \App\Models\Product::class)
                                <a href="{{ route('admin.items.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>{{ __('purchases.Add Item') }}
                                </a>
                            @endcan
                            <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-cog me-1"></i>{{ __('common.Actions') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="exportItems()"><i class="fas fa-file-excel me-2"></i>{{ __('common.Export CSV') }}</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="document.getElementById('import-file').click()"><i class="fas fa-file-import me-2"></i>{{ __('common.Import CSV') }}</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="bulkUpdateAction()"><i class="fas fa-edit me-2"></i>{{ __('common.Bulk Update') }}</a></li>
                                </ul>
                                <input type="file" id="import-file" accept=".csv,.txt" class="d-none" onchange="importItems(this)">
                            </div>
                            <button class="btn btn-outline-secondary" onclick="toggleFilters()">
                                <i class="fas fa-filter me-1"></i>{{ __('products.Filters') }}
                            </button>
                            <button class="btn btn-outline-secondary" onclick="toggleColumns()">
                                <i class="fas fa-columns me-1"></i>{{ __('common.Columns') }}
                            </button>
                        </div>
                    </div>

                    {{-- Advanced Filters --}}
                    <div id="filterPanel" class="px-3 pb-3" style="{{ collect($filters)->filter()->isNotEmpty() ? '' : 'display: none;' }}">
                        <form method="GET" action="{{ route('admin.items.index') }}" id="filterForm">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label form-label-sm">{{ __('common.Search') }}</label>
                                    <input type="text" class="form-control form-control-sm" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('products.Name, SKU, barcode...') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label form-label-sm">{{ __('common.Category') }}</label>
                                    <select class="form-select form-select-sm" name="category_id">
                                        <option value="">{{ __('common.All') }}</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                                {{ $category->categoryName }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label form-label-sm">{{ __('products.Manufacturer') }}</label>
                                    <select class="form-select form-select-sm" name="manufacturer_id">
                                        <option value="">{{ __('common.All') }}</option>
                                        @foreach($manufacturers as $manufacturer)
                                            <option value="{{ $manufacturer->id }}" {{ ($filters['manufacturer_id'] ?? '') == $manufacturer->id ? 'selected' : '' }}>
                                                {{ $manufacturer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label form-label-sm">{{ __('common.Stock Status') }}</label>
                                    <select class="form-select form-select-sm" name="stock_status">
                                        <option value="">{{ __('common.All') }}</option>
                                        <option value="in_stock" {{ ($filters['stock_status'] ?? '') == 'in_stock' ? 'selected' : '' }}>{{ __('products.In Stock') }}</option>
                                        <option value="low_stock" {{ ($filters['stock_status'] ?? '') == 'low_stock' ? 'selected' : '' }}>{{ __('products.Low Stock') }}</option>
                                        <option value="out_of_stock" {{ ($filters['stock_status'] ?? '') == 'out_of_stock' ? 'selected' : '' }}>{{ __('products.Out of Stock') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label form-label-sm">{{ __('common.Status') }}</label>
                                    <select class="form-select form-select-sm" name="active">
                                        <option value="">{{ __('common.All') }}</option>
                                        <option value="1" {{ ($filters['active'] ?? '') == '1' ? 'selected' : '' }}>{{ __('common.Active') }}</option>
                                        <option value="0" {{ ($filters['active'] ?? '') == '0' ? 'selected' : '' }}>{{ __('common.Inactive') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label form-label-sm">&nbsp;</label>
                                    <div class="d-flex gap-1">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                                        <a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-2">
                                    <label class="form-label form-label-sm">{{ __('products.Min Price') }}</label>
                                    <input type="number" class="form-control form-control-sm" name="min_price" value="{{ $filters['min_price'] ?? '' }}" step="0.01">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label form-label-sm">{{ __('products.Max Price') }}</label>
                                    <input type="number" class="form-control form-control-sm" name="max_price" value="{{ $filters['max_price'] ?? '' }}" step="0.01">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label form-label-sm">{{ __('products.Expiring Before') }}</label>
                                    <input type="date" class="form-control form-control-sm" name="expire_date" value="{{ $filters['expire_date'] ?? '' }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label form-label-sm">{{ __('products.Prescription') }}</label>
                                    <select class="form-select form-select-sm" name="prescription_required">
                                        <option value="">{{ __('common.All') }}</option>
                                        <option value="true" {{ ($filters['prescription_required'] ?? '') == 'true' ? 'selected' : '' }}>{{ __('gateways.Required') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label form-label-sm">{{ __('products.Sort By') }}</label>
                                    <select class="form-select form-select-sm" name="sort">
                                        <option value="created_at" {{ ($filters['sort'] ?? '') == 'created_at' ? 'selected' : '' }}>{{ __('products.Date Created') }}</option>
                                        <option value="productName" {{ ($filters['sort'] ?? '') == 'productName' ? 'selected' : '' }}>{{ __('common.Name') }}</option>
                                        <option value="sales_price" {{ ($filters['sort'] ?? '') == 'sales_price' ? 'selected' : '' }}>{{ __('common.Price') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label form-label-sm">{{ __('products.Direction') }}</label>
                                    <select class="form-select form-select-sm" name="direction">
                                        <option value="desc" {{ ($filters['direction'] ?? '') == 'desc' ? 'selected' : '' }}>{{ __('products.Descending') }}</option>
                                        <option value="asc" {{ ($filters['direction'] ?? '') == 'asc' ? 'selected' : '' }}>{{ __('products.Ascending') }}</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Bulk Actions Bar --}}
                    <div id="bulkActionsBar" class="px-3 pb-2" class="d-none">
                        <div class="d-flex align-items-center gap-3">
                            <span id="selectedCount" class="badge badge-primary">0 {{ __('products.selected') }}</span>
                            <button class="btn btn-sm btn-outline-primary" onclick="bulkUpdateAction()"><i class="fas fa-edit me-1"></i>{{ __('common.Update') }}</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="bulkDelete()"><i class="fas fa-trash me-1"></i>{{ __('common.Delete') }}</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="clearSelection()"><i class="fas fa-times me-1"></i>{{ __('products.Clear') }}</button>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="erp-box-content">
                        <div class="table-responsive">
                            <table class="table table-hover" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th class="table-header-content" class="w-40">
                                            <input type="checkbox" class="form-check-input" id="select-all">
                                        </th>
                                        <th class="table-header-content">{{ __('common.SL') }}.</th>
                                        <th class="table-header-content col-item" data-col="name">{{ __('products.Item Name') }}</th>
                                        <th class="table-header-content col-item" data-col="sku" class="d-none">{{ __('common.SKU') }}</th>
                                        <th class="table-header-content col-item" data-col="barcode" class="d-none">{{ __('products.Barcode') }}</th>
                                        <th class="table-header-content col-item" data-col="category">{{ __('common.Category') }}</th>
                                        <th class="table-header-content col-item" data-col="manufacturer" class="d-none">{{ __('products.Manufacturer') }}</th>
                                        <th class="table-header-content col-item" data-col="unit">{{ __('products.Unit') }}</th>
                                        <th class="table-header-content col-item" data-col="purchase_price" class="d-none">{{ __('common.Purchase Price') }}</th>
                                        <th class="table-header-content col-item" data-col="selling_price">{{ __('products.Selling Price') }}</th>
                                        <th class="table-header-content col-item" data-col="stock">{{ __('common.Stock') }}</th>
                                        <th class="table-header-content col-item" data-col="reorder" class="d-none">{{ __('products.Reorder Point') }}</th>
                                        <th class="table-header-content col-item" data-col="status">{{ __('common.Status') }}</th>
                                        <th class="table-header-content">{{ __('common.Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $product)
                                        <tr class="table-content" data-id="{{ $product->id }}">
                                            <td class="table-single-content">
                                                <input type="checkbox" class="form-check-input item-checkbox" value="{{ $product->id }}">
                                            </td>
                                            <td class="table-single-content">{{ ($products->firstItem() ?? 1) + $loop->index }}</td>
                                            <td class="table-single-content col-item" data-col="name">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="product-avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:var(--primary-lighter);color:var(--primary);font-weight:600;font-size:14px">
                                                        {{ strtoupper(substr($product->productName, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <a href="{{ route('admin.items.show', $product->id) }}" class="fw-semibold text-decoration-none">{{ $product->productName }}</a>
                                                        @if($product->scientific_name)
                                                            <small class="d-block text-muted">{{ $product->scientific_name }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="table-single-content col-item" data-col="sku" class="d-none">
                                                <code>{{ $product->sku ?? $product->productCode ?? '-' }}</code>
                                            </td>
                                            <td class="table-single-content col-item" data-col="barcode" class="d-none">
                                                {{ $product->barcode ?? '-' }}
                                            </td>
                                            <td class="table-single-content col-item" data-col="category">
                                                <span class="badge badge-soft-info">{{ $product->category->categoryName ?? '-' }}</span>
                                            </td>
                                            <td class="table-single-content col-item" data-col="manufacturer" class="d-none">
                                                {{ $product->manufacturer->name ?? '-' }}
                                            </td>
                                            <td class="table-single-content col-item" data-col="unit">
                                                {{ $product->unit->unitName ?? '-' }}
                                            </td>
                                            <td class="table-single-content col-item" data-col="purchase_price" class="d-none">
                                                {{ number_format($product->purchase_with_tax ?? $product->purchase_without_tax ?? 0, 2) }}
                                            </td>
                                            <td class="table-single-content col-item" data-col="selling_price">
                                                <strong>{{ number_format($product->sales_price ?? 0, 2) }}</strong>
                                            </td>
                                            <td class="table-single-content col-item" data-col="stock">
                                                @php
                                                    $stock = $product->stocks_sum_productstock ?? 0;
                                                @endphp
                                                @if($product->track_inventory)
                                                    @if($stock <= 0)
                                                        <span class="badge badge-soft-danger">{{ $stock }}</span>
                                                    @elseif($product->alert_qty > 0 && $stock <= $product->alert_qty)
                                                        <span class="badge badge-soft-warning">{{ $stock }}</span>
                                                    @else
                                                        <span class="badge badge-soft-success">{{ $stock }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="table-single-content col-item" data-col="reorder" class="d-none">
                                                {{ $product->reorder_point ?? '-' }}
                                            </td>
                                            <td class="table-single-content col-item" data-col="status">
                                                @if($product->archived)
                                                    <span class="badge badge-soft-secondary">{{ __('products.Archived') }}</span>
                                                @elseif($product->active)
                                                    <span class="badge badge-soft-success">{{ __('common.Active') }}</span>
                                                @else
                                                    <span class="badge badge-soft-danger">{{ __('common.Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td class="table-single-content">
                                                <div class="d-flex gap-1">
                                                    @can('view', $product)
                                                        <a href="{{ route('admin.items.show', $product->id) }}" class="btn btn-sm btn-info" title="{{ __('common.View') }}">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    @endcan
                                                    @can('update', $product)
                                                        <a href="{{ route('admin.items.edit', $product->id) }}" class="btn btn-sm btn-warning" title="{{ __('common.Edit') }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                    @can('delete', $product)
                                                        <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="{{ $product->id }}" data-name="{{ $product->productName }}" title="{{ __('common.Delete') }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="14" class="text-center text-muted py-5">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                                                    <h5>{{ __('common.No items found') }}</h5>
                                                    <p>{{ __('products.Start by adding your first product or adjusting your filters.') }}</p>
                                                    @can('create', \App\Models\Product::class)
                                                        <a href="{{ route('admin.items.create') }}" class="btn btn-primary mt-2">
                                                            <i class="fas fa-plus me-2"></i>{{ __('products.Add First Item') }}
                                                        </a>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        @if($products->hasPages())
                            <div class="d-flex justify-content-between align-items-center px-3 pb-3">
                                <div class="text-muted">
                                    {{ __('products.Showing') }} {{ $products->firstItem() ?? 0 }} {{ __('products.to') }} {{ $products->lastItem() ?? 0 }} {{ __('products.of') }} {{ $products->total() }} {{ __('products.items') }}
                                </div>
                                {{ $products->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk Update Modal --}}
    <div class="modal fade" id="bulkUpdateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('common.Bulk Update Items') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('products.Field to Update') }}</label>
                        <select class="form-select" id="bulkField">
                            <option value="">{{ __('products.Select field...') }}</option>
                            <option value="active">{{ __('common.Active Status') }}</option>
                            <option value="category_id">{{ __('common.Category') }}</option>
                            <option value="manufacturer_id">{{ __('products.Manufacturer') }}</option>
                            <option value="tax_id">{{ __('common.Tax') }}</option>
                            <option value="reorder_point">{{ __('products.Reorder Point') }}</option>
                            <option value="track_inventory">{{ __('products.Track Inventory') }}</option>
                            <option value="prescription_required">{{ __('products.Prescription Required') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('products.New Value') }}</label>
                        <input type="text" class="form-control" id="bulkValue" placeholder="{{ __('products.Enter new value...') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.Cancel') }}</button>
                    <button type="button" class="btn btn-primary" onclick="executeBulkUpdate()">{{ __('products.Update Selected') }}</button>
                </div>
            </div>
        </div>
    </div>

    @push('script')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const baseUrl = '{{ url("admin") }}';

        // Toggle filters
        function toggleFilters() {
            const panel = document.getElementById('filterPanel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }

        // Toggle column visibility
        function toggleColumns() {
            const cols = document.querySelectorAll('.col-item');
            cols.forEach(col => {
                col.style.display = col.style.display === 'none' ? '' : 'none';
            });
        }

        // Select all
        document.getElementById('select-all')?.addEventListener('change', function() {
            document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = this.checked);
            updateBulkBar();
        });

        document.querySelectorAll('.item-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkBar);
        });

        function updateBulkBar() {
            const checked = document.querySelectorAll('.item-checkbox:checked');
            document.getElementById('bulkActionsBar').style.display = checked.length > 0 ? 'block' : 'none';
            document.getElementById('selectedCount').textContent = checked.length + ' {{ __('products.selected') }}';
        }

        function getSelectedIds() {
            return Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);
        }

        function clearSelection() {
            document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('select-all').checked = false;
            updateBulkBar();
        }

        // Delete
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('{{ __("Are you sure you want to delete :name?", ["name" => ""]) }}' + this.dataset.name + '?')) {
                    fetch(`${baseUrl}/items/${this.dataset.id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            toastr.success(data.message);
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            toastr.error(data.message);
                        }
                    });
                }
            });
        });

        // Bulk update
        function bulkUpdateAction() {
            const ids = getSelectedIds();
            if (ids.length === 0) {
                toastr.warning('{{ __('products.Please select items first.') }}');
                return;
            }
            new bootstrap.Modal(document.getElementById('bulkUpdateModal')).show();
        }

        function executeBulkUpdate() {
            const ids = getSelectedIds();
            const field = document.getElementById('bulkField').value;
            const value = document.getElementById('bulkValue').value;

            if (!field || !value) {
                toastr.error('{{ __('products.Please select a field and enter a value.') }}');
                return;
            }

            fetch(`${baseUrl}/items/bulk-update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ product_ids: ids, data: { [field]: value } })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    toastr.error(data.message || 'Error');
                }
            });
        }

        function bulkDelete() {
            if (!confirm('{{ __('products.Are you sure you want to delete the selected items?') }}')) return;
            // TODO: Implement bulk delete
        }

        // Export
        function exportItems() {
            window.location.href = '{{ route("admin.items.export") }}' + '?' + new URLSearchParams(window.location.search).toString();
        }

        // Import
        function importItems(input) {
            const file = input.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            fetch(`${baseUrl}/items/import`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    toastr.error(data.message);
                }
            });
            input.value = '';
        }

        // Auto-submit filters on select change
        document.querySelectorAll('#filterForm select').forEach(select => {
            select.addEventListener('change', () => document.getElementById('filterForm').submit());
        });
    </script>
    @endpush
@endsection
