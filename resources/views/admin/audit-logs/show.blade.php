@extends('layouts.master')

@section('title', __('audit.Audit Log Details'))

@section('main_content')
<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-header p-16">
                    <h4>{{ __('audit.Audit Log Details') }}</h4>
                    <a href="{{ route('admin.audit-logs.index') }}" class="theme-btn print-btn text-light">
                        <i class="fas fa-list me-1"></i> {{ __('common.View List') }}
                    </a>
                </div>

                <div class="order-form-section p-16">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4>{{ __('audit.Log Information') }}</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>{{ __('common.Date') }}:</strong> {{ $auditLog->created_at->format('d M Y H:i:s') }}</p>
                                            <p><strong>{{ __('common.User') }}:</strong> {{ $auditLog->user->name ?? 'System' }}</p>
                                            <p><strong>{{ __('common.Email') }}:</strong> {{ $auditLog->user->email ?? 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>{{ __('common.Action') }}:</strong>
                                                <span class="badge badge-{{ $auditLog->action === 'delete' ? 'danger' : ($auditLog->action === 'create' ? 'success' : 'info') }}">
                                                    {{ ucfirst($auditLog->action) }}
                                                </span>
                                            </p>
                                            <p><strong>{{ __('audit.Model') }}:</strong> {{ class_basename($auditLog->model_type ?? '') }}</p>
                                            <p><strong>{{ __('audit.Model ID') }}:</strong> {{ $auditLog->model_id ?? 'N/A' }}</p>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="row">
                                        <div class="col-12">
                                            <p><strong>{{ __('common.Description') }}:</strong></p>
                                            <p>{{ $auditLog->description ?? 'N/A' }}</p>
                                        </div>
                                    </div>

                                    @if($auditLog->old_values)
                                    <hr>
                                    <div class="row">
                                        <div class="col-12">
                                            <p><strong>{{ __('audit.Old Values') }}:</strong></p>
                                            <pre class="bg-light p-3 rounded">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    </div>
                                    @endif

                                    @if($auditLog->new_values)
                                    <hr>
                                    <div class="row">
                                        <div class="col-12">
                                            <p><strong>{{ __('audit.New Values') }}:</strong></p>
                                            <pre class="bg-light p-3 rounded">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4>{{ __('audit.Metadata') }}</h4>
                                </div>
                                <div class="card-body">
                                    <p><strong>{{ __('audit.IP Address') }}:</strong> {{ $auditLog->ip_address ?? 'N/A' }}</p>
                                    <p><strong>{{ __('audit.User Agent') }}:</strong> {{ Str::limit($auditLog->user_agent ?? 'N/A', 50) }}</p>
                                    <p><strong>{{ __('audit.Business') }}:</strong> {{ $auditLog->business->companyName ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
