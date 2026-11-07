<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="auto">
<head>
    <meta charset="UTF-8">
    <title>{ __('Barcode Labels') } ({{ $barcodes->count() }})</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .toolbar {
            position: sticky; top: 0; background: #fff; padding: 12px 20px;
            border-bottom: 1px solid #ddd; display: flex; gap: 10px; align-items: center;
            z-index: 10;
        }
        .toolbar button { padding: 8px 18px; border: 0; border-radius: 8px; cursor: pointer; font-size: 14px; }
        .btn-print { background: #0a84ff; color: #fff; }
        .btn-back { background: #e9e9eb; color: #000; text-decoration: none; }
        .sheet { display: flex; flex-wrap: wrap; gap: 8mm; padding: 10mm; justify-content: center; }
        .label {
            width: 62mm; height: 34mm; background: #fff; border: 1px dashed #bbb;
            padding: 2mm; text-align: center; overflow: hidden;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .label .name { font-size: 9pt; font-weight: bold; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .label .meta { font-size: 7pt; color: #555; }
        .label img { max-width: 100%; height: auto; }
        @media print {
            .toolbar { display: none; }
            body { background: #fff; }
            .label { border: 0; page-break-inside: avoid; }
            .sheet { padding: 0; gap: 4mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn-print" onclick="window.print()">🖨️ { __('Print') }</button>
        <a class="btn-back" href="{{ route('admin.barcodes.index') }}">{ __('Back') }</a>
        <span style="color:#777; font-size: 13px;">{{ $barcodes->count() }} { __('labels') }</span>
    </div>

    <div class="sheet">
        @foreach($barcodes as $barcode)
            <div class="label">
                <div class="name">{{ $barcode->product?->name }}</div>
                <img src="{{ route('admin.barcodes.image', $barcode) }}" alt="{{ $barcode->barcode_number }}">
                <div>
                    <div class="font-monospace" style="font-size: 8pt;">{{ $barcode->barcode_number }}</div>
                    <div class="meta">
                        @if($barcode->batch)
                            {{ __('Batch') }}: {{ $barcode->batch->batch_no }}
                            @if($barcode->batch->expire_date) · {{ __('Exp') }}: {{ $barcode->batch->expire_date->format('Y-m-d') }} @endif
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
