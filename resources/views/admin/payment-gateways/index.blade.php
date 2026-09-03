@extends('layouts.master')

@section('title')
    {{ __('gateways.Payment Gateways Management') }}
@endsection

@push('css')
<style>
    .gateway-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    .gateway-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .gateway-card.active {
        border-left-color: #28a745;
    }
    .gateway-card.inactive {
        border-left-color: #dc3545;
        opacity: 0.7;
    }
    .gateway-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    .gateway-icon.vodafone-cash { background: linear-gradient(135deg, #e60013, #ff6b6b); color: white; }
    .gateway-icon.bank-card { background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; }
    .gateway-icon.fawry { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; }
    .gateway-icon.orange-cash { background: linear-gradient(135deg, #ff6600, #ff9500); color: white; }
    .gateway-icon.instapay { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
    .gateway-icon.cash { background: linear-gradient(135deg, #6b7280, #9ca3af); color: white; }
    
    .stat-card {
        border-radius: 12px;
        padding: 20px;
        color: white;
        position: relative;
        overflow: hidden;
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: rgba(255,255,255,0.1);
        transform: rotate(45deg);
    }
    .stat-card h2 {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
    }
    .stat-card h5 {
        font-size: 0.875rem;
        opacity: 0.9;
        margin: 0;
    }
    .stat-card small {
        font-size: 0.75rem;
        opacity: 0.8;
    }
    
    .btn-action {
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: all 0.2s ease;
    }
    .btn-action:hover {
        transform: scale(1.05);
    }
    
    .filter-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .table-responsive {
        border-radius: 12px;
        overflow: hidden;
    }
    
    .table thead th {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
    }
    
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
    }
</style>
@endpush

@section('main_content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">{{ __('business.Payment Gateways') }}</h2>
            <p class="text-muted mb-0">{{ __('gateways.Manage Egyptian payment gateways for subscriptions and POS') }}</p>
        </div>
        <a href="{{ route('admin.payment-gateways.create', ['company_id' => $companyId, 'branch_id' => $branchId]) }}" 
           class="btn btn-primary btn-action">
            <i class="fas fa-plus me-2"></i> {{ __('gateways.Add Gateway') }}
        </a>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-primary">
                <h5>{{ __('gateways.Total Gateways') }}</h5>
                <h2>{{ $gateways->count() }}</h2>
                <small>{{ __('gateways.Configured payment methods') }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-success">
                <h5>{{ __('common.Active') }}</h5>
                <h2>{{ $gateways->where('is_active', true)->count() }}</h2>
                <small>{{ __('gateways.Currently active gateways') }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-warning">
                <h5>{{ __('gateways.By Company') }}</h5>
                <h2>{{ $gateways->whereNull('branch_id')->count() }}</h2>
                <small>{{ __('gateways.Company-level configurations') }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-info">
                <h5>{{ __('gateways.By Branch') }}</h5>
                <h2>{{ $gateways->whereNotNull('branch_id')->count() }}</h2>
                <small>{{ __('gateways.Branch-specific configurations') }}</small>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label fw-bold">{{ __('gateways.Company') }}</label>
                <select class="form-select" id="companyFilter" onchange="filterGateways()">
                    <option value="">{{ __('gateways.All Companies') }}</option>
                    @foreach(\App\Models\Business::all() as $company)
                        <option value="{{ $company->id }}" {{ $companyId == $company->id ? 'selected' : '' }}>
                            {{ $company->companyName }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">{{ __('common.Branch') }}</label>
                <select class="form-select" id="branchFilter" onchange="filterGateways()">
                    <option value="">{{ __('gateways.All Branches') }}</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                            {{ $branch->branch_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">{{ __('gateways.Gateway Type') }}</label>
                <select class="form-select" id="gatewayTypeFilter" onchange="filterGateways()">
                    <option value="">{{ __('common.All Types') }}</option>
                    @foreach($gatewayTypes as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">{{ __('common.Status') }}</label>
                <select class="form-select" id="statusFilter" onchange="filterGateways()">
                    <option value="">{{ __('common.All Status') }}</option>
                    <option value="active">{{ __('common.Active') }}</option>
                    <option value="inactive">{{ __('common.Inactive') }}</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Gateways Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('gateways.Gateway Type') }}</th>
                            <th>{{ __('gateways.Company') }}</th>
                            <th>{{ __('common.Branch') }}</th>
                            <th>{{ __('gateways.Transaction Fee') }}</th>
                            <th>{{ __('common.Status') }}</th>
                            <th>{{ __('purchases.Priority') }}</th>
                            <th>{{ __('common.Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gateways as $gateway)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="gateway-icon {{ str_replace('_', '-', $gateway->gateway_type) }} me-3">
                                        <i class="fas {{ getGatewayIcon($gateway->gateway_type) }}"></i>
                                    </div>
                                    <div>
                                        <strong>{{ $gateway->gateway_type_label }}</strong>
                                        @if($gateway->branch_id)
                                        <small class="text-muted d-block">{{ __('gateways.Branch-specific') }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $gateway->company->companyName ?? 'N/A' }}</td>
                            <td>{{ $gateway->branch ? $gateway->branch->branch_name : 'All Branches' }}</td>
                            <td>
                                @if($gateway->transaction_fee > 0)
                                    <span class="badge bg-info">{{ $gateway->transaction_fee }}{{ $gateway->transaction_fee_type == 'percentage' ? '%' : ' EGP' }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('gateways.No Fee') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $gateway->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $gateway->is_active ? __('common.Active') : __('common.Inactive') }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">#{{ $gateway->sort_order }}</span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    @can('gateways-edit')
                                    <a href="{{ route('admin.payment-gateways.edit', $gateway->id) }}" 
                                       class="btn btn-sm btn-info btn-action" title="{{ __('common.Edit') }}">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan
                                    @can('gateways-edit')
                                    <button onclick="toggleStatus({{ $gateway->id }})" 
                                            class="btn btn-sm {{ $gateway->is_active ? 'btn-warning' : 'btn-success' }} btn-action" 
                                            title="{{ $gateway->is_active ? __('gateways.Deactivate') : __('gateways.Activate') }}">
                                        <i class="fas {{ $gateway->is_active ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
                                    </button>
                                    @endcan
                                    <a href="{{ route('admin.payment-gateways.transactions', $gateway->id) }}" 
                                       class="btn btn-sm btn-secondary btn-action" title="{{ __('loyalty.Transactions') }}">
                                        <i class="fas fa-list"></i>
                                    </a>
                                    @can('gateways-delete')
                                    <button onclick="deleteGateway({{ $gateway->id }})" 
                                            class="btn btn-sm btn-danger btn-action" title="{{ __('common.Delete') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-credit-card fa-3x mb-3"></i>
                                    <p class="mb-0">{{ __('gateways.No payment gateways found') }}</p>
                                    <a href="{{ route('admin.payment-gateways.create', ['company_id' => $companyId, 'branch_id' => $branchId]) }}" 
                                       class="btn btn-primary btn-sm mt-2">
                                        {{ __('gateways.Add Your First Gateway') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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
function filterGateways() {
    const companyId = document.getElementById('companyFilter').value;
    const branchId = document.getElementById('branchFilter').value;
    const gatewayType = document.getElementById('gatewayTypeFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    let url = '{{ route("admin.payment-gateways.index") }}?';
    if (companyId) url += 'company_id=' + companyId + '&';
    if (branchId) url += 'branch_id=' + branchId + '&';
    if (gatewayType) url += 'gateway_type=' + gatewayType + '&';
    if (status) url += 'status=' + status;
    
    window.location.href = url;
}

function toggleStatus(id) {
    if (!confirm('{{ __('gateways.Are you sure you want to toggle the gateway status?') }}')) {
        return;
    }
    
    fetch(`{{ route('admin.payment-gateways.toggle-status', ['id' => ':id']) }}`.replace(':id', id), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error occurred');
        }
    })
    .catch(error => {
        alert('Error occurred: ' + error);
    });
}

function deleteGateway(id) {
    if (!confirm('{{ __('gateways.Are you sure you want to delete this payment gateway?') }}')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.payment-gateways.destroy", ['id' => ':id']) }}'.replace(':id', id);
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);
    
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';
    form.appendChild(methodInput);
    
    document.body.appendChild(form);
    form.submit();
}
</script>
@endpush