<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcodes - {{ count($barcodes) }} Items</title>
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
        
        .barcodes-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 20px;
        }
        
        .barcode-container {
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
            page-break-inside: avoid;
        }
        
        .barcode-header {
            margin-bottom: 8px;
        }
        
        .product-name {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .product-info {
            font-size: 9px;
            color: #666;
            margin-bottom: 3px;
        }
        
        .barcode-image {
            width: 100%;
            height: 60px;
            margin: 8px 0;
            background-color: #fff;
        }
        
        .barcode-number {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 3px 0;
        }
        
        .barcode-footer {
            margin-top: 8px;
            font-size: 8px;
            color: #999;
        }
        
        .price {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            margin-top: 3px;
        }
        
        .expiry {
            font-size: 9px;
            color: #d32f2f;
            margin-top: 2px;
        }
        
        .batch-info {
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }
        
        @media print {
            .barcodes-grid {
                gap: 5px;
                padding: 10px;
            }
            
            .barcode-container {
                padding: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="barcodes-grid">
        @foreach($barcodes as $barcode)
        <div class="barcode-container">
            @if($barcode->print_settings['show_product_name'] ?? true)
            <div class="barcode-header">
                <div class="product-name">{{ $barcode->product->name }}</div>
                @if($barcode->product->generic_name)
                <div class="product-info">{{ $barcode->product->generic_name }}</div>
                @endif
            </div>
            @endif
            
            {{-- Barcode image placeholder --}}
            <div class="barcode-image">
                <div style="display: flex; justify-content: center; align-items: center; height: 100%;">
                    <div style="font-size: 8px; color: #999;">[BARCODE]</div>
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
                {{ $barcode->printed_at->format('Y-m-d') }}
            </div>
        </div>
        @endforeach
    </div>
</body>
</html>
