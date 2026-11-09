@extends('layouts.master')

@section('title')
    {{ __('New Subscription') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">{ __('New Subscription') }</h4>
            <form action="{{ route('admin.subscriptions.store') }}" method="POST" class="row">
    @csrf
    
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Business') }</label>
                    <select name="business_id" class="form-select" required>
                        <option value="">--</option>
                        @foreach($businesses as $item)
                            <option value="{{ $item->id }}" >{{ $item->company_name ?? $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Plan') }</label>
                    <select name="plan_id" class="form-select" required>
                        <option value="">--</option>
                        @foreach($plans as $item)
                            <option value="{{ $item->id }}" >{{ $item->name ?? $item->id }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Payment method') }</label>
                    <input type="text" name="payment_method" value="{{ old('payment_method') }}" class="form-control">
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
