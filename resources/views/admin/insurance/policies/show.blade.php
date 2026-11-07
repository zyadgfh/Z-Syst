@extends('layouts.master')

@section('title')
    {{ __('Insurance Policy') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('Policy') }}: {{ $policy->policy_number }}</h4>
            <span class="badge bg-secondary">{{ $policy->status }}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><strong>{{ __('Holder') }}:</strong> {{ $policy->holder_name }}</div>
            <div class="col-md-4"><strong>{{ __('Company') }}:</strong> {{ $policy->company?->name ?? $policy->insurance_company_id }}</div>
            <div class="col-md-4"><strong>{{ __('Plan') }}:</strong> {{ $policy->plan_type }}</div>
            <div class="col-md-4"><strong>{{ __('Annual limit') }}:</strong> {{ $policy->annual_limit }}</div>
            <div class="col-md-4"><strong>{{ __('Used') }}:</strong> {{ $policy->used_amount }}</div>
            <div class="col-md-4"><strong>{{ __('Remaining') }}:</strong> {{ $policy->remaining_limit }}</div>
            <div class="col-md-4"><strong>{{ __('Coverage') }}:</strong> {{ $policy->coverage_percent }}%</div>
            <div class="col-md-4"><strong>{{ __('Valid') }}:</strong> {{ $policy->start_date }} → {{ $policy->end_date }}</div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <a href="{{ route('admin.insurance.policies.edit', $policy) }}" class="btn btn-primary">{{ __('Edit') }}</a>
            <a href="{{ route('admin.insurance.policies.index') }}" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>

@endsection
