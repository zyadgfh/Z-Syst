@extends('admin.onboarding.index')

@section('onboarding_content')
<div class="card" class="card-bordered-lg">
    <h2 class="section-title-hero">
        <i class="fas fa-flag-checkered" class="text-indigo"></i> {{ __('business.Review & Complete') }}
    </h2>
    <p class="fz14-gray-mb28">
        {{ __('business.Here\'s what you\'ve set up. You\'re ready to go!') }}
    </p>

    {{-- Summary --}}
    <div class="grid-3col mb-28">
        <div class="card-bg-subtle">
            <div class="fz28-bold-indigo">{{ $business->companyName ?? '—' }}</div>
            <div class="fs-12-color-subtle">{{ __('business.Store Name') }}</div>
        </div>
        <div class="card-bg-subtle">
            <div class="fz28-bold-green">{{ $categories }}</div>
            <div class="fs-12-color-subtle">{{ __('business.Categories') }}</div>
        </div>
        <div class="card-bg-subtle">
            <div class="fz28-bold-amber">{{ $products }}</div>
            <div class="fs-12-color-subtle">{{ __('business.Products') }}</div>
        </div>
    </div>

    {{-- What's next --}}
    <div class="card-info-outline">
        <h3 class="fz16-semibold-blue-mb12">
            <i class="fas fa-lightbulb"></i> {{ __('business.What\'s Next?') }}
        </h3>
        <ul class="policy-text">
            <li>{{ __('business.Add more products from the Products page') }}</li>
            <li>{{ __('business.Set up suppliers for purchase management') }}</li>
            <li>{{ __('business.Configure payment gateways for online sales') }}</li>
            <li>{{ __('business.Process your first sale from the POS') }}</li>
            <li>{{ __('business.Check your dashboard for real-time analytics') }}</li>
        </ul>
    </div>

    <div class="flex-end-gap12">
        <a href="{{ route('admin.onboarding.step', 3) }}" class="input-btn-outline">
            <i class="fas fa-arrow-left"></i> {{ __('common.Back') }}
        </a>
        <a href="{{ route('admin.onboarding.complete') }}" class="btn-gradient-indigo">
            🚀 {{ __('business.Launch My Dashboard') }}
        </a>
    </div>
</div>
@endsection
