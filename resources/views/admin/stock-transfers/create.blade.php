@extends('layouts.admin')

@section('title', __('warehouse.New Stock Transfer'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('warehouse.New Stock Transfer') }}</h1>
        <a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="transferForm" method="POST" action="{{ route('admin.stock-transfers.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('warehouse.From Warehouse') }} *</label>
                        <select name="from_warehouse_id" class="form-select" required id="fromWarehouse">
                            <option value="">{{ __('common.Select Warehouse') }}</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" data-warehouse-id="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('warehouse.To Warehouse') }} *</label>
                        <select name="to_warehouse_id" class="form-select" required id="toWarehouse">
                            <option value="">{{ __('common.Select Warehouse') }}</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('products.Product') }} *</label>
                        <input type="text" name="product_id" id="productSearch" class="form-control" placeholder="{{ __('common.Search product') }}..." required>
                        <input type="hidden" name="product_id" id="productId">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('common.Quantity') }} *</label>
                        <input type="number" name="quantity" class="form-control" min="1" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">{{ __('common.Notes') }}</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary-blue">
                        <i class="fas fa-save me-1"></i> {{ __('common.Create Transfer') }}
                    </button>
                    <a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-secondary">{{ __('common.Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
