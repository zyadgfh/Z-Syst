<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode - {{ $barcode->barcode_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        
        .barcode-container {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            padding: 10px;
            border: 1px solid #000;
            text-align: center;
        }
        
        .barcode-header {
            margin-bottom: 10px;
        }
        
        .product-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .product-info {
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .barcode-image {
            width: 100%;
            height: 80px;
            margin: 10px 0;
            background-color: #fff;
        }
        
        .barcode-number {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 5px 0;
        }
        
        .barcode-footer {
            margin-top: 10px;
            font-size: 9px;
            color: #999;
        }
        
        .price {
            font-size: 16px;
            font-weight: bold;
            color: #000;
            margin-top: 5px;
        }
        
        .expiry {
            font-size: 10px;
            color: #d32f2f;
            margin-top: 2px;
        }
        
        .batch-info {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="barcode-container">
        @if($barcode->print_settings['show_product_name'] ?? true)
        <div class="barcode-header">
            <div class="product-name">{{ $barcode->product->name }}</div>
            @if($barcode->product->generic_name)
            <div class="product-info">{{ $barcode->product->generic_name }}</div>
            @endif
        </div>
        @endif
        
        {{-- Barcode image placeholder - in production, use actual barcode image --}}
        <div class="barcode-image">
            <div style="display: flex; justify-content: center; align-items: center; height: 100%;">
                <div style="font-size: 10px; color: #999;">[BARCODE IMAGE]</div>
            </div>
        </div>
        
        <div class="barcode-number">{{ $barcode->barcode_number }}</div>
        
        @if($barcode->print_settings['show_price'] ?? false && $barcode->product->selling_price)
        <div class="price">{{ number_format($barcode->product->selling_price, 2) }}</div>
        @endif
        
        @if($barcode->batch && ($barcode->print_settings['show_expiry'] ?? true))
        @if($barcode->batch->expiry_date)
        <div class="expiry">Exp: {{ $barcode->batch->expiry_date->format('Y-m-d') }}</div>
        @endif
        @endif
        
        @if($barcode->batch && ($barcode->print_settings['show_batch'] ?? true))
        <div class="batch-info">Batch: {{ $barcode->batch->batch_number }}</div>
        @endif
        
        <div class="barcode-footer">
            Printed: {{ $barcode->printed_at->format('Y-m-d H:i') }}
        </div>
    </div>
</body>
</html>
