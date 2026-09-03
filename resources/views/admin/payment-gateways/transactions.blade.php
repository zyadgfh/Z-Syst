@extends('layouts.admin')

@section('title')
    {{ __('gateways.Payment Transactions') }} - {{ $gateway->gateway_type_label }}
@endsection

@push('css')
<style>
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
    
    .transaction-card {
        border-left: 4px solid transparent;
        transition: all 0.3s ease;
    }
    .transaction-card:hover {
        transform: translateX(4px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .transaction-card.completed { border-left-color: #28a745; }
    .transaction-card.pending { border-left-color: #ffc107; }
    .transaction-card.failed { border-left-color: #dc3545; }
    .transaction-card.refunded { border-left-color: #6c757d; }
    
    .filter-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">{{ __('gateways.Payment Transactions') }}: {{ $gateway->gateway_type_label }}</h2>
            <p class="text-muted mb-0">{{ __('gateways.View and manage payment transactions for this gateway') }}</p>
        </div>
        <a href="{{ route('admin.payment-gateways.index', ['company_id' => $gateway->company_id, 'branch_id' => $gateway->branch_id]) }}" 
           class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> {{ __('gateways.Back to Gateways') }}
        </a>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-primary">
                <h5>{{ __('gateways.Total Transactions') }}</h5>
                <h2>{{ $stats['total_count'] }}</h2>
                <small>{{ __('purchases.Total Amount') }}: {{ number_format($stats['total_amount'], 2) }} EGP</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-success">
                <h5>{{ __('common.Completed') }}</h5>
                <h2>{{ $stats['completed_count'] }}</h2>
                <small>{{ __('common.Amount') }}: {{ number_format($stats['completed_amount'], 2) }} EGP</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-warning">
                <h5>{{ __('common.Pending') }}</h5>
                <h2>{{ $stats['pending_count'] }}</h2>
                <small>{{ __('gateways.Awaiting completion') }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-danger">
                <h5>{{ __('gateways.Failed') }}</h5>
                <h2>{{ $stats['failed_count'] }}</h2>
                <small>{{ __('gateways.Requires attention') }}</small>
            </div>
        </div>
    </div>

    <!-- Detailed Statistics -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0">{{ __('gateways.By Gateway Type') }}</h6>
                </div>
                <div class="card-body">
                    @foreach($stats['by_gateway_type'] as $type => $data)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ $type }}</span>
                        <div>
                            <span class="badge bg-primary">{{ $data['count'] }} {{ __('gateways.transactions') }}</span>
                            <span class="badge bg-info">{{ number_format($data['amount'], 2) }} EGP</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0">{{ __('gateways.By Transaction Type') }}</h6>
                </div>
                <div class="card-body">
                    @foreach($stats['by_transaction_type'] as $type => $data)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ $type }}</span>
                        <div>
                            <span class="badge bg-primary">{{ $data['count'] }} {{ __('gateways.transactions') }}</span>
                            <span class="badge bg-info">{{ number_format($data['amount'], 2) }} EGP</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-section">
        <div class="row">
            <div class="col-md-3">
                <label class="fw-bold">{{ __('common.Status') }}</label>
                <select class="form-select" id="statusFilter" onchange="filterTransactions()">
                    <option value="">{{ __('common.All Status') }}</option>
                    <option value="pending">{{ __('common.Pending') }}</option>
                    <option value="completed">{{ __('common.Completed') }}</option>
                    <option value="failed">{{ __('gateways.Failed') }}</option>
                    <option value="refunded">{{ __('gateways.Refunded') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="fw-bold">{{ __('gateways.Transaction Type') }}</label>
                <select class="form-select" id="typeFilter" onchange="filterTransactions()">
                    <option value="">{{ __('common.All Types') }}</option>
                    <option value="subscription">{{ __('gateways.Subscription') }}</option>
                    <option value="sale">{{ __('gateways.Sale') }}</option>
                    <option value="refund">{{ __('gateways.Refund') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="fw-bold">{{ __('gateways.Date From') }}</label>
                <input type="date" class="form-control" id="dateFrom" onchange="filterTransactions()">
            </div>
            </div>
            <div class="col-md-3">
                <label class="fw-bold">{{ __('gateways.Date To') }}</label>
                <input type="date" class="form-control" id="dateTo" onchange="filterTransactions()">
            </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('common.Reference') }}</th>
                            <th>{{ __('common.Type') }}</th>
                            <th>{{ __('common.Amount') }}</th>
                            <th>{{ __('common.Customer') }}</th>
                            <th>{{ __('common.Status') }}</th>
                            <th>{{ __('common.Date') }}</th>
                            <th>{{ __('common.Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr class="transaction-card {{ $transaction->status }}">
                            <td>
                                <div class="fw-bold">{{ $transaction->internal_reference }}</div>
                                @if($transaction->reference_id)
                                <small class="text-muted">{{ $transaction->reference_id }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">{{ $transaction->transaction_type_label }}</span>
                            </td>
                            <td>
                                <div class="fw-bold">{{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}</div>
                            </td>
                            <td>
                                @if($transaction->customer_phone)
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-phone me-2 text-muted"></i>
                                    {{ $transaction->customer_phone }}
                                </div>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ getStatusBadgeClass($transaction->status) }}">
                                    {{ $transaction->status_label }}
                                </span>
                            </td>
                            <td>
                                <div>{{ $transaction->created_at->format('Y-m-d H:i') }}</div>
                                <small class="text-muted">{{ $transaction->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button onclick="viewTransaction({{ $transaction->id }})" 
                                            class="btn btn-sm btn-info" title="{{ __('gateways.View Details') }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if($transaction->status == 'completed')
                                    <button onclick="processRefund({{ $transaction->id }})" 
                                            class="btn btn-sm btn-warning" title="{{ __('gateways.Process Refund') }}">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    @endif
                                    @if($transaction->status == 'pending')
                                    <button onclick="verifyTransaction({{ $transaction->id }})" 
                                            class="btn btn-sm btn-secondary" title="{{ __('gateways.Verify Status') }}">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-receipt fa-3x mb-3"></i>
                                    <p class="mb-0">{{ __('loyalty.No transactions found') }}</p>
                                    <small>{{ __('gateways.Transactions will appear here once payments are processed') }}</small>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
        {{ $transactions->appends(request()->query())->links() }}
    </div>
</div>

<!-- Transaction Detail Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('gateways.Transaction Details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="transactionDetails">
                <!-- Transaction details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.Close') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
@php
function getStatusBadgeClass($status) {
    return match($status) {
        'completed' => 'bg-success',
        'pending' => 'bg-warning',
        'failed' => 'bg-danger',
        'refunded' => 'bg-secondary',
        default => 'bg-info',
    };
}
@endphp

<script>
function filterTransactions() {
    const status = document.getElementById('statusFilter').value;
    const type = document.getElementById('typeFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    let url = '{{ route("admin.payment-gateways.transactions", $gateway->id) }}?';
    if (status) url += 'status=' + status + '&';
    if (type) url += 'transaction_type=' + type + '&';
    if (dateFrom) url += 'date_from=' + dateFrom + '&';
    if (dateTo) url += 'date_to=' + dateTo;
    
    window.location.href = url;
}

function viewTransaction(id) {
    fetch(`/api/admin/transactions/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const details = data.transaction;
                const modalBody = document.getElementById('transactionDetails');
                modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>{{ __('gateways.Transaction Information') }}</h6>
                            <table class="table table-sm">
                                <tr><td>{{ __('gateways.Internal Reference') }}</td><td><strong>${details.internal_reference}</strong></td></tr>
                                <tr><td>{{ __('gateways.External Reference') }}</td><td>${details.reference_id || '-'}</td></tr>
                                <tr><td>{{ __('common.Type') }}</td><td>${details.transaction_type_label}</td></tr>
                                <tr><td>{{ __('common.Amount') }}</td><td><strong>${number_format(details.amount, 2)} ${details.currency}</strong></td></tr>
                                <tr><td>{{ __('common.Status') }}</td><td><span class="badge ${getStatusBadgeClass(details.status)}">${details.status_label}</span></td></tr>
                                <tr><td>{{ __('gateways.Created At') }}</td><td>${details.created_at}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>{{ __('gateways.Customer Information') }}</h6>
                            <table class="table table-sm">
                                <tr><td>{{ __('common.Phone') }}</td><td>${details.customer_phone || '-'}</td></tr>
                                <tr><td>{{ __('common.Email') }}</td><td>${details.customer_email || '-'}</td></tr>
                            </table>
                        </div>
                    </div>
                    @if(details.metadata)
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>{{ __('gateways.Additional Information') }}</h6>
                            <pre>{{ json_encode(details.metadata, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                    @endif
                `;
                
                const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
                modal.show();
            } else {
                alert('{{ __('gateways.Error loading transaction details') }}');
            }
        })
        .catch(error => {
            alert('{{ __('gateways.Error loading transaction details:') }} ' + error);
        });
}

function processRefund(id) {
    if (!confirm('{{ __('gateways.Are you sure you want to process a refund for this transaction?') }}')) {
        return;
    }
    
    const amount = prompt('{{ __('gateways.Enter refund amount (leave empty for full refund):') }}');
    
    fetch('/api/payments/refund', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            transaction_id: id,
            amount: amount || null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('{{ __('gateways.Refund processed successfully') }}');
            location.reload();
        } else {
            alert('{{ __('gateways.Refund failed:') }} ' + (data.error || data.message));
        }
    })
    .catch(error => {
        alert('{{ __('gateways.Error processing refund:') }} ' + error);
    });
}

function verifyTransaction(id) {
    if (!confirm('{{ __('gateways.Do you want to verify the status of this transaction?') }}')) {
        return;
    }
    
    fetch('/api/payments/verify', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            transaction_id: id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('{{ __('gateways.Transaction status verified:') }} ' + data.transaction.status_label);
            location.reload();
        } else {
            alert('{{ __('gateways.Verification failed:') }} ' + data.message);
        }
    })
    .catch(error => {
        alert('{{ __('gateways.Error verifying transaction:') }} ' + error);
    });
}
</script>
@endpush