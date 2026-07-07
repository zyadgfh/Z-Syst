@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.environment.menu.templateTitle') }}
@endsection

@section('title')
    <i class="fa fa-cog fa-fw" aria-hidden="true"></i>
    {{ trans('installer_messages.environment.menu.title') }}
@endsection

@section('container')

    <b class="text-center d-block environment-desc">
        {{ trans('installer_messages.environment.menu.desc') }}
    </b>

    <div class="center-flex">
        <div class="btn-wrapper">
            <div class="btn-glow"></div>
            <a href="{{ route('LaravelInstaller::environmentWizard') }}" class="btn" role="button" title="payment">
                {{ trans('installer_messages.next') }}
                <svg aria-hidden="true" viewBox="0 0 10 10" height="10" width="10" fill="none" class="arrow">
                    <path d="M0 5h7" class="line1"></path>
                    <path d="M1 1l4 4-4 4" class="line2"></path>
                </svg>
            </a>
        </div>
    </div>
    
@endsection
