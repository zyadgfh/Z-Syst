@extends('landing::layouts.web.master')

@section('title')
    {{ __('Blog') }}
@endsection

@section('main_content')
<div class="custom-container mb-4">
    <div class="banner-bg  blog-header p-4">
        <div class="container">
            <p class="mb-0 fw-bolder custom-clr-dark">
                {{ __('Home') }} <span class="font-monospace">></span> {{ __('Blog') }}
            </p>
        </div>
    </div>
</div>

<div class="container mb-4">
    <div class="blog-intro-card section-card p-4 p-lg-5">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="section-pill mb-2">
                    <i class="fa-solid fa-newspaper"></i>
                    {{ __('Knowledge hub') }}
                </div>
                <h2 class="langing-section-title mb-2">{{ __('Fresh insights for your team') }}</h2>
                <p class="section-description pt-0 mb-0">{{ __('Discover practical guides, product updates, and pharmacy tips in one place.') }}</p>
            </div>
            <a href="#blogs-container" class="custom-btn custom-outline-btn">
                {{ __('Explore articles') }}
            </a>
        </div>
    </div>
</div>

    @include('landing::web.components.blog')
@endsection
