@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ __('Verify Envato Purchase Code') }}
@endsection

@section('title')
    <i class="fa fa-key fa-fw" aria-hidden="true"></i>
    {{ __('Verify Envato Purchase Code') }}
@endsection

@section('container')

    <form method="post" action="{{ route('LaravelInstaller::codeVerifyProcess') }}" class="tabs-wrap">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <div class="form-group bottom-gap {{ $errors->has('purchase_code') ? ' has-error ' : '' }}">
            <label for="purchase_code">
                {{ __('Purchase Code') }}
            </label>
            <input type="text" name="purchase_code" id="purchase_code" value="" placeholder="{{ __('Envato purchase code')}}" />
            @if ($errors->has('purchase_code'))
                <span class="error-block">
                            <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ $errors->first('purchase_code') }}
                </span>
            @endif
        </div>

        <div class="center-flex">
            <div class="btn-wrapper">
                <div class="btn-glow"></div>
                <button type="submit" class="btn" role="button">
                    {{ __('Verify Code') }}
                    <svg aria-hidden="true" viewBox="0 0 10 10" height="10" width="10" fill="none" class="arrow">
                        <path d="M0 5h7" class="line1"></path>
                        <path d="M1 1l4 4-4 4" class="line2"></path>
                    </svg>
                </button>
            </div>
        </div>
    </form>

@endsection
