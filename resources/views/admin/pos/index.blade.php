@extends('layouts.master')

@section('title')
    {{ __('Sales Board (POS)') }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="row g-3">

        {{-- Status strip --}}
        <div class="col-12">
            <div class="card"><div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
                <span class="badge {{ $approved ? 'bg-success' : 'bg-danger' }} p-2">
                    {{ $approved ? __('الجهاز مُعتمد') : __('الجهاز بانتظار الاعتماد — إدارة الأجهزة') }}
                </span>
                @if($device)
                    <span class="text-muted small">🖥 {{ $device->name }} ({{ $device->type }})</span>
                    <span class="text-muted small">{{ __('ماسح') }}: {{ $device->has_scanner ? '✓' : '—' }}</span>
                @endif
                <span class="text-muted small">
                    {{ __('طابعة باركود') }}: {{ $devicePayload['printers']['barcode']['name'] ?? '—' }}
                </span>
                <span class="text-muted small">
                    {{ __('طابعة إيصالات') }}: {{ $devicePayload['printers']['receipt']['name'] ?? '—' }}
                </span>
                <a href="{{ route('admin.device-setup.index') }}" class="btn btn-sm btn-light ms-auto">⚙ {{ __('إعدادات الأجهزة') }}</a>
            </div></div>
        </div>

        {{-- Scan box + cart --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <label class="form-label fw-bold">{ __('امسح الباركود أو اكتب الكود ثم Enter') }</label>
                    <input type="text" id="scanBox" class="form-control form-control-lg font-monospace"
                           placeholder="⌨ {{ __('حقل الماسح — يعمل تلقائيًا عند المسح') }}" autocomplete="off">
                    <div id="scanHint" class="form-text">
                        {{ __('الماسح يكتب الكود كضربات لوحة مفاتيح وينهيه بـ Enter — سيتعرف عليه الحقل ويفرد الصنف في الفاتورة.') }}
                    </div>

                    <table class="table table-hover align-middle mt-3">
                        <thead class="table-light"><tr>
                            <th>{{ __('الصنف') }}</th><th style="width:110px">{{ __('الدفعة') }}</th>
                            <th style="width:90px">{{ __('الكمية') }}</th><th style="width:110px">{{ __('السعر') }}</th>
                            <th class="text-end">{{ __('الإجمالي') }}</th><th></th>
                        </tr></thead>
                        <tbody id="cartBody">
                            <tr id="emptyRow"><td colspan="6" class="text-center text-muted py-4">{{ __('لا أصناف بعد — امسح أول باركود') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Totals + checkout --}}
        <div class="col-lg-4">
            <div class="card"><div class="card-body">
                <h5>{{ __('الفاتورة') }}</h5>
                <table class="table table-sm">
                    <tr><td>{{ __('عدد الأصناف') }}</td><td class="text-end" id="sumLines">0</td></tr>
                    <tr><td>{{ __('إجمالي الكمية') }}</td><td class="text-end" id="sumQty">0</td></tr>
                    <tr class="fs-5 fw-bold"><td>{{ __('الإجمالي') }}</td><td class="text-end" id="sumTotal">0.00</td></tr>
                </table>

                <label class="form-label">{{ __('المدفوع') }}</label>
                <input type="number" id="paidAmount" step="0.01" min="0" class="form-control form-control-lg mb-2" value="0">

                <select id="paymentType" class="form-select mb-3">
                    <option value="cash">{{ __('نقدي') }}</option>
                    <option value="card">{{ __('بطاقة') }}</option>
                    <option value="wallet">{{ __('محفظة') }}</option>
                </select>

                <button id="checkoutBtn" class="btn btn-success btn-lg w-100">✓ {{ __('إتمام البيع') }}</button>
                <div id="posMsg" class="alert d-none mt-3 mb-0 py-2"></div>
            </div></div>
        </div>
    </div>
</div>

@push('modal')
<script>
(function () {
    const CFG = @json($settings);
    const DEVICE = @json($devicePayload);
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const cart = new Map();   // productId → line

    const scanBox = document.getElementById('scanBox');
    const cartBody = document.getElementById('cartBody');
    const msg = document.getElementById('posMsg');

    // ---------- scanner capture (keyboard-wedge) ----------
    let buffer = '', lastKey = 0;
    const MAX_MS = CFG.scanner.max_input_ms || 150;

    if (CFG.scanner.auto_focus) { setInterval(() => { if (document.activeElement !== scanBox && !document.activeElement.closest('input,select')) scanBox.focus(); }, 2500); scanBox.focus(); }

    scanBox.addEventListener('keydown', async (e) => {
        const now = Date.now();
        if (now - lastKey > MAX_MS) buffer = '';   // slow typing ≠ scanner
        lastKey = now;

        if (e.key === 'Enter') {
            e.preventDefault();
            const code = (buffer || scanBox.value).trim();
            buffer = ''; scanBox.value = '';
            if (code.length >= (CFG.scanner.min_chars || 4)) await handleScan(code);
            return;
        }
        if (e.key.length === 1) buffer += e.key;
    });

    async function handleScan(code) {
        const res = await fetch('{{ route("admin.pos.lookup") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ code }),
        }).then(r => r.json()).catch(() => null);

        if (!res || !res.success) { flash('danger', res?.message || '{{ __('كود غير معروف') }}'); return; }
        if (!CFG.scanner.auto_add) { flash('info', res.data.name + ' — ' + res.data.price); return; }
        addToCart(res.data);
        if (CFG.scanner.beep) beep();
    }

    // ---------- cart ----------
    function addToCart(item) {
        const key = item.product_id + '|' + (item.batch_no ?? '');
        if (cart.has(key)) {
            const line = cart.get(key);
            line.qty += 1;
        } else {
            cart.set(key, { product_id: item.product_id, name: item.name, price: item.price,
                            qty: 1, batch_no: item.batch_no ?? '', stock: item.stock });
        }
        renderCart(key);
    }

    function renderCart(flashKey) {
        cartBody.innerHTML = '';
        if (cart.size === 0) { cartBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">{{ __('لا أصناف بعد') }}</td></tr>'; totals(); return; }

        for (const [key, line] of cart) {
            const tr = document.createElement('tr');
            if (key === flashKey) tr.className = 'table-success fw-bold';
            tr.innerHTML =
                '<td>' + line.name + '</td>' +
                '<td class="font-monospace small">' + (line.batch_no || '—') + '</td>' +
                '<td><input type="number" min="1" class="form-control form-control-sm qty" value="' + line.qty + '" data-key="' + key + '"></td>' +
                '<td><input type="number" min="0" step="0.01" class="form-control form-control-sm price" value="' + line.price + '" data-key="' + key + '"></td>' +
                '<td class="text-end line-total">' + (line.qty * line.price).toFixed(2) + '</td>' +
                '<td><button class="btn btn-sm btn-light rm" data-key="' + key + '">✕</button></td>';
            cartBody.appendChild(tr);
        }
        cartBody.querySelectorAll('.qty').forEach(i => i.onchange = () => { cart.get(i.dataset.key).qty = Math.max(1, +i.value); renderCart(); });
        cartBody.querySelectorAll('.price').forEach(i => i.onchange = () => { cart.get(i.dataset.key).price = +i.value; renderCart(); });
        cartBody.querySelectorAll('.rm').forEach(b => b.onclick = () => { cart.delete(b.dataset.key); renderCart(); });
        totals();
    }

    function totals() {
        let qty = 0, total = 0;
        for (const line of cart.values()) { qty += line.qty; total += line.qty * line.price; }
        document.getElementById('sumLines').textContent = cart.size;
        document.getElementById('sumQty').textContent = qty;
        document.getElementById('sumTotal').textContent = total.toFixed(2);
        if (document.getElementById('paidAmount').value === '0') document.getElementById('paidAmount').value = total.toFixed(2);
    }

    // ---------- checkout ----------
    document.getElementById('checkoutBtn').onclick = async () => {
        if (cart.size === 0) return;
        const products = [...cart.values()].map(l => ({
            product_id: l.product_id, price: l.price, lossProfit: 0,
            quantities: l.qty, batch_no: l.batch_no || null,
        }));
        const payload = { products, paidAmount: +document.getElementById('paidAmount').value,
                          paymentType: document.getElementById('paymentType').value,
                          isPaid: +document.getElementById('paidAmount').value >= totals0() };

        function totals0() { let t = 0; for (const l of cart.values()) t += l.qty * l.price; return t; }
        payload.dueAmount = Math.max(0, totals0() - payload.paidAmount);

        const res = await fetch('{{ route("admin.pos.checkout") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        }).then(r => r.json()).catch(e => ({ success: false, message: String(e) }));

        if (!res.success) { flash('danger', res.message || '{{ __('فشل البيع') }}'); return; }

        cart.clear(); renderCart();
        document.getElementById('paidAmount').value = 0;
        flash('success', res.message);

        // Print rules — the server decides per business settings.
        if (res.print?.barcode_url) window.open(res.print.barcode_url, '_blank');
        if (res.print?.receipt_on_sale === 'auto' && res.print?.receipt_url) window.open(res.print.receipt_url, '_blank');
    };

    function flash(kind, text) {
        msg.className = 'alert alert-' + kind + ' mt-3 mb-0 py-2';
        msg.textContent = text;
        setTimeout(() => msg.classList.add('d-none'), 4000);
    }

    function beep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator(), gain = ctx.createGain();
            osc.frequency.value = 880; gain.gain.value = 0.08;
            osc.connect(gain); gain.connect(ctx.destination);
            osc.start(); osc.stop(ctx.currentTime + 0.09);
        } catch (e) {}
    }
})();
</script>
@endpush
@endsection
