<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barcode Labels — {{ $product->productName }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { margin: 8mm; size: A4; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #000; }

        .labels-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 4mm;
            justify-content: flex-start;
        }

        .barcode-label {
            width: {{ $size === 'large' ? '90mm' : ($size === 'small' ? '50mm' : '70mm') }};
            border: 1.5px solid #000;
            padding: 3mm;
            text-align: center;
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: {{ $size === 'large' ? '42mm' : ($size === 'small' ? '25mm' : '32mm') }};
        }

        .product-name {
            font-weight: 700;
            font-size: {{ $size === 'small' ? '8px' : ($size === 'large' ? '12px' : '10px') }};
            margin-bottom: 1.5mm;
            line-height: 1.2;
            max-height: 7mm;
            overflow: hidden;
            width: 100%;
        }

        .scientific-name {
            font-size: 7px;
            color: #555;
            font-style: italic;
            margin-bottom: 1mm;
        }

        .barcode-image {
            margin: 1.5mm 0;
            text-align: center;
        }

        .barcode-image img {
            height: {{ $size === 'small' ? '18mm' : ($size === 'large' ? '28mm' : '22mm') }};
            width: auto;
            max-width: 100%;
        }

        .barcode-number {
            font-family: 'Courier New', 'Consolas', monospace;
            font-size: {{ $size === 'small' ? '8px' : '10px' }};
            font-weight: 700;
            letter-spacing: 2px;
            margin-top: 0.5mm;
        }

        .barcode-type-badge {
            font-size: 6px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .price {
            font-size: {{ $size === 'small' ? '10px' : '13px' }};
            font-weight: 800;
            margin-top: 1mm;
        }

        .expiry-info {
            font-size: 8px;
            color: #c00;
            margin-top: 0.5mm;
        }

        .batch-info {
            font-size: 7px;
            color: #666;
            margin-top: 0.5mm;
        }

        .item-code {
            font-size: 7px;
            color: #888;
            margin-top: 0.5mm;
        }

        .rx-required {
            font-size: 7px;
            color: #c00;
            font-weight: 700;
            margin-top: 1mm;
        }
    </style>
</head>
<body>
    <div class="labels-grid">
        @foreach(range(1, $quantity) as $i)
            <div class="barcode-label">
                {{-- Product Name --}}
                <div class="product-name">{{ $product->productName }}</div>

                @if($showScientific && $product->scientific_name)
                    <div class="scientific-name">{{ $product->scientific_name }}</div>
                @endif

                {{-- Real Barcode Image via picqer/php-barcode-generator --}}
                <div class="barcode-image">
                    @php
                        $barcodeType = match(strtoupper($barcodeType ?? 'CODE128')) {
                            'EAN13' => \Picqer\Barcode\BarcodeGenerator::TYPE_EAN_13,
                            'EAN8' => \Picqer\Barcode\BarcodeGenerator::TYPE_EAN_8,
                            'UPC' => \Picqer\Barcode\BarcodeGenerator::TYPE_UPC_A,
                            'UPCA' => \Picqer\Barcode\BarcodeGenerator::TYPE_UPC_A,
                            'CODE39' => \Picqer\Barcode\BarcodeGenerator::TYPE_CODE_39,
                            'ITF' => \Picqer\Barcode\BarcodeGenerator::TYPE_INTERLEAVED_2_5,
                            'CODE128', 'CODE_128' => \Picqer\Barcode\BarcodeGenerator::TYPE_CODE_128,
                            default => \Picqer\Barcode\BarcodeGenerator::TYPE_CODE_128,
                        };
                        $generator = new \Picqer\Barcode\BarcodeGeneratorHTML();
                        $barcodeHeight = $size === 'large' ? 50 : ($size === 'small' ? 25 : 35);
                        $barcodeHtml = $generator->getBarcode($barcodeNumber, $barcodeType, 2, $barcodeHeight);
                    @endphp
                    {!! $barcodeHtml !!}
                </div>

                {{-- Barcode Number --}}
                <div class="barcode-number">{{ $barcodeNumber }}</div>
                <div class="barcode-type-badge">{{ strtoupper($barcodeType ?? 'CODE128') }}</div>

                {{-- Optional: Price --}}
                @if($showPrice && ($product->sales_price ?? 0) > 0)
                    <div class="price">{{ number_format($product->sales_price, 2) }}</div>
                @endif

                {{-- Optional: Expiry --}}
                @if($showExpiry && $batchExpireDate)
                    <div class="expiry-info">EXP: {{ $batchExpireDate }}</div>
                @endif

                {{-- Optional: Batch --}}
                @if($showBatch && $batchNo)
                    <div class="batch-info">BATCH: {{ $batchNo }}</div>
                @endif

                {{-- Optional: SKU / Code --}}
                @if($showCode && ($product->sku ?? $product->productCode ?? $product->barcode))
                    <div class="item-code">{{ $product->sku ?? $product->productCode ?? '' }}</div>
                @endif

                {{-- Prescription Required --}}
                @if($product->prescription_required)
                    <div class="rx-required">&#8478; PRESCRIPTION REQUIRED</div>
                @endif
            </div>
        @endforeach
    </div>
</body>
</html>
