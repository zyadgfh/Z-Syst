<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Receipt') }} #{{ $receipt['receipt_number'] ?? '' }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        .receipt-details {
            margin-bottom: 15px;
        }
        .receipt-details p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th, table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }
        table th {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .totals {
            margin-top: 15px;
        }
        .totals p {
            margin: 5px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            border-top: 1px solid #000;
            padding-top: 10px;
            font-style: italic;
        }
        .barcode {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    @php
        $settings = $receipt['settings'] ?? [];
    @endphp

    <div class="header">
        <h2>{{ $settings['header'] ?? 'Z-Syst Pharmacy' }}</h2>
        <p>{{ __('Receipt') }} #{{ $receipt['receipt_number'] ?? '' }}</p>
    </div>

    <div class="receipt-details">
        <p><strong>{{ __('Date') }}:</strong> {{ $receipt['date'] ?? '' }}</p>
        @if(isset($receipt['party']) && $receipt['party'])
            <p><strong>{{ __('Customer') }}:</strong> {{ $receipt['party']['name'] ?? '' }}</p>
            @if(isset($receipt['party']['phone']))
                <p><strong>{{ __('Phone') }}:</strong> {{ $receipt['party']['phone'] }}</p>
            @endif
            @if(isset($receipt['party']['address']))
                <p><strong>{{ __('Address') }}:</strong> {{ $receipt['party']['address'] }}</p>
            @endif
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('Item') }}</th>
                <th>{{ __('Qty') }}</th>
                <th>{{ __('Price') }}</th>
                <th>{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($receipt['items'] ?? []) as $item)
                <tr>
                    <td>{{ $item['name'] ?? '' }}</td>
                    <td>{{ $item['quantity'] ?? '' }}</td>
                    <td>{{ number_format($item['price'] ?? 0, 2) }}</td>
                    <td>{{ number_format($item['total'] ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <p><strong>{{ __('Subtotal') }}:</strong> {{ number_format($receipt['subtotal'] ?? 0, 2) }}</p>
        <p><strong>{{ __('Paid Amount') }}:</strong> {{ number_format($receipt['paid_amount'] ?? 0, 2) }}</p>
        <p><strong>{{ __('Due Amount') }}:</strong> {{ number_format($receipt['due_amount'] ?? 0, 2) }}</p>
        @if(isset($receipt['payment_type']) && $receipt['payment_type'])
            <p><strong>{{ __('Payment Type') }}:</strong> {{ $receipt['payment_type'] }}</p>
        @endif
    </div>

    @if(($settings['show_barcode'] ?? false))
        <div class="barcode">
            <p>{{ __('Barcode') }}: [{{ $receipt['receipt_number'] ?? '' }}]</p>
        </div>
    @endif

    <div class="footer">
        <p>{{ $settings['footer'] ?? 'Thank you for your business!' }}</p>
        @if(($settings['show_qr_code'] ?? false))
            <p>{{ __('Scan for more information') }}</p>
        @endif
    </div>
</body>
</html>