@extends('layouts.admin')

@section('title')
    {{ __('purchases.New Purchase Invoice') }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="erp-table-section">
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <h4 class="mb-0">{{ __('purchases.New Purchase Invoice') }}</h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>{{ __('purchases.Back to List') }}
                        </a>
                    </div>
                </div>

                <div class="p-16">
                    <form id="purchaseForm">
                        @csrf

                        {{-- Header: Supplier, Branch, Date --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('purchases.Supplier') }} <span class="text-danger">*</span></label>
                                <select name="party_id" id="party_id" class="form-select" required>
                                    <option value="">{{ __('purchases.Select Supplier') }}</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }} ({{ $supplier->phone ?? '' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('common.Branch') }}</label>
                                <select name="branch_id" id="branch_id" class="form-select">
                                    <option value="">{{ __('purchases.Select Branch') }}</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('purchases.Invoice Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="purchaseDate" id="purchaseDate" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('purchases.Payment Type') }}</label>
                                <select name="paymentType" id="paymentType" class="form-select">
                                    <option value="Cash">{{ __('purchases.Cash') }}</option>
                                    <option value="Card">{{ __('purchases.Card') }}</option>
                                    <option value="Bank Transfer">{{ __('purchases.Bank Transfer') }}</option>
                                </select>
                            </div>
                        </div>

                        {{-- Barcode Search --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('purchases.Scan Barcode or Search Product') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                    <input type="text" id="barcodeInput" class="form-control" placeholder="{{ __('purchases.Scan barcode or type product name...') }}" autofocus>
                                    <button type="button" class="btn btn-outline-primary" onclick="searchByBarcode()">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <div id="productSearchResults" class="list-group mt-1 dropdown-scroll"></div>
                            </div>
                        </div>

                        {{-- Items Table --}}
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="w-40">#</th>
                                        <th>{{ __('common.Product') }}</th>
                                        <th class="w-120">{{ __('products.Barcode') }}</th>
                                        <th class="w-80">{{ __('purchases.Batch') }}</th>
                                        <th class="w-100">{{ __('common.Qty') }}</th>
                                        <th class="w-130">{{ __('common.Purchase Price') }}</th>
                                        <th class="w-100">{{ __('purchases.Discount') }}</th>
                                        <th class="w-100">{{ __('common.Tax') }}</th>
                                        <th class="w-130">{{ __('common.Total') }}</th>
                                        <th class="w-50"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr id="emptyRow">
                                        <td colspan="10" class="text-center text-muted py-4">
                                            {{ __('purchases.No items added. Scan a barcode or search for a product.') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Totals Section --}}
                        <div class="row justify-content-end">
                            <div class="col-md-5">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>{{ __('purchases.Subtotal') }}</span>
                                            <span id="subtotal">0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>{{ __('purchases.Discount') }}</span>
                                            <input type="number" name="discountAmount" id="discountAmount" class="form-control form-control-sm text-end" class="w-120" value="0" min="0" step="0.01" onchange="recalculateTotals()">
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>{{ __('common.Tax') }}</span>
                                            <input type="number" name="tax_amount" id="taxAmount" class="form-control form-control-sm text-end" class="w-120" value="0" min="0" step="0.01" onchange="recalculateTotals()">
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between mb-2 fw-bold fs-5">
                                            <span>{{ __('purchases.Grand Total') }}</span>
                                            <span id="grandTotal" class="text-primary">0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>{{ __('purchases.Paid Amount') }}</span>
                                            <input type="number" name="paidAmount" id="paidAmount" class="form-control form-control-sm text-end" class="w-120" value="0" min="0" step="0.01" onchange="recalculateTotals()">
                                        </div>
                                        <div class="d-flex justify-content-between fw-bold {{ $errors->has('dueAmount') ? 'text-danger' : '' }}">
                                            <span>{{ __('purchases.Remaining / Debt') }}</span>
                                            <span id="dueAmount" class="text-danger">0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Note --}}
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('common.Note') }}</label>
                                <textarea name="note" id="note" class="form-control" rows="2" placeholder="{{ __('purchases.Optional notes...') }}"></textarea>
                            </div>
                        </div>

                        {{-- Save Button --}}
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">{{ __('common.Cancel') }}</a>
                            <button type="button" class="btn btn-primary btn-lg" onclick="confirmSave()" id="saveBtn">
                                <i class="fas fa-save me-2"></i>{{ __('purchases.Save Invoice') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Confirmation Modal --}}
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('purchases.Confirm Save') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.Cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="submitPurchase()" id="confirmBtn">
                    <i class="fas fa-check me-1"></i>{{ __('common.Confirm') }}
                </button>
            </div>
        </div>
    </div>
