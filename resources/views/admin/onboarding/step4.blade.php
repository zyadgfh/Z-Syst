@extends('admin.onboarding.index')

@section('onboarding_content')
<div class="card" style="border: 1px solid #e5e7eb; border-radius: 12px; padding: 32px;">
    <h2 style="font-size: 20px; font-weight: 600; color: #1f2937; margin-bottom: 4px;">
        <i class="fas fa-flag-checkered" style="color: #6366f1;"></i> {{ __('Review & Complete') }}
    </h2>
    <p style="font-size: 14px; color: #6b7280; margin-bottom: 28px;">
        {{ __('Here\'s what you\'ve set up. You\'re ready to go!') }}
    </p>

    {{-- Summary --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 28px;">
        <div style="padding: 20px; background: #f8fafc; border-radius: 10px; text-align: center; border: 1px solid #e2e8f0;">
            <div style="font-size: 28px; font-weight: 700; color: #6366f1;">{{ $business->companyName ?? '—' }}</div>
            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">{{ __('Store Name') }}</div>
        </div>
        <div style="padding: 20px; background: #f8fafc; border-radius: 10px; text-align: center; border: 1px solid #e2e8f0;">
            <div style="font-size: 28px; font-weight: 700; color: #22c55e;">{{ $categories }}</div>
            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">{{ __('Categories') }}</div>
        </div>
        <div style="padding: 20px; background: #f8fafc; border-radius: 10px; text-align: center; border: 1px solid #e2e8f0;">
            <div style="font-size: 28px; font-weight: 700; color: #f59e0b;">{{ $products }}</div>
            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">{{ __('Products') }}</div>
        </div>
    </div>

    {{-- What's next --}}
    <div style="padding: 20px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; margin-bottom: 28px;">
        <h3 style="font-size: 15px; font-weight: 600; color: #1e40af; margin-bottom: 12px;">
            <i class="fas fa-lightbulb"></i> {{ __('What\'s Next?') }}
        </h3>
        <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #1e3a5f; line-height: 1.8;">
            <li>{{ __('Add more products from the Products page') }}</li>
            <li>{{ __('Set up suppliers for purchase management') }}</li>
            <li>{{ __('Configure payment gateways for online sales') }}</li>
            <li>{{ __('Process your first sale from the POS') }}</li>
            <li>{{ __('Check your dashboard for real-time analytics') }}</li>
        </ul>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 12px;">
        <a href="{{ route('admin.onboarding.step', 3) }}" style="padding: 10px 20px; font-size: 14px; color: #6b7280; text-decoration: none; border: 1px solid #e5e7eb; border-radius: 8px;">
            <i class="fas fa-arrow-left"></i> {{ __('Back') }}
        </a>
        <a href="{{ route('admin.onboarding.complete') }}" style="padding: 12px 32px; font-size: 15px; font-weight: 600; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; text-decoration: none; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; transition: transform 0.2s;">
            🚀 {{ __('Launch My Dashboard') }}
        </a>
    </div>
</div>
@endsection
