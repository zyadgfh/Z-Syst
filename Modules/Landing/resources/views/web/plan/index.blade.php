@extends('landing::layouts.web.master')

@section('title')
    {{ __('Plan') }}
@endsection

@section('main_content')
<div class="custom-container">
    <section class="banner-bg page-header p-4">
        <div class="container">
          <p class="mb-0 fw-bolder custom-clr-dark">
            {{ __('Home') }} <span class="font-monospace">></span> {{ __('Pricing Plan') }}
          </p>
        </div>
    </section>
</div>

  @include('landing::web.components.plan')

@endsection
