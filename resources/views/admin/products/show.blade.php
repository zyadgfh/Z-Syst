@extends('layouts.master')

@section('title')
    {{ $product->productName }} — {{ __('Item Details') }}
@endsection

@section('main_content')
<div class="container-fluid m-h-100">
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>{{ __('Back') }}
            </a>
            <div>
                <h4 class="mb-0">{{ $product->productName }}</h4>
                <div class="d-flex gap-2 align-items-center">
                    @if($product->sku || $product->productCode)
                        <code>{{ $product->sku ?? $product->productCode }}</code>
                    @endif
                    @if($product->barcode)
                        <span class="badge badge-soft-secondary">{{ $product->barcode }}</span>
                    @endif
                    @if($product->active)
                        <span class="badge badge-soft-success">{{ __('Active') }}</span>
                    @else
                        <span class="badge badge-soft-danger">{{ __('Inactive') }}</span>
                    @endif
                    @if($product->prescription_required)
                        <span class="badge badge-soft-warning"><i class="fas fa-prescription me-1"></i>{{ __('Rx Required') }}</span>
                    @endif
                    @if($product->controlled_item)
                        <span class="badge badge-soft-danger"><i class="fas fa-exclamation-triangle me-1"></i>{{ __('Controlled') }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            @if($product->barcode)
                <button class="btn btn-info" onclick="openBarcodePrintModal()">
                    <i class="fas fa-barcode me-1"></i>{{ __('Print Barcode') }}
                </button>
            @endif
            @can('update', $product)
                <a href="{{ route('admin.items.edit', $product->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i>{{ __('Edit') }}
                </a>
            @endcan
            @can('delete', $product)
                <button class="btn btn-danger" onclick="deleteItem()">
                    <i class="fas fa-trash me-1"></i>{{ __('Delete') }}
                </button>
            @endcan
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">{{ __('Current Stock') }}</div>
                    <h3 class="mb-0 @if($kpis['current_stock'] <= 0) text-danger @elseif($product->alert_qty > 0 && $kpis['current_stock'] <= $product->alert_qty) text-warning @else text-success @endif">
                        {{ $kpis['current_stock'] }}
                    </h3>
                    @can('update', $product)
                        <button class="btn btn-sm btn-outline-primary mt-2" onclick="openStockAdjustModal()">
                            <i class="fas fa-plus-minus me-1"></i>{{ __('Adjust Stock') }}
                        </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">{{ __('Selling Price') }}</div>
                    <h3 class="mb-0 text-primary">{{ number_format($kpis['current_selling_price'], 2) }}</h3>
                    @if($product->wholesale_price)
                        <small class="text-muted">{{ __('Wholesale') }}: {{ number_format($product->wholesale_price, 2) }}</small>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">{{ __('Avg Cost') }}</div>
                    <h3 class="mb-0">{{ number_format($kpis['average_cost'], 2) }}</h3>
                    <small class="text-muted">{{ __('Margin') }}: {{ number_format($kpis['profit_margin'], 1) }}%</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">{{ __('Stock Status') }}</div>
                    <h3 class="mb-0">
                        @if($kpis['reorder_status'] === 'out_of_stock')
                            <span class="text-danger">{{ __('Out of Stock') }}</span>
                        @elseif($kpis['reorder_status'] === 'low_stock')
                            <span class="text-warning">{{ __('Low Stock') }}</span>
                        @elseif($kpis['reorder_status'] === 'reorder')
                            <span class="text-info">{{ __('Reorder') }}</span>
                        @else
                            <span class="text-success">{{ __('In Stock') }}</span>
                        @endif
                    </h3>
                    <small class="text-muted">{{ __('Sold') }}: {{ number_format($kpis['total_sold']) }} | {{ __('Purchased') }}: {{ number_format($kpis['total_purchased']) }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs" id="itemTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview" type="button">
                        <i class="fas fa-info-circle me-1"></i>{{ __('Overview') }}
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pricing" type="button">
                        <i class="fas fa-tag me-1"></i>{{ __('Pricing') }}
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#inventory" type="button">
                        <i class="fas fa-warehouse me-1"></i>{{ __('Inventory') }}
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#suppliers" type="button">
                        <i class="fas fa-truck me-1"></i>{{ __('Suppliers') }}
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#barcodes" type="button">
                        <i class="fas fa-barcode me-1"></i>{{ __('Barcodes') }}
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#stock-history" type="button">
                        <i class="fas fa-history me-1"></i>{{ __('Stock History') }}
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#print-history" type="button">
                        <i class="fas fa-print me-1"></i>{{ __('Print History') }}
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#timeline" type="button">
                        <i class="fas fa-stream me-1"></i>{{ __('Timeline') }}
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-4">
                {{-- Overview Tab --}}
                <div class="tab-pane fade show active" id="overview">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="text-muted mb-3">{{ __('Basic Information') }}</h6>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr><td class="text-muted" style="width:40%">{{ __('Product Name') }}</td><td><strong>{{ $product->productName }}</strong></td></tr>
                                        @if($product->scientific_name)
                                            <tr><td class="text-muted">{{ __('Scientific Name') }}</td><td>{{ $product->scientific_name }}</td></tr>
                                        @endif
                                        @if($product->commercial_name)
                                            <tr><td class="text-muted">{{ __('Commercial Name') }}</td><td>{{ $product->commercial_name }}</td></tr>
                                        @endif
                                        <tr><td class="text-muted">{{ __('SKU') }}</td><td><code>{{ $product->sku ?? $product->productCode ?? '-' }}</code></td></tr>
                                        <tr><td class="text-muted">{{ __('Internal Code') }}</td><td>{{ $product->internal_code ?? '-' }}</td></tr>
                                        <tr><td class="text-muted">{{ __('Category') }}</td><td>{{ $product->category->categoryName ?? '-' }}</td></tr>
                                        @if($product->subcategory)
                                            <tr><td class="text-muted">{{ __('Subcategory') }}</td><td>{{ $product->subcategory->categoryName }}</td></tr>
                                        @endif
                                        <tr><td class="text-muted">{{ __('Manufacturer') }}</td><td>{{ $product->manufacturer->name ?? '-' }}</td></tr>
                                        @if($product->brand)
                                            <tr><td class="text-muted">{{ __('Brand') }}</td><td>{{ $product->brand->name }}</td></tr>
                                        @endif
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        @if($product->dosage_form)
                                            <tr><td class="text-muted" style="width:40%">{{ __('Dosage Form') }}</td><td>{{ $product->dosage_form }}</td></tr>
                                        @endif
                                        @if($product->strength)
                                            <tr><td class="text-muted">{{ __('Strength') }}</td><td>{{ $product->strength }}</td></tr>
                                        @endif
                                        @if($product->active_ingredient)
                                            <tr><td class="text-muted">{{ __('Active Ingredient') }}</td><td>{{ $product->active_ingredient }}</td></tr>
                                        @endif
                                        @if($product->concentration)
                                            <tr><td class="text-muted">{{ __('Concentration') }}</td><td>{{ $product->concentration }}</td></tr>
                                        @endif
                                        @if($product->route_of_administration)
                                            <tr><td class="text-muted">{{ __('Route') }}</td><td>{{ $product->route_of_administration }}</td></tr>
                                        @endif
                                        <tr><td class="text-muted">{{ __('Unit') }}</td><td>{{ $product->unit->unitName ?? '-' }}</td></tr>
                                        @if($product->medicine_type)
                                            <tr><td class="text-muted">{{ __('Type') }}</td><td>{{ $product->medicine_type->name }}</td></tr>
                                        @endif
                                        <tr><td class="text-muted">{{ __('Refrigerated') }}</td><td>{{ $product->refrigerated ? __('Yes') : __('No') }}</td></tr>
                                    </table>
                                </div>
                            </div>

                            @if($product->description || $product->notes)
                                <h6 class="text-muted mb-2">{{ __('Description') }}</h6>
                                <p>{{ $product->description ?? '-' }}</p>
                                @if($product->notes)
                                    <h6 class="text-muted mb-2">{{ __('Notes') }}</h6>
                                    <p class="text-muted">{{ $product->notes }}</p>
                                @endif
                            @endif
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-muted mb-3">{{ __('Inventory Settings') }}</h6>
                            <table class="table table-sm">
                                <tr><td class="text-muted">{{ __('Track Inventory') }}</td><td>{{ $product->track_inventory ? __('Yes') : __('No') }}</td></tr>
                                <tr><td class="text-muted">{{ __('Allow Negative Stock') }}</td><td>{{ $product->allow_negative_stock ? __('Yes') : __('No') }}</td></tr>
                                <tr><td class="text-muted">{{ __('Alert Quantity') }}</td><td>{{ $product->alert_qty }}</td></tr>
                                <tr><td class="text-muted">{{ __('Reorder Point') }}</td><td>{{ $product->reorder_point }}</td></tr>
                                @if($product->reorder_quantity)
                                    <tr><td class="text-muted">{{ __('Reorder Qty') }}</td><td>{{ $product->reorder_quantity }}</td></tr>
                                @endif
                                @if($product->minimum_stock)
                                    <tr><td class="text-muted">{{ __('Minimum Stock') }}</td><td>{{ $product->minimum_stock }}</td></tr>
                                @endif
                                @if($product->maximum_stock)
                                    <tr><td class="text-muted">{{ __('Maximum Stock') }}</td><td>{{ $product->maximum_stock }}</td></tr>
                                @endif
                            </table>

                            <h6 class="text-muted mb-3 mt-4">{{ __('Expiration') }}</h6>
                            <table class="table table-sm">
                                <tr><td class="text-muted">{{ __('Track Expiration') }}</td><td>{{ $product->track_expiration ? __('Yes') : __('No') }}</td></tr>
                                @if($product->expiration_warning_days)
                                    <tr><td class="text-muted">{{ __('Warning Days') }}</td><td>{{ $product->expiration_warning_days }} {{ __('days') }}</td></tr>
                                @endif
                                @if($product->minimum_remaining_shelf_life)
                                    <tr><td class="text-muted">{{ __('Min Shelf Life') }}</td><td>{{ $product->minimum_remaining_shelf_life }} {{ __('days') }}</td></tr>
                                @endif
                            </table>

                            @if($product->storage_instructions)
                                <h6 class="text-muted mb-3 mt-4">{{ __('Storage') }}</h6>
                                <p class="text-muted">{{ $product->storage_instructions }}</p>
                            @endif

                            <h6 class="text-muted mb-3 mt-4">{{ __('Audit') }}</h6>
                            <table class="table table-sm">
                                <tr><td class="text-muted">{{ __('Created') }}</td><td>{{ $product->created_at?->format('d M Y H:i') }}</td></tr>
                                <tr><td class="text-muted">{{ __('Updated') }}</td><td>{{ $product->updated_at?->format('d M Y H:i') }}</td></tr>
                                @if($product->createdByUser)
                                    <tr><td class="text-muted">{{ __('Created By') }}</td><td>{{ $product->createdByUser->name }}</td></tr>
                                @endif
                                @if($product->updatedByUser)
                                    <tr><td class="text-muted">{{ __('Updated By') }}</td><td>{{ $product->updatedByUser->name }}</td></tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Pricing Tab --}}
                <div class="tab-pane fade" id="pricing">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">{{ __('Price Information') }}</h6>
                            <table class="table table-bordered">
                                <tr><td class="text-muted">{{ __('Purchase Price (excl. tax)') }}</td><td><strong>{{ number_format($product->purchase_without_tax ?? 0, 2) }}</strong></td></tr>
                                <tr><td class="text-muted">{{ __('Purchase Price (incl. tax)') }}</td><td><strong>{{ number_format($product->purchase_with_tax ?? 0, 2) }}</strong></td></tr>
                                <tr><td class="text-muted">{{ __('Selling Price') }}</td><td><strong class="text-primary">{{ number_format($product->sales_price ?? 0, 2) }}</strong></td></tr>
                                <tr><td class="text-muted">{{ __('Wholesale Price') }}</td><td>{{ number_format($product->wholesale_price ?? 0, 2) }}</td></tr>
                                @if($product->minimum_selling_price)
                                    <tr><td class="text-muted">{{ __('Min Selling Price') }}</td><td>{{ number_format($product->minimum_selling_price, 2) }}</td></tr>
                                @endif
                                @if($product->special_price)
                                    <tr><td class="text-muted">{{ __('Special Price') }}</td><td class="text-success fw-bold">{{ number_format($product->special_price, 2) }}</td></tr>
                                @endif
                                <tr><td class="text-muted">{{ __('Profit Margin') }}</td><td><span class="badge badge-soft-success">{{ number_format($product->profit_margin, 1) }}%</span></td></tr>
                                <tr><td class="text-muted">{{ __('Tax Type') }}</td><td>{{ ucfirst($product->tax_type ?? 'exclusive') }}</td></tr>
                                @if($product->tax)
                                    <tr><td class="text-muted">{{ __('Tax Rate') }}</td><td>{{ $product->tax->rate }}% ({{ $product->tax->name }})</td></tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">{{ __('Profit Analysis') }}</h6>
                            @php
                                $cost = $product->purchase_with_tax ?? $product->purchase_without_tax ?? 0;
                                $price = $product->getCurrentSellingPriceAttribute();
                                $profit = $price - $cost;
                            @endphp
                            <div class="row">
                                <div class="col-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body text-center">
                                            <div class="text-muted small">{{ __('Gross Profit per Unit') }}</div>
                                            <h4 class="{{ $profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($profit, 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card border-0 bg-light">
                                        <div class="card-body text-center">
                                            <div class="text-muted small">{{ __('Total Revenue (Sold)') }}</div>
                                            <h4 class="text-primary">{{ number_format($kpis['total_sold'] * $price, 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Price History Chart --}}
                            @if($priceHistory->count() > 0)
                                <div class="mt-4">
                                    <h6 class="text-muted mb-3">{{ __('Price History') }}</h6>
                                    <div style="position:relative;height:250px">
                                        <canvas id="priceHistoryChart"></canvas>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Inventory Tab --}}
                <div class="tab-pane fade" id="inventory">
                    <h6 class="text-muted mb-3">{{ __('Stock Batches') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('Batch') }}</th>
                                    <th>{{ __('Stock') }}</th>
                                    <th>{{ __('Expiry') }}</th>
                                    <th>{{ __('Purchase Price') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($product->allStocks as $stock)
                                    <tr>
                                        <td><code>{{ $stock->batch_no ?? '-' }}</code></td>
                                        <td>
                                            <strong class="{{ $stock->productStock <= 0 ? 'text-danger' : '' }}">{{ $stock->productStock }}</strong>
                                        </td>
                                        <td>
                                            @if($stock->expire_date)
                                                @if($stock->expire_date < now())
                                                    <span class="badge badge-soft-danger">{{ $stock->expire_date->format('d/m/Y') }} ({{ __('Expired') }})</span>
                                                @elseif($stock->expire_date < now()->addDays(30))
                                                    <span class="badge badge-soft-warning">{{ $stock->expire_date->format('d/m/Y') }}</span>
                                                @else
                                                    <span class="badge badge-soft-success">{{ $stock->expire_date->format('d/m/Y') }}</span>
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ number_format($stock->purchase_price ?? 0, 2) }}</td>
                                        <td>
                                            @if($stock->productStock > 0)
                                                <span class="badge badge-soft-success">{{ __('In Stock') }}</span>
                                            @else
                                                <span class="badge badge-soft-secondary">{{ __('Empty') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-3">{{ __('No stock batches found.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Suppliers Tab --}}
                <div class="tab-pane fade" id="suppliers">
                    <h6 class="text-muted mb-3">{{ __('Supplier Relationships') }}</h6>
                    @if($product->itemSuppliers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('Supplier') }}</th>
                                        <th>{{ __('Item Code') }}</th>
                                        <th>{{ __('Purchase Price') }}</th>
                                        <th>{{ __('Lead Time') }}</th>
                                        <th>{{ __('Min Order') }}</th>
                                        <th>{{ __('Preferred') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($product->itemSuppliers as $itemSupplier)
                                        <tr>
                                            <td>{{ $itemSupplier->supplier->name ?? $itemSupplier->supplier->company_name ?? '-' }}</td>
                                            <td><code>{{ $itemSupplier->supplier_item_code ?? '-' }}</code></td>
                                            <td>{{ $itemSupplier->supplier_purchase_price ? number_format($itemSupplier->supplier_purchase_price, 2) : '-' }}</td>
                                            <td>{{ $itemSupplier->lead_time_days ? $itemSupplier->lead_time_days . ' ' . __('days') : '-' }}</td>
                                            <td>{{ $itemSupplier->minimum_order_quantity ?? '-' }}</td>
                                            <td>
                                                @if($itemSupplier->is_preferred)
                                                    <span class="badge badge-soft-success"><i class="fas fa-star me-1"></i>{{ __('Preferred') }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-truck fa-2x mb-2"></i>
                            <p>{{ __('No supplier relationships configured.') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Barcodes Tab --}}
                <div class="tab-pane fade" id="barcodes">
                    <h6 class="text-muted mb-3">{{ __('Product Barcodes') }}</h6>
                    @if($product->barcode)
                        <div class="mb-3">
                            <strong>{{ __('Primary Barcode') }}:</strong> <code class="fs-5">{{ $product->barcode }}</code>
                            @if($product->barcode_type)
                                <span class="badge badge-soft-secondary ms-2">{{ $product->barcode_type }}</span>
                            @endif
                        </div>
                    @endif
                    @if($product->gtin)
                        <div class="mb-3"><strong>{{ __('GTIN') }}:</strong> <code>{{ $product->gtin }}</code></div>
                    @endif

                    @if($product->barcodes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ __('Barcode Number') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Created') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($product->barcodes as $barcode)
                                        <tr>
                                            <td><code>{{ $barcode->barcode_number }}</code></td>
                                            <td>{{ $barcode->barcode_type }}</td>
                                            <td>
                                                @if($barcode->is_active)
                                                    <span class="badge badge-soft-success">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge badge-soft-secondary">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $barcode->created_at?->format('d M Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-barcode fa-2x mb-2"></i>
                            <p>{{ __('No additional barcodes registered.') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Stock History Tab --}}
                <div class="tab-pane fade" id="stock-history">
                    {{-- Summary Stats using Blade component --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-2">
                            <x-stat-card value="+{{ $stockMovementStats['total_in'] }}" label="{{ __('Total In') }}" color="success" icon="fas fa-arrow-down" size="sm" />
                        </div>
                        <div class="col-md-2">
                            <x-stat-card value="-{{ $stockMovementStats['total_out'] }}" label="{{ __('Total Out') }}" color="danger" icon="fas fa-arrow-up" size="sm" />
                        </div>
                        <div class="col-md-2">
                            <x-stat-card value="{{ $stockMovementStats['net_change'] >= 0 ? '+' : '' }}{{ $stockMovementStats['net_change'] }}" label="{{ __('Net Change') }}" color="{{ $stockMovementStats['net_change'] >= 0 ? 'primary' : 'warning' }}" icon="fas fa-exchange-alt" size="sm" />
                        </div>
                        <div class="col-md-2">
                            <x-stat-card value="{{ $stockMovementStats['total_adjustments'] }}" label="{{ __('Adjustments') }}" color="info" icon="fas fa-sliders-h" size="sm" />
                        </div>
                        <div class="col-md-2">
                            <x-stat-card value="{{ $stockMovementStats['total_movements'] }}" label="{{ __('Total Events') }}" color="secondary" icon="fas fa-list" size="sm" />
                        </div>
                    </div>

                    {{-- Filters --}}
                    <form method="GET" class="row g-2 mb-3" id="stockHistoryFilter">
                        <input type="hidden" name="tab" value="stock-history">
                        <div class="col-md-2">
                            <select name="movement_type" class="form-select form-select-sm">
                                <option value="">{{ __('All Types') }}</option>
                                <option value="in" {{ request('movement_type') === 'in' ? 'selected' : '' }}>{{ __('Stock In') }}</option>
                                <option value="out" {{ request('movement_type') === 'out' ? 'selected' : '' }}>{{ __('Stock Out') }}</option>
                                <option value="adjustment" {{ request('movement_type') === 'adjustment' ? 'selected' : '' }}>{{ __('Adjustment') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="movement_from" class="form-control form-control-sm" value="{{ request('movement_from') }}" placeholder="{{ __('From') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="movement_to" class="form-control form-control-sm" value="{{ request('movement_to') }}" placeholder="{{ __('To') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="movement_user_id" class="form-select form-select-sm">
                                <option value="">{{ __('All Users') }}</option>
                                @foreach($movementUsers as $u)
                                    <option value="{{ $u->id }}" {{ request('movement_user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-filter me-1"></i>{{ __('Filter') }}</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('admin.items.show', $product->id) }}?tab=stock-history" class="btn btn-sm btn-outline-secondary w-100"><i class="fas fa-times me-1"></i>{{ __('Clear') }}</a>
                        </div>
                    </form>

                    {{-- Movements Table using data-table component --}}
                    <x-data-table
                        :headers="[
                            ['label' => __('Date & Time'), 'style' => 'white-space:nowrap'],
                            __('Type'),
                            __('Qty'),
                            __('Before'),
                            __('After'),
                            __('Batch'),
                            __('User'),
                            __('Notes'),
                        ]"
                        empty-icon="fas fa-inbox"
                        empty-text="{{ __('No stock movements recorded for this item.') }}"
                    >
                        @forelse($stockMovements as $movement)
                            <tr>
                                <td style="white-space:nowrap">
                                    <div>{{ $movement->created_at->format('d M Y') }}</div>
                                    <small class="text-muted">{{ $movement->created_at->format('H:i:s') }}</small>
                                </td>
                                <td>
                                    @if($movement->movement_type === 'in')
                                        <span class="badge badge-soft-success"><i class="fas fa-arrow-down me-1"></i>{{ __('In') }}</span>
                                    @elseif($movement->movement_type === 'out')
                                        <span class="badge badge-soft-danger"><i class="fas fa-arrow-up me-1"></i>{{ __('Out') }}</span>
                                    @else
                                        <span class="badge badge-soft-info"><i class="fas fa-exchange-alt me-1"></i>{{ __('Adjust') }}</span>
                                    @endif
                                </td>
                                <td><strong class="{{ $movement->movement_type === 'out' ? 'text-danger' : ($movement->movement_type === 'in' ? 'text-success' : 'text-primary') }}">{{ $movement->movement_type === 'out' ? '-' : '+' }}{{ $movement->quantity }}</strong></td>
                                <td>{{ $movement->before_quantity }}</td>
                                <td><strong>{{ $movement->after_quantity }}</strong></td>
                                <td><code>{{ $movement->stock->batch_no ?? $movement->batch_no ?? '-' }}</code></td>
                                <td>
                                    <span class="d-flex align-items-center">
                                        <i class="fas fa-user-circle me-1 text-muted"></i>{{ $movement->user->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-muted" style="max-width:200px">{{ Str::limit($movement->notes, 50) }}</td>
                            </tr>
                        @empty
                            @slot('empty') @endslot
                        @endforelse
                    </x-data-table>

                    {{-- Pagination --}}
                    @if($stockMovements->hasPages())
                        <div class="d-flex justify-content-center mt-3">
                            {{ $stockMovements->withQueryString()->links() }}
                        </div>
                    @endif
                </div>

                {{-- Print History Tab --}}
                <div class="tab-pane fade" id="print-history">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0">{{ __('Barcode Label Print History') }}</h6>
                        <span class="badge badge-soft-secondary">{{ $printHistory->count() }} {{ __('prints') }}</span>
                    </div>
                    @if($printHistory->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('User') }}</th>
                                        <th>{{ __('Quantity') }}</th>
                                        <th>{{ __('Size') }}</th>
                                        <th>{{ __('Barcode') }}</th>
                                        <th>{{ __('Batch') }}</th>
                                        <th>{{ __('Options') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($printHistory as $print)
                                        <tr>
                                            <td>{{ $print->created_at->format('d M Y H:i') }}</td>
                                            <td>{{ $print->user->name ?? '-' }}</td>
                                            <td><strong>{{ $print->quantity }}</strong> {{ __('label(s)') }}</td>
                                            <td><span class="badge badge-soft-info">{{ ucfirst($print->size) }}</span></td>
                                            <td><code>{{ $print->barcode_number }}</code></td>
                                            <td>{{ $print->batch_no ?? '-' }}</td>
                                            <td>
                                                @if($print->options)
                                                    @if($print->options['show_price'] ?? false)<span class="badge badge-soft-success me-1">Price</span>@endif
                                                    @if($print->options['show_expiry'] ?? true)<span class="badge badge-soft-warning me-1">Expiry</span>@endif
                                                    @if($print->options['show_batch'] ?? true)<span class="badge badge-soft-info me-1">Batch</span>@endif
                                                    @if($print->options['show_code'] ?? true)<span class="badge badge-soft-secondary me-1">Code</span>@endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-print fa-3x mb-3"></i>
                            <h5>{{ __('No barcode labels printed yet') }}</h5>
                            <p>{{ __('Click "Print Barcode" to generate and download barcode labels.') }}</p>
                        </div>
                    @endif

                    {{-- Recent Sales & Purchases --}}
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">{{ __('Recent Sales') }}</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr><th>{{ __('Invoice') }}</th><th>{{ __('Date') }}</th><th>{{ __('Amount') }}</th></tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentSales as $detail)
                                            <tr>
                                                <td>{{ $detail->sale->invoiceNumber ?? '-' }}</td>
                                                <td>{{ $detail->sale?->saleDate?->format('d/m/Y') ?? '-' }}</td>
                                                <td>{{ number_format($detail->sale->totalAmount ?? 0, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center text-muted">{{ __('No sales recorded.') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">{{ __('Recent Purchases') }}</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr><th>{{ __('Invoice') }}</th><th>{{ __('Date') }}</th><th>{{ __('Amount') }}</th></tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentPurchases as $detail)
                                            <tr>
                                                <td>{{ $detail->purchase->invoiceNumber ?? '-' }}</td>
                                                <td>{{ $detail->purchase?->purchaseDate?->format('d/m/Y') ?? '-' }}</td>
                                                <td>{{ number_format($detail->purchase->totalAmount ?? 0, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center text-muted">{{ __('No purchases recorded.') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Timeline Tab --}}
                <div class="tab-pane fade" id="timeline">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted mb-0"><i class="fas fa-stream me-1"></i>{{ __('Activity Timeline') }}</h6>
                        <span class="badge badge-soft-secondary">{{ count($timelineEvents) }} {{ __('events') }}</span>
                    </div>

                    @if(count($timelineEvents) > 0)
                        {{-- CSS Timeline Styles --}}
                        <style>
                            .item-timeline { position: relative; padding-left: 28px; }
                            .item-timeline::before {
                                content: '';
                                position: absolute;
                                left: 9px;
                                top: 6px;
                                bottom: 6px;
                                width: 2px;
                                background: linear-gradient(to bottom, #dee2e6, #dee2e6 80%, transparent);
                                border-radius: 1px;
                            }
                            .timeline-entry { position: relative; padding-bottom: 20px; }
                            .timeline-entry:last-child { padding-bottom: 0; }
                            .timeline-entry:last-child::before { display: none; }
                            .timeline-dot {
                                position: absolute;
                                left: -28px;
                                top: 2px;
                                width: 22px;
                                height: 22px;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 9px;
                                color: white;
                                z-index: 2;
                                box-shadow: 0 0 0 3px white, 0 2px 4px rgba(0,0,0,0.1);
                            }
                            .timeline-entry:hover .timeline-card {
                                transform: translateX(2px);
                                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                            }
                            .timeline-card {
                                transition: all 0.2s ease;
                                border-left: 3px solid transparent;
                            }
                            .timeline-card.type-stock_in { border-left-color: #28a745; }
                            .timeline-card.type-stock_out { border-left-color: #dc3545; }
                            .timeline-card.type-adjustment { border-left-color: #17a2b8; }
                            .timeline-card.type-created { border-left-color: #007bff; }
                            .timeline-card.type-updated { border-left-color: #ffc107; }
                            .timeline-card.type-deleted { border-left-color: #6c757d; }
                            .timeline-card.type-price_change { border-left-color: #fd7e14; }
                            .timeline-card.type-barcode_print { border-left-color: #6f42c1; }
                        </style>

                        <div class="item-timeline">
                            @foreach($timelineEvents as $event)
                                <div class="timeline-entry">
                                    <div class="timeline-dot bg-{{ $event['color'] }}">
                                        <i class="fas {{ $event['icon'] }}"></i>
                                    </div>
                                    <div class="timeline-card card border-0 shadow-sm">
                                        <div class="card-body py-2 px-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <strong class="text-sm">{{ $event['title'] }}</strong>
                                                        @if(isset($event['badge']))
                                                            <span class="badge badge-soft-{{ $event['badge_color'] ?? 'secondary' }}" style="font-size:10px">{{ $event['badge'] }}</span>
                                                        @endif
                                                    </div>
                                                    <p class="text-muted mb-0 small" style="line-height:1.4">{{ $event['description'] }}</p>
                                                </div>
                                                <div class="text-end flex-shrink-0 ms-3" style="min-width:90px">
                                                    <small class="text-muted d-block" style="font-size:11px">{{ $event['date']->format('d M H:i') }}</small>
                                                    <small class="text-muted" style="font-size:11px">{{ $event['date']->diffForHumans() }}</small>
                                                    <div class="mt-1">
                                                        <small class="text-muted"><i class="fas fa-user-circle me-1"></i>{{ $event['user'] }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-stream fa-3x mb-3 opacity-25"></i>
                            <h6>{{ __('No activity recorded yet') }}</h6>
                            <small>{{ __('Stock movements, price changes, and other events will appear here.') }}</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Barcode Print Modal --}}
<div class="modal fade" id="barcodePrintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-barcode me-2"></i>{{ __('Print Barcode Labels') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex align-items-center mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <span>
                        {{ __('Barcode') }}: <strong><code>{{ $product->barcode ?? __('Not set') }}</code></strong>
                        @if($product->barcode_type)
                            <span class="badge badge-soft-secondary ms-1">{{ $product->barcode_type }}</span>
                        @endif
                    </span>
                </div>

                <form id="barcodePrintForm" onsubmit="return false;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Number of Labels') }} <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity" id="barcodeQty" value="4" min="1" max="200" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Label Size') }}</label>
                            <select class="form-select" name="size" id="barcodeSize">
                                <option value="small">{{ __('Small') }} (50×28mm)</option>
                                <option value="standard" selected>{{ __('Standard') }} (70×35mm)</option>
                                <option value="large">{{ __('Large') }} (90×45mm)</option>
                            </select>
                        </div>
                    </div>

                    @if($product->allStocks->count() > 1)
                        <div class="mb-3">
                            <label class="form-label">{{ __('Batch (optional)') }}</label>
                            <select class="form-select" name="batch_id">
                                <option value="">{{ __('First batch') }}</option>
                                @foreach($product->allStocks as $stock)
                                    <option value="{{ $stock->id }}">
                                        {{ $stock->batch_no ?? __('Batch') }} #{{ $stock->id }}
                                        — {{ $stock->productStock }} {{ __('units') }}
                                        @if($stock->expire_date)
                                            | {{ __('Exp') }}: {{ $stock->expire_date->format('d/m/Y') }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <h6 class="text-muted mb-2">{{ __('Label Options') }}</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="show_scientific" id="bpShowScientific" value="1" checked>
                                <label class="form-check-label" for="bpShowScientific">{{ __('Show scientific name') }}</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="show_price" id="bpShowPrice" value="1">
                                <label class="form-check-label" for="bpShowPrice">{{ __('Show selling price') }}</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="show_expiry" id="bpShowExpiry" value="1" checked>
                                <label class="form-check-label" for="bpShowExpiry">{{ __('Show expiry date') }}</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="show_batch" id="bpShowBatch" value="1" checked>
                                <label class="form-check-label" for="bpShowBatch">{{ __('Show batch number') }}</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="show_code" id="bpShowCode" value="1" checked>
                                <label class="form-check-label" for="bpShowCode">{{ __('Show SKU / code') }}</label>
                            </div>
                        </div>
                    </div>

                    {{-- Live preview --}}
                    <div class="mt-3 p-3 bg-light rounded" id="barcodePreview" style="border:1px dashed #ccc">
                        <div class="text-center">
                            <div class="fw-bold" style="font-size:11px">{{ $product->productName }}</div>
                            @if($product->scientific_name)
                                <div style="font-size:8px;color:#666;font-style:italic">{{ $product->scientific_name }}</div>
                            @endif
                            <div style="font-family:monospace;font-weight:700;letter-spacing:3px;margin:5px 0">
                                ||| {{ $product->barcode }} |||
                            </div>
                            <div style="font-size:7px;color:#888">{{ $product->barcode_type ?? 'CODE128' }}</div>
                            <div style="font-size:10px;color:#666;margin-top:4px">
                                {{ __('×') }} <span id="previewQty">4</span> {{ __('labels') }}
                                · {{ __('Size') }}: <span id="previewSize">{{ __('Standard') }}</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="downloadBarcodePdf()" id="barcodeDownloadBtn">
                    <i class="fas fa-download me-1"></i>{{ __('Download PDF') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Stock Adjustment Modal --}}
<div class="modal fade" id="stockAdjustModal" tabindex="-1" aria-labelledby="stockAdjustModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockAdjustModalLabel">
                    <i class="fas fa-plus-minus me-2"></i>{{ __('Adjust Stock') }} — {{ $product->productName }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex align-items-center mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <span>{{ __('Current total stock:') }} <strong>{{ $kpis['current_stock'] }}</strong> {{ __('units') }}</span>
                </div>

                <form id="stockAdjustForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Adjustment Type') }} <span class="text-danger">*</span></label>
                            <select class="form-select" id="adjustType" required onchange="onAdjustTypeChange()">
                                <option value="in">{{ __('Stock In (Add)') }}</option>
                                <option value="out">{{ __('Stock Out (Remove)') }}</option>
                                <option value="adjustment">{{ __('Set Exact Quantity') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" id="adjustQtyLabel">{{ __('Quantity to Add') }} <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="adjustQty" min="1" value="1" required>
                            <small class="text-muted" id="adjustQtyHelp">{{ __('Enter the quantity to add to stock.') }}</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Batch Number') }}</label>
                            <input type="text" class="form-control" id="adjustBatchNo" placeholder="{{ __('Optional batch/lot number') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Expiration Date') }}</label>
                            <input type="date" class="form-control" id="adjustExpireDate">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Purchase Price') }}</label>
                            <input type="number" step="0.01" class="form-control" id="adjustPurchasePrice" value="{{ $product->purchase_with_tax ?? $product->purchase_without_tax ?? 0 }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Reference / Notes') }}</label>
                            <input type="text" class="form-control" id="adjustNotes" placeholder="{{ __('e.g. New delivery, Return from customer...') }}">
                        </div>
                    </div>

                    <hr>

                    {{-- Batch selector for stock out from specific batch --}}
                    <div id="batchSelectorSection" class="d-none">
                        <label class="form-label"><strong>{{ __('Select Batch to Remove From') }}</strong></label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width:30px"></th>
                                        <th>{{ __('Batch') }}</th>
                                        <th>{{ __('Available') }}</th>
                                        <th>{{ __('Expiry') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="batchList">
                                    @foreach($product->allStocks->where('productStock', '>', 0) as $stock)
                                        <tr>
                                            <td><input type="radio" name="selectedBatchId" value="{{ $stock->id }}" {{ $loop->first ? 'checked' : '' }} data-stock="{{ $stock->productStock }}"></td>
                                            <td><code>{{ $stock->batch_no ?? '-' }}</code></td>
                                            <td><strong>{{ $stock->productStock }}</strong></td>
                                            <td>{{ $stock->expire_date ? $stock->expire_date->format('d/m/Y') : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Live preview --}}
                    <div class="card bg-light" id="adjustPreview" class="d-none">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between">
                                <span>{{ __('Current Stock:') }}</span>
                                <strong>{{ $kpis['current_stock'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>{{ __('Adjustment:') }}</span>
                                <strong id="previewAdjustment" class="text-success">+1</strong>
                            </div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between">
                                <span><strong>{{ __('New Stock:') }}</strong></span>
                                <strong id="previewNewStock" class="text-primary fs-5">{{ $kpis['current_stock'] + 1 }}</strong>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="submitStockAdjust()" id="adjustSubmitBtn">
                    <i class="fas fa-save me-1"></i>{{ __('Apply Adjustment') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const baseUrl = '{{ url("admin") }}';
    const productId = {{ $product->id }};

    const currentStock = {{ $kpis['current_stock'] }};

    // ── Barcode Print ──
    function openBarcodePrintModal() {
        if (!'{{ $product->barcode }}') {
            toastr.warning('{{ __('No barcode set for this item. Edit the item to add one.') }}');
            return;
        }
        new bootstrap.Modal(document.getElementById('barcodePrintModal')).show();
    }

    // Update preview on input changes
    document.getElementById('barcodeQty')?.addEventListener('input', function() {
        document.getElementById('previewQty').textContent = this.value || '1';
    });
    document.getElementById('barcodeSize')?.addEventListener('change', function() {
        const labels = { small: '{{ __('Small') }}', standard: '{{ __('Standard') }}', large: '{{ __('Large') }}' };
        document.getElementById('previewSize').textContent = labels[this.value] || this.value;
    });

    function downloadBarcodePdf() {
        const btn = document.getElementById('barcodeDownloadBtn');
        const form = document.getElementById('barcodePrintForm');
        const formData = new FormData(form);

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>{{ __('Generating...') }}';

        // Build URL with query parameters
        const params = new URLSearchParams(formData).toString();
        const url = `${baseUrl}/items/${productId}/print-barcode?${params}`;

        // Create a hidden form to POST and trigger file download
        const hiddenForm = document.createElement('form');
        hiddenForm.method = 'POST';
        hiddenForm.action = url;
        hiddenForm.style.display = 'none';

        // Add CSRF token
        const csrfField = document.createElement('input');
        csrfField.type = 'hidden';
        csrfField.name = '_token';
        csrfField.value = csrfToken;
        hiddenForm.appendChild(csrfField);

        // Add all form fields
        for (const [key, value] of formData.entries()) {
            if (key === '_token') continue;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            hiddenForm.appendChild(input);
        }

        document.body.appendChild(hiddenForm);
        hiddenForm.submit();

        // Reset button after a delay
        setTimeout(() => {
            hiddenForm.remove();
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download me-1"></i>{{ __('Download PDF') }}';
            bootstrap.Modal.getInstance(document.getElementById('barcodePrintModal'))?.hide();
        }, 2000);
    }

    function deleteItem() {
        if (!confirm('{{ __("Are you sure you want to delete :name?", ["name" => $product->productName]) }}')) return;

        fetch(`${baseUrl}/items/${productId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                setTimeout(() => window.location.href = '{{ route("admin.items.index") }}', 1000);
            } else {
                toastr.error(data.message);
            }
        });
    }

    // ── Stock Adjustment Modal ──
    function openStockAdjustModal() {
        new bootstrap.Modal(document.getElementById('stockAdjustModal')).show();
        updatePreview();
    }

    function onAdjustTypeChange() {
        const type = document.getElementById('adjustType').value;
        const qtyLabel = document.getElementById('adjustQtyLabel');
        const qtyHelp = document.getElementById('adjustQtyHelp');
        const batchSection = document.getElementById('batchSelectorSection');
        const qtyInput = document.getElementById('adjustQty');

        batchSection.classList.add('d-none');

        switch(type) {
            case 'in':
                qtyLabel.innerHTML = '{{ __('Quantity to Add') }} <span class="text-danger">*</span>';
                qtyHelp.textContent = '{{ __('Enter the quantity to add to stock.') }}';
                qtyInput.min = 1;
                break;
            case 'out':
                qtyLabel.innerHTML = '{{ __('Quantity to Remove') }} <span class="text-danger">*</span>';
                qtyHelp.textContent = '{{ __('Enter the quantity to remove from stock.') }}';
                qtyInput.min = 1;
                qtyInput.max = currentStock;
                batchSection.classList.remove('d-none');
                break;
            case 'adjustment':
                qtyLabel.innerHTML = '{{ __('New Total Quantity') }} <span class="text-danger">*</span>';
                qtyHelp.textContent = '{{ __('Set the exact total quantity for this item.') }}';
                qtyInput.min = 0;
                qtyInput.max = '';
                break;
        }
        updatePreview();
    }

    function updatePreview() {
        const type = document.getElementById('adjustType').value;
        const qty = parseInt(document.getElementById('adjustQty').value) || 0;
        const preview = document.getElementById('adjustPreview');
        const adjText = document.getElementById('previewAdjustment');
        const newStockText = document.getElementById('previewNewStock');

        if (qty <= 0) {
            preview.style.display = 'none';
            return;
        }

        preview.style.display = 'block';
        let newStock, adjDisplay;

        switch(type) {
            case 'in':
                newStock = currentStock + qty;
                adjDisplay = '+' + qty;
                adjText.className = 'strong text-success';
                break;
            case 'out':
                newStock = Math.max(0, currentStock - qty);
                adjDisplay = '-' + qty;
                adjText.className = 'strong text-danger';
                break;
            case 'adjustment':
                newStock = qty;
                const diff = qty - currentStock;
                adjDisplay = (diff >= 0 ? '+' : '') + diff;
                adjText.className = diff >= 0 ? 'strong text-success' : 'strong text-danger';
                break;
        }

        adjText.textContent = adjDisplay;
        newStockText.textContent = newStock;
    }

    document.getElementById('adjustQty')?.addEventListener('input', updatePreview);
    document.querySelectorAll('input[name="selectedBatchId"]').forEach(r => r.addEventListener('change', updatePreview));

    function submitStockAdjust() {
        const type = document.getElementById('adjustType').value;
        const qty = parseInt(document.getElementById('adjustQty').value);
        const batchNo = document.getElementById('adjustBatchNo').value;
        const expireDate = document.getElementById('adjustExpireDate').value;
        const purchasePrice = document.getElementById('adjustPurchasePrice').value;
        const notes = document.getElementById('adjustNotes').value;
        const btn = document.getElementById('adjustSubmitBtn');

        // Validate
        if (!qty || qty < 0) {
            toastr.error('{{ __('Please enter a valid quantity.') }}');
            return;
        }

        if (type === 'out') {
            const selectedBatch = document.querySelector('input[name="selectedBatchId"]:checked');
            if (!selectedBatch) {
                toastr.error('{{ __('Please select a batch to remove from.') }}');
                return;
            }
        }

        const payload = {
            adjustment_type: type,
            quantity: qty,
            batch_no: batchNo || null,
            expire_date: expireDate || null,
            purchase_price: purchasePrice ? parseFloat(purchasePrice) : null,
            notes: notes || null,
        };

        // For stock out, include the selected stock_id
        if (type === 'out') {
            const selectedBatch = document.querySelector('input[name="selectedBatchId"]:checked');
            if (selectedBatch) {
                payload.stock_id = parseInt(selectedBatch.value);
            }
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>{{ __('Processing...') }}';

        fetch(`${baseUrl}/items/${productId}/stock-adjust`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                bootstrap.Modal.getInstance(document.getElementById('stockAdjustModal'))?.hide();
                // Reload page to reflect updated stock
                setTimeout(() => window.location.reload(), 500);
            } else {
                toastr.error(data.message);
            }
        })
        .catch(err => {
            toastr.error('{{ __('An error occurred while adjusting stock.') }}');
            console.error(err);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i>{{ __('Apply Adjustment') }}';
        });
    }

    // ── Price History Chart ──
    <?php
    $priceChartData = collect($priceHistory)->sortBy('created_at')->values()->map(function($p) {
        return [
            'date' => $p->created_at->format('d M Y'),
            'purchase' => (float) $p->purchase_with_tax,
            'selling' => (float) $p->sales_price,
            'wholesale' => (float) $p->wholesale_price,
        ];
    });
    ?>
    const priceHistoryData = <?php echo json_encode($priceChartData); ?>;

    if (priceHistoryData.length > 0) {
        const ctx = document.getElementById('priceHistoryChart');
        if (ctx) {
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: priceHistoryData.map(d => d.date),
                    datasets: [
                        {
                            label: '{{ __('Selling Price') }}',
                            data: priceHistoryData.map(d => d.selling),
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 4,
                        },
                        {
                            label: '{{ __('Purchase Price') }}',
                            data: priceHistoryData.map(d => d.purchase),
                            borderColor: '#1cc88a',
                            backgroundColor: 'rgba(28, 200, 138, 0.1)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 4,
                        },
                        {
                            label: '{{ __('Wholesale Price') }}',
                            data: priceHistoryData.map(d => d.wholesale),
                            borderColor: '#f6c23e',
                            backgroundColor: 'rgba(246, 194, 62, 0.1)',
                            fill: false,
                            tension: 0.3,
                            borderWidth: 1,
                            borderDash: [5, 5],
                            pointRadius: 3,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2)
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: false, ticks: { callback: v => v.toFixed(2) } }
                    }
                }
            });
        }
    }

    function submitStockAdjust() {
        const type = document.getElementById('adjustType').value;
        const qty = parseInt(document.getElementById('adjustQty').value) || 0;
        const batchNo = document.getElementById('adjustBatchNo').value;
        const expireDate = document.getElementById('adjustExpireDate').value;
        const purchasePrice = document.getElementById('adjustPurchasePrice').value;
        const notes = document.getElementById('adjustNotes').value;
        const btn = document.getElementById('adjustSubmitBtn');

        if (qty <= 0) {
            toastr.error('{{ __('Please enter a valid quantity.') }}');
            return;
        }

        // Calculate the qty for the API
        let qtyToSend;
        switch(type) {
            case 'in': qtyToSend = qty; break;
            case 'out': qtyToSend = -qty; break;
            case 'adjustment': qtyToSend = qty; break;
        }

        // Get batch info from selected radio
        let selectedStockId = null;
        if (type === 'out') {
            const checked = document.querySelector('input[name="selectedBatchId"]:checked');
            if (checked) selectedStockId = checked.value;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>{{ __('Processing...') }}';

        fetch(`${baseUrl}/items/${productId}/update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'X-HTTP-Method-Override': 'PUT'
            },
            body: JSON.stringify({
                qty: qtyToSend,
                batch_no: batchNo || null,
                expire_date: expireDate || null,
                purchase_with_tax: purchasePrice || null,
                notes: notes || `Stock ${type} adjustment`
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                bootstrap.Modal.getInstance(document.getElementById('stockAdjustModal'))?.hide();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                toastr.error(data.message || '{{ __("Error adjusting stock") }}');
            }
        })
        .catch(() => {
            toastr.error('{{ __("Network error") }}');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i>{{ __('Apply Adjustment') }}';
        });
    }

    // Auto-select tab from URL parameter
    (function() {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');
        if (tab) {
            const tabBtn = document.querySelector(`[data-bs-target="#${tab}"]`);
            if (tabBtn) {
                const bsTab = new bootstrap.Tab(tabBtn);
                bsTab.show();
            }
        }
    })();

    // ── Real-time Stock Updates via Laravel Echo ──
    if (typeof Echo !== 'undefined') {
        Echo.private(`item.${productId}.stock`)
            .listen('.stock.updated', (e) => {
                // Update the stock KPI cards in real-time
                const stockEl = document.querySelector('[data-stock-total]');
                if (stockEl) {
                    stockEl.textContent = e.new_total_stock;
                    stockEl.classList.add('text-success');
                    setTimeout(() => stockEl.classList.remove('text-success'), 1500);
                }

                // Show notification
                const type = e.movement.type;
                const qty = e.movement.quantity;
                const msg = type === 'in'
                    ? `{{ __('Stock added') }}: +${qty} ({{ __('Total') }}: ${e.new_total_stock})`
                    : type === 'out'
                    ? `{{ __('Stock removed') }}: -${qty} ({{ __('Total') }}: ${e.new_total_stock})`
                    : `{{ __('Stock adjusted') }}: ${e.new_total_stock}`;

                toastr.info(msg, `{{ __('Stock Update') }} by ${e.movement.user}`);

                // Update the stock history tab count if visible
                const badgeEl = document.querySelector('[data-bs-target="#stock-history"] .badge');
                if (badgeEl) {
                    const current = parseInt(badgeEl.textContent) || 0;
                    badgeEl.textContent = current + 1;
                }
            });
    }
</script>
@endpush
@endsection
