@extends('layouts.master')

@section('title')
    {{ __('Initiate Recall') }}
@endsection

@section('main_content')
    <div class="erp-table-section">
        <div class="container-fluid">
            <div class="card">
                <div class="card-bodys">
                    <div class="table-header p-16">
                        <h4>{{ __('Initiate Recall') }}</h4>
                        <a href="{{ route('admin.traceability.recalls') }}" class="add-order-btn rounded-2 active">
                            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Recalls') }}
                        </a>
                    </div>

                    <div class="p-4">
                        <form action="{{ route('admin.traceability.recalls.store') }}" method="POST" class="recall-form">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="product_id" class="form-label">{{ __('Product') }} <span class="text-danger">*</span></label>
                                    <select class="form-select" id="product_id" name="product_id" required>
                                        <option value="">{{ __('Select Product') }}</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" {{ (request('batch_id') ? true : old('product_id') == $product->id) ? 'selected' : '' }}>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="batch_lot_number" class="form-label">{{ __('Batch/Lot #') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="batch_lot_number" name="batch_lot_number" value="{{ old('batch_lot_number') }}" required>
                                </div>
                                <div class="col-12">
                                    <label for="reason" class="form-label">{{ __('Recall Reason') }} <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="severity" class="form-label">{{ __('Severity') }} <span class="text-danger">*</span></label>
                                    <select class="form-select" id="severity" name="severity" required>
                                        <option value="low">{{ __('Low') }}</option>
                                        <option value="medium" selected>{{ __('Medium') }}</option>
                                        <option value="high">{{ __('High') }}</option>
                                        <option value="critical">{{ __('Critical') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="reported_by" class="form-label">{{ __('Reported By') }}</label>
                                    <input type="text" class="form-control" id="reported_by" name="reported_by" value="{{ auth()->user()->name }}">
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">{{ __('Initiate Recall') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
