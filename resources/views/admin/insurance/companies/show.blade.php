@extends('layouts.master')

@section('title')
    {{ __('Insurance Company') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ $company->name }} <small class="text-muted">({{ $company->code }})</small></h4>
            <span class="badge bg-secondary">{{ $company->status }}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><strong>{{ __('Contact') }}:</strong> {{ $company->contact_person }}</div>
            <div class="col-md-4"><strong>{{ __('Phone') }}:</strong> {{ $company->phone }}</div>
            <div class="col-md-4"><strong>{{ __('Email') }}:</strong> {{ $company->email }}</div>
            <div class="col-md-4"><strong>{{ __('City') }}:</strong> {{ $company->city }}</div>
            <div class="col-md-4"><strong>{{ __('Coverage') }}:</strong> {{ $company->default_coverage_percent }}%</div>
            <div class="col-md-4"><strong>{{ __('Copay') }}:</strong> {{ $company->default_copay_percent }}%</div>
            <div class="col-md-4"><strong>{{ __('Settlement days') }}:</strong> {{ $company->settlement_days }}</div>
            <div class="col-12"><strong>{{ __('Address') }}:</strong> {{ $company->address }}</div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <a href="{{ route('admin.insurance.companies.edit', $company) }}" class="btn btn-primary">{{ __('Edit') }}</a>
            <a href="{{ route('admin.insurance.companies.index') }}" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>

@endsection
