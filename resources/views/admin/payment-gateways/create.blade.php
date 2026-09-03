@extends('layouts.admin')

@section('title')
    {{ __('gateways.Add Payment Gateway') }}
@endsection

@push('css')
<style>
    .gateway-preview {
        border: 2px dashed #dee2e6;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        background: #f8f9fa;
        margin-bottom: 20px;
    }
    .gateway-preview.selected {
        border-color: #3b82f6;
        background: #eff6ff;
    }
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
    
    .required-field {
        position: relative;
    }
    .required-field::after {
        content: '*';
        color: #dc3545;
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
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
            <h2 class="mb-1">{{ __('gateways.Add Payment Gateway') }}</h2>
            <p class="text-muted mb-0">{{ __('gateways.Configure a new payment gateway for your pharmacy') }}</p>
        </div>
        <a href="{{ route('admin.payment-gateways.index', ['company_id' => $companyId, 'branch_id' => $branchId]) }}" 
           class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> {{ __('gateways.Back to Gateways') }}
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.payment-gateways.store') }}" method="POST" id="gatewayForm">
                @csrf
                
                <!-- Basic Information -->
                <h5 class="mb-3">{{ __('gateways.Basic Information') }}</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="company_id" class="fw-bold">{{ __('gateways.Company') }} <span class="text-danger">*</span></label>
                            <select class="form-select" id="company_id" name="company_id" required onchange="loadBranches()">
                                <option value="">{{ __('gateways.Select Company') }}</option>
                                @foreach(\App\Models\Business::all() as $company)
                                    <option value="{{ $company->id }}" {{ $companyId == $company->id ? 'selected' : '' }}>
                                        {{ $company->companyName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="branch_id" class="fw-bold">{{ __('common.Branch') }}</label>
                            <select class="form-select" id="branch_id" name="branch_id">
                                <option value="">{{ __('gateways.All Branches') }}</option>
                            </select>
                            <small class="text-muted">{{ __('gateways.Leave empty to apply to all branches') }}</small>
                        </div>
                    </div>
                </div>

                <!-- Gateway Type Selection -->
                <h5 class="mb-3">{{ __('gateways.Select Gateway Type') }}</h5>
                <div class="row mb-4">
                    @foreach($gatewayTypes as $type => $label)
                    <div class="col-md-4 mb-3">
                        <div class="gateway-preview {{ $selectedType == $type ? 'selected' : '' }}" 
                             onclick="selectGatewayType('{{ $type }}')" 
                             class="cursor-pointer"
                             data-gateway-type="{{ $type }}">
                            <div class="gateway-icon-large {{ str_replace('_', '-', $type) }}">
                                <i class="fas {{ getGatewayIcon($type) }}"></i>
                            </div>
                            <h6>{{ $label }}</h6>
                            <small class="text-muted">{{ getGatewayDescription($type) }}</small>
                        </div>
                        <input type="hidden" name="gateway_type" id="gateway_type_{{ $type }}" value="{{ $type }}">
                    </div>
                    @endforeach
                </div>

                <!-- Fees Configuration -->
                <h5 class="mb-3">{{ __('gateways.Transaction Fees') }}</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="transaction_fee" class="fw-bold">{{ __('gateways.Transaction Fee') }}</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" id="transaction_fee" name="transaction_fee" value="0" min="0">
                                <select class="form-select" id="transaction_fee_type" name="transaction_fee_type" class="thumb-contain-sm">
                                    <option value="percentage">%</option>
                                    <option value="fixed">EGP</option>
                                </select>
                            </div>
                            <small class="text-muted">{{ __('gateways.Set to 0 for no transaction fee') }}</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="sort_order" class="fw-bold">{{ __('gateways.Display Priority') }}</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                            <small class="text-muted">{{ __('gateways.Lower numbers appear first in payment selection') }}</small>
                        </div>
                    </div>
                </div>

                <!-- Gateway Configuration -->
                <div id="configFields" class="config-section">
                    <h5 class="mb-3">{{ __('gateways.Gateway Configuration') }}</h5>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ __('gateways.Select a gateway type above to see required configuration fields') }}
                    </div>
                </div>

                <!-- Additional Settings -->
                <h5 class="mb-3">{{ __('gateways.Additional Settings') }}</h5>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
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
                              placeholder="{{ __('gateways.Add any notes or instructions for this gateway configuration...') }}"></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i> {{ __('gateways.Save Gateway') }}
                    </button>
                    <button type="button" onclick="testConfiguration()" class="btn btn-info">
                        <i class="fas fa-vial me-2"></i> {{ __('gateways.Test Configuration') }}
                    </button>
                    <a href="{{ route('admin.payment-gateways.index', ['company_id' => $companyId, 'branch_id' => $branchId]) }}" 
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

function getGatewayDescription($gatewayType) {
    return match($gatewayType) {
        'vodafone_cash' => 'Mobile wallet payments via Vodafone Cash',
        'bank_card' => 'Credit/debit card payments (Visa, Mastercard, Meeza)',
        'fawry' => 'Egyptian payment network for cash payments',
        'orange_cash' => 'Mobile wallet payments via Orange Cash',
        'instapay' => 'Instant bank transfer payments',
        'cash' => 'Manual cash payments with change calculation',
        default => 'Payment gateway',
    };
}
@endphp

<script>
let selectedGatewayType = '{{ $selectedType }}';

function selectGatewayType(type) {
    // Remove selected class from all previews
    document.querySelectorAll('.gateway-preview').forEach(preview => {
        preview.classList.remove('selected');
    });
    
    // Add selected class to clicked preview
    event.currentTarget.classList.add('selected');
    
    // Update hidden input
    document.getElementById('gateway_type_' + type).checked = true;
    document.getElementById('gateway_type').value = type;
    
    selectedGatewayType = type;
    loadRequiredFields();
}

function loadBranches() {
    const companyId = document.getElementById('company_id').value;
    const branchSelect = document.getElementById('branch_id');
    
    branchSelect.innerHTML = '<option value="">{{ __('gateways.All Branches') }}</option>';
    
    if (companyId) {
        fetch(`/api/branches/${companyId}`)
            .then(response => response.json())
            .then(data => {
                data.forEach(branch => {
                    const option = document.createElement('option');
                    option.value = branch.id;
                    option.textContent = branch.branch_name;
                    branchSelect.appendChild(option);
                });
            });
    }
}

function loadRequiredFields() {
    const configFieldsDiv = document.getElementById('configFields');
    
    if (!selectedGatewayType) {
        configFieldsDiv.innerHTML = `
            <h5 class="mb-3">{{ __('gateways.Gateway Configuration') }}</h5>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                {{ __('gateways.Select a gateway type above to see required configuration fields') }}
            </div>
        `;
        return;
    }
    
    fetch(`{{ route('admin.payment-gateways.get-required-fields') }}?gateway_type=${selectedGatewayType}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.fields) {
                let html = '<h5 class="mb-3">{{ __('gateways.Gateway Configuration') }}</h5><div class="row">';
                
                for (const [key, label] of Object.entries(data.fields)) {
                    html += `
                        <div class="col-md-6 mb-3">
                            <label for="config_${key}" class="fw-bold required-field">${label}</label>
                            <input type="text" class="form-control" id="config_${key}" name="config_data[${key}]" required
                                   placeholder="{{ __('gateways.Enter ') + label + '..." }}">
                        </div>
                    `;
                }
                
                html += '</div>';
                configFieldsDiv.innerHTML = html;
            }
        });
}

function testConfiguration() {
    const configData = {};
    
    // Collect config data
    document.querySelectorAll('[name^="config_data["]').forEach(input => {
        const key = input.name.match(/config_data\[(.+)\]/)[1];
        configData[key] = input.value;
    });
    
    if (!selectedGatewayType) {
        showTestResult(false, '{{ __('gateways.Please select a gateway type first') }}');
        return;
    }
    
    // Show loading state
    showTestResult(false, '{{ __('gateways.Testing configuration...') }}');
    
    fetch('{{ route("admin.payment-gateways.test-configuration") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            gateway_type: selectedGatewayType,
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

// Load initial data
document.addEventListener('DOMContentLoaded', function() {
    if ('{{ $companyId }}') {
        loadBranches();
    }
    
    if ('{{ $selectedType }}') {
        loadRequiredFields();
    }
});
</script>
@endpush