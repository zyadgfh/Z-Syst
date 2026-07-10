@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.welcome.templateTitle') }}
@endsection

@section('title')
    {{ __("installer_messages.title") }}
@endsection

@section('container')
    <h1 class="page-title">
      {{__('We Makes')}} <span class="gradient-text">{{__('Globally')}}</span> {{__('Growth')}}<br /> {{__('Increase Your Revenue')}}
    </h1>
    <p class="text-center paragraph">
      {{ trans('installer_messages.welcome.message') }}
    </p>
    <br>
    <div class="center-flex">
      <div class="btn-wrapper">
        <div class="btn-glow"></div>
        <a href="{{ route('LaravelInstaller::requirements') }}" class="btn" role="button" title="payment">
          {{ trans('installer_messages.next') }}
          <svg aria-hidden="true" viewBox="0 0 10 10" height="10" width="10" fill="none" class="arrow">
            <path d="M0 5h7" class="line1"></path>
            <path d="M1 1l4 4-4 4" class="line2"></path>
          </svg>
        </a>
      </div>
    </div>
@endsection
