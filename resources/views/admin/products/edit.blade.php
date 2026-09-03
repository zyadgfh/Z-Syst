@extends('layouts.admin')

@section('title')
    {{ __('common.Edit') }}: {{ $product->productName }}
@endsection

@section('main_content')
<div class="container-fluid m-h-100">
    <div class="erp-table-section">
        <div class="card">
            <div class="card-bodys">
                <div class="chart-header p-16 border-0 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ __('products.Edit Item') }}: {{ $product->productName }}</h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.items.show', $product->id) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-eye me-1"></i>{{ __('common.View') }}
                        </a>
                        <a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>{{ __('common.Back') }}
                        </a>
                    </div>
                </div>

                <div class="p-16">
                    <form action="{{ route('admin.items.update', $product->id) }}" method="POST" id="editItemForm" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <ul class="nav nav-tabs mb-4" id="editItemTabs" role="tablist">
                            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-identity" type="button"><i class="fas fa-tag me-1"></i>{{ __('products.Identity') }}</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-classification" type="button"><i class="fas fa-layer-group me-1"></i>{{ __('products.Classification') }}</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pricing" type="button"><i class="fas fa-dollar-sign me-1"></i>{{ __('products.Pricing') }}</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-inventory" type="button"><i class="fas fa-warehouse me-1"></i>{{ __('products.Inventory') }}</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pharmacy" type="button"><i class="fas fa-pills me-1"></i>{{ __('products.Pharmacy') }}</button></li>
                        </ul>

                        <div class="tab-content">
                            {{-- Identity Tab --}}
                            <div class="tab-pane fade show active" id="tab-identity">
                                <div class="row">
                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.Product Name') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="productName" required value="{{ old('productName', $product->productName) }}">
                                                @error('productName') <small class="text-danger">{{ $message }}</small> @enderror
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.Barcode') }}</label>
                                                <input type="text" class="form-control" name="barcode" value="{{ old('barcode', $product->barcode) }}">
                                                @error('barcode') <small class="text-danger">{{ $message }}</small> @enderror
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('common.SKU') }}</label>
                                                <input type="text" class="form-control" name="sku" value="{{ old('sku', $product->sku ?? $product->productCode) }}">
                                                @error('sku') <small class="text-danger">{{ $message }}</small> @enderror
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.Scientific Name') }}</label>
                                                <input type="text" class="form-control" name="scientific_name" value="{{ old('scientific_name', $product->scientific_name) }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.Commercial Name') }}</label>
                                                <input type="text" class="form-control" name="commercial_name" value="{{ old('commercial_name', $product->commercial_name) }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.Short Name') }}</label>
                                                <input type="text" class="form-control" name="short_name" value="{{ old('short_name', $product->short_name) }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.Barcode Type') }}</label>
                                                <select class="form-select" name="barcode_type">
                                                    @foreach(['CODE128', 'EAN13', 'UPC', 'QR'] as $type)
                                                        <option value="{{ $type }}" {{ old('barcode_type', $product->barcode_type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.GTIN') }}</label>
                                                <input type="text" class="form-control" name="gtin" value="{{ old('gtin', $product->gtin) }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.Internal Code') }}</label>
                                                <input type="text" class="form-control" name="internal_code" value="{{ old('internal_code', $product->internal_code) }}">
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ __('common.Description') }}</label>
                                                <textarea class="form-control" name="description" rows="2">{{ old('description', $product->description) }}</textarea>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ __('common.Notes') }}</label>
                                                <textarea class="form-control" name="notes" rows="2">{{ old('notes', $product->notes) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">{{ __('common.Status') }}</h6>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="active" id="active" value="1" {{ old('active', $product->active) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="active">{{ __('common.Active') }}</label>
                                                </div>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="prescription_required" value="1" {{ old('prescription_required', $product->prescription_required) ? 'checked' : '' }}>
                                                    <label class="form-check-label">{{ __('products.Prescription Required') }}</label>
                                                </div>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="controlled_item" value="1" {{ old('controlled_item', $product->controlled_item) ? 'checked' : '' }}>
                                                    <label class="form-check-label">{{ __('products.Controlled Item') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Classification Tab --}}
                            <div class="tab-pane fade" id="tab-classification">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('common.Category') }} <span class="text-danger">*</span></label>
                                        <select class="form-select" name="category_id" required>
                                            <option value="">{{ __('products.Select Category') }}</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->categoryName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('products.Manufacturer') }}</label>
                                        <select class="form-select" name="manufacturer_id">
                                            <option value="">{{ __('products.Select Manufacturer') }}</option>
                                            @foreach($manufacturers as $manufacturer)
                                                <option value="{{ $manufacturer->id }}" {{ old('manufacturer_id', $product->manufacturer_id) == $manufacturer->id ? 'selected' : '' }}>{{ $manufacturer->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Unit') }}</label>
                                        <select class="form-select" name="unit_id">
                                            <option value="">{{ __('products.Select Unit') }}</option>
                                            @foreach($units as $unit)
                                                <option value="{{ $unit->id }}" {{ old('unit_id', $product->unit_id) == $unit->id ? 'selected' : '' }}>{{ $unit->unitName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Dosage Form') }}</label>
                                        <input type="text" class="form-control" name="dosage_form" value="{{ old('dosage_form', $product->dosage_form) }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Strength') }}</label>
                                        <input type="text" class="form-control" name="strength" value="{{ old('strength', $product->strength) }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Route of Administration') }}</label>
                                        <input type="text" class="form-control" name="route_of_administration" value="{{ old('route_of_administration', $product->route_of_administration) }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Product Type') }}</label>
                                        <select class="form-select" name="product_type">
                                            @foreach(['product' => __('common.Product'), 'service' => __('products.Service'), 'combo' => __('products.Combo')] as $val => $label)
                                                <option value="{{ $val }}" {{ old('product_type', $product->product_type ?? 'product') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Unit Type') }}</label>
                                        <input type="text" class="form-control" name="unit_type" value="{{ old('unit_type', $product->unit_type) }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('products.Preferred Supplier') }}</label>
                                        <select class="form-select" name="preferred_supplier_id">
                                            <option value="">{{ __('purchases.Select Supplier') }}</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}" {{ old('preferred_supplier_id', $product->preferred_supplier_id) == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Pricing Tab --}}
                            <div class="tab-pane fade" id="tab-pricing">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Purchase Price (excl. tax)') }}</label>
                                        <input type="number" step="0.01" class="form-control" name="purchase_without_tax" value="{{ old('purchase_without_tax', $product->purchase_without_tax) }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Purchase Price (incl. tax)') }}</label>
                                        <input type="number" step="0.01" class="form-control" name="purchase_with_tax" value="{{ old('purchase_with_tax', $product->purchase_with_tax) }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Selling Price') }} <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" class="form-control" name="sales_price" required value="{{ old('sales_price', $product->sales_price) }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Wholesale Price') }}</label>
                                        <input type="number" step="0.01" class="form-control" name="wholesale_price" value="{{ old('wholesale_price', $product->wholesale_price) }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Minimum Selling Price') }}</label>
                                        <input type="number" step="0.01" class="form-control" name="minimum_selling_price" value="{{ old('minimum_selling_price', $product->minimum_selling_price) }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Special Price') }}</label>
                                        <input type="number" step="0.01" class="form-control" name="special_price" value="{{ old('special_price', $product->special_price) }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Profit %') }}</label>
                                        <input type="number" step="0.01" class="form-control" name="profit_percent" value="{{ old('profit_percent', $product->profit_percent) }}" min="0" max="100">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('common.Tax') }}</label>
                                        <select class="form-select" name="tax_id">
                                            <option value="">{{ __('products.No Tax') }}</option>
                                            @php
                                                $taxes = \App\Models\Tax::where('business_id', auth()->user()->business_id)->where('status', 1)->get();
                                            @endphp
                                            @foreach($taxes as $tax)
                                                <option value="{{ $tax->id }}" {{ old('tax_id', $product->tax_id) == $tax->id ? 'selected' : '' }}>{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Tax Type') }}</label>
                                        <select class="form-select" name="tax_type">
                                            <option value="exclusive" {{ old('tax_type', $product->tax_type ?? 'exclusive') == 'exclusive' ? 'selected' : '' }}>{{ __('products.Exclusive') }}</option>
                                            <option value="inclusive" {{ old('tax_type', $product->tax_type) == 'inclusive' ? 'selected' : '' }}>{{ __('products.Inclusive') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Inventory Tab --}}
                            <div class="tab-pane fade" id="tab-inventory">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="track_inventory" value="1" {{ old('track_inventory', $product->track_inventory) ? 'checked' : '' }}>
                                            <label class="form-check-label"><strong>{{ __('products.Track Inventory') }}</strong></label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="allow_negative_stock" value="1" {{ old('allow_negative_stock', $product->allow_negative_stock) ? 'checked' : '' }}>
                                            <label class="form-check-label">{{ __('products.Allow Negative Stock') }}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="track_expiration" value="1" {{ old('track_expiration', $product->track_expiration) ? 'checked' : '' }}>
                                            <label class="form-check-label">{{ __('products.Track Expiration') }}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="refrigerated" value="1" {{ old('refrigerated', $product->refrigerated) ? 'checked' : '' }}>
                                            <label class="form-check-label">{{ __('products.Refrigerated') }}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Add Quantity') }}</label>
                                        <input type="number" class="form-control" name="qty" value="{{ old('qty', 0) }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('common.Batch Number') }}</label>
                                        <input type="text" class="form-control" name="batch_no" value="{{ old('batch_no') }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Expiration Date') }}</label>
                                        <input type="date" class="form-control" name="expire_date" value="{{ old('expire_date') }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Alert Quantity') }}</label>
                                        <input type="number" class="form-control" name="alert_qty" value="{{ old('alert_qty', $product->alert_qty) }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Reorder Point') }}</label>
                                        <input type="number" class="form-control" name="reorder_point" value="{{ old('reorder_point', $product->reorder_point) }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Reorder Quantity') }}</label>
                                        <input type="number" class="form-control" name="reorder_quantity" value="{{ old('reorder_quantity', $product->reorder_quantity) }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Minimum Stock') }}</label>
                                        <input type="number" class="form-control" name="minimum_stock" value="{{ old('minimum_stock', $product->minimum_stock) }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Maximum Stock') }}</label>
                                        <input type="number" class="form-control" name="maximum_stock" value="{{ old('maximum_stock', $product->maximum_stock) }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Safety Stock') }}</label>
                                        <input type="number" class="form-control" name="safety_stock" value="{{ old('safety_stock', $product->safety_stock) }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Expiration Warning Days') }}</label>
                                        <input type="number" class="form-control" name="expiration_warning_days" value="{{ old('expiration_warning_days', $product->expiration_warning_days) }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Min Remaining Shelf Life') }}</label>
                                        <input type="number" class="form-control" name="minimum_remaining_shelf_life" value="{{ old('minimum_remaining_shelf_life', $product->minimum_remaining_shelf_life) }}" min="0">
                                    </div>
                                </div>
                            </div>

                            {{-- Pharmacy Tab --}}
                            <div class="tab-pane fade" id="tab-pharmacy">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Active Ingredient') }}</label>
                                        <input type="text" class="form-control" name="active_ingredient" value="{{ old('active_ingredient', $product->active_ingredient) }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Concentration') }}</label>
                                        <input type="text" class="form-control" name="concentration" value="{{ old('concentration', $product->concentration) }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Dosage') }}</label>
                                        <input type="text" class="form-control" name="dosage" value="{{ old('dosage', $product->dosage) }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Package Size') }}</label>
                                        <input type="number" class="form-control" name="package_size" value="{{ old('package_size', $product->package_size) }}" min="1">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Package Unit') }}</label>
                                        <input type="text" class="form-control" name="package_unit" value="{{ old('package_unit', $product->package_unit) }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Temperature Requirements') }}</label>
                                        <input type="text" class="form-control" name="temperature_requirements" value="{{ old('temperature_requirements', $product->temperature_requirements) }}">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">{{ __('products.Storage Instructions') }}</label>
                                        <textarea class="form-control" name="storage_instructions" rows="2">{{ old('storage_instructions', $product->storage_instructions) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('admin.items.show', $product->id) }}" class="btn btn-secondary">{{ __('common.Cancel') }}</a>
                            <button type="submit" class="btn btn-primary btn-lg" id="saveBtn">
                                <i class="fas fa-save me-2"></i>{{ __('products.Update Item') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const baseUrl = '{{ url("admin") }}';

    document.getElementById('editItemForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>{{ __('common.Saving...') }}';

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'X-HTTP-Method-Override': 'PUT'
            },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                setTimeout(() => window.location.href = '{{ route("admin.items.show", $product->id) }}', 1000);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>{{ __('products.Update Item') }}';
                if (data.errors) {
                    Object.entries(data.errors).forEach(([field, msgs]) => {
                        toastr.error(`${field}: ${msgs.join(', ')}`);
                    });
                } else {
                    toastr.error(data.message || '{{ __('common.An error occurred') }}');
                }
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-2"></i>{{ __('products.Update Item') }}';
            toastr.error('{{ __('products.Network error. Please try again.') }}');
        });
    });
</script>
@endpush
@endsection