</div>

@-- Barcode Print Modal --
<div class="modal fade" id="barcodePrintModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-barcode me-2"></i>{{ __('purchases.Print Barcodes') }} — <span id="barcodeInvoiceNo"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ __('purchases.Select the number of labels to print for each item.') }}</p>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('common.Product') }}</th>
                                <th>{{ __('products.Barcode') }}</th>
                                <th>{{ __('purchases.Batch') }}</th>
                                <th>{{ __('purchases.Qty Purchased') }}</th>
                                <th>{{ __('purchases.Labels to Print') }}</th>
                            </tr>
                        </thead>
                        <tbody id="barcodePrintBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="skipBarcodePrint()">
                    {{ __('common.Skip') }}
                </button>
                <button type="button" class="btn btn-primary" onclick="printBarcodes()">
                    <i class="fas fa-print me-1"></i>{{ __('purchases.Print Labels') }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    #itemsTable tbody tr td { vertical-align: middle; }
    .item-row input { padding: 4px 8px; font-size: 13px; }
    .item-remove { color: #dc3545; cursor: pointer; border: none; background: none; }
    .item-remove:hover { color: #a71d2a; }
</style>
@endsection

@push('js')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const baseUrl = '{{ url("admin") }}';
    let itemCounter = 0;
    let products = @json($products);

    // Barcode / Product search
    const barcodeInput = document.getElementById('barcodeInput');
    barcodeInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchByBarcode();
        }
    });
    barcodeInput.addEventListener('input', function() {
        const q = this.value.trim();
        if (q.length >= 2) {
            searchProducts(q);
        } else {
            document.getElementById('productSearchResults').style.display = 'none';
        }
    });

    function searchByBarcode() {
        const barcode = barcodeInput.value.trim();
        if (!barcode) return;

        fetch(`${baseUrl}/purchases/search-barcode`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ barcode })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                addItem(data.data);
                barcodeInput.value = '';
                barcodeInput.focus();
                document.getElementById('productSearchResults').style.display = 'none';
            } else {
                toastr.warning(data.message || '{{ __('purchases.Product not found.') }}');
            }
        });
    }

    function searchProducts(query) {
        fetch(`${baseUrl}/purchases/search-products`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ query })
        })
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('productSearchResults');
            if (data.success && data.data.length > 0) {
                container.innerHTML = data.data.map(p => `
                    <button type="button" class="list-group-item list-group-item-action" onclick="selectProduct(${JSON.stringify(p).replace(/"/g, '&quot;')})">
                        <strong>${p.productName}</strong>
                        <br><small class="text-muted">${p.productCode || ''} | ${p.purchase_without_tax}</small>
                    </button>
                `).join('');
                container.style.display = 'block';
            } else {
                container.innerHTML = '<div class="list-group-item text-muted">{{ __('purchases.No products found.') }}</div>';
                container.style.display = 'block';
            }
        });
    }

    function selectProduct(product) {
        addItem({
            product_id: product.id,
            barcode: product.productCode || '',
            product_name: product.productName,
            purchase_price: product.purchase_without_tax,
            sales_price: product.sales_price,
            profit_percent: product.profit_percent,
        });
        document.getElementById('productSearchResults').style.display = 'none';
        barcodeInput.value = '';
        barcodeInput.focus();
    }

    function addItem(product) {
        document.getElementById('emptyRow')?.remove();
        itemCounter++;
        const rowId = `item-${itemCounter}`;
        const price = product.purchase_price || 0;

        const html = `
        <tr id="${rowId}" class="item-row">
            <td>${itemCounter}</td>
            <td>
                <input type="hidden" name="products[${itemCounter}][product_id]" value="${product.product_id}">
                <strong>${product.product_name}</strong>
            </td>
            <td><input type="text" name="products[${itemCounter}][barcode]" class="form-control form-control-sm" value="${product.barcode || ''}" readonly></td>
            <td><input type="text" name="products[${itemCounter}][batch_no]" class="form-control form-control-sm" placeholder="{{ __('purchases.Batch') }}"></td>
            <td><input type="number" name="products[${itemCounter}][quantities]" class="form-control form-control-sm qty-input" value="1" min="1" onchange="recalculateLine(this)" oninput="recalculateLine(this)"></td>
            <td><input type="number" name="products[${itemCounter}][purchase_with_tax]" class="form-control form-control-sm price-input" value="${price}" min="0" step="0.01" onchange="recalculateLine(this)" oninput="recalculateLine(this)"></td>
            <td><input type="number" name="products[${itemCounter}][discount]" class="form-control form-control-sm discount-input" value="0" min="0" step="0.01" onchange="recalculateLine(this)" oninput="recalculateLine(this)"></td>
            <td><input type="number" name="products[${itemCounter}][tax]" class="form-control form-control-sm tax-input" value="0" min="0" step="0.01" onchange="recalculateLine(this)" oninput="recalculateLine(this)"></td>
            <td><span class="line-total fw-bold">0.00</span></td>
            <td><button type="button" class="item-remove" onclick="removeItem('${rowId}')"><i class="fas fa-trash"></i></button></td>
        </tr>`;
        document.getElementById('itemsBody').insertAdjacentHTML('beforeend', html);

        // Recalculate the new line
        const newRow = document.getElementById(rowId);
        recalculateLine(newRow.querySelector('.qty-input'));
    }

    function removeItem(rowId) {
        document.getElementById(rowId)?.remove();
        recalculateTotals();
        if (document.querySelectorAll('.item-row').length === 0) {
            document.getElementById('itemsBody').innerHTML = '<tr id="emptyRow"><td colspan="10" class="text-center text-muted py-4">{{ __('purchases.No items added.') }}</td></tr>';
        }
    }

    function recalculateLine(el) {
        const row = el.closest('tr');
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const discount = parseFloat(row.querySelector('.discount-input').value) || 0;
        const tax = parseFloat(row.querySelector('.tax-input').value) || 0;
        const lineTotal = (qty * price) - discount + tax;
        row.querySelector('.line-total').textContent = lineTotal.toFixed(2);
        recalculateTotals();
    }

    function recalculateTotals() {
        let subtotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            subtotal += parseFloat(row.querySelector('.line-total')?.textContent) || 0;
        });
        const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
        const tax = parseFloat(document.getElementById('taxAmount').value) || 0;
        const grandTotal = subtotal - discount + tax;
        const paid = parseFloat(document.getElementById('paidAmount').value) || 0;
        const due = grandTotal - paid;

        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);
        document.getElementById('dueAmount').textContent = Math.max(0, due).toFixed(2);
    }

    function confirmSave() {
        const supplier = document.getElementById('party_id').value;
        if (!supplier) {
            toastr.error('{{ __('purchases.Please select a supplier.') }}');
            return;
        }
        const items = document.querySelectorAll('.item-row');
        if (items.length === 0) {
            toastr.error('{{ __('purchases.Please add at least one item.') }}');
            return;
        }

        const supplierName = document.getElementById('party_id').options[document.getElementById('party_id').selectedIndex].text;
        const total = document.getElementById('grandTotal').textContent;
        document.getElementById('confirmMessage').innerHTML = `{{ __('purchases.Do you agree to save this purchase invoice?') }}<br><br><strong>{{ __('purchases.Supplier:') }}</strong> ${supplierName}<br><strong>{{ __('purchases.Total:') }}</strong> ${total}`;
        new bootstrap.Modal(document.getElementById('confirmModal')).show();
    }

    function submitPurchase() {
        const btn = document.getElementById('confirmBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>{{ __('common.Saving...') }}';

        // Collect form data
        const formData = {
            party_id: document.getElementById('party_id').value,
            branch_id: document.getElementById('branch_id').value || null,
            purchaseDate: document.getElementById('purchaseDate').value,
            paymentType: document.getElementById('paymentType').value,
            discountAmount: document.getElementById('discountAmount').value,
            tax_amount: document.getElementById('taxAmount').value,
            paidAmount: document.getElementById('paidAmount').value,
            note: document.getElementById('note').value,
            products: [],
        };

        document.querySelectorAll('.item-row').forEach(row => {
            formData.products.push({
                product_id: row.querySelector('[name*="product_id"]').value,
                barcode: row.querySelector('[name*="barcode"]').value,
                batch_no: row.querySelector('[name*="batch_no"]').value,
                quantities: row.querySelector('[name*="quantities"]').value,
                purchase_with_tax: row.querySelector('[name*="purchase_with_tax"]').value,
                purchase_without_tax: row.querySelector('[name*="purchase_with_tax"]').value,
                discount: row.querySelector('[name*="discount"]').value,
                tax: row.querySelector('[name*="tax"]').value,
            });
        });

        fetch(`${baseUrl}/purchases/store-ajax`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(formData),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                bootstrap.Modal.getInstance(document.getElementById('confirmModal'))?.hide();
                // Show barcode print dialog
                if (data.data && data.data.items) {
                    showBarcodePrintDialog(data.data.items, data.data.invoice_number);
                } else {
                    setTimeout(() => { window.location.href = data.redirect; }, 1000);
                }
            } else {
                toastr.error(data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-1"></i>{{ __('common.Confirm') }}';
            }
        })
        .catch(() => {
            toastr.error('{{ __('purchases.Error saving purchase.') }}');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-1"></i>{{ __('common.Confirm') }}';
        });
    }

    // Close search results on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#barcodeInput') && !e.target.closest('#productSearchResults')) {
            document.getElementById('productSearchResults').style.display = 'none';
        }
    });

    // Barcode Print Dialog
    function showBarcodePrintDialog(items, invoiceNumber) {
        const printBody = document.getElementById('barcodePrintBody');
        printBody.innerHTML = '';
        items.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.product_name || item.product_id}</td>
                <td><code>${item.barcode || 'N/A'}</code></td>
                <td>${item.batch_no || '—'}</td>
                <td>${item.qty}</td>
                <td>
                    <input type="number" class="form-control form-control-sm print-qty" value="${item.qty}" min="1" data-barcode="${item.barcode || ''}" data-product="${item.product_name || ''}">
                </td>
            `;
            printBody.appendChild(row);
        });
        document.getElementById('barcodeInvoiceNo').textContent = invoiceNumber || '';
        new bootstrap.Modal(document.getElementById('barcodePrintModal')).show();
    }

    function printBarcodes() {
        const printData = [];
        document.querySelectorAll('#barcodePrintBody tr').forEach(row => {
            const qty = parseInt(row.querySelector('.print-qty').value) || 1;
            const barcode = row.querySelector('.print-qty').dataset.barcode;
            const product = row.querySelector('.print-qty').dataset.product;
            if (barcode) {
                printData.push({ barcode, product, qty });
            }
        });

        if (printData.length === 0) {
            toastr.warning('{{ __('purchases.No barcodes to print.') }}');
            return;
        }

        // Generate printable barcode labels
        let printContent = '<html><head><title>Barcodes</title><style>';
        printContent += 'body{font-family:monospace;margin:10px}';
        printContent += '.label{border:1px solid #000;padding:5px;margin:5px;display:inline-block;text-align:center;width:200px}';
        printContent += '.label .barcode{text-size:12px;letter-spacing:2px;margin:5px 0}';
        printContent += '.label .product{font-size:10px;margin:2px 0}';
        printContent += '.label .batch{font-size:9px;color:#666}';
        printContent += '</style></head><body>';

        printData.forEach(item => {
            for (let i = 0; i < item.qty; i++) {
                printContent += `<div class="label"><div class="product">${item.product}</div><div class="barcode">||| ${item.barcode} |||</div><div class="batch">${item.barcode}</div></div>`;
            }
        });

        printContent += '</body></html>';

        const printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => { printWindow.print(); }, 500);
    }

    function skipBarcodePrint() {
        bootstrap.Modal.getInstance(document.getElementById('barcodePrintModal'))?.hide();
        setTimeout(() => { window.location.href = '{{ route("admin.purchases.index") }}'; }, 500);
    }
</script>
@endpush
