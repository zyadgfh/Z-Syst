@extends('layouts.master')

@section('title')
    {{ __('products.Create New Item') }}
@endsection

@section('main_content')
<div class="container-fluid m-h-100">
    <div class="erp-table-section">
        <div class="card">
            <div class="card-bodys">
                {{-- Header --}}
                <div class="chart-header p-16 border-0 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ __('products.Create New Item') }}</h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>{{ __('common.Back') }}
                        </a>
                    </div>
                </div>

                <div class="p-16">
                    {{-- Barcode Scanner Input --}}
                    <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                        <i class="fas fa-barcode me-3 fa-lg"></i>
                        <div>
                            <strong>{{ __('products.Quick Start') }}:</strong> {{ __('products.Scan a barcode or type one in the barcode field below. The system will check for existing items automatically.') }}
                        </div>
                    </div>

                    {{-- Duplicate Warning --}}
                    <div id="duplicateWarning" class="alert alert-warning d-none mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                            <div id="duplicateMessage"></div>
                        </div>
                    </div>

                    <form action="{{ route('admin.items.store') }}" method="POST" id="itemForm" enctype="multipart/form-data">
                        @csrf

                        {{-- Tabs Navigation --}}
                        <ul class="nav nav-tabs mb-4" id="createItemTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-identity" type="button">
                                    <i class="fas fa-tag me-1"></i>{{ __('products.Identity') }}
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-classification" type="button">
                                    <i class="fas fa-layer-group me-1"></i>{{ __('products.Classification') }}
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pricing" type="button">
                                    <i class="fas fa-dollar-sign me-1"></i>{{ __('products.Pricing') }}
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-inventory" type="button">
                                    <i class="fas fa-warehouse me-1"></i>{{ __('products.Inventory') }}
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pharmacy" type="button">
                                    <i class="fas fa-pills me-1"></i>{{ __('products.Pharmacy') }}
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            {{-- Identity Tab --}}
                            <div class="tab-pane fade show active" id="tab-identity">
                                <div class="row">
                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.Product Name') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="productName" id="productName" required value="{{ old('productName') }}">
                                                @error('productName') <small class="text-danger">{{ $message }}</small> @enderror
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.Barcode') }}</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                                    <input type="text" class="form-control" name="barcode" id="barcode" value="{{ old('barcode') }}" placeholder="{{ __('products.Scan or type...') }}">
                                                </div>
                                                @error('barcode') <small class="text-danger">{{ $message }}</small> @enderror
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('common.SKU') }}</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="sku" id="sku" value="{{ old('sku') }}">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="generateCode()" title="{{ __('products.Auto-generate') }}">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                </div>
                                                @error('sku') <small class="text-danger">{{ $message }}</small> @enderror
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.Scientific Name') }}</label>
                                                <input type="text" class="form-control" name="scientific_name" value="{{ old('scientific_name') }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.Commercial Name') }}</label>
                                                <input type="text" class="form-control" name="commercial_name" value="{{ old('commercial_name') }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.Short Name') }}</label>
                                                <input type="text" class="form-control" name="short_name" value="{{ old('short_name') }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.Barcode Type') }}</label>
                                                <select class="form-select" name="barcode_type">
                                                    <option value="CODE128" {{ old('barcode_type') == 'CODE128' ? 'selected' : '' }}>CODE128</option>
                                                    <option value="EAN13" {{ old('barcode_type') == 'EAN13' ? 'selected' : '' }}>EAN13</option>
                                                    <option value="UPC" {{ old('barcode_type') == 'UPC' ? 'selected' : '' }}>UPC</option>
                                                    <option value="QR" {{ old('barcode_type') == 'QR' ? 'selected' : '' }}>QR</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.GTIN') }}</label>
                                                <input type="text" class="form-control" name="gtin" value="{{ old('gtin') }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{ __('products.Internal Code') }}</label>
                                                <input type="text" class="form-control" name="internal_code" value="{{ old('internal_code') }}">
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ __('common.Description') }}</label>
                                                <textarea class="form-control" name="description" rows="2">{{ old('description') }}</textarea>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">{{ __('common.Notes') }}</label>
                                                <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">{{ __('common.Status') }}</h6>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="active" id="active" value="1" checked>
                                                    <label class="form-check-label" for="active">{{ __('common.Active') }}</label>
                                                </div>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="prescription_required" id="prescription_required" value="1">
                                                    <label class="form-check-label" for="prescription_required">{{ __('products.Prescription Required') }}</label>
                                                </div>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="controlled_item" id="controlled_item" value="1">
                                                    <label class="form-check-label" for="controlled_item">{{ __('products.Controlled Item') }}</label>
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
                                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->categoryName }}</option>
                                            @endforeach
                                        </select>
                                        @error('category_id') <small class="text-danger">{{ $message }}</small> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('products.Manufacturer') }}</label>
                                        <select class="form-select" name="manufacturer_id">
                                            <option value="">{{ __('products.Select Manufacturer') }}</option>
                                            @foreach($manufacturers as $manufacturer)
                                                <option value="{{ $manufacturer->id }}" {{ old('manufacturer_id') == $manufacturer->id ? 'selected' : '' }}>{{ $manufacturer->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Unit') }}</label>
                                        <select class="form-select" name="unit_id">
                                            <option value="">{{ __('products.Select Unit') }}</option>
                                            @foreach($units as $unit)
                                                <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->unitName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Dosage Form') }}</label>
                                        <input type="text" class="form-control" name="dosage_form" value="{{ old('dosage_form') }}" placeholder="{{ __('products.tablet, capsule, syrup...') }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Strength') }}</label>
                                        <input type="text" class="form-control" name="strength" value="{{ old('strength') }}" placeholder="{{ __('products.500mg, 10ml...') }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Route of Administration') }}</label>
                                        <input type="text" class="form-control" name="route_of_administration" value="{{ old('route_of_administration') }}" placeholder="{{ __('products.oral, topical, IV...') }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Product Type') }}</label>
                                        <select class="form-select" name="product_type">
                                            <option value="product" {{ old('product_type', 'product') == 'product' ? 'selected' : '' }}>{{ __('common.Product') }}</option>
                                            <option value="service" {{ old('product_type') == 'service' ? 'selected' : '' }}>{{ __('products.Service') }}</option>
                                            <option value="combo" {{ old('product_type') == 'combo' ? 'selected' : '' }}>{{ __('products.Combo') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Unit Type') }}</label>
                                        <input type="text" class="form-control" name="unit_type" value="{{ old('unit_type') }}" placeholder="{{ __('products.each, pack, bottle...') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ __('products.Preferred Supplier') }}</label>
                                        <select class="form-select" name="preferred_supplier_id">
                                            <option value="">{{ __('purchases.Select Supplier') }}</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}" {{ old('preferred_supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
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
                                        <input type="number" step="0.01" class="form-control" name="purchase_without_tax" value="{{ old('purchase_without_tax', 0) }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Purchase Price (incl. tax)') }}</label>
                                        <input type="number" step="0.01" class="form-control" name="purchase_with_tax" value="{{ old('purchase_with_tax', 0) }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Selling Price') }} <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" class="form-control" name="sales_price" value="{{ old('sales_price', 0) }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Wholesale Price') }}</label>
                                        <input type="number" step="0.01" class="form-control" name="wholesale_price" value="{{ old('wholesale_price', 0) }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Minimum Selling Price') }}</label>
                                        <input type="number" step="0.01" class="form-control" name="minimum_selling_price" value="{{ old('minimum_selling_price') }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Special Price') }}</label>
                                        <input type="number" step="0.01" class="form-control" name="special_price" value="{{ old('special_price') }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Profit %') }}</label>
                                        <input type="number" step="0.01" class="form-control" name="profit_percent" value="{{ old('profit_percent', 0) }}" min="0" max="100">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('common.Tax') }}</label>
                                        <select class="form-select" name="tax_id">
                                            <option value="">{{ __('products.No Tax') }}</option>
                                            @php
                                                $taxes = \App\Models\Tax::where('business_id', auth()->user()->business_id)->where('status', 1)->get();
                                            @endphp
                                            @foreach($taxes as $tax)
                                                <option value="{{ $tax->id }}" {{ old('tax_id') == $tax->id ? 'selected' : '' }}>{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Tax Type') }}</label>
                                        <select class="form-select" name="tax_type">
                                            <option value="exclusive" {{ old('tax_type', 'exclusive') == 'exclusive' ? 'selected' : '' }}>{{ __('products.Exclusive') }}</option>
                                            <option value="inclusive" {{ old('tax_type') == 'inclusive' ? 'selected' : '' }}>{{ __('products.Inclusive') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Inventory Tab --}}
                            <div class="tab-pane fade" id="tab-inventory">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="track_inventory" id="track_inventory" value="1" checked>
                                            <label class="form-check-label" for="track_inventory"><strong>{{ __('products.Track Inventory') }}</strong></label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="allow_negative_stock" id="allow_negative_stock" value="1">
                                            <label class="form-check-label" for="allow_negative_stock">{{ __('products.Allow Negative Stock') }}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="track_expiration" id="track_expiration" value="1">
                                            <label class="form-check-label" for="track_expiration">{{ __('products.Track Expiration') }}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="refrigerated" id="refrigerated" value="1">
                                            <label class="form-check-label" for="refrigerated">{{ __('products.Refrigerated') }}</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Initial Quantity') }}</label>
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
                                        <input type="number" class="form-control" name="alert_qty" value="{{ old('alert_qty', 0) }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Reorder Point') }}</label>
                                        <input type="number" class="form-control" name="reorder_point" value="{{ old('reorder_point', 0) }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Reorder Quantity') }}</label>
                                        <input type="number" class="form-control" name="reorder_quantity" value="{{ old('reorder_quantity') }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Minimum Stock') }}</label>
                                        <input type="number" class="form-control" name="minimum_stock" value="{{ old('minimum_stock', 0) }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Maximum Stock') }}</label>
                                        <input type="number" class="form-control" name="maximum_stock" value="{{ old('maximum_stock') }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Safety Stock') }}</label>
                                        <input type="number" class="form-control" name="safety_stock" value="{{ old('safety_stock') }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Expiration Warning Days') }}</label>
                                        <input type="number" class="form-control" name="expiration_warning_days" value="{{ old('expiration_warning_days', 30) }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Min Remaining Shelf Life') }}</label>
                                        <input type="number" class="form-control" name="minimum_remaining_shelf_life" value="{{ old('minimum_remaining_shelf_life') }}" min="0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">{{ __('products.Conversion Factor') }}</label>
                                        <input type="number" step="0.0001" class="form-control" name="conversion_factor" value="{{ old('conversion_factor', 1) }}" min="0.0001">
                                    </div>
                                </div>
                            </div>

                            {{-- Pharmacy Tab --}}
                            <div class="tab-pane fade" id="tab-pharmacy">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Active Ingredient') }}</label>
                                        <input type="text" class="form-control" name="active_ingredient" value="{{ old('active_ingredient') }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Concentration') }}</label>
                                        <input type="text" class="form-control" name="concentration" value="{{ old('concentration') }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Dosage') }}</label>
                                        <input type="text" class="form-control" name="dosage" value="{{ old('dosage') }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Package Size') }}</label>
                                        <input type="number" class="form-control" name="package_size" value="{{ old('package_size') }}" min="1">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Package Unit') }}</label>
                                        <input type="text" class="form-control" name="package_unit" value="{{ old('package_unit') }}" placeholder="{{ __('products.strip, box, bottle...') }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('products.Temperature Requirements') }}</label>
                                        <input type="text" class="form-control" name="temperature_requirements" value="{{ old('temperature_requirements') }}" placeholder="{{ __('products.2-8°C, room temp...') }}">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">{{ __('products.Storage Instructions') }}</label>
                                        <textarea class="form-control" name="storage_instructions" rows="2">{{ old('storage_instructions') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="continueCreating">
                                    <label class="form-check-label" for="continueCreating">{{ __('products.Continue creating after save') }}</label>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.items.index') }}" class="btn btn-secondary">{{ __('common.Cancel') }}</a>
                                <button type="submit" class="btn btn-primary btn-lg" id="saveBtn">
                                    <i class="fas fa-save me-2"></i>{{ __('products.Save Item') }}
                                </button>
                            </div>
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
    let duplicateCheckTimer = null;

    // ── Duplicate Detection ──
    ['barcode', 'sku', 'productName', 'scientific_name'].forEach(fieldName => {
        const el = document.getElementById(fieldName) || document.querySelector(`[name="${fieldName}"]`);
        if (el) {
            el.addEventListener('input', function() {
                clearTimeout(duplicateCheckTimer);
                duplicateCheckTimer = setTimeout(() => checkDuplicates(), 500);
            });
        }
    });

    function checkDuplicates() {
        const data = {
            barcode: document.getElementById('barcode')?.value || '',
            sku: document.getElementById('sku')?.value || '',
            productName: document.getElementById('productName')?.value || '',
            scientific_name: document.querySelector('[name="scientific_name"]')?.value || '',
        };

        if (!data.barcode && !data.sku && !data.productName) return;

        fetch(`${baseUrl}/items/check-duplicates`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(data => {
            const warning = document.getElementById('duplicateWarning');
            const message = document.getElementById('duplicateMessage');
            if (data.has_duplicates) {
                let html = '<strong>{{ __('products.Possible Duplicate Found!') }}</strong><br>';
                Object.entries(data.duplicates).forEach(([key, product]) => {
                    html += `{{ __('products.Match by') }} <strong>${key}</strong>: <a href="${baseUrl}/items/${product.id}" target="_blank">${product.productName}</a> (${product.productCode || product.barcode || product.sku || ''})<br>`;
                });
                message.innerHTML = html;
                warning.classList.remove('d-none');
            } else {
                warning.classList.add('d-none');
            }
        });
    }

    // ── Auto-generate SKU ──
    function generateCode() {
        fetch(`${baseUrl}/items/generate-code`, {
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('sku').value = data.internal_code;
                toastr.success('{{ __('products.Code generated:') }} ' + data.internal_code);
            }
        });
    }

    // ── Form Submission ──
    document.getElementById('itemForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>{{ __('common.Saving...') }}';

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                if (document.getElementById('continueCreating').checked) {
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    setTimeout(() => window.location.href = data.redirect || '{{ route("admin.items.index") }}', 1000);
                }
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-2"></i>{{ __('products.Save Item') }}';
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
            btn.innerHTML = '<i class="fas fa-save me-2"></i>{{ __('products.Save Item') }}';
            toastr.error('{{ __('products.Network error. Please try again.') }}');
        });
    });

    // ── Barcode input: auto-search existing ──
    document.getElementById('barcode')?.addEventListener('blur', function() {
        const barcode = this.value.trim();
        if (barcode && barcode.length >= 3) {
            fetch(`${baseUrl}/items/search-barcode`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ barcode })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    if (confirm('{{ __('products.An item with this barcode already exists. View it?') }}')) {
                        window.location.href = `${baseUrl}/items/${data.data.id}`;
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection
