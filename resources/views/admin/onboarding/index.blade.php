@extends('layouts.master')

@section('title')
    {{ __('business.Getting Started') }}
@endsection

@section('main_content')
<div class="container-fluid" style="max-width: 800px; margin: 0 auto; padding: 40px 20px;">

    {{-- Header --}}
    <div style="text-align: center; margin-bottom: 32px;">
        <h1 style="font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">
            {{ __('business.Welcome to Z-Syst! 🎉') }}
        </h1>
        <p style="font-size: 15px; color: #6b7280;">
            {{ __('business.Let\'s set up your store in a few quick steps.') }}
        </p>
    </div>

    {{-- Step Indicator --}}
    <div style="display: flex; justify-content: center; gap: 8px; margin-bottom: 40px;">
        @foreach($steps as $num => $step)
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600;
                {{ $step['done'] ? 'background: #dcfce7; color: #16a34a;' : (($num == request()->segment(3) || ($num == 1 && request()->is('*/onboarding'))) ? 'background: #6366f1; color: #fff;' : 'background: #f3f4f6; color: #9ca3af;') }}">
                @if($step['done'])
                    <i class="fas fa-check"></i>
                @else
                    {{ $num }}
                @endif
            </div>
            <span style="font-size: 12px; color: {{ $step['done'] ? '#16a34a' : '#6b7280' }}; font-weight: {{ $num == request()->segment(3) ? '600' : '400' }};">
                {{ $step['title'] }}
            </span>
            @if(!$loop->last)
            <div style="width: 30px; height: 2px; background: {{ $step['done'] ? '#22c55e' : '#e5e7eb' }}; margin: 0 4px;"></div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Step Content --}}
    @yield('onboarding_content')

    {{-- Skip link --}}
    <div style="text-align: center; margin-top: 24px;">
        <a href="{{ route('admin.onboarding.skip') }}" style="font-size: 13px; color: #9ca3af; text-decoration: none;">
            {{ __('business.Skip onboarding') }} <i class="fas fa-arrow-right" style="font-size: 10px;"></i>
        </a>
    </div>
</div>
@endsection
