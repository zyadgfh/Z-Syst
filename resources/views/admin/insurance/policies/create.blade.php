@extends('layouts.master')

@section('title')
    {{ __('Add Insurance Policy') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">{ __('Add Insurance Policy') }</h4>
            <form action="{{ route('admin.insurance.policies.store') }}" method="POST" class="row">
    @csrf
    
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Company') }</label>
                    <select name="insurance_company_id" class="form-select" required>
                        <option value="">--</option>
                        @foreach($companies as $item)
                            <option value="{{ $item->id }}" >{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Policy number') }</label>
                    <input type="text" name="policy_number" value="{{ old('policy_number') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Member ID') }</label>
                    <input type="text" name="member_id" value="{{ old('member_id') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Card number') }</label>
                    <input type="text" name="card_number" value="{{ old('card_number') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Holder name') }</label>
                    <input type="text" name="holder_name" value="{{ old('holder_name') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Holder DOB') }</label>
                    <input type="date" name="holder_dob" value="{{ old('holder_dob') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Holder phone') }</label>
                    <input type="text" name="holder_phone" value="{{ old('holder_phone') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Plan type') }</label>
                    <input type="text" name="plan_type" value="{{ old('plan_type') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Status') }</label>
                    <input type="text" name="status" value="{{ old('status') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Start date') }</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('End date') }</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Annual limit') }</label>
                    <input type="number" name="annual_limit" value="{{ old('annual_limit') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Coverage %') }</label>
                    <input type="number" name="coverage_percent" value="{{ old('coverage_percent') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Copay %') }</label>
                    <input type="number" name="copay_percent" value="{{ old('copay_percent') }}" class="form-control">
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
