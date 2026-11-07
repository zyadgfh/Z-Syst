@extends('layouts.master')

@section('title')
    {{ __('Edit Supplier Payment') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">{ __('Edit Supplier Payment') }</h4>
            <form action="{{ route('admin.supplier-payments.update', $payment) }}" method="POST" class="row">
    @csrf
    @method('PUT')
    
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Supplier') }</label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">--</option>
                        @foreach($suppliers as $item)
                            <option value="{{ $item->id }}" @selected($payment && $payment->supplier_id == $item->id)>{{ $item->company_name ?? $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Payment date') }</label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', $payment ? $payment->payment_date : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Payment method') }</label>
                    <input type="text" name="payment_method" value="{{ old('payment_method', $payment ? $payment->payment_method : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Reference') }</label>
                    <input type="text" name="reference" value="{{ old('reference', $payment ? $payment->reference : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Bank reference') }</label>
                    <input type="text" name="bank_reference" value="{{ old('bank_reference', $payment ? $payment->bank_reference : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Amount') }</label>
                    <input type="number" name="amount" value="{{ old('amount', $payment ? $payment->amount : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Status') }</label>
                    <input type="text" name="status" value="{{ old('status', $payment ? $payment->status : '') }}" class="form-control">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">{ __('Notes') }</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $payment ? $payment->notes : '') }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">{ __('Save changes') }</button>
                    <a href="javascript:history.back()" class="btn btn-light">{ __('Cancel') }</a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
