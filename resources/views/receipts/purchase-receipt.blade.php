<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Purchase Receipt') }} - {{ $data['receipt_number'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .receipt-info {
            margin-bottom: 20px;
            background: #f9f9f9;
            padding: 10px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background: #f0f0f0;
            font-weight: bold;
        }
        .totals {
            text-align: right;
            margin-top: 20px;
        }
        .totals .row {
            margin-bottom: 5px;
        }
        .totals .total {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
            padding-top: 10px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .barcode {
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            <h1>{{ __('PURCHASE RECEIPT') }}</h1>
            @if($data['header'])
                <p>{{ $data['header'] }}</p>
            @endif
        </div>

        <!-- Company Info -->
        <div class="company-info">
            <h3>{{ $data['purchase']->business->companyName ?? 'Pharmacy' }}</h3>
            @if($data['purchase']->business->address)
                <p>{{ $data['purchase']->business->address }}</p>
            @endif
            @if($data['purchase']->business->phoneNumber)
                <p>{{ __('Phone') }}: {{ $data['purchase']->business->phoneNumber }}</p>
            @endif
        </div>

        <!-- Receipt Info -->
        <div class="receipt-info">
            <p><strong>{{ __('Receipt Number') }}:</strong> {{ $data['receipt_number'] }}</p>
            <p><strong>{{ __('Date') }}:</strong> {{ $data['purchase']->purchaseDate->format('Y-m-d H:i') }}</p>
            <p><strong>{{ __('Invoice Number') }}:</strong> {{ $data['purchase']->invoiceNumber }}</p>
        </div>

        <!-- Supplier Info -->
        @if($data['supplier'])
        <div class="supplier-info" style="margin-bottom: 20px;">
            <h4>{{ __('Supplier Information') }}</h4>
            <p><strong>{{ __('Name') }}:</strong> {{ $data['supplier']->name }}</p>
            @if($data['supplier']->phone)
                <p><strong>{{ __('Phone') }}:</strong> {{ $data['supplier']->phone }}</p>
            @endif
            @if($data['supplier']->address)
                <p><strong>{{ __('Address') }}:</strong> {{ $data['supplier']->address }}</p>
            @endif
        </div>
        @endif

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>{{ __('Item') }}</th>
                    <th>{{ __('Quantity') }}</th>
                    <th>{{ __('Price') }}</th>
                    <th>{{ __('Discount') }}</th>
                    <th>{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['items'] as $item)
                <tr>
                    <td>{{ $item['product_name'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>{{ number_format($item['price'], 2) }}</td>
                    <td>{{ number_format($item['discount'], 2) }}</td>
                    <td>{{ number_format($item['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="row">
                <span>{{ __('Subtotal') }}:</span>
                <span>{{ number_format($data['subtotal'], 2) }}</span>
            </div>
            @if($data['tax'] > 0)
            <div class="row">
                <span>{{ __('Tax') }}:</span>
                <span>{{ number_format($data['tax'], 2) }}</span>
            </div>
            @endif
            @if($data['discount'] > 0)
            <div class="row">
                <span>{{ __('Discount') }}:</span>
                <span>-{{ number_format($data['discount'], 2) }}</span>
            </div>
            @endif
            <div class="row total">
                <span>{{ __('Total') }}:</span>
                <span>{{ number_format($data['total'], 2) }}</span>
            </div>
            <div class="row">
                <span>{{ __('Paid') }}:</span>
                <span>{{ number_format($data['paid'], 2) }}</span>
            </div>
            <div class="row">
                <span>{{ __('Due') }}:</span>
                <span>{{ number_format($data['due'], 2) }}</span>
            </div>
            <div class="row">
                <span>{{ __('Payment Method') }}:</span>
                <span>{{ ucfirst($data['payment_method']) }}</span>
            </div>
        </div>

        <!-- Barcode -->
        @if($data['show_barcode'])
        <div class="barcode">
            <p>{{ __('Receipt Number') }}: {{ $data['receipt_number'] }}</p>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            @if($data['footer'])
                <p>{{ $data['footer'] }}</p>
            @endif
            <p>{{ __('Purchase recorded successfully') }}</p>
            <p>{{ __('Generated on') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
