@extends('layouts.master')

@section('title')
    {{ __('gateways.Edit Payment Gateway') }}: {{ $gateway->gateway_type_label }}
@endsection

@push('css')
<style>
    .gateway-icon-large {
        width: 80px;
        height: 80px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin: 0 auto 15px;
    }
    .gateway-icon-large.vodafone-cash { background: linear-gradient(135deg, #e60013, #ff6b6b); color: white; }
    .gateway-icon-large.bank-card { background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; }
    .gateway-icon-large.fawry { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; }
    .gateway-icon-large.orange-cash { background: linear-gradient(135deg, #ff6600, #ff9500); color: white; }
    .gateway-icon-large.instapay { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
    .gateway-icon-large.cash { background: linear-gradient(135deg, #6b7280, #9ca3af); color: white; }
    
    .config-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .override-section {
        background: #fff3cd;
        border: 1px solid #ffeeba;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .test-result {
        padding: 15px;
        border-radius: 8px;
        margin-top: 15px;
        display: none;
    }
    .test-result.success {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }
    .test-result.error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }
</style>
@endpush

@section('main_content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">{{ __('gateways.Edit Payment Gateway') }}: {{ $gateway->gateway_type_label }}</h2>
            <p class="text-muted mb-0">{{ __('gateways.Update gateway configuration and settings') }}</p>
        </div>
        <a href="{{ route('admin.payment-gateways.index', ['company_id' => $gateway->company_id, 'branch_id' => $gateway->branch_id]) }}" 
           class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> {{ __('gateways.Back to Gateways') }}
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Gateway Preview -->
            <div class="text-center mb-4">
                <div class="gateway-icon-large {{ str_replace('_', '-', $gateway->gateway_type) }}">
                    <i class="fas {{ getGatewayIcon($gateway->gateway_type) }}"></i>
                </div>
                <h4>{{ $gateway->gateway_type_label }}</h4>
                @if($gateway->branch_id)
                <small class="text-muted">{{ __('gateways.Branch-specific configuration for') }} {{ $gateway->branch->branch_name }}</small>
                @else
                <small class="text-muted">{{ __('gateways.Company-wide configuration') }}</small>
                @endif
            </div>

            <form action="{{ route('admin.payment-gateways.update', $gateway->id) }}" method="POST" id="gatewayForm">
                @csrf
                @method('PUT')
                
                <!-- Basic Information -->
                <h5 class="mb-3">{{ __('gateways.Basic Information') }}</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="company_id" class="fw-bold">{{ __('gateways.Company') }} <span class="text-danger">*</span></label>
                            <select class="form-select" id="company_id" name="company_id" required disabled>
                                <option value="">{{ __('gateways.Select Company') }}</option>
                                @foreach(\App\Models\Business::all() as $company)
                                    <option value="{{ $company->id }}" {{ $gateway->company_id == $company->id ? 'selected' : '' }}>
                                        {{ $company->companyName }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="company_id" value="{{ $gateway->company_id }}">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="branch_id" class="fw-bold">{{ __('common.Branch') }}</label>
                            <select class="form-select" id="branch_id" name="branch_id">
                                <option value="">{{ __('gateways.All Branches') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ $gateway->branch_id == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->branch_name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ __('gateways.Leave empty to apply to all branches') }}</small>
                        </div>
                    </div>
                </div>

                <!-- Fees Configuration -->
                <h5 class="mb-3">{{ __('gateways.Transaction Fees') }}</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="transaction_fee" class="fw-bold">{{ __('gateways.Transaction Fee') }}</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" id="transaction_fee" name="transaction_fee" value="{{ $gateway->transaction_fee }}" min="0">
                                <select class="form-select" id="transaction_fee_type" name="transaction_fee_type" style="max-width: 120px;">
                                    <option value="percentage" {{ $gateway->transaction_fee_type == 'percentage' ? 'selected' : '' }}>%</option>
                                    <option value="fixed" {{ $gateway->transaction_fee_type == 'fixed' ? 'selected' : '' }}>EGP</option>
                                </select>
                            </div>
                            <small class="text-muted">{{ __('gateways.Set to 0 for no transaction fee') }}</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="sort_order" class="fw-bold">{{ __('gateways.Display Priority') }}</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ $gateway->sort_order }}" min="0">
                            <small class="text-muted">{{ __('gateways.Lower numbers appear first in payment selection') }}</small>
                        </div>
                    </div>
                </div>

                <!-- Company Configuration -->
                <div class="config-section">
                    <h5 class="mb-3">{{ __('gateways.Company Configuration') }}</h5>
                    <div class="row">
                        @foreach($requiredFields as $key => $label)
                        <div class="col-md-6 mb-3">
                            <label for="config_{{ $key }}" class="fw-bold">{{ $label }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="config_{{ $key }}" 
                                   name="config_data[{{ $key }}]" 
                                   value="{{ $gateway->config_data[$key] ?? '' }}"
                                   required>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Branch Configuration Override -->
                <div class="override-section">
                    <h5 class="mb-3">{{ __('gateways.Branch Configuration Override') }}</h5>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ __('gateways.Override company-level configuration for this branch only. Leave fields empty to use company configuration.') }}
                    </div>
                    <div class="row">
                        @foreach($requiredFields as $key => $label)
                        <div class="col-md-6 mb-3">
                            <label for="branch_config_{{ $key }}" class="fw-bold">{{ $label }} ({{ __('gateways.Branch Override') }})</label>
                            <input type="text" class="form-control" id="branch_config_{{ $key }}" 
                                   name="branch_config_data[{{ $key }}]" 
                                   value="{{ $gateway->branch_config_data[$key] ?? '' }}"
                                   placeholder="{{ __('gateways.Leave empty to use company config') }}">
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Additional Settings -->
                <h5 class="mb-3">{{ __('gateways.Additional Settings') }}</h5>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ $gateway->is_active ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="is_active">
                                {{ __('common.Active') }}
                            </label>
                            <small class="text-muted d-block">{{ __('Enable this payment gateway for use' }}</small>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label for="notes" class="fw-bold">{{ __('common.Notes') }}</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="1000" 
                              placeholder="{{ __('gateways.Add any notes or instructions for this gateway configuration...') }}">{{ $gateway->notes }}</textarea>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i> {{ __('gateways.Update Gateway') }}
                    </button>
                    <button type="button" onclick="testConfiguration()" class="btn btn-info">
                        <i class="fas fa-vial me-2"></i> {{ __('gateways.Test Configuration') }}
                    </button>
                    <a href="{{ route('admin.payment-gateways.index', ['company_id' => $gateway->company_id, 'branch_id' => $gateway->branch_id]) }}" 
                       class="btn btn-secondary">
                        {{ __('common.Cancel') }}
                    </a>
                </div>

                <!-- Test Result -->
                <div id="testResult" class="test-result"></div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
@php
function getGatewayIcon($gatewayType) {
    return match($gatewayType) {
        'vodafone_cash' => 'fa-mobile-alt',
        'bank_card' => 'fa-credit-card',
        'fawry' => 'fa-store',
        'orange_cash' => 'fa-mobile',
        'instapay' => 'fa-bolt',
        'cash' => 'fa-money-bill-wave',
        default => 'fa-credit-card',
    };
}
@endphp

<script>
function testConfiguration() {
    const configData = {};
    
    // Collect config data
    document.querySelectorAll('[name^="config_data["]').forEach(input => {
        const key = input.name.match(/config_data\[(.+)\]/)[1];
        configData[key] = input.value;
    });
    
    // Show loading state
    showTestResult(false, '{{ __('gateways.Testing configuration...') }}');
    
    fetch('{{ route("admin.payment-gateways.test-configuration") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            gateway_type: '{{ $gateway->gateway_type }}',
            config_data: configData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            showTestResult(true, '{{ __('gateways.Configuration is valid! This gateway is ready to use.') }}');
        } else {
            showTestResult(false, '{{ __('gateways.Configuration is invalid:') }} ' + data.message);
        }
    })
    .catch(error => {
        showTestResult(false, '{{ __('gateways.Error testing configuration:') }} ' + error);
    });
}

function showTestResult(success, message) {
    const resultDiv = document.getElementById('testResult');
    resultDiv.style.display = 'block';
    resultDiv.className = 'test-result ' + (success ? 'success' : 'error');
    resultDiv.innerHTML = '<i class="fas ' + (success ? 'fa-check-circle' : 'fa-exclamation-circle') + ' me-2"></i>' + message;
}
</script>
@endpush