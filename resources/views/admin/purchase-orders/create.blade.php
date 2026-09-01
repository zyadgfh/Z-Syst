@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>{{ __("Create Purchase Order") }}</h2>
                <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> {{ __("Back") }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.purchase-orders.store') }}" id="purchase-order-form">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_id">{{ __("Supplier") }} *</label>
                                    <select class="form-control select2" id="supplier_id" name="supplier_id" required>
                                        <option value="">{{ __("Select Supplier") }}</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="priority">{{ __("Priority") }} *</label>
                                    <select class="form-control" id="priority" name="priority" required>
                                        <option value="normal">{{ __("Normal") }}</option>
                                        <option value="low">{{ __("Low") }}</option>
                                        <option value="high">{{ __("High") }}</option>
                                        <option value="urgent">{{ __("Urgent") }}</option>
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
                                    <label for="expected_delivery_date">{{ __("Expected Delivery Date") }} *</label>
                                    <input type="date" class="form-control" id="expected_delivery_date" name="expected_delivery_date" required>
                                    @error('expected_delivery_date')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="shipping_address">{{ __("Shipping Address") }}</label>
                                    <input type="text" class="form-control" id="shipping_address" name="shipping_address">
                                    @error('shipping_address')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="terms">{{ __("Terms") }}</label>
                                    <textarea class="form-control" id="terms" name="terms" rows="3"></textarea>
                                    @error('terms')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="notes">{{ __("Notes") }}</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                    @error('notes')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h4>{{ __("Order Items") }}</h4>
                        <div class="table-responsive">
                            <table class="table" id="items-table">
                                <thead>
                                    <tr>
                                        <th>{{ __("Product") }}</th>
                                        <th>{{ __("Quantity") }}</th>
                                        <th>{{ __("Unit Price") }}</th>
                                        <th>{{ __("Discount (%)") }}</th>
                                        <th>{{ __("Total") }}</th>
                                        <th>{{ __("Actions") }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select class="form-control product-select" name="items[0][product_id]" required>
                                                <option value="">{{ __("Select Product") }}</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" data-price="{{ $product->purchase_price }}">
                                                        {{ $product->name }} ({{ $product->sku }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control quantity" name="items[0][quantity]" min="1" value="1" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control unit-price" name="items[0][unit_price]" step="0.01" min="0" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control discount" name="items[0][discount]" step="0.01" min="0" max="100" value="0">
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
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-success btn-sm" id="add-item">
                            <i class="fas fa-plus"></i> {{ __("Add Item") }}
                        </button>

                        <hr>

                        <div class="row">
                            <div class="col-md-6 offset-md-6">
                                <div class="form-group">
                                    <label>{{ __("Tax") }}</label>
                                    <input type="number" class="form-control" id="tax" name="tax" step="0.01" min="0" value="0">
                                </div>
                                <div class="form-group">
                                    <label>{{ __("Shipping Cost") }}</label>
                                    <input type="number" class="form-control" id="shipping_cost" name="shipping_cost" step="0.01" min="0" value="0">
                                </div>
                                <div class="form-group">
                                    <label><strong>{{ __("Total Amount") }}</strong></label>
                                    <input type="number" class="form-control" id="total_amount" name="total_amount" step="0.01" min="0" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ __("Save as Draft") }}
                            </button>
                            <button type="submit" class="btn btn-success" name="submit_type" value="send">
                                <i class="fas fa-paper-plane"></i> {{ __("Send to Supplier") }}
                            </button>
                            <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-secondary">
                                {{ __("Cancel") }}
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

        let itemCount = 1;

        // Add item
        $('#add-item').click(function() {
            let html = `
                <tr>
                    <td>
                        <select class="form-control product-select" name="items[${itemCount}][product_id]" required>
                            <option value="">{{ __("Select Product") }}</option>
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
