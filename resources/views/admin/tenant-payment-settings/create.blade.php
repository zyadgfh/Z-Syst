@extends('layouts.master')

@section('title')
    {{ __('New Tenant Payment Setting') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">{ __('New Tenant Payment Setting') }</h4>
            <form action="{{ route('admin.tenant-payment-settings.store') }}" method="POST" class="row">
    @csrf
    
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Tenant (business)') }</label>
                    <select name="tenant_id" class="form-select" required>
                        <option value="">--</option>
                        @foreach($tenants as $item)
                            <option value="{{ $item->id }}" >{{ $item->companyName ?? $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Gateway') }</label>
                    <select name="gateway_id" class="form-select" required>
                        <option value="">--</option>
                        @foreach($gateways as $item)
                            <option value="{{ $item->id }}" >{{ $item->title ?? $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Merchant phone') }</label>
                    <input type="text" name="merchant_phone" value="{{ old('merchant_phone') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Merchant name') }</label>
                    <input type="text" name="merchant_name" value="{{ old('merchant_name') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Merchant code') }</label>
                    <input type="text" name="merchant_code" value="{{ old('merchant_code') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Merchant key') }</label>
                    <input type="text" name="merchant_key" value="{{ old('merchant_key') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('InstaPay ID') }</label>
                    <input type="text" name="merchant_instapay_id" value="{{ old('merchant_instapay_id') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Bank name') }</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Account number') }</label>
                    <input type="text" name="account_number" value="{{ old('account_number') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Branch name') }</label>
                    <input type="text" name="branch_name" value="{{ old('branch_name') }}" class="form-control">
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
