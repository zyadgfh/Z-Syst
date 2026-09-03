@extends('admin.onboarding.index')

@section('onboarding_content')
<div class="card" class="card-bordered-lg">
    <h2 class="section-title-hero">
        <i class="fas fa-flag-checkered" class="text-indigo"></i> {{ __('business.Review & Complete') }}
    </h2>
    <p style="font-size: 14px; color: #6b7280; margin-bottom: 28px;">
        {{ __('business.Here\'s what you\'ve set up. You\'re ready to go!') }}
    </p>

    {{-- Summary --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 28px;">
        <div class="card-bg-subtle">
            <div style="font-size: 28px; font-weight: 700; color: #6366f1;">{{ $business->companyName ?? '—' }}</div>
            <div class="fs-12-color-subtle">{{ __('business.Store Name') }}</div>
        </div>
        <div class="card-bg-subtle">
            <div style="font-size: 28px; font-weight: 700; color: #22c55e;">{{ $categories }}</div>
            <div class="fs-12-color-subtle">{{ __('business.Categories') }}</div>
        </div>
        <div class="card-bg-subtle">
            <div style="font-size: 28px; font-weight: 700; color: #f59e0b;">{{ $products }}</div>
            <div class="fs-12-color-subtle">{{ __('business.Products') }}</div>
        </div>
    </div>

    {{-- What's next --}}
    <div style="padding: 20px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; margin-bottom: 28px;">
        <h3 style="font-size: 15px; font-weight: 600; color: #1e40af; margin-bottom: 12px;">
            <i class="fas fa-lightbulb"></i> {{ __('business.What\'s Next?') }}
        </h3>
        <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #1e3a5f; line-height: 1.8;">
            <li>{{ __('business.Add more products from the Products page') }}</li>
            <li>{{ __('business.Set up suppliers for purchase management') }}</li>
            <li>{{ __('business.Configure payment gateways for online sales') }}</li>
            <li>{{ __('business.Process your first sale from the POS') }}</li>
            <li>{{ __('business.Check your dashboard for real-time analytics') }}</li>
        </ul>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 12px;">
        <a href="{{ route('admin.onboarding.step', 3) }}" class="input-btn-outline">
            <i class="fas fa-arrow-left"></i> {{ __('common.Back') }}
        </a>
        <a href="{{ route('admin.onboarding.complete') }}" style="padding: 12px 32px; font-size: 15px; font-weight: 600; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; text-decoration: none; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; transition: transform 0.2s;">
            🚀 {{ __('business.Launch My Dashboard') }}
        </a>
    </div>
</div>
@endsection
