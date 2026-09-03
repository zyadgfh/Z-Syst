@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>{{ __('purchases.Edit Purchase Order') }} #{{ $purchaseOrder->po_number }}</h2>
                <div class="btn-group">
                    <a href="{{ route('admin.purchase-orders.show', $purchaseOrder) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> {{ __('common.Back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.purchase-orders.update', $purchaseOrder) }}" id="purchase-order-form">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_id">{{ __('purchases.Supplier') }} *</label>
                                    <select class="form-control select2" id="supplier_id" name="supplier_id" required>
                                        <option value="">{{ __('purchases.Select Supplier') }}</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" {{ $purchaseOrder->supplier_id == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="priority">{{ __('purchases.Priority') }} *</label>
                                    <select class="form-control" id="priority" name="priority" required>
                                        <option value="normal" {{ $purchaseOrder->priority === 'normal' ? 'selected' : '' }}>{{ __('purchases.Normal') }}</option>
                                        <option value="low" {{ $purchaseOrder->priority === 'low' ? 'selected' : '' }}>{{ __('purchases.Low') }}</option>
                                        <option value="high" {{ $purchaseOrder->priority === 'high' ? 'selected' : '' }}>{{ __('purchases.High') }}</option>
                                        <option value="urgent" {{ $purchaseOrder->priority === 'urgent' ? 'selected' : '' }}>{{ __('purchases.Urgent') }}</option>
                                    </select>
                                    @error('priority')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expected_delivery_date">{{ __('purchases.Expected Delivery Date') }} *</label>
                                    <input type="date" class="form-control" id="expected_delivery_date" name="expected_delivery_date" value="{{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('Y-m-d') : '' }}" required>
                                    @error('expected_delivery_date')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="shipping_address">{{ __('purchases.Shipping Address') }}</label>
                                    <input type="text" class="form-control" id="shipping_address" name="shipping_address" value="{{ $purchaseOrder->shipping_address }}">
                                    @error('shipping_address')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="terms">{{ __('purchases.Terms') }}</label>
                                    <textarea class="form-control" id="terms" name="terms" rows="3">{{ $purchaseOrder->terms }}</textarea>
                                    @error('terms')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="notes">{{ __('common.Notes') }}</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3">{{ $purchaseOrder->notes }}</textarea>
                                    @error('notes')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h4>{{ __('purchases.Order Items') }}</h4>
                        <div class="table-responsive">
                            <table class="table" id="items-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('common.Product') }}</th>
                                        <th>{{ __('common.Quantity') }}</th>
                                        <th>{{ __('purchases.Unit Price') }}</th>
                                        <th>{{ __('purchases.Discount (%)') }}</th>
                                        <th>{{ __('common.Total') }}</th>
                                        <th>{{ __('common.Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchaseOrder->items as $index => $item)
                                        <tr>
                                            <td>
                                                <select class="form-control product-select" name="items[{{ $index }}][product_id]" required>
                                                    <option value="">{{ __('purchases.Select Product') }}</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}" data-price="{{ $product->purchase_price }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                            {{ $product->name }} ({{ $product->sku }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control quantity" name="items[{{ $index }}][quantity]" min="1" value="{{ $item->quantity }}" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control unit-price" name="items[{{ $index }}][unit_price]" step="0.01" min="0" value="{{ $item->unit_price }}" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control discount" name="items[{{ $index }}][discount]" step="0.01" min="0" max="100" value="{{ $item->discount }}">
                                            </td>
                                            <td>
                                                <span class="item-total">{{ number_format($item->total, 2) }}</span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-item">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-success btn-sm" id="add-item">
                            <i class="fas fa-plus"></i> {{ __('purchases.Add Item') }}
                        </button>

                        <hr>

                        <div class="row">
                            <div class="col-md-6 offset-md-6">
                                <div class="form-group">
                                    <label>{{ __('common.Tax') }}</label>
                                    <input type="number" class="form-control" id="tax" name="tax" step="0.01" min="0" value="{{ $purchaseOrder->tax }}">
                                </div>
                                <div class="form-group">
                                    <label>{{ __('purchases.Shipping Cost') }}</label>
                                    <input type="number" class="form-control" id="shipping_cost" name="shipping_cost" step="0.01" min="0" value="{{ $purchaseOrder->shipping_cost }}">
                                </div>
                                <div class="form-group">
                                    <label><strong>{{ __('purchases.Total Amount') }}</strong></label>
                                    <input type="number" class="form-control" id="total_amount" name="total_amount" step="0.01" min="0" value="{{ $purchaseOrder->total_amount }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ __('purchases.Update Purchase Order') }}
                            </button>
                            <a href="{{ route('admin.purchase-orders.show', $purchaseOrder) }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2();

        let itemCount = {{ $purchaseOrder->items->count() }};

        // Add item
        $('#add-item').click(function() {
            let html = `
                <tr>
                    <td>
                        <select class="form-control product-select" name="items[${itemCount}][product_id]" required>
                            <option value="">{{ __('purchases.Select Product') }}</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->purchase_price }}">
                                    {{ $product->name }} ({{ $product->sku }})
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" class="form-control quantity" name="items[${itemCount}][quantity]" min="1" value="1" required>
                    </td>
                    <td>
                        <input type="number" class="form-control unit-price" name="items[${itemCount}][unit_price]" step="0.01" min="0" required>
                    </td>
                    <td>
                        <input type="number" class="form-control discount" name="items[${itemCount}][discount]" step="0.01" min="0" max="100" value="0">
                    </td>
                    <td>
                        <span class="item-total">0.00</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $('#items-table tbody').append(html);
            itemCount++;
        });

        // Remove item
        $(document).on('click', '.remove-item', function() {
            if ($('#items-table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                calculateTotal();
            }
        });

        // Auto-fill unit price when product selected
        $(document).on('change', '.product-select', function() {
            let price = $(this).find(':selected').data('price');
            $(this).closest('tr').find('.unit-price').val(price);
            calculateItemTotal($(this).closest('tr'));
        });

        // Calculate item total on change
        $(document).on('change input', '.quantity, .unit-price, .discount', function() {
            calculateItemTotal($(this).closest('tr'));
        });

        // Calculate total on tax/shipping change
        $('#tax, #shipping_cost').on('change input', calculateTotal);

        function calculateItemTotal(row) {
            let quantity = parseFloat(row.find('.quantity').val()) || 0;
            let unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
            let discount = parseFloat(row.find('.discount').val()) || 0;

            let subtotal = quantity * unitPrice;
            let discountAmount = subtotal * (discount / 100);
            let total = subtotal - discountAmount;

            row.find('.item-total').text(total.toFixed(2));
            calculateTotal();
        }

        function calculateTotal() {
            let itemsTotal = 0;
            $('.item-total').each(function() {
                itemsTotal += parseFloat($(this).text()) || 0;
            });

            let tax = parseFloat($('#tax').val()) || 0;
            let shipping = parseFloat($('#shipping_cost').val()) || 0;

            let total = itemsTotal + tax + shipping;
            $('#total_amount').val(total.toFixed(2));
        }
    });
</script>
@endpush
