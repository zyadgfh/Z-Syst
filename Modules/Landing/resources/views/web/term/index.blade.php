@extends('landing::layouts.web.master')

@section('title')
    {{ __('Term And Condition') }}
@endsection

@section('main_content')
    <div class="banner-bg  blog-header p-4">
        <div class="container">
            <p class="mb-0 fw-bolder custom-clr-dark">
                {{ __('Home') }} <span class="font-monospace">></span> {{ __('Terms Of Service') }}
            </p>
        </div>
    </div>
    @include('landing::web.components.term')
@endsection
