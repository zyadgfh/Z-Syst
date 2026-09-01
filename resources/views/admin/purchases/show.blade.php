@extends('layouts.master')

@section('title')
    {{ __('Purchase Invoice') }} #{{ $data->invoiceNumber }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="erp-table-section">
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <h4 class="mb-0">{{ __('Purchase Invoice') }} #{{ $data->invoiceNumber }}</h4>
                    <div class="d-flex gap-2">
                        @if(in_array($data->status, ['received', 'returned_partially']) && auth()->user()->can('purchases-create'))
                        <a href="{{ route('admin.purchases.returns.create') }}?purchase_id={{ $data->id }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-undo me-1"></i>{{ __('Return') }}
                        </a>
                        @endif
                        @if($data->status === 'received' && auth()->user()->can('purchases-edit'))
                        <a href="{{ route('admin.purchases.edit', $data->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit me-1"></i>{{ __('Edit') }}
                        </a>
                        @endif
                        <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>{{ __('Back') }}
                        </a>
                    </div>
                </div>

                <div class="p-16">
                    <div class="row g-4">
                        {{-- Invoice Info --}}
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header"><strong>{{ __('Invoice Information') }}</strong></div>
                                <div class="card-body">
                                    <table class="table table-borderless mb-0">
                                        <tr><td class="text-muted" style="width:150px">{{ __('Invoice Number') }}</td><td><strong>{{ $data->invoiceNumber }}</strong></td></tr>
                                        <tr><td class="text-muted">{{ __('Supplier') }}</td><td>{{ $data->party->name ?? '—' }}</td></tr>
                                        <tr><td class="text-muted">{{ __('Branch') }}</td><td>{{ $data->branch->branch_name ?? '—' }}</td></tr>
                                        <tr><td class="text-muted">{{ __('Date') }}</td><td>{{ $data->purchaseDate ? \Carbon\Carbon::parse($data->purchaseDate)->format('d/m/Y H:i') : '—' }}</td></tr>
                                        <tr><td class="text-muted">{{ __('Created By') }}</td><td>{{ $data->user->name ?? '—' }}</td></tr>
                                        <tr>
                                            <td class="text-muted">{{ __('Status') }}</td>
                                            <td>
                                                @php
                                                    $statusColors = ['received' => 'success', 'canceled' => 'danger', 'returned_partially' => 'warning', 'returned_fully' => 'info'];
                                                @endphp
                                                <span class="badge bg-{{ $statusColors[$data->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $data->status)) }}</span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Financial Summary --}}
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header"><strong>{{ __('Financial Summary') }}</strong></div>
                                <div class="card-body">
                                    <table class="table table-borderless mb-0">
                                        <tr><td class="text-muted" style="width:150px">{{ __('Subtotal') }}</td><td class="text-end">{{ number_format($data->totalAmount + $data->discountAmount - $data->tax_amount, 2) }}</td></tr>
                                        <tr><td class="text-muted">{{ __('Discount') }}</td><td class="text-end">{{ number_format($data->discountAmount, 2) }}</td></tr>
                                        <tr><td class="text-muted">{{ __('Tax') }}</td><td class="text-end">{{ number_format($data->tax_amount, 2) }}</td></tr>
                                        <tr class="border-top"><td class="fw-bold">{{ __('Grand Total') }}</td><td class="text-end fw-bold fs-5">{{ number_format($data->totalAmount, 2) }}</td></tr>
                                        <tr><td class="text-muted">{{ __('Paid') }}</td><td class="text-end text-success">{{ number_format($data->paidAmount, 2) }}</td></tr>
                                        <tr><td class="fw-bold">{{ __('Remaining') }}</td><td class="text-end fw-bold {{ $data->dueAmount > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($data->dueAmount, 2) }}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Items Table --}}
                    <div class="card mt-4">
                        <div class="card-header"><strong>{{ __('Invoice Items') }}</strong></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Product') }}</th>
                                            <th>{{ __('Barcode') }}</th>
                                            <th>{{ __('Batch') }}</th>
                                            <th class="text-end">{{ __('Qty') }}</th>
                                            <th class="text-end">{{ __('Price') }}</th>
                                            <th class="text-end">{{ __('Discount') }}</th>
                                            <th class="text-end">{{ __('Total') }}</th>
                                            <th>{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data->details as $index => $detail)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $detail->product->productName ?? '—' }}</td>
                                            <td><code>{{ $detail->product->barcode ?? $detail->product->productCode ?? '—' }}</code></td>
                                            <td>
                                                <code>{{ $detail->batch_no ?? '—' }}</code>
                                                @if($detail->expire_date)
                                                    <br><small class="text-muted">{{ $detail->expire_date }}</small>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ $detail->quantities }}</td>
                                            <td class="text-end">{{ number_format($detail->purchase_with_tax, 2) }}</td>
                                            <td class="text-end">0.00</td>
                                            <td class="text-end fw-bold">{{ number_format($detail->purchase_with_tax * $detail->quantities, 2) }}</td>
                                            <td>
                                                @if($detail->product_id)
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="printBatchBarcode({{ $detail->product_id }}, '{{ addslashes($detail->batch_no ?? '') }}', '{{ addslashes($detail->expire_date ?? '') }}', {{ $detail->quantities }})" title="{{ __('Print Barcode') }}">
                                                        <i class="fas fa-barcode"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Purchase Returns --}}
                    @if($data->purchaseReturns->count() > 0)
                    <div class="card mt-4">
                        <div class="card-header"><strong>{{ __('Purchase Returns') }}</strong></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('Return #') }}</th>
                                            <th>{{ __('Date') }}</th>
                                            <th>{{ __('Credit Amount') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data->purchaseReturns as $return)
                                        <tr>
                                            <td>{{ $return->invoice_no }}</td>
                                            <td>{{ $return->return_date ? \Carbon\Carbon::parse($return->return_date)->format('d/m/Y') : '—' }}</td>
                                            <td class="text-danger">{{ number_format($return->credit_amount ?? $return->total_amount ?? 0, 2) }}</td>
                                            <td><span class="badge bg-{{ ($return->status ?? 'completed') === 'completed' ? 'success' : 'warning' }}">{{ ucfirst($return->status ?? 'completed') }}</span></td>
                                            <td><a href="{{ route('admin.purchases.returns.show', $return->id) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($data->note)
                    <div class="card mt-4">
                        <div class="card-header"><strong>{{ __('Notes') }}</strong></div>
                        <div class="card-body">{{ $data->note }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const baseUrl = '{{ url("admin") }}';

    /**
     * Print batch-specific barcode for a purchase line item.
     * Opens a form submission to generate and download the PDF.
     */
    function printBatchBarcode(productId, batchNo, expireDate, qty) {
        // Determine the quantity to print (use batch qty or default to 4)
        const printQty = Math.min(Math.max(qty || 4, 1), 200);

        // Build form data
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${baseUrl}/items/${productId}/print-barcode`;
        form.style.display = 'none';

        // CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);

        // Quantity
        const qtyInput = document.createElement('input');
        qtyInput.type = 'hidden';
        qtyInput.name = 'quantity';
        qtyInput.value = printQty;
        form.appendChild(qtyInput);

        // Size — standard
        const sizeInput = document.createElement('input');
        sizeInput.type = 'hidden';
        sizeInput.name = 'size';
        sizeInput.value = 'standard';
        form.appendChild(sizeInput);

        // Show batch and expiry on label
        const showBatch = document.createElement('input');
        showBatch.type = 'hidden';
        showBatch.name = 'show_batch';
        showBatch.value = '1';
        form.appendChild(showBatch);

        const showExpiry = document.createElement('input');
        showExpiry.type = 'hidden';
        showExpiry.name = 'show_expiry';
        showExpiry.value = '1';
        form.appendChild(showExpiry);

        const showCode = document.createElement('input');
        showCode.type = 'hidden';
        showCode.name = 'show_code';
        showCode.value = '1';
        form.appendChild(showCode);

        // Batch ID — if we have a stock_id we could pass it, but we use batch_no instead
        // The printBarcode controller resolves the batch from the product's stock batches

        document.body.appendChild(form);
        form.submit();

        // Clean up after a short delay
        setTimeout(() => form.remove(), 3000);

        // Show notification
        if (typeof toastr !== 'undefined') {
            toastr.info(`{{ __('Generating') }} ${printQty} {{ __('barcode labels') }}...`, `{{ __('Print Barcode') }}`);
        }
    }
</script>
@endpush
@endsection
