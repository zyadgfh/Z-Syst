@extends('layouts.master')

@section('title')
    {{ __('Edit Insurance Company') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">{ __('Edit Insurance Company') }</h4>
            <form action="{{ route('admin.insurance.companies.update', $company) }}" method="POST" class="row">
    @csrf
    @method('PUT')
    
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Name') }</label>
                    <input type="text" name="name" value="{{ old('name', $company ? $company->name : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Code') }</label>
                    <input type="text" name="code" value="{{ old('code', $company ? $company->code : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Contact person') }</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person', $company ? $company->contact_person : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Phone') }</label>
                    <input type="text" name="phone" value="{{ old('phone', $company ? $company->phone : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Email') }</label>
                    <input type="email" name="email" value="{{ old('email', $company ? $company->email : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('City') }</label>
                    <input type="text" name="city" value="{{ old('city', $company ? $company->city : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Country') }</label>
                    <input type="text" name="country" value="{{ old('country', $company ? $company->country : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Status') }</label>
                    <input type="text" name="status" value="{{ old('status', $company ? $company->status : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Coverage %') }</label>
                    <input type="number" name="default_coverage_percent" value="{{ old('default_coverage_percent', $company ? $company->default_coverage_percent : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Copay %') }</label>
                    <input type="number" name="default_copay_percent" value="{{ old('default_copay_percent', $company ? $company->default_copay_percent : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{ __('Settlement days') }</label>
                    <input type="number" name="settlement_days" value="{{ old('settlement_days', $company ? $company->settlement_days : '') }}" class="form-control">
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
