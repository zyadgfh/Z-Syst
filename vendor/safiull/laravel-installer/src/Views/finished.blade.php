@extends('vendor.installer.layouts.master')

@section('template_title')
    {{ trans('installer_messages.final.templateTitle') }}
@endsection

@section('title')
    <i class="fa fa-flag-checkered fa-fw" aria-hidden="true"></i>
    {{ trans('installer_messages.final.title') }}
@endsection

@section('container')

	@if(session('message')['dbOutputLog'])
		<p><strong><small>{{ trans('installer_messages.final.migration') }}</small></strong></p>
		<pre><code>{{ session('message')['dbOutputLog'] }}</code></pre>
	@endif

	<p><strong><small>{{ trans('installer_messages.final.console') }}</small></strong></p>
	<pre><code>{{ $finalMessages }}</code></pre>

	<p><strong><small>{{ trans('installer_messages.final.log') }}</small></strong></p>
	<pre><code>{{ $finalStatusMessage }}</code></pre>

	<p><strong><small>{{ trans('installer_messages.final.env') }}</small></strong></p>
	<pre><code>{{ $finalEnvFile }}</code></pre>

	<div class="center-flex">
        <div class="btn-wrapper">
            <div class="btn-glow"></div>
            <a href="{{ url('/') }}" class="btn" role="button" title="payment">
                {{ trans('installer_messages.final.exit') }}
                <svg aria-hidden="true" viewBox="0 0 10 10" height="10" width="10" fill="none" class="arrow">
                    <path d="M0 5h7" class="line1"></path>
                    <path d="M1 1l4 4-4 4" class="line2"></path>
                </svg>
            </a>
        </div>
    </div>

@endsection
