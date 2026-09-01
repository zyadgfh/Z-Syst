@extends('layouts.master')

@section('title')
    {{ __('Edit Purchase Invoice') }} #{{ $purchase->invoiceNumber }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="erp-table-section">
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <h4 class="mb-0">{{ __('Edit Purchase Invoice') }} #{{ $purchase->invoiceNumber }}</h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>{{ __('Back') }}
                        </a>
                    </div>
                </div>

                <div class="p-16">
                    <form id="purchaseForm">
                        @csrf
                        @method('PUT')

                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Supplier') }} <span class="text-danger">*</span></label>
                                <select name="party_id" id="party_id" class="form-select" required>
                                    <option value="">{{ __('Select Supplier') }}</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected($purchase->party_id == $supplier->id)>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Branch') }}</label>
                                <select name="branch_id" id="branch_id" class="form-select">
                                    <option value="">{{ __('Select Branch') }}</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected($purchase->branch_id == $branch->id)>{{ $branch->branch_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Invoice Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="purchaseDate" id="purchaseDate" class="form-control" value="{{ $purchase->purchaseDate ? \Carbon\Carbon::parse($purchase->purchaseDate)->format('Y-m-d') : '' }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Payment Type') }}</label>
                                <select name="paymentType" id="paymentType" class="form-select">
                                    <option value="Cash" @selected($purchase->paymentType == 'Cash')>{{ __('Cash') }}</option>
                                    <option value="Card" @selected($purchase->paymentType == 'Card')>{{ __('Card') }}</option>
                                    <option value="Bank Transfer" @selected($purchase->paymentType == 'Bank Transfer')>{{ __('Bank Transfer') }}</option>
                                </select>
                            </div>
                        </div>

                        {{-- Barcode Search --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Add Item (Barcode or Search)') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                    <input type="text" id="barcodeInput" class="form-control" placeholder="{{ __('Scan barcode or search...') }}">
                                    <button type="button" class="btn btn-outline-primary" onclick="searchByBarcode()"><i class="fas fa-search"></i></button>
                                </div>
                                <div id="productSearchResults" class="list-group mt-1" style="display:none; max-height:200px; overflow-y:auto;"></div>
                            </div>
                        </div>

                        {{-- Items Table --}}
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:40px">#</th>
                                        <th>{{ __('Product') }}</th>
                                        <th style="width:80px">{{ __('Batch') }}</th>
                                        <th style="width:100px">{{ __('Qty') }}</th>
                                        <th style="width:130px">{{ __('Purchase Price') }}</th>
                                        <th style="width:130px">{{ __('Total') }}</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    @foreach($purchase->details as $index => $detail)
                                    <tr id="item-{{ $index }}" class="item-row">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <input type="hidden" name="products[{{ $index }}][product_id]" value="{{ $detail->product_id }}">
                                            <strong>{{ $detail->product->productName ?? '—' }}</strong>
                                        </td>
                                        <td><input type="text" name="products[{{ $index }}][batch_no]" class="form-control form-control-sm" value="{{ $detail->batch_no ?? '' }}"></td>
                                        <td><input type="number" name="products[{{ $index }}][quantities]" class="form-control form-control-sm qty-input" value="{{ $detail->quantities }}" min="1" onchange="recalculateLine(this)" oninput="recalculateLine(this)"></td>
                                        <td><input type="number" name="products[{{ $index }}][purchase_with_tax]" class="form-control form-control-sm price-input" value="{{ $detail->purchase_with_tax }}" min="0" step="0.01" onchange="recalculateLine(this)" oninput="recalculateLine(this)"></td>
                                        <td><span class="line-total fw-bold">{{ number_format($detail->purchase_with_tax * $detail->quantities, 2) }}</span></td>
                                        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('item-{{ $index }}')"><i class="fas fa-trash"></i></button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Totals --}}
                        <div class="row justify-content-end">
                            <div class="col-md-5">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2"><span>{{ __('Subtotal') }}</span><span id="subtotal">0.00</span></div>
                                        <div class="d-flex justify-content-between mb-2"><span>{{ __('Discount') }}</span><input type="number" name="discountAmount" id="discountAmount" class="form-control form-control-sm text-end" style="width:120px" value="{{ $purchase->discountAmount }}" min="0" step="0.01" onchange="recalculateTotals()"></div>
                                        <div class="d-flex justify-content-between mb-2"><span>{{ __('Tax') }}</span><input type="number" name="tax_amount" id="taxAmount" class="form-control form-control-sm text-end" style="width:120px" value="{{ $purchase->tax_amount }}" min="0" step="0.01" onchange="recalculateTotals()"></div>
                                        <hr>
                                        <div class="d-flex justify-content-between mb-2 fw-bold fs-5"><span>{{ __('Grand Total') }}</span><span id="grandTotal" class="text-primary">0.00</span></div>
                                        <div class="d-flex justify-content-between mb-2"><span>{{ __('Paid Amount') }}</span><input type="number" name="paidAmount" id="paidAmount" class="form-control form-control-sm text-end" style="width:120px" value="{{ $purchase->paidAmount }}" min="0" step="0.01" onchange="recalculateTotals()"></div>
                                        <div class="d-flex justify-content-between fw-bold"><span>{{ __('Remaining') }}</span><span id="dueAmount" class="text-danger">0.00</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Note') }}</label>
                                <textarea name="note" id="note" class="form-control" rows="2">{{ $purchase->note }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                            <button type="button" class="btn btn-primary btn-lg" onclick="confirmSave()">
                                <i class="fas fa-save me-2"></i>{{ __('Update Invoice') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ __('Confirm Update') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><p>{{ __('Are you sure you want to update this purchase invoice?') }}</p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="submitPurchase()" id="confirmBtn"><i class="fas fa-check me-1"></i>{{ __('Confirm') }}</button>
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
    let itemCounter = {{ $purchase->details->count() }};

    const barcodeInput = document.getElementById('barcodeInput');
    barcodeInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); searchByBarcode(); } });
    barcodeInput.addEventListener('input', function() { if (this.value.trim().length >= 2) searchProducts(this.value.trim()); else document.getElementById('productSearchResults').style.display = 'none'; });

    function searchByBarcode() {
        const barcode = barcodeInput.value.trim();
        if (!barcode) return;
        fetch(`${baseUrl}/purchases/search-barcode`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ barcode }) })
        .then(r => r.json()).then(data => { if (data.success) { addItem(data.data); barcodeInput.value = ''; barcodeInput.focus(); document.getElementById('productSearchResults').style.display = 'none'; } else { toastr.warning(data.message); } });
    }

    function searchProducts(query) {
        fetch(`${baseUrl}/purchases/search-products`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ query }) })
        .then(r => r.json()).then(data => {
            const c = document.getElementById('productSearchResults');
            if (data.success && data.data.length > 0) { c.innerHTML = data.data.map(p => `<button type="button" class="list-group-item list-group-item-action" onclick='selectProduct(${JSON.stringify(p).replace(/'/g, "\\'")})'><strong>${p.productName}</strong><br><small>${p.productCode||''} | ${p.purchase_without_tax}</small></button>`).join(''); c.style.display = 'block'; }
            else { c.innerHTML = '<div class="list-group-item text-muted">No products found.</div>'; c.style.display = 'block'; }
        });
    }

    function selectProduct(p) { addItem({ product_id: p.id, barcode: p.productCode||'', product_name: p.productName, purchase_price: p.purchase_without_tax }); document.getElementById('productSearchResults').style.display = 'none'; barcodeInput.value = ''; barcodeInput.focus(); }

    function addItem(product) {
        itemCounter++;
        const rowId = `item-${itemCounter}`;
        const html = `<tr id="${rowId}" class="item-row"><td>${itemCounter}</td><td><input type="hidden" name="products[${itemCounter}][product_id]" value="${product.product_id}"><strong>${product.product_name}</strong></td><td><input type="text" name="products[${itemCounter}][batch_no]" class="form-control form-control-sm"></td><td><input type="number" name="products[${itemCounter}][quantities]" class="form-control form-control-sm qty-input" value="1" min="1" onchange="recalculateLine(this)" oninput="recalculateLine(this)"></td><td><input type="number" name="products[${itemCounter}][purchase_with_tax]" class="form-control form-control-sm price-input" value="${product.purchase_price||0}" min="0" step="0.01" onchange="recalculateLine(this)" oninput="recalculateLine(this)"></td><td><span class="line-total fw-bold">0.00</span></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('${rowId}')"><i class="fas fa-trash"></i></button></td></tr>`;
        document.getElementById('itemsBody').insertAdjacentHTML('beforeend', html);
        recalculateLine(document.getElementById(rowId).querySelector('.qty-input'));
    }

    function removeItem(rowId) { document.getElementById(rowId)?.remove(); recalculateTotals(); }

    function recalculateLine(el) {
        const row = el.closest('tr');
        const total = (parseFloat(row.querySelector('.qty-input').value)||0) * (parseFloat(row.querySelector('.price-input').value)||0);
        row.querySelector('.line-total').textContent = total.toFixed(2);
        recalculateTotals();
    }

    function recalculateTotals() {
        let subtotal = 0;
        document.querySelectorAll('.item-row').forEach(r => { subtotal += parseFloat(r.querySelector('.line-total')?.textContent)||0; });
        const discount = parseFloat(document.getElementById('discountAmount').value)||0;
        const tax = parseFloat(document.getElementById('taxAmount').value)||0;
        const grand = subtotal - discount + tax;
        const paid = parseFloat(document.getElementById('paidAmount').value)||0;
        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('grandTotal').textContent = grand.toFixed(2);
        document.getElementById('dueAmount').textContent = Math.max(0, grand - paid).toFixed(2);
    }

    function confirmSave() { new bootstrap.Modal(document.getElementById('confirmModal')).show(); }

    function submitPurchase() {
        const btn = document.getElementById('confirmBtn'); btn.disabled = true;
        const formData = { party_id: document.getElementById('party_id').value, branch_id: document.getElementById('branch_id').value||null, purchaseDate: document.getElementById('purchaseDate').value, paymentType: document.getElementById('paymentType').value, discountAmount: document.getElementById('discountAmount').value, tax_amount: document.getElementById('taxAmount').value, paidAmount: document.getElementById('paidAmount').value, note: document.getElementById('note').value, products: [] };
        document.querySelectorAll('.item-row').forEach(r => { formData.products.push({ product_id: r.querySelector('[name*="product_id"]').value, batch_no: r.querySelector('[name*="batch_no"]').value, quantities: r.querySelector('[name*="quantities"]').value, purchase_with_tax: r.querySelector('[name*="purchase_with_tax"]').value, purchase_without_tax: r.querySelector('[name*="purchase_with_tax"]').value }); });
        fetch(`${baseUrl}/purchases/${purchaseId}/update-ajax`, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(formData) })
        .then(r => r.json()).then(data => { if (data.success) { toastr.success(data.message); bootstrap.Modal.getInstance(document.getElementById('confirmModal'))?.hide(); setTimeout(() => window.location.href = data.redirect, 1000); } else { toastr.error(data.message); btn.disabled = false; } });
    }

    document.addEventListener('click', e => { if (!e.target.closest('#barcodeInput') && !e.target.closest('#productSearchResults')) document.getElementById('productSearchResults').style.display = 'none'; });
    recalculateTotals();
</script>
@endpush
