@extends('landing::layouts.web.master')

@section('title')
    {{ __('Policy') }}
@endsection

@section('main_content')
    <div class="banner-bg  blog-header p-4">
        <div class="container">
            <p class="mb-0 fw-bolder custom-clr-dark">
                {{ __('Home') }} <span class="font-monospace">></span> {{ __('Privacy Policy') }}
            </p>
        </div>
    </div>

    <section class="terms-policy-section">
        <div class="container">
            <h2 class="langing-section-title mb-4">{{ Str::words($page_data['headings']['privacy_title'] ?? '', 4, '...') }}</h2>
            <div>
                {!! $privacy_policy->value['description'] ?? '' !!}
            </div>
        </div>
    </section>
@endsection
