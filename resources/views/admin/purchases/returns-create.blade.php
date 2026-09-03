@extends('layouts.admin')

@section('title')
    {{ __('purchases.Create Purchase Return') }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="erp-table-section">
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <h4 class="mb-0">{{ __('purchases.Create Purchase Return') }} — {{ $purchase->invoiceNumber }}</h4>
                    <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>{{ __('purchases.Back to Invoice') }}
                    </a>
                </div>

                <div class="p-16">
                    {{-- Purchase Info --}}
                    <div class="alert alert-info mb-4">
                        <strong>{{ __('purchases.Supplier:') }}</strong> {{ $purchase->party->name ?? '—' }} |
                        <strong>{{ __('purchases.Invoice:') }}</strong> {{ $purchase->invoiceNumber }} |
                        <strong>{{ __('purchases.Date:') }}</strong> {{ $purchase->purchaseDate ? \Carbon\Carbon::parse($purchase->purchaseDate)->format('d/m/Y') : '—' }}
                    </div>

                    <form id="returnForm" onsubmit="return false;">
                        @csrf
                        <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('common.Product') }}</th>
                                        <th>{{ __('purchases.Batch') }}</th>
                                        <th class="text-center">{{ __('purchases.Purchased Qty') }}</th>
                                        <th class="text-center">{{ __('purchases.Already Returned') }}</th>
                                        <th class="text-center">{{ __('purchases.Returnable') }}</th>
                                        <th class="text-center">{{ __('purchases.Return Qty') }}</th>
                                        <th class="text-end">{{ __('purchases.Unit Price') }}</th>
                                        <th class="text-end">{{ __('purchases.Credit Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($returnableItems as $index => $item)
                                    @if($item['returnable_qty'] > 0)
                                    <tr>
                                        <td>
                                            <strong>{{ $item['product_name'] }}</strong>
                                            <input type="hidden" name="items[{{ $index }}][purchase_detail_id]" value="{{ $item['purchase_detail_id'] }}">
                                        </td>
                                        <td>{{ $item['batch_no'] ?? '—' }}</td>
                                        <td class="text-center">{{ $item['purchased_qty'] }}</td>
                                        <td class="text-center">{{ $item['returned_qty'] }}</td>
                                        <td class="text-center fw-bold text-primary">{{ $item['returnable_qty'] }}</td>
                                        <td class="text-center">
                                            <input type="number" name="items[{{ $index }}][return_qty]"
                                                   class="form-control form-control-sm text-center return-qty w-80"
                                                   class="d-inline-block"
                                                   value="0" min="0" max="{{ $item['returnable_qty'] }}"
                                                   data-unit-price="{{ $item['unit_price'] }}"
                                                   data-returnable="{{ $item['returnable_qty'] }}"
                                                   onchange="validateReturnQty(this); recalculateReturn()"
                                                   oninput="recalculateReturn()">
                                        </td>
                                        <td class="text-end">{{ number_format($item['unit_price'], 2) }}</td>
                                        <td class="text-end return-line-total">0.00</td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Return Totals --}}
                        <div class="row justify-content-end">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between fw-bold fs-5">
                                            <span>{{ __('purchases.Total Credit') }}</span>
                                            <span id="totalCredit" class="text-danger">0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Reason --}}
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('common.Reason') }}</label>
                                <textarea name="reason" class="form-control" rows="2" placeholder="{{ __('purchases.Reason for return...') }}"></textarea>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-outline-secondary">{{ __('common.Cancel') }}</a>
                            <button type="button" class="btn btn-warning" id="processReturnBtn" onclick="confirmReturn()" disabled>
                                <i class="fas fa-undo me-1"></i>{{ __('purchases.Process Return') }}
                            </button>
                            <div id="returnWarning" class="w-100 text-end text-muted mt-2" class="d-none">
                                <small><i class="fas fa-info-circle me-1"></i>{{ __('purchases.Enter a return quantity above to enable this button.') }}</small>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Confirmation Modal --}}
<div class="modal fade" id="confirmReturnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('purchases.Confirm Purchase Return') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('purchases.Are you sure you want to process this purchase return? This will deduct stock and update the supplier balance.') }}</p>
                <p><strong>{{ __('purchases.Total Credit:') }}</strong> <span id="confirmCredit" class="text-danger"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.Cancel') }}</button>
                <button type="button" class="btn btn-warning" onclick="submitReturn()" id="confirmReturnBtn">
                    <i class="fas fa-check me-1"></i>{{ __('common.Confirm') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const baseUrl = '{{ url("admin") }}';
    const purchaseId = {{ $purchase->id }};

    function validateReturnQty(input) {
        const max = parseInt(input.dataset.returnable);
        if (parseInt(input.value) > max) {
            input.value = max;
            toastr.warning(`{{ __('purchases.Maximum returnable quantity is') }} ${max}`);
        }
        if (parseInt(input.value) < 0) {
            input.value = 0;
        }
    }

    function recalculateReturn() {
        let total = 0;
        let hasQty = false;
        document.querySelectorAll('.return-qty').forEach(input => {
            const qty = parseInt(input.value) || 0;
            const price = parseFloat(input.dataset.unitPrice) || 0;
            const lineTotal = qty * price;
            input.closest('tr').querySelector('.return-line-total').textContent = lineTotal.toFixed(2);
            total += lineTotal;
            if (qty > 0) hasQty = true;
        });
        document.getElementById('totalCredit').textContent = total.toFixed(2);

        // Real-time enable/disable of submit button
        const submitBtn = document.getElementById('processReturnBtn');
        const warning = document.getElementById('returnWarning');
        if (hasQty) {
            submitBtn.disabled = false;
            warning.style.display = 'none';
        } else {
            submitBtn.disabled = true;
            warning.style.display = 'block';
        }
    }

    function confirmReturn() {
        const items = document.querySelectorAll('.return-qty');
        let hasQty = false;
        let credit = 0;
        items.forEach(i => {
            const qty = parseInt(i.value) || 0;
            if (qty > 0) hasQty = true;
            credit += qty * (parseFloat(i.dataset.unitPrice) || 0);
        });
        if (!hasQty) {
            toastr.error('{{ __('purchases.Please enter return quantity for at least one item.') }}');
            return;
        }
        document.getElementById('confirmCredit').textContent = document.getElementById('totalCredit').textContent;
        new bootstrap.Modal(document.getElementById('confirmReturnModal')).show();
    }

    function submitReturn() {
        const btn = document.getElementById('confirmReturnBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>{{ __('common.Processing...') }}';

        const items = [];
        document.querySelectorAll('.return-qty').forEach(input => {
            const qty = parseInt(input.value) || 0;
            if (qty > 0) {
                const row = input.closest('tr');
                items.push({
                    purchase_detail_id: row.querySelector('[name*="purchase_detail_id"]').value,
                    return_qty: qty,
                });
            }
        });

        fetch(`${baseUrl}/purchases/returns/store`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ purchase_id: purchaseId, items, reason: document.querySelector('[name="reason"]').value }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                bootstrap.Modal.getInstance(document.getElementById('confirmReturnModal'))?.hide();
                setTimeout(() => { window.location.href = data.redirect; }, 1000);
            } else {
                toastr.error(data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-1"></i>{{ __('common.Confirm') }}';
            }
        })
        .catch(() => {
            toastr.error('{{ __('purchases.Error processing return.') }}');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-1"></i>{{ __('common.Confirm') }}';
        });
    }
</script>
@endpush
