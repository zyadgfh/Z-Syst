@extends('layouts.master')

@section('title')
    {{ __('Generate Barcodes') }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">{ __('Generate Barcodes') }</h4>

            <form method="POST" action="{{ route('admin.barcodes.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{ __('Product') } *</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">--</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->product_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">{ __('Batch (optional)') }</label>
                        <select name="batch_id" class="form-select">
                            <option value="">{ __('No batch — product-level label') }</option>
                            @foreach(\App\Models\Stock::where('business_id', auth()->user()->business_id)->with('product')->orderBy('expire_date')->limit(200)->get() as $stock)
                                <option value="{{ $stock->id }}">{{ '#'.$stock->id.' '.($stock->product?->name ?? '').' — '.$stock->batch_no }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">{ __('Symbology') } *</label>
                        <select name="type" class="form-select" required>
                            <option value="CODE128" selected>CODE128 — { __('internal codes, alphanumeric') }</option>
                            <option value="EAN13">EAN-13 — { __('retail, 13 digits (checksum minted)') }</option>
                            <option value="UPC">UPC-A — { __('retail, 12 digits (checksum minted)') }</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">{ __('Label size') }</label>
                        <select name="size" class="form-select">
                            <option value="small">{ __('Small') }</option>
                            <option value="standard" selected>{ __('Standard') }</option>
                            <option value="large">{ __('Large') }</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">{ __('Quantity') } *</label>
                        <input type="number" name="quantity" value="1" min="1" max="100" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{ __('Generate & Open Labels') }</button>
                <a href="{{ route('admin.barcodes.index') }}" class="btn btn-light">{ __('Cancel') }</a>
            </form>
        </div>
    </div>
</div>
@endsection
