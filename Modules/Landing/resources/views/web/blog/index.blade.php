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
    @include('landing::web.components.blog')
@endsection
