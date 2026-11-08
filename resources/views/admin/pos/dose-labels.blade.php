<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ __('ملصقات الجرعات') }}</title>
<style>
    :root {
        --ap-font: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        --ap-bg: #f5f5f7; --ap-surface: #ffffff; --ap-text: #1d1d1f; --ap-text-2: #6e6e73;
        --ap-accent: #0071e3; --ap-hairline: rgba(0,0,0,.08);
        --ap-spring: cubic-bezier(0.32, 0.72, 0, 1);
    }
    * { box-sizing: border-box; }
    body { font-family: var(--ap-font); background: var(--ap-bg); color: var(--ap-text); margin: 0; padding: 24px; }
    .sheet-head { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
    .sheet-head h1 { font-size: 20px; letter-spacing: -0.022em; margin: 0; }
    .sheet-head .meta { color: var(--ap-text-2); font-size: 13px; }
    .sheet-head button {
        margin-inline-start: auto; border: 0; background: var(--ap-accent); color: #fff;
        font: inherit; font-weight: 600; padding: 10px 22px; border-radius: 12px; cursor: pointer;
        transition: transform .25s var(--ap-spring), background .25s var(--ap-spring);
    }
    .sheet-head button:active { transform: scale(.96); background: #0060c2; }
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px; }
    .label-card {
        background: var(--ap-surface); border-radius: 18px; padding: 18px;
        box-shadow: 0 4px 24px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
        border: 1px solid var(--ap-hairline);
        page-break-inside: avoid; break-inside: avoid;
    }
    .label-card .product { font-size: 19px; font-weight: 700; letter-spacing: -0.014em; }
    .label-card .dose {
        font-size: 15px; font-weight: 650; margin-top: 6px; line-height: 1.5;
        color: var(--ap-accent);
    }
    .label-card .hint { color: var(--ap-text-2); font-size: 12.5px; margin-top: 2px; }
    .label-card .code { margin-top: 10px; }
    .label-card .code svg { max-width: 100%; height: 56px; }
    .label-card .payload { font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: 11px; color: var(--ap-text-2); letter-spacing: .06em; }
    @media print {
        body { background: #fff; padding: 0; }
        .sheet-head button { display: none; }
        .label-card { box-shadow: none; border: 1px dashed #bbb; }
    }
</style>
</head>
<body>
    <div class="sheet-head">
        <h1>💊 {{ __('ملصقات الجرعات') }}</h1>
        <span class="meta">{{ $sale->invoiceNumber ?? ('#'.$sale->id) }} · {{ optional($sale->saleDate)->format('Y-m-d') }}</span>
        <button onclick="window.print()">🖨 {{ __('طباعة') }}</button>
    </div>

    <div class="grid">
        @forelse($labels as $l)
            <div class="label-card">
                <div class="product">{{ $l['product'] }}</div>
                <div class="dose">⏰ {{ $l['dose'] }}</div>
                @if($l['schedule_hint'])
                    <div class="hint">{{ $l['schedule_hint'] }}</div>
                @endif
                <div class="code">{!! $l['svg'] !!}</div>
                <div class="payload">{{ $l['payload'] }}</div>
            </div>
        @empty
            <div class="hint">{{ __('لا جرعات مسجلة على هذه الفاتورة') }}</div>
        @endforelse
    </div>
</body>
</html>
