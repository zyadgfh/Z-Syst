@extends('layouts.admin')

@section('title')
    {{ __('warehouses.Edit Warehouse') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('warehouses.Edit Warehouse') }}</h4>
                        <a href="{{ route('admin.warehouses.index') }}" class="add-order-btn rounded-2 active">
                            <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
                        </a>
                    </div>

                    <div class="p-4">
                        <form action="{{ route('admin.warehouses.update', $warehouse) }}" method="POST" class="warehouse-form">
                            @csrf
                            @method('PUT')
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">{{ __('common.Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ $warehouse->name }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="code" class="form-label">{{ __('common.Code') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="code" name="code" value="{{ $warehouse->code }}" required>
                                </div>
                                <div class="col-12">
                                    <label for="location" class="form-label">{{ __('warehouses.Location / Address') }}</label>
                                    <textarea class="form-control" id="location" name="location" rows="2">{{ $warehouse->location }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="type" class="form-label">{{ __('warehouses.Warehouse Type') }}</label>
                                    <select class="form-select" id="type" name="type">
                                        <option value="standard" {{ $warehouse->type === 'standard' ? 'selected' : '' }}>{{ __('products.Standard') }}</option>
                                        <option value="cold_storage" {{ $warehouse->type === 'cold_storage' ? 'selected' : '' }}>{{ __('warehouses.Cold Storage') }}</option>
                                        <option value="distribution" {{ $warehouse->type === 'distribution' ? 'selected' : '' }}>{{ __('warehouses.Distribution Center') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="is_active" class="form-label">{{ __('common.Status') }}</label>
                                    <select class="form-select" id="is_active" name="is_active">
                                        <option value="1" {{ $warehouse->is_active ? 'selected' : '' }}>{{ __('common.Active') }}</option>
                                        <option value="0" {{ !$warehouse->is_active ? 'selected' : '' }}>{{ __('common.Inactive') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" {{ $warehouse->is_default ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_default">{{ __('warehouses.Set as Default') }}</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">{{ __('warehouses.Update Warehouse') }}</button>
                            </div>
                        </form>

                        <div class="mt-4 pt-4 border-top">
                            <h5>{{ __('products.Current Stock') }}</h5>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('common.Product') }}</th>
                                            <th>{{ __('common.Quantity') }}</th>
                                            <th>{{ __('products.Unit') }}</th>
                                            <th>{{ __('common.Expiry Date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($warehouse->stocks as $stock)
                                            <tr>
                                                <td>{{ $stock->product->name ?? __('common.Unknown') }}</td>
                                                <td>{{ $stock->qty }}</td>
                                                <td>{{ $stock->unit->name ?? '' }}</td>
                                                <td>{{ $stock->expire_date ? \Carbon\Carbon::parse($stock->expire_date)->format('Y-m-d') : '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-3">{{ __('warehouses.No stock assigned to this warehouse') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
