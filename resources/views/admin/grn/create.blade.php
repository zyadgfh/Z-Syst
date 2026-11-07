@extends('layouts.master')

@section('title')
    {{ __('New Goods Received Note') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">{ __('New Goods Received Note') }</h4>
            <form action="{{ route('admin.grn.store') }}" method="POST" class="row">
    @csrf
    
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Supplier') }</label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">--</option>
                        @foreach($suppliers as $item)
                            <option value="{{ $item->id }}" >{{ $item->company_name ?? $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Purchase Order') }</label>
                    <select name="purchase_order_id" class="form-select" required>
                        <option value="">--</option>
                        @foreach($purchaseOrders as $item)
                            <option value="{{ $item->id }}" >{{ "PO #".$item->id }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Received date') }</label>
                    <input type="date" name="received_date" value="{{ old('received_date') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Location') }</label>
                    <input type="text" name="location" value="{{ old('location') }}" class="form-control">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">{ __('Notes') }</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">{ __('Create') }</button>
                    <a href="javascript:history.back()" class="btn btn-light">{ __('Cancel') }</a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
