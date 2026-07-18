<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة حرارية</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 5px;
            font-size: 12px;
        }
        .invoice {
            width: 76mm;
            padding: 5px;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        .items {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 5px 0;
        }
        .total {
            text-align: left;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="invoice">
        <div class="header">
            <h2 style="margin: 0;">فاتورة</h2>
            <p style="margin: 3px 0;">رقم: {{ $invoice_number }}</p>
            <p style="margin: 3px 0;">{{ $date }}</p>
        </div>

        <div style="margin: 5px 0;">
            <p>عميل: {{ $customer }}</p>
        </div>

        <div class="items">
            @foreach($items as $item)
            <div class="item-row">
                <span>{{ $item['name'] ?? 'منتج' }}</span>
                <span>{{ $item['quantity'] ?? 1 }} x {{ number_format($item['unit_price'] ?? 0, 2) }}</span>
            </div>
            @endforeach
        </div>

        <div class="total">
            الإجمالي: {{ number_format($total_amount, 2) }} ج.م
        </div>

        <div style="text-align: center; margin-top: 10px;">
            <p>شكراً لتعاملكم معنا</p>
        </div>
    </div>
</body>
</html>