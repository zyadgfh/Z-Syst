@extends('layouts.master')

@section('title')
    {{ __('Subscription') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('Subscription') }} #{{ $subscription->id }}</h4>
            <span class="badge bg-secondary">{{ $subscription->status }}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><strong>{{ __('Business') }}:</strong> {{ $subscription->business?->company_name ?? $subscription->business_id }}</div>
            <div class="col-md-4"><strong>{{ __('Plan') }}:</strong> {{ $subscription->plan?->name ?? $subscription->plan_id }}</div>
            <div class="col-md-4"><strong>{{ __('Payment method') }}:</strong> {{ $subscription->payment_method }}</div>
            <div class="col-md-4"><strong>{{ __('Starts') }}:</strong> {{ $subscription->starts_at?->format('Y-m-d') }}</div>
            <div class="col-md-4"><strong>{{ __('Ends') }}:</strong> {{ $subscription->ends_at?->format('Y-m-d') }}</div>
            <div class="col-md-4"><strong>{{ __('Trial ends') }}:</strong> {{ $subscription->trial_ends_at?->format('Y-m-d') }}</div>
        </div>
        <div class="d-flex gap-2 mt-4 flex-wrap">
            <form action="{{ route('admin.subscriptions.renew', $subscription) }}" method="POST">@csrf<button class="btn btn-success">{{ __('Renew') }}</button></form>
            <form action="{{ route('admin.subscriptions.generate-invoice', $subscription) }}" method="POST">@csrf<button class="btn btn-outline-primary">{{ __('Generate invoice') }}</button></form>
            <form action="{{ route('admin.subscriptions.cancel', $subscription) }}" method="POST">@csrf<button class="btn btn-outline-danger">{{ __('Cancel') }}</button></form>
            <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>

@endsection
