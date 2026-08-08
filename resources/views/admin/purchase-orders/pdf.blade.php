<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order #{{ $purchaseOrder->po_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-box {
            border: 1px solid #ddd;
            padding: 15px;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
        }
        .info-box p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th {
            background-color: #f5f5f5;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .totals {
            margin-left: auto;
            width: 300px;
        }
        .totals table {
            margin-top: 10px;
        }
        .totals td {
            padding: 5px 10px;
        }
        .totals .total {
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        .signature-box {
            border: 1px solid #ddd;
            padding: 30px 10px;
            text-align: center;
        }
        .signature-box p {
            margin: 50px 0 0 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PURCHASE ORDER</h1>
        <p>PO Number: {{ $purchaseOrder->po_number }}</p>
        <p>Date: {{ $purchaseOrder->order_date ? $purchaseOrder->order_date->format('d M Y') : now()->format('d M Y') }}</p>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h3>Supplier Information</h3>
            @if($purchaseOrder->supplier)
                <p><strong>{{ $purchaseOrder->supplier->name }}</strong></p>
                <p>{{ $purchaseOrder->supplier->address ?? '' }}</p>
                <p>{{ $purchaseOrder->supplier->phone ?? '' }}</p>
                <p>{{ $purchaseOrder->supplier->email ?? '' }}</p>
            @else
                <p>N/A</p>
            @endif
        </div>
        <div class="info-box">
            <h3>Order Information</h3>
            <p><strong>Status:</strong> {{ ucfirst($purchaseOrder->status) }}</p>
            <p><strong>Priority:</strong> {{ ucfirst($purchaseOrder->priority) }}</p>
            <p><strong>Expected Delivery:</strong> {{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('d M Y') : 'N/A' }}</p>
            <p><strong>Created By:</strong> {{ $purchaseOrder->createdBy ? $purchaseOrder->createdBy->name : 'N/A' }}</p>
        </div>
    </div>

    @if($purchaseOrder->shipping_address)
        <div class="info-box" style="margin-bottom: 30px;">
            <h3>Shipping Address</h3>
            <p>{{ $purchaseOrder->shipping_address }}</p>
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>SKU</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Discount</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product ? $item->product->name : 'N/A' }}</td>
                    <td>{{ $item->product ? $item->product->sku : 'N/A' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $item->discount }}%</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td>{{ number_format($purchaseOrder->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Tax:</strong></td>
                <td>{{ number_format($purchaseOrder->tax, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Shipping:</strong></td>
                <td>{{ number_format($purchaseOrder->shipping_cost, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Discount:</strong></td>
                <td>{{ number_format($purchaseOrder->discount_amount, 2) }}</td>
            </tr>
            <tr class="total">
                <td><strong>Total:</strong></td>
                <td>{{ number_format($purchaseOrder->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($purchaseOrder->terms)
        <div class="info-box" style="margin-top: 30px;">
            <h3>Terms & Conditions</h3>
            <p>{{ $purchaseOrder->terms }}</p>
        </div>
    @endif

    @if($purchaseOrder->notes)
        <div class="info-box" style="margin-top: 20px;">
            <h3>Notes</h3>
            <p>{{ $purchaseOrder->notes }}</p>
        </div>
    @endif

    <div class="footer">
        <div class="footer-grid">
            <div class="signature-box">
                <p>__________________________</p>
                <p>Supplier Signature</p>
            </div>
            <div class="signature-box">
                <p>__________________________</p>
                <p>Authorized Signature</p>
            </div>
            <div class="signature-box">
                <p>__________________________</p>
                <p>Date</p>
            </div>
        </div>
    </div>
</body>
</html>
