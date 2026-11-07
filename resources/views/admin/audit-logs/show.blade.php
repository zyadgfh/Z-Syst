@extends('layouts.master')

@section('title')
    {{ __('Audit Log') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">{{ __('Audit Log') }} #{{ $auditLog->id }}</h4>
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-light">{{ __('Back') }}</a>
            </div>
            <div class="row g-3">
                <div class="col-md-6"><strong>{{ __('Action') }}:</strong> {{ $auditLog->action }}</div>
                <div class="col-md-6"><strong>{{ __('Model') }}:</strong> {{ $auditLog->model_type ? class_basename($auditLog->model_type) : '—' }} #{{ $auditLog->model_id }}</div>
                <div class="col-md-6"><strong>{{ __('Actor') }}:</strong> {{ $auditLog->user?->email ?? '—' }}</div>
                <div class="col-md-6"><strong>{{ __('Business') }}:</strong> {{ $auditLog->business?->companyName ?? '—' }}</div>
                <div class="col-md-6"><strong>{{ __('IP') }}:</strong> {{ $auditLog->ip_address }}</div>
                <div class="col-md-6"><strong>{{ __('Date') }}:</strong> {{ $auditLog->created_at?->format('Y-m-d H:i:s') }}</div>
                <div class="col-12"><strong>{{ __('Description') }}:</strong><p>{{ $auditLog->description }}</p></div>
                @if($auditLog->old_values)
                    <div class="col-md-6"><strong>{{ __('Old values') }}:</strong><pre class="bg-light p-3 rounded small">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></div>
                @endif
                @if($auditLog->new_values)
                    <div class="col-md-6"><strong>{{ __('New values') }}:</strong><pre class="bg-light p-3 rounded small">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
