@extends('layouts.master')

@section('title')
    {{ __('purchases.Supplier Dashboard') }} — {{ $supplier->name }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="erp-table-section">
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <h4 class="mb-0"><i class="fas fa-truck me-2"></i>{{ $supplier->name }}</h4>
                    <div class="d-flex gap-2">
                        @if(auth()->user()->can('purchases-create'))
                        <button class="btn btn-success btn-sm" onclick="showPaymentModal()">
                            <i class="fas fa-money-bill me-1"></i>{{ __('purchases.Record Payment') }}
                        </button>
                        @endif
                        <a href="{{ route('admin.purchases.index') }}?party_id={{ $supplier->id }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-receipt me-1"></i>{{ __('purchases.View Purchases') }}
                        </a>
                    </div>
                </div>

                <div class="p-16">
                    {{-- Summary Cards --}}
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="text-muted mb-1">{{ __('purchases.Current Balance') }}</div>
                                    <h3 class="fw-bold {{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($balance, 2) }}
                                    </h3>
                                    <small class="text-muted">{{ __('purchases.Amount owed to supplier') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="text-muted mb-1">{{ __('purchases.Total Purchases') }}</div>
                                    <h3 class="fw-bold text-primary">{{ number_format($summary['total_purchases'], 2) }}</h3>
                                    <small class="text-muted">{{ $summary['transaction_count'] }} {{ __('gateways.transactions') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="text-muted mb-1">{{ __('purchases.Total Returns') }}</div>
                                    <h3 class="fw-bold text-warning">{{ number_format($summary['total_returns'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="text-muted mb-1">{{ __('purchases.Total Payments') }}</div>
                                    <h3 class="fw-bold text-success">{{ number_format($summary['total_payments'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Supplier Info --}}
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header"><strong>{{ __('purchases.Supplier Information') }}</strong></div>
                                <div class="card-body">
                                    <table class="table table-borderless mb-0">
                                        <tr><td class="text-muted" class="w-120">{{ __('common.Name') }}</td><td>{{ $supplier->name }}</td></tr>
                                        <tr><td class="text-muted">{{ __('common.Phone') }}</td><td>{{ $supplier->phone ?? '—' }}</td></tr>
                                        <tr><td class="text-muted">{{ __('common.Email') }}</td><td>{{ $supplier->email ?? '—' }}</td></tr>
                                        <tr><td class="text-muted">{{ __('common.Address') }}</td><td>{{ $supplier->address ?? '—' }}</td></tr>
                                        <tr><td class="text-muted">{{ __('common.Status') }}</td><td><span class="badge bg-{{ $supplier->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($supplier->status ?? 'active') }}</span></td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Recent Purchases --}}
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header"><strong>{{ __('products.Recent Purchases') }}</strong></div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead><tr><th>{{ __('common.Invoice') }}</th><th>{{ __('common.Date') }}</th><th class="text-end">{{ __('common.Total') }}</th><th class="text-end">{{ __('purchases.Due') }}</th></tr></thead>
                                            <tbody>
                                                @forelse($recentPurchases as $p)
                                                <tr>
                                                    <td><a href="{{ route('admin.purchases.show', $p->id) }}">{{ $p->invoiceNumber }}</a></td>
                                                    <td>{{ $p->purchaseDate ? \Carbon\Carbon::parse($p->purchaseDate)->format('d/m/Y') : '—' }}</td>
                                                    <td class="text-end">{{ number_format($p->totalAmount, 2) }}</td>
                                                    <td class="text-end {{ $p->dueAmount > 0 ? 'text-danger' : '' }}">{{ number_format($p->dueAmount, 2) }}</td>
                                                </tr>
                                                @empty
                                                <tr><td colspan="4" class="text-center text-muted py-3">{{ __('purchases.No purchases yet.') }}</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Ledger Timeline --}}
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong><i class="fas fa-history me-2"></i>{{ __('purchases.Transaction Ledger') }}</strong>
                            <span class="badge bg-secondary">{{ $ledger->count() }} {{ __('purchases.entries') }}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('common.Date') }}</th>
                                            <th>{{ __('common.Type') }}</th>
                                            <th>{{ __('common.Description') }}</th>
                                            <th>{{ __('common.Reference') }}</th>
                                            <th class="text-end">{{ __('purchases.Debit') }}</th>
                                            <th class="text-end">{{ __('purchases.Credit') }}</th>
                                            <th class="text-end">{{ __('loyalty.Balance') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($ledger as $entry)
                                        <tr>
                                            <td><small>{{ $entry->created_at->format('d/m/Y H:i') }}</small></td>
                                            <td><span class="badge bg-light text-dark">{{ $entry->transaction_type_label }}</span></td>
                                            <td>{{ $entry->description }}</td>
                                            <td><code>{{ $entry->invoice_number ?? '—' }}</code></td>
                                            <td class="text-end {{ $entry->debit > 0 ? 'text-danger' : '' }}">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '—' }}</td>
                                            <td class="text-end {{ $entry->credit > 0 ? 'text-success' : '' }}">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '—' }}</td>
                                            <td class="text-end fw-bold">{{ number_format($entry->balance_after, 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('purchases.No transactions found.') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Payment Modal --}}
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('purchases.Record Payment to Supplier') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('purchases.Supplier') }}</label>
                        <input type="text" class="form-control" value="{{ $supplier->name }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('purchases.Current Balance') }}</label>
                        <input type="text" class="form-control" value="{{ number_format($balance, 2) }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('purchases.Payment Amount') }} <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="paymentAmount" class="form-control" min="0.01" step="0.01" max="{{ $balance }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('purchases.Payment Method') }}</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">{{ __('purchases.Cash') }}</option>
                            <option value="bank_transfer">{{ __('purchases.Bank Transfer') }}</option>
                            <option value="card">{{ __('purchases.Card') }}</option>
                            <option value="cheque">{{ __('purchases.Cheque') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('purchases.Payment Date') }}</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.Cancel') }}</button>
                <button type="button" class="btn btn-success" onclick="submitPayment()" id="submitPaymentBtn">
                    <i class="fas fa-check me-1"></i>{{ __('purchases.Record Payment') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const supplierId = {{ $supplier->id }};

    function showPaymentModal() {
        new bootstrap.Modal(document.getElementById('paymentModal')).show();
    }

    function submitPayment() {
        const btn = document.getElementById('submitPaymentBtn');
        const form = document.getElementById('paymentForm');
        const formData = new FormData(form);

        if (!formData.get('amount') || parseFloat(formData.get('amount')) <= 0) {
            toastr.error('{{ __('purchases.Please enter a valid payment amount.') }}');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>{{ __('common.Processing...') }}';

        fetch(`{{ url("admin/suppliers") }}/${supplierId}/payment`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                bootstrap.Modal.getInstance(document.getElementById('paymentModal'))?.hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-1"></i>{{ __('purchases.Record Payment') }}';
            }
        })
        .catch(() => {
            toastr.error('{{ __('purchases.Error recording payment.') }}');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-1"></i>{{ __('purchases.Record Payment') }}';
        });
    }
</script>
@endpush
